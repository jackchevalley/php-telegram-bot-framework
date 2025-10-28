<?php
if (!defined('MAINSTART')) { die(); }


// Variabili generali
require_once __DIR__ . '/../../public/configs.php';


// Se si tratta di messaggi degli amministratori ed è una delle chat admin, redirigi alla sezione apposita
if (in_array($chatID, $ADMIN_CHATS)) {
    require_once __DIR__ . '/../../other/sections/admin_commands.php';
    die();
}


die();
