<?php
use GuzzleHttp\Exception\GuzzleException;
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }




###############################################################################################
# GLOBAL CONSTANTS AND VARIABLES

// Configurations and variables, we include them again to make sure they are available
require_once 'configs.php';
require_once 'env_loader.php';

$admin_errors_ID = $MAIN_ADMIN;


    // CREATE VARIABLES FROM THE UPDATE
if(isset($update)) {
    if(isset($update['channel_post']))
        $update['message'] = $update['channel_post'];


    // Skip successful payment messages, we handle pre-checkout only
    if (isset($update['message']['successful_payment'])) die();


    // Callback query
    if(isset($update["callback_query"])) {
        $msg = strip_tags($update["callback_query"]["data"]);

        $cbid = $update["callback_query"]["id"];
        $cbmid = $update["callback_query"]["message"]["message_id"];
        $chatID = $update["callback_query"]["message"]["chat"]["id"];
        $userID = $update["callback_query"]["from"]["id"];

        $name = strip_tags($update["callback_query"]["from"]["first_name"]);

        if(isset($update["callback_query"]["from"]["username"]))
            $username = strip_tags($update["callback_query"]["from"]["username"]);
    }

    // Stars Payments
    elseif(isset($update['pre_checkout_query'])) {
        if(isset($update["pre_checkout_query"]["from"]["id"])) {
            $userID = $update["pre_checkout_query"]["from"]["id"];
            $chatID = $userID;
        }

        if(isset($update["pre_checkout_query"]["from"]["first_name"]))
            $name = strip_tags($update["message"]["from"]["first_name"]);

        if(isset($update["pre_checkout_query"]["from"]["username"]))
            $username = $update["message"]["from"]["username"];
    }

    // Normal messages
    else {

        if(isset($update["message"]["chat"]["id"]))
            $chatID = $update["message"]["chat"]["id"];

        if(isset($update["message"]["from"]["id"]))
            $userID = $update["message"]["from"]["id"];

        if(isset($update["message"]["from"]["first_name"]))
            $name = strip_tags($update["message"]["from"]["first_name"]);

        if(isset($update["message"]["from"]["username"]))
            $username = $update["message"]["from"]["username"];

        if(isset($update["message"]["chat"]["title"]))
            $title = $update["message"]["chat"]["title"];
    }



    // Messages
    if(isset($update['message']['text'])) {
        $msg = $update["message"]["text"];
    }

    // Photo
    elseif(isset($update['message']["photo"])) {
        $photo = $update["message"]["photo"];
        $photo = end($photo)['file_id'];
        $MEDIA_TYPE = "photo";

        $generic_file_id = $photo; // for logging purposes
    }

    // Video
    elseif(isset($update['message']["video"]['file_id'])) {
        $video = $update["message"]["video"]['file_id'];
        $MEDIA_TYPE = "video";

        $generic_file_id = $video; // for logging purposes
    }

    // Video note
    elseif(isset($update['message']["video_note"]['file_id'])) {
        $video_note = $update["message"]["video_note"]['file_id'];
        $MEDIA_TYPE = "video_note";

        $generic_file_id = $video_note; // for logging purposes
    }

    // Audio (voice note)
    elseif(isset($update['message']["voice"]['file_id'])) {
        $voice = $update["message"]["voice"]['file_id'];
        $MEDIA_TYPE = "voice";

        $generic_file_id = $voice; // for logging purposes
    }

    // Animation (GIF)
    elseif(isset($update['message']["animation"]['file_id'])) {
        $animation = $update["message"]["animation"]['file_id'];
        $MEDIA_TYPE = "animation";

        $generic_file_id = $animation; // for logging purposes
    }

    // Sticker
    elseif(isset($update['message']["sticker"]['file_id'])) {
        $sticker = $update["message"]["sticker"]['file_id'];
        $MEDIA_TYPE = "sticker";

        $generic_file_id = $sticker; // for logging purposes
    }

    // Location
    elseif(isset($update['message']["location"])) {
        $location = $update["message"]["location"];
        $MEDIA_TYPE = "location";
    }

    // Venue
    elseif(isset($update['message']["venue"])) {
        $venue = $update["message"]["venue"];
        $MEDIA_TYPE = "venue";
    }

    // Dice
    elseif(isset($update['message']["dice"])) {
        $dice = $update["message"]["dice"];
        $MEDIA_TYPE = "dice";
    }

    // Caption
    if(isset($update['message']['caption']))
        $caption = strip_tags($update['message']['caption']);

    // Message ID
    if(isset($update["message"]["message_id"]))
        $message_id = $update["message"]["message_id"];

}




