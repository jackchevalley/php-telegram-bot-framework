<?php
if (!defined('MAINSTART')) { die(); }
require_once 'public/configs.php';



// IGNORE AND DELETE
if (isset($msg) and $msg == '//') {
    if (isset($cbmid)) cb_reply();
    die();
}
elseif (isset($msg) and ($msg == '/del' or $msg == '/del_2')) {
    if (isset($cbmid)) {
        del($cbmid);
        if ($msg == '/del_2') del($cbmid - 1);
    }
    die();
}



// VARIABLE CREATION
if (true) {

    // Admins - verify if the current user is an admin
    $is_admin = in_array($userID, $ADMINS);
    $adm = $is_admin ? $userID : $MAIN_ADMIN;


    // Useful for use in texts and buttons, $empty_char is the invisible character
    $trash_icon = "🗑";
    $empty_char = "ㅤ";
    $bc = "Back 🔙";


    // Common buttons and texts
    $hide_button_row = [['text' => $trash_icon, 'callback_data' => '/del_2']];



    // Base function for db input management
    function temp($val = null, $userID = null): void {
        if (!$userID) global $userID;
        if (!$val) $val = null;

        secure("UPDATE users SET temp = :temp WHERE user_id = :id", ["temp" => $val, "id" => $userID]);
    }



    // USER AND DATABASE VARIABLES
    if (isset($userID) && $userID && defined('DB_ENABLED') && DB_ENABLED) {

        // User data and settings | New user initialization
        $us = secure("SELECT * FROM users WHERE user_id = :id", ['id' => $userID], 1);
        if (!(isset($us['user_id']))) {

            secure("INSERT INTO users(user_id, first_name, username) VALUE (:id, :name, :username)", [
                'id' => $userID,
                'name' => $name,
                'username' => $username ?? null
            ]);

            // Reload the data
            $us = secure("SELECT * FROM users WHERE user_id = :id", ['id' => $userID], 1);
        }
        if (!$us['temp']) $us['temp'] = "";

        // Update user data
        $new_username = $username ?? null;
        if ($us['first_name'] != $name || $us['username'] != $new_username || !$us['active']) {

            $query_params = [];
            $query_args = [];
            if ($us['first_name'] != $name) {
                $query_params[] .= "first_name = :n";
                $query_args['n'] = $name;
                $us['first_name'] = $name;
            }
            if ($us['username'] != $new_username) {
                $query_params[] .= "username = :u";
                $query_args['u'] = $new_username;
                $us['username'] = $new_username;
            }
            if (!$us['active']) {
                $query_params[] .= "active = 1";
                $us['active'] = 1;
            }

            // Update if necessary
            if (count($query_params)) {

                $query_args['id'] = $userID;
                $query = "UPDATE users SET ". implode(", ", $query_params) ." WHERE user_id = :id";
                secure($query, $query_args);

                unset($query);
                unset($query_args);
                unset($query_params);
                unset($new_username);
            }
        }


        // User tag for generic use
        $user_tag = getUserTag($userID, $name, $username ?? '');


        // BLOCKED USERS
        $bot_blocked_users = secure("SELECT * FROM blocked_users WHERE user_id = :id AND enabled = 1 ORDER BY ID DESC", ['id' => $userID], 1);
        if (isset($bot_blocked_users['user_id'])) {

            // Permanent block
            if ($bot_blocked_users['blocked']) {
                die();
            }
        }
    }



    // REDIRECT TO GROUP AND CHANNEL PANELS
    if ($chatID < 0) {
        if (isset($userID)) require_once 'other/sections/groups.php';
        else require_once 'other/sections/channels.php';
        exit();
    }
}



// Text message handling
if (isset($msg)) {

    // CHECK THAT ALL COMMANDS INSIDE HARD MENUS ARE PRESENT HERE
    $COMMANDS_ALIAS = [
        '/command_one' => '/start',
        '/command_two' => '/miao',
    ];


    // Check if the message is a command alias
    if (strlen($msg) < $LIMIT_MSG_ALIAS_LENGTH and isset($COMMANDS_ALIAS[$msg])) $msg = $COMMANDS_ALIAS[$msg];


    // COMMANDS SECTION
    if (str_starts_with($msg, '/')) {


        // Initial command, start
        if ($msg == "/start") {

            $text = [];
            $text[] = "<b>Welcome <a href='tg://user?id=$userID'>$name</a>!</b>";
            $text[] = "";
            $text[] = "The bot is working...";
            $text[] = date("Y-m-d H:i:s");

            $inline_menu = [];
            $inline_menu[] = [
                ['text' => "Button one", 'callback_data' => '/command_one'],
            ];
            $inline_menu[] = [
                ['text' => "Button two", 'callback_data' => '/command_two'],
                ['text' => "Button three", 'callback_data' => '/miao']
            ];


            if (isset($cbmid)) {
                cb_reply($text, $inline_menu);
            }
            else {
                smg($chatID, $text, $inline_menu);
            }

            temp();
        }

        // Miao command
        elseif ($msg == "/miao") {
            lock_non_callback();

            $inline_menu = [];
            $inline_menu[] = [['text' => $bc, 'callback_data' => '/start']];

            $text = "<b>Miao Miao 🐱</b>";
            cb_reply($text, $inline_menu);

            temp();
        }


        // Other commands here...


        elseif ($is_admin) {

            require_once 'other/sections/admin_commands.php';
            die();
        }


        // Command not recognized
        else {
            error("0. Command not recognized.");
        }
    }


    // TEXT INPUT SECTION (Non-commands)
    elseif ($us['temp']) {
        $temp = $us['temp'];



        // Administrator Section
        if ($is_admin) {

            require_once 'other/sections/admin_commands.php';
            die();
        }

        die();
    }


    // Generic unrecognized message
    else {
        error("1. Command not recognized.");
    }
}

// MEDIA HANDLING (CHAT and ADMIN)
else {

    // Receiving media input for admins
    if ($is_admin and $us['temp']) {

        require_once 'other/sections/admin_commands.php';
        die();
    }


    // Generic unrecognized message
    else {
        error("2. Command not recognized.");
    }
}

