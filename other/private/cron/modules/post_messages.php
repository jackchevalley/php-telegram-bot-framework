<?php

# run every minute
const BASE_ROOT = __DIR__ . '/../../../..';
const MAINSTART = true;

require_once BASE_ROOT . '/public/access.php';
require_once BASE_ROOT . '/public/database.php';
require_once BASE_ROOT . '/public/functions.php';
require_once BASE_ROOT . '/other/private/cron/resources/configs.php';
require_once BASE_ROOT . '/other/private/cron/resources/functions.php';

require_once BASE_ROOT . '/public/libs/vendor/autoload.php';
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

// Crea il client delle richieste
$client = new Client([
    'base_uri' => 'https://api.telegram.org/'. $API .'/',
    'timeout'  => 0
]);




# ############################################################################
# | Configurations

const ADMIN_ONLY = false;               // Enable debug for admins only
$DEBUG_ADMINS = [158472703];

$CHUNK_DIMENSION = 20;              // Number of messages to send asynchronously in parallel
$UPDATE_EVERY_N_MESSAGES = 30;      // Update admin status every N messages
$SLEEP_BETWEEN_CHUNKS = 1;          // Sleep time between chunks (in seconds)




############################################################################
# | Actual Script
# Send post messages

logger("[INFO] Checking for post...", break_line: True);
if (!file_exists(BASE_ROOT .'/data/posts/post_info.json')) {
    logger("[INFO] No post found", break_line: True);
    exit;
}



// Get post info
$post_info = json_decode(file_get_contents(BASE_ROOT .'/data/posts/post_info.json'), true);
if (!$post_info or empty($post_info)) {
    logger("[INFO] No data post found", break_line: True);
}

// Check if post is ready
if (!$post_info['ready'] || ($post_info['send_at'] && $post_info['send_at'] > time())) {
    logger("[INFO] Post not ready: ". ($post_info['ready'] ? (($post_info['send_at']) ? date('Y-m-d H:i') : "building") : 'Not marked as ready'), break_line: True);
    exit;
}


// Remove the post file to avoid multiple sends
unlink(BASE_ROOT .'/data/posts/post_info.json');


// Collect users
logger("[INFO] Post found, collecting users...", break_line: True);
$users = secure('SELECT * FROM users WHERE attivo = 1', 0, 3);
if (empty($users)) {
    logger("[INFO] No users found", break_line: True);
    exit;
}



############################################################################
# | Send the post to all users

logger("[INFO] Sending post to ". count($users) ." users...\n", break_line: True);
$errors_list = [];
$success = 0;
$errors = 0;
$sent = 0;


// Filter users for admin-only mode
if (ADMIN_ONLY) {
    $users = array_filter($users, fn($u) => in_array($u['user_id'], $DEBUG_ADMINS));
}


