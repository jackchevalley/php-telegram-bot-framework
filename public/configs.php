<?php
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }



// ============================================
// BASE BOT CONFIGURATION
// ============================================
const INTERNAL_USER_ID = 0;     // ID used for system messages

// Bot username
$bot_username = "YourBotUsername_bot";

// Bot domain
$DOMAIN_URL = "https://your-domain.com/";

// Admin list (Telegram IDs) | The first one is the main admin
$MAIN_ADMIN = 158472703;
$ADMINS = [
    $MAIN_ADMIN,
    // more admins...
];

$GENERIC_ADMIN_CHAT_ID = -100131213121312; // Generic chat for admins
$ADMIN_CHATS = [
    $GENERIC_ADMIN_CHAT_ID,
    // more admin chats...
];



// ============================================
// GENERIC LIMITS
// ============================================

// Text size limit for command aliases
$LIMIT_MSG_ALIAS_LENGTH = 50;



// ============================================
// OTHER PARAMETERS
// ============================================