###############################################################################################
# Guzzle HTTP Client SETUP

/**
 * Execute HTTP request using Guzzle client
 * Central function for all Telegram API communications
 *
 * @param string $url The API endpoint URL
 * @param array $args Request parameters
 * @param string $method HTTP method (POST, GET, etc.)
 * @return array Decoded JSON response
 */
function request(string $url, array $args = [], string $method='POST'): array
{

    global $client;

    try {
        if ($args)
            $response = $client->request($method, $url, ['json' => $args]);
        else
            $response = $client->request($method, $url);
    }
    catch (GuzzleException $e) {
        if (method_exists($e, 'getResponse')) {
            return json_decode($e->getResponse()->getBody(), true);
        }
    }

    return json_decode($response->getBody(), true);
}




###############################################################################################
# MESSAGE SENDING FUNCTIONS

/**
 * Send text message with full customization options
 * Main function for sending text-based communications
 *
 * @param int $chatID Target chat or user ID
 * @param int|string|array $text Message text (arrays will be joined with newlines)
 * @param array $menu Inline keyboard markup
 * @param array $hard_menu Reply keyboard markup or "remove" to remove keyboard
 * @param string $parse_mode Text parsing mode (html, markdown, etc.)
 * @param bool|array|string $link_preview Link preview configuration
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @param bool $no_parse Whether to skip variable parsing in text
 * @return int|string Message ID on success, error description on failure
 */