// Split users into chunks
$user_chunks = array_chunk($users, $CHUNK_DIMENSION);
$total_users = count($users);
foreach ($user_chunks as $chunk_index => $chunk) {
    logger("[INFO] Processing chunk ". ($chunk_index + 1) ."/". count($user_chunks) ." (". count($chunk) ." users)...", break_line: True);

    // Create promises for async requests
    $promises = [];
    $chunk_users = [];
    foreach ($chunk as $user) {
        // Prepare request parameters
        $args = [
            'chat_id' => $user['user_id'],
            'from_chat_id' => $post_info['chat_id'],
            'message_id' => $post_info['message_id'],
        ];

        if (!empty($post_info['inline_keyboard'])) {
            $args['reply_markup'] = json_encode(['inline_keyboard' => $post_info['inline_keyboard']]);
        }

        // Create async promise
        $promises[$user['user_id']] = $client->postAsync('copyMessage', ['form_params' => $args]);
        $chunk_users[$user['user_id']] = $user;
    }


    // Wait for all promises to settle and process results
    $results = Promise\Utils::settle($promises)->wait();
    foreach ($results as $user_id => $result) {
        $sent++;

        $user = $chunk_users[$user_id];
        if ($result['state'] === 'fulfilled') {
            try {
                $response = json_decode($result['value']->getBody(), true);
                if (isset($response['ok']) && $response['ok'] && isset($response['result']['message_id'])) {
                    $success++;
                } else {
                    $errors++;

                    // Extract error description and store
                    $error_desc = $response['description'] ?? 'Unknown error';
                    if (!in_array($error_desc, $errors_list)) {
                        $errors_list[] = $error_desc;
                    }

                    // Mark user as inactive if not a flood wait error
                    if (!str_starts_with($error_desc, "Too Many Requests")) {
                        secure('UPDATE users SET attivo = 0 WHERE user_id = '. $user['user_id']);
                    }
                }
            } catch (Exception $e) {
                $errors++;
                $error_msg = $e->getMessage();
                if (!in_array($error_msg, $errors_list)) {
                    $errors_list[] = $error_msg;
                }
            }
        } else {
            // Request failed
            $errors++;
            $error_msg = $result['reason']->getMessage() ?? 'Request failed';

            if (!in_array($error_msg, $errors_list)) {
                $errors_list[] = $error_msg;
            }

            // Mark user as inactive
            if (!str_contains($error_msg, "Too Many Requests")) {
                secure('UPDATE users SET attivo = 0 WHERE user_id = '. $user['user_id']);
            }
        }
    }


    // Update admin status
    if ($sent % $UPDATE_EVERY_N_MESSAGES == 0 && $post_info['cbm_id']) {
        $percent = round(($sent / $total_users) * 100, 2);
        $success_percent = round(($success / ($sent ?: 1)) * 100, 2);

        $cb_text = [];
        $cb_text[] = "⚙️ <b>Invio post globale...</b> ";
        $cb_text[] = "";
        $cb_text[] = "📤 Inviato a <b>". $sent ."</b> utenti su <b>". $total_users ."</b> (<b>". $percent ."%</b>)";
        $cb_text[] = "✅ Successi: <b>". $success ."</b> (<b>". $success_percent ."%</b>)";
        $cb_text[] = "❌ Errori: <b>". $errors ."</b>";

        edit_text($post_info['chat_id'], $post_info['cbm_id'], $cb_text);
        sleep(1);
    }


    // Log progress after each chunk
    $percent = round(($sent / $total_users) * 100, 2);
    logger("[INFO] Sent ". $sent ."/". $total_users ." (". $percent ."%) messages", break_line: True);
    logger("\t - Success: ". $success, break_line: True);
    logger("\t - Errors: ". $errors, break_line: True);


    // Show errors
    if ($errors > 0 && !empty($errors_list)) {
        foreach ($errors_list as $error) {
            logger("\t ---> ". $error, break_line: True);
        }
    }


    // Sleep between chunks (except for the last one)
    if ($chunk_index < count($user_chunks) - 1) {
        sleep($SLEEP_BETWEEN_CHUNKS);
    }
}


// Final update
logger(break_line: True);
if ($post_info['cbm_id']) {
    logger("[DEBUG] Final updating admin...", break_line: True);

    $percent = 100;
    $success_percent = round(($success / (count($users) ?: 1)) * 100, 2);


    $cb_text = [];
    $cb_text[] = "⚙️ <b>Invio post globale completato!</b> ";
    $cb_text[] = "";
    $cb_text[] = "📤 Inviato a <b>". count($users) ."</b> utenti su <b>". count($users) ."</b> (<b>". $percent ."%</b>)";
    $cb_text[] = "✅ Successi: <b>". $success ."</b> (<b>". $success_percent ."%</b>)";
    $cb_text[] = "❌ Errori: <b>". $errors ."</b>";

    // Edit message
    edit_text($post_info['chat_id'], $post_info['cbm_id'], $cb_text);
}

logger("[SUCCESS] Done", break_line: True);
logger(break_line: True);


exit();