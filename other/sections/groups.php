<?php
if (!defined('MAINSTART')) { die(); }


// General variables
require_once __DIR__ . '/../../public/configs.php';


// If it's a message from admins and it's one of the admin chats, redirect to the appropriate section
if (in_array($chatID, $ADMIN_CHATS)) {
    require_once __DIR__ . '/../../other/sections/admin_commands.php';
    die();
}


die();