function sm(int $chatID, int|string|array $text, array $menu = [], array $hard_menu = [], string $parse_mode = "html", array|string|bool $link_preview = false, int|bool $reply_to = false, bool $protect_content = false, bool $no_parse = false): int|string {
    if(is_array($text))
        $text = implode("\n", $text);

    if(!$no_parse)
        $text = parse($text);

    // Link Options
    if ($link_preview === false) {
        $link_preview = ['is_disabled' => true];
    }
    elseif ($link_preview === true) {
        $link_preview = ['is_disabled' => false];
    }
    else {
        if (!is_array($link_preview)) {
            $link_preview = [
                'url' => $link_preview,
                'show_above_text' => false,
            ];
        }
    }


    // Arguments for the request
    $args = [
        'chat_id' => $chatID,
        'text' => $text,
        'parse_mode' => $parse_mode,
        'link_preview_options' => $link_preview,
        'protect_content' => $protect_content
    ];


    // Menu Options
    if(isset($menu) and $menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }
    if(isset($hard_menu) and $hard_menu) {
        $rm = ['keyboard' => $hard_menu, 'resize_keyboard' => true];
        $rm = json_encode($rm);

        $args["reply_markup"] = $rm;
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }


    // Send the message
    $response = request('sendMessage', $args);
    if(isset($response['result']['message_id'])) {
        return (int)$response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendMessage error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send message and delete previous message (send with cleanup)
 * Useful for updating bot responses without cluttering the chat
 *
 * @param int $chatID Target chat or user ID
 * @param int|string|array $text Message text
 * @param array $menu Inline keyboard markup
 * @param array $hard_menu Reply keyboard markup
 * @param string $parse_mode Text parsing mode
 * @param array|string|bool $link_preview Link preview configuration
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content
 * @param bool $no_parse Whether to skip variable parsing in text
 * @return int|string Message ID on success, error description on failure
 */
function smg(int $chatID, int|string|array $text, array $menu = [], array $hard_menu = [], string $parse_mode = "html", array|string|bool $link_preview = false, int|bool $reply_to = false, bool $protect_content = false, bool $no_parse = false): int|string {

    $result = sm($chatID, $text, $menu, $hard_menu, $parse_mode, $link_preview, $reply_to, $protect_content, $no_parse);
    if (is_numeric($result)) {

        // If global last_id is set, delete previous message
        if (isset($GLOBALS['us'])) {
            global $us;
            if(isset($us['last_id']) and $us['last_id'] != 0) {
                del($us['last_id']);
            }
        }

        secure("UPDATE users SET last_id = :id WHERE user_id = :chat", [
            'id' => $result,
            'chat' => $chatID
        ]);
    }

    return $result;
}

/**
 * Forward or copy message from one chat to another
 * Tries to forward first, falls back to copying if forwarding fails
 *
 * @param int $chatID Target chat ID
 * @param int $from_chatID Source chat ID
 * @param int $message_id Message ID to forward or copy
 * @param bool $use_copy Whether to force copying instead of forwarding
 * @param string $caption Optional caption for copied message (not used in forwarding)
 * @return int|string Message ID on success, error description on failure
 */
function forwardOrCopy(int $chatID, int $from_chatID, int $message_id, bool $use_copy = false, string $caption = ''): int|string {
    $result = null;

    // Try forwarding first if not forced to copy
    if (!$use_copy) {
        $result = forwardMessage($chatID, $from_chatID, $message_id);
    }

    // If forward fails, try copying
    if (!$result or !is_numeric($result)) {
        $result = copyMessage($chatID, $from_chatID, $message_id, $caption);
    }

    return $result;
}

/**
 * Forward message from one chat to another
 *
 * @param int $chatID Target chat ID
 * @param int $from_chatID Source chat ID
 * @param int $message_id Message ID to forward
 * @return int|string Message ID on success, error description on failure
 */
function forwardMessage(int $chatID, int $from_chatID, int $message_id): int|string {
    $args = [
        'chat_id' => $chatID,
        'from_chat_id' => $from_chatID,
        'message_id' => $message_id
    ];

    $response = request('forwardMessage', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "forwardMessage error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Copy message from one chat to another without forwarding header
 *
 * @param int $chatID Target chat ID
 * @param int $from_chatID Source chat ID
 * @param int $message_id Message ID to copy
 * @param string $caption Optional caption for copied message
 * @param array $inline_menu Optional inline keyboard for copied message
 * @return int|string Message ID on success, error description on failure
 */
function copyMessage(int $chatID, int $from_chatID, int $message_id, string $caption = '', array $inline_menu = []): int|string {
    $args = [
        'chat_id' => $chatID,
        'from_chat_id' => $from_chatID,
        'message_id' => $message_id,
    ];

    if($caption) {
        $args['caption'] = $caption;
        $args['parse_mode'] = 'html';
    }

    if($inline_menu) {
        $rm = ['inline_keyboard' => $inline_menu];
        $rm = json_encode($rm);
        $args["reply_markup"] = $rm;
    }

    $response = request('copyMessage', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "copyMessage error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}



###############################################################################################
# MESSAGE EDITING AND CALLBACK FUNCTIONS

/**
 * Edit existing text message
 * Used for updating dynamic content without sending new messages
 *
 * @param int $chatID Target chat ID
 * @param int $message_id Message ID to edit
 * @param int|string|array $text New message text
 * @param array $menu New inline keyboard markup
 * @param array|string|bool $link_preview Link preview configuration
 * @param bool $no_parse Whether to skip variable parsing in text
 * @return bool True on success, false on failure
 */
function edit_text(int $chatID, int $message_id, int|string|array $text, array $menu = [], array|string|bool $link_preview = false, bool $no_parse = false): bool {
    if(is_array($text))
        $text = implode("\n", $text);

    if (!$no_parse)
        $text = parse($text);


    // Link Options
    if ($link_preview === false) {
        $link_preview = ['is_disabled' => true];
    }
    elseif ($link_preview === true) {
        $link_preview = ['is_disabled' => false];
    }
    else {
        if (!is_array($link_preview)) {
            $link_preview = [
                'url' => $link_preview,
                'show_above_text' => false,
            ];
        }
    }


    $args = [
        'chat_id' => $chatID,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'html',
        'link_preview_options' => $link_preview
    ];

    if(isset($menu) and $menu) {
        $rm = ['inline_keyboard' => $menu];
        $rm = json_encode($rm);
        $args["reply_markup"] = $rm;
    }

    $response = request('editMessageText', $args);
    if (!isset($response['ok']) or !$response['ok']) {
        if ($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "edit_text error: " . $response['description'], parse_mode: '');
        return false;
    }
    return true;
}

/**
 * Edit inline keyboard of existing message
 *
 * @param int $chatID Target chat ID
 * @param int $message_id Message ID to edit
 * @param array $menu New inline keyboard markup
 * @return bool True on success, false on failure
 */
function edit_keyboard(int $chatID, int $message_id, array $menu = []): bool {
    $args = [
        'chat_id' => $chatID,
        'message_id' => $message_id,
    ];

    if(isset($menu) and $menu) {
        $rm = ['inline_keyboard' => $menu];
        $rm = json_encode($rm);
        $args["reply_markup"] = $rm;
    }

    $response = request('editMessageReplyMarkup', $args);
    if (!isset($response['ok']) or !$response['ok']) {
        if ($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "edit_keyboard error: " . $response['description'], parse_mode: '');
        return false;
    }
    return true;
}

/**
 * Handle callback query responses (inline keyboard button presses)
 * Provides unified interface for editing messages and answering callbacks
 *
 * @param int|string|array $text New message text (false to skip editing)
 * @param array $menu New inline keyboard markup
 * @param int $cbmid Callback message ID (auto-detected if 0)
 * @param array|string|bool $link_preview Link preview configuration
 * @param int $cbid Callback query ID (auto-detected if false)
 * @param string $ntext Notification text to show user
 * @param bool $ntype Whether to show notification as alert (true) or toast (false)
 */
function cb_reply(
    int|string|array $text = '',
    array $menu = [],
    int $cbmid = 0,
    array|string|bool $link_preview = false,
    int $cbid = 0,
    string $ntext = '',
    bool $ntype = false
): void {
    global $chatID;

    if(!$cbmid && isset($GLOBALS['cbmid'])) $cbmid = $GLOBALS['cbmid'];
    if(!$cbid && isset($GLOBALS['cbid'])) $cbid = $GLOBALS['cbid'];

    if($text) {
        edit_text($chatID, $cbmid, $text, $menu, $link_preview);
    }

    if(isset($cbid) and $cbid) {
        $args = ['callback_query_id' => $cbid];

        // Screen notification
        if(isset($ntext) and $ntext) {
            $args['text'] = $ntext;
            $args['show_alert'] = $ntype;
        }

        request('answerCallbackQuery', $args);
    }
}




###############################################################################################
# MEDIA SENDING FUNCTIONS

/**
 * Send photo with optional caption and menu
 *
 * @param int $chatID Target chat ID
 * @param string $photo Photo file ID or URL
 * @param string|array $caption Photo caption
 * @param array $menu Inline keyboard markup
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendImage(int $chatID, string $photo, string|array $caption = '', array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    if(!$caption)
        $caption = '';

    if(is_array($caption))
        $caption = implode("\n", $caption);


    $args = [
        'chat_id' => $chatID,
        'photo' => $photo,
        'parse_mode' => 'html',
        'caption' => $caption,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendPhoto', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendImage error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send video with optional caption and menu
 *
 * @param int $chatID Target chat ID
 * @param string $video Video file ID or URL
 * @param string|array $caption Video caption
 * @param array $menu Inline keyboard markup
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendVideo(int $chatID, string $video, string|array $caption = '', array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    if(!$caption)
        $caption = '';

    if(is_array($caption))
        $caption = implode("\n", $caption);


    $args = [
        'chat_id' => $chatID,
        'video' => $video,
        'parse_mode' => 'html',
        'caption' => $caption,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendVideo', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendVideo error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send video note (circular video message)
 *
 * @param int $chatID Target chat ID
 * @param string $video_note Video note file ID
 * @param array $menu Inline keyboard markup
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendVideoNote(int $chatID, string $video_note, array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    $args = [
        'chat_id' => $chatID,
        'video_note' => $video_note,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendVideoNote', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendVideoNote error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send voice message with optional caption
 *
 * @param int $chatID Target chat ID
 * @param string $voice Voice file ID
 * @param string|array $caption Voice message caption
 * @param array $menu Inline keyboard markup
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendVoice(int $chatID, string $voice, string|array $caption = '', array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    if(!$caption)
        $caption = '';

    if(is_array($caption))
        $caption = implode("\n", $caption);

    $args = [
        'chat_id' => $chatID,
        'voice' => $voice,
        'parse_mode' => 'html',
        'caption' => $caption,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendVoice', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendVoice error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send animation (GIF) with optional caption
 *
 * @param int $chatID Target chat ID
 * @param string $animation Animation file ID or URL
 * @param string|array $caption Animation caption
 * @param array $menu Inline keyboard markup
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendAnimation(int $chatID, string $animation, string|array $caption = '', array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    if(!$caption)
        $caption = '';

    if(is_array($caption))
        $caption = implode("\n", $caption);

    $args = [
        'chat_id' => $chatID,
        'animation' => $animation,
        'parse_mode' => 'html',
        'caption' => $caption,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendAnimation', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendAnimation error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send sticker
 *
 * @param int $chatID Target chat ID
 * @param string $sticker Sticker file ID
 * @param array $menu Inline keyboard markup
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendSticker(int $chatID, string $sticker, array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    $args = [
        'chat_id' => $chatID,
        'sticker' => $sticker,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendSticker', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendSticker error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send location coordinates
 *
 * @param int $chatID Target chat ID
 * @param float $latitude Location latitude
 * @param float $longitude Location longitude
 * @param array $menu Inline keyboard markup
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendLocation(int $chatID, float $latitude, float $longitude, array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    $args = [
        'chat_id' => $chatID,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'protect_content' => $protect_content
    ];

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendLocation', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendLocation error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}

/**
 * Send venue (location with business details)
 *
 * @param int $chatID Target chat ID
 * @param float $latitude Venue latitude
 * @param float $longitude Venue longitude
 * @param string $title Venue name
 * @param string $address Venue address
 * @param string $foursquare_id Foursquare venue ID (optional)
 * @param array $menu Inline keyboard markup
 * @param int|false $reply_to Message ID to reply to
 * @param bool $protect_content Whether to protect content from forwarding
 * @return int|string Message ID on success, error description on failure
 */
function sendVenue(int $chatID, float $latitude, float $longitude, string $title, string $address, string $foursquare_id = '', array $menu = [], int|bool $reply_to = false, bool $protect_content = false): int|string {
    $args = [
        'chat_id' => $chatID,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'title' => $title,
        'address' => $address,
        'protect_content' => $protect_content
    ];

    if($foursquare_id) {
        $args['foursquare_id'] = $foursquare_id;
    }

    if($menu) {
        $rm = [
            'inline_keyboard' => $menu,
            'force_reply' => false
        ];
        $args["reply_markup"] = json_encode($rm);
    }

    // Reply to Options
    if(isset($reply_to) and $reply_to) {
        $args['reply_to_message_id'] = $reply_to;
    }

    $response = request('sendVenue', $args);
    if(isset($response['result']['message_id'])) {
        return $response['result']['message_id'];
    }
    else {
        if($chatID == $GLOBALS['admin_errors_ID'])
            sm($chatID, "sendVenue error: ". $response['description'], parse_mode: '');

        return $response['description'];
    }
}




###############################################################################################
# UTILITY AND HELPER FUNCTIONS

/**
 * Check if user is member of a specific chat/channel
 * Currently returns true for testing purposes
 *
 * @param int $userID User ID to check
 * @param int $chatID Chat/channel ID to check membership
 * @return bool True if user is member, false otherwise
 */
function is_member(int $userID, int $chatID): bool {
    if (is_admin($userID)) return true;

    $args = [
        'chat_id' => $chatID,
        'user_id' => $userID
    ];
    $response = request('getChatMember', $args);
    if(isset($response['ok']) and $response['ok']) {
        if(isset($response['result']['status']) and in_array($response['result']['status'], ['member', 'administrator', 'creator', 'restricted'])) {
            return true;
        }
    }

    return false;
}

/**
 * Delete a message from chat
 *
 * @param int $message_id Message ID to delete
 * @param int|false $chatID Chat ID (uses global $chatID if false)
 */
function del(int $message_id, int|bool $chatID = false): void {
    if(!$chatID) global $chatID;

    $args = [
        'chat_id' => $chatID,
        'message_id' => $message_id
    ];
    request('deleteMessage', $args);
}

/**
 * Send error message and optionally stop execution
 * Standardized error handling for user-facing errors
 *
 * @param string $text Error message to send
 * @param int|false $chatID Target chat ID (uses global if false)
 * @param bool $die Whether to stop script execution after sending error
 */
function error(string $text, int|bool $chatID = False, bool $die = True): void
{
    if($chatID === False)
        global $chatID;

    if (isset($GLOBALS['cbid'])) {
        cb_reply();
    }

    sm($chatID, '❌ '. $text);
    if($die)
        die();
}

/**
 * Parse text and replace variable placeholders with their values
 * Replaces $variableName with the value of $GLOBALS['variableName']
 *
 * @param string $text Text containing variable placeholders
 * @return string Text with variables replaced by their values
 */
function parse(string $text): string {
    $re = '/([$])\w+/imu';
    preg_match_all($re, $text, $matches);

    if (!isset($matches[0]) or !$matches[0] or !count($matches[0]))
        return $text;

    foreach($matches[0] as $var) {
        $nm = str_replace("$", "", $var);

        if(isset($GLOBALS[$nm])) {
            $val = $GLOBALS[$nm];
            if (strtolower($nm) == 'api') $val = "HIDDEN_FOR_SECURITY";
            $text = str_replace($var, $val, $text);
        }
    }

    return $text;
}


/**
 * Check if user is fully admin of the bot
 *
 * @param int $userID User ID to check
 * @return bool True if user has premium, false otherwise
 */
function is_admin(int $userID): bool {
    require_once __DIR__ . '/../public/configs.php';
    return in_array($userID, $GLOBALS['ADMINS']);
}

/**
 * Format UUID for use in URLs or identifiers
 * Replaces hyphens with underscores for better compatibility
 *
 * @param string $uuid UUID string
 * @return string Formatted UUID with underscores
 */
function format_uuid(string $uuid): string {
    return str_replace('-', '_', $uuid);
}

/**
 * Resolve server file path to public URL
 * Converts internal file paths to accessible URLs based on domain configuration
 *
 * @param string $path Internal file path
 * @return string Publicly accessible URL
 */
function resolve_path_to_url(string $path): string {
    $private_path = '/var/www/html/';
    $path = realpath($path);
    return str_replace($private_path, $GLOBALS['DOMAIN_URL'], $path);
}

/**
 * Retrieve user information from Telegram API
 * Fetches user details such as name, username, and profile picture
 *
 * @param int|string $user User ID or Username to retrieve information for
 * @param bool $from_telegram Whether to force fetching data from Telegram API
 * @return array|false User information array on success, false on failure
 */
function getUser(int|string $user, bool $from_telegram = false): array|false {

    // Find userID if not provided
    if (!is_numeric($user)) {
        $userID = resolve_username_to_id($user);
        if (!$userID) return false;
    }
    else $userID = $user;

    // Try to get data from local database first if not forced to fetch from Telegram
    if (!$from_telegram) {
        $us_info = secure('SELECT * FROM users WHERE user_id = :chat', [
            'chat' => $userID
        ], 1);

        // If all data is present in local database, return it
        if (isset($us_info['first_name']) and $us_info['first_name']) {
            return [
                'id' => $us_info['user_id'],
                'user_id' => $us_info['user_id'],
                'first_name' => $us_info['first_name'],
                'username' => $us_info['username']
            ];
        }
    }

    // Get data from telegram API if necessary
    $r = request('getChat', ['chat_id' => $userID], 'GET');
    if(isset($r['ok']) and $r['ok']) {
        return $r['result'] + ['user_id' => $r['result']['id']];
    }

    return false;
}

/**
 * Generate HTML link to mention a user by their ID
 * Formats user mention with name and optional username
 *
 * @param int $userID User ID to mention
 * @param string $name Display name for the user
 * @param string $username Optional username (without @)
 * @return string HTML formatted user mention
 */
function getUserTag(int $userID, string $name = '', string $username = ''): string {

    // If only userID is provided, fetch user info from database or Telegram
    if (!$name) {
        $user_info = getUser($userID);
        if (!$user_info) {
            return "<a href='tg://user?id=$userID'>User</a> [<code>$userID</code>]";
        }

        $name = $user_info['first_name'];
        $username = $user_info['username'] ?? '';
    }

    // Fallback to userID if name is empty
    $name = $name ? : $userID;
    $name = trim(strip_tags($name));

    // Check if user is premium
    $extra_tag = '';
    if (is_admin($userID)) $extra_tag = '⚔️';

    return "<a href='tg://user?id=$userID'>$name</a> [<code>$userID</code>]". ($username ? " - (@$username)" : "") . " | #u_$userID $extra_tag";
}

/**
 * Cut text to a maximum length without breaking words
 * Ensures that the returned text does not exceed the specified length
 *
 * @param string $text Input text to cut
 * @param int $max_length Maximum allowed length of the text
 * @return string Cut text within the specified length
 */
function cut_text(string $text, int $max_length): string {
    if (strlen($text) <= $max_length) {
        return $text;
    }

    $output = '';
    foreach (explode(' ', $text) as $var) {

        $output .= $var;
        if(strlen($output) >= $max_length)
            return $output . '...';
        else
            $output .= ' ';
    }

    return $output;
}

/**
 * Resolve Telegram username to user ID
 * Looks up the user ID associated with a given username
 *
 * @param int|string $content Telegram username (with or without @)
 * @return int|false User ID if found, false otherwise
 */
function resolve_username_to_id(int|string $content): int | false {

    // If it's already a numeric ID, check existence and return
    if (is_numeric($content)){
        $check = secure('SELECT * FROM users WHERE user_id = :chat', [
            'chat' => (int)$content
        ], 1);
        return (isset($check['user_id'])) ? (int)$check['user_id'] : false;
    }

    // If it's a username, remove @ and search
    $username = ltrim($content, '@');
    $check = secure('SELECT * FROM users WHERE username = :username', [
        'username' => $username
    ], 1);
    return (isset($check['user_id'])) ? (int)$check['user_id'] : false;
}

/**
 * Parse date and time from various input formats
 * Supports absolute dates, times, and relative time formats
 *
 * @param string $input Input date/time string (e.g., "10:20 15-08-2023", "50m", "1h", "3d")
 * @param string $format Output date format (default 'H:i d-m-Y')
 * @return false|string Formatted date/time string or false on failure
 * @throws Exception If date/time parsing fails
 */
function parseDateTime(string $input, string $format = 'H:i d-m-Y'): false|string
{
    // Clean up input - remove newlines and multiple spaces
    $input = str_replace("\n", " ", $input);
    while (str_contains($input, "  ")) {  // Fixed: correct parameter order
        $input = str_replace("  ", " ", $input);
    }
    $input = trim($input);

    // Check if input is a relative time format (e.g., "50m", "1h", "3d", "4w", "1mo", "2y")
    if (preg_match('/^(\d+)(m|h|d|w|mo|y)$/i', $input, $matches)) {
        $amount = (int)$matches[1];
        $unit = strtolower($matches[2]);

        $currentDateTime = new DateTime();

        try {
            switch ($unit) {
                case 'm':
                    $construct_time = $currentDateTime->modify("+$amount minutes");
                    break;
                case 'h':
                    $construct_time = $currentDateTime->modify("+$amount hours");
                    break;
                case 'd':
                    $construct_time = $currentDateTime->modify("+$amount days");
                    break;
                case 'w':
                    $construct_time = $currentDateTime->modify("+$amount weeks");
                    break;
                case 'mo':
                    $construct_time = $currentDateTime->modify("+$amount months");
                    break;
                case 'y':
                    $construct_time = $currentDateTime->modify("+$amount years");
                    break;
                default:
                    return false;
            }
        } catch (Exception) {
            return false;
        }

        return $construct_time->format($format);
    }

    $parts = explode(' ', $input);

    // Detect if input contains time (has colon) or is date-only
    $hasTime = str_contains($input, ':');

    $hours = '00';
    $minutes = '00';

    if ($hasTime) {
        // Handle the time part
        $time = $parts[0];
        $timeParts = explode(':', $time);
        $hours = $timeParts[0];
        $minutes = $timeParts[1] ?? '';

        // Validate time components
        if (empty($hours) || !is_numeric($hours) || $hours < 0 || $hours > 23) {
            return false;
        }
        if (empty($minutes) || !is_numeric($minutes) || $minutes < 0 || $minutes > 59) {
            return false;
        }

        // Ensure proper formatting
        $hours = str_pad($hours, 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);

        // Handle the date part
        $date = $parts[1] ?? '';
    } else {
        // No time specified, entire input is the date part
        $date = $input;
    }

    // Get the current date and time
    $currentDateTime = new DateTime();

    // Case 1: No date specified (e.g., "10:20")
    if (empty($date)) {
        try {
            $construct_time = new DateTime('today ' . $hours . ':' . $minutes);
        } catch (Exception) {
            return false;
        }

        // If the time has already passed today, schedule for tomorrow
        // For date-only input (no time), we don't move to next day since it defaults to 00:00
        if ($hasTime && $construct_time <= $currentDateTime) {
            $construct_time->modify('+1 day');
        }
    }
    // Case 2: Date specified
    else {
        $dateParts = str_replace('/', '-', $date);
        $dateParts = explode('-', $dateParts);

        $day = $dateParts[0] ?? '';
        $month = $dateParts[1] ?? '';
        $year = $dateParts[2] ?? '';

        // Validate day
        if (empty($day) || !is_numeric($day) || $day < 1 || $day > 31) {
            return false;
        }

        // Case 2a: Only day specified (e.g., "10:20 15")
        if (empty($month)) {
            $currentMonth = $currentDateTime->format('m');
            $currentYear = $currentDateTime->format('Y');

            // Validate day for current month
            if (!checkdate($currentMonth, $day, $currentYear)) {
                return false;
            }

            $str_date = $day . '-' . $currentMonth . '-' . $currentYear;
            try {
                $construct_time = new DateTime($str_date . ' ' . $hours . ':' . $minutes);
            } catch (Exception) {
                return false;
            }

            // If the date/time has passed, move to next month
            if ($construct_time <= $currentDateTime) {
                $construct_time->modify('+1 month');

                // Re-validate the day for the new month
                $newMonth = $construct_time->format('m');
                $newYear = $construct_time->format('Y');
                if (!checkdate($newMonth, $day, $newYear)) {
                    // If day is invalid for new month, find the last valid day
                    $lastDay = date('t', mktime(0, 0, 0, $newMonth, 1, $newYear));
                    $construct_time->setDate($newYear, $newMonth, min($day, $lastDay));
                }
            }
        }
        // Case 2b: Day and month specified
        else {
            // Validate month
            if (!is_numeric($month) || $month < 1 || $month > 12) {
                return false;
            }

            // Handle year
            if (empty($year)) {
                // No year specified, use current year
                $year = $currentDateTime->format('Y');
            } else {
                // Handle 2-digit years
                if (strlen($year) === 2) {
                    $currentYear = (int)$currentDateTime->format('Y');
                    $currentCentury = floor($currentYear / 100) * 100;
                    $twoDigitYear = (int)$year;

                    // Use a 50-year window: if 2-digit year is < 50, assume next century
                    if ($twoDigitYear < 50) {
                        $year = $currentCentury + 100 + $twoDigitYear;
                    } else {
                        $year = $currentCentury + $twoDigitYear;
                    }
                }
            }

            // Validate the complete date
            if (!checkdate($month, $day, $year)) {
                return false;
            }

            $str_date = $day . '-' . $month . '-' . $year;
            try {
                $construct_time = new DateTime($str_date . ' ' . $hours . ':' . $minutes);
            } catch (Exception) {
                return false;
            }

            // If no year was originally specified and the date/time has passed, move to next year
            if (empty($dateParts[2]) && $construct_time <= $currentDateTime) {
                $construct_time->modify('+1 year');
            }
        }
    }

    // Return the formatted date/time if it's in the future (or today for date-only inputs)
    if ($construct_time > $currentDateTime || (!$hasTime && $construct_time->format('Y-m-d') >= $currentDateTime->format('Y-m-d'))) {
        return $construct_time->format($format);
    } else {
        return false;
    }
}

/**
 * Calculate absolute difference between two dates in specified units
 *
 * @param string $date1 First date string
 * @param string $date2 Second date string
 * @param string $format Unit for difference ('year', 'month', 'day', 'hour', 'minute', 'second')
 * @return int Difference between dates in specified units
 * @throws InvalidArgumentException If date strings are invalid or format is unsupported
 */
function dateDifference(string $date1, string $date2, string $format = 'minute'): int {
    try {
        if ($date1 == "now") $date1 = date('Y-m-d H:i:s');
        $datetime1 = new DateTime($date1);
    } catch (DateMalformedStringException) {
        throw new InvalidArgumentException("Invalid date1: $date1");
    }

    try {
        if ($date2 == "now") $date2 = date('Y-m-d H:i:s');
        $datetime2 = new DateTime($date2);
    } catch (DateMalformedStringException) {
        throw new InvalidArgumentException("Invalid date2: $date2");
    }

    $interval = $datetime1->diff($datetime2);
    $difference = match ($format) {
        'year' => (int)$interval->format('%y'),
        'month' => (int)$interval->format('%m') + ((int)$interval->format('%y') * 12),
        'day' => (int)$interval->format('%a'),
        'hour' => ((int)$interval->format('%a') * 24) + (int)$interval->format('%h'),
        'minute' => (((int)$interval->format('%a') * 24 + (int)$interval->format('%h')) * 60) + (int)$interval->format('%i'),
        'second' => ((((int)$interval->format('%a') * 24 + (int)$interval->format('%h')) * 60 + (int)$interval->format('%i')) * 60) + (int)$interval->format('%s'),
        default => throw new InvalidArgumentException("Invalid format: $format"),
    };

    return abs($difference);
}

/**
 * Convert boolean/int value to corresponding emoji or return same value
 *
 * @param int|bool|string|null $value Int, Boolean or null to convert
 * @param string $true_emoji Emoji to use for true value (default '✅')
 * @param string $false_emoji Emoji to use for false value (default '❌')
 * @param bool $value_as_true If true, return the original $value instead of emoji when $value is truthy
 *
 * @return string|int Corresponding emoji or the original value
 */
function bool_to_value(int|bool|string|null $value, string $true_emoji = '✅', string $false_emoji = '❌', bool $value_as_true = false): string|int {
    if ($value) {
        return $value_as_true ? $value : $true_emoji;
    }
    return $false_emoji;
}

/**
 * Ensure that callback query ID (cbid) is set before proceeding
 * Terminates execution with a fatal error if cbid is not set
 */
function lock_non_callback(): void {
    if (!isset($GLOBALS['cbid'])) die("[FATAL] lock_cbmid called without cbid set");
}




###############################################################################################
# DEBUG AND DEVELOPMENT FUNCTIONS

/**
 * Build and display SQL query for debugging purposes
 * Shows the final SQL query with parameters substituted
 * Only sends to admin for security
 *
 * @param string $sql SQL query with parameter placeholders
 * @param array|null $array Parameter values to substitute
 * @param bool $force_to_admin Force sending to admin even if not in admin chat
 */
function build(string $sql, array|null $array = [], bool $force_to_admin = false): void
{
    foreach(array_keys($array) as $var) {
        $sql = str_replace(":". $var, '"'. $array[$var] .'"', $sql);
    }

    global $chatID;
    if(
        ($chatID === $GLOBALS['admin_errors_ID'] or $force_to_admin)
        and function_exists("sm")
    ) {
        sm($GLOBALS['admin_errors_ID'], "<b>BUILD EXECUTED</b> \n\n<code>" . $sql . "</code>\n\n<code>" . print_r($array, true) . "</code>");
    }
}
