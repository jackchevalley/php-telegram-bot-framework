<?php
if(!defined('MAINSTART')) { die(); }



// ============================================
// CONFIGURAZIONE BASE BOT
// ============================================
const INTERNAL_USER_ID = 0;     // ID usato per i messaggi di sistema

//Username del bot
$bot_username = "YourBotUsername_bot";

// Dominio del bot
$DOMAIN_URL = "https://your-domain.com/";

// Elenco degli admin (ID Telegram) | The first one is the main admin
$MAIN_ADMIN = 158472703;
$ADMINS = [
    $MAIN_ADMIN,
    // more admins...
];

$GENERIC_ADMIN_CHAT_ID = -100131213121312; // Chat generica per gli admin
$ADMIN_CHATS = [
    $GENERIC_ADMIN_CHAT_ID,
    // more admin chats...
];



// ============================================
// LIMITI GENERICI
// ============================================

// Limiti dimensione testo per alias comandi
$LIMIT_MSG_ALIAS_LENGTH = 50;



// ============================================
// ALTRI PARAMETRI
// ============================================

