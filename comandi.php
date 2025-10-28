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



//CREAZIONE VARIABILI
if (true) {

    //Amministratori - verifica se l'utente corrente è un admin
    $is_admin = in_array($userID, $ADMINS);
    $adm = $is_admin ? $userID : $ADMINS[0]; // Usa il primo admin della lista come fallback


    // Utili da usare nei testi e nei bottoni, emp sarebbe il carattere invisibile
    $bin = "🗑";
    $emp = "ㅤ";
    $bc = "Indietro 🔙";


    // Bottoni e testi comuni
    $hide_button_row = [['text' => $bin, 'callback_data' => '/del_2']];



    // Funzione base per gestione input db
    function temp($val = null, $userID = null): void {
        if (!$userID) global $userID;
        if (!$val) $val = null;

        secure("UPDATE users SET temp = :temp WHERE user_id = :id", ["temp" => $val, "id" => $userID]);
    }



    // VARIABILI UTENTE E DATABASE
    if (isset($userID) && $userID && defined('DB_ENABLED') && DB_ENABLED) {

        // Dati utente e impostazioni | Inizializzazione nuovo utente
        $us = secure("SELECT * FROM users WHERE user_id = :id", ['id' => $userID], 1);
        if (!(isset($us['user_id']))) {

            secure("INSERT INTO users(user_id, first_name, username) VALUE (:id, :name, :username)", [
                'id' => $userID,
                'name' => $nome,
                'username' => $username ?? null
            ]);

            // Ricarico i dati
            $us = secure("SELECT * FROM users WHERE user_id = :id", ['id' => $userID], 1);
        }
        if (!$us['temp']) $us['temp'] = "";

        // Aggiorna dati utente
        $new_username = $username ?? null;
        if ($us['first_name'] != $nome || $us['username'] != $new_username || !$us['attivo']) {

            $query_params = [];
            $query_args = [];
            if ($us['first_name'] != $nome) {
                $query_params[] .= "first_name = :n";
                $query_args['n'] = $nome;
                $us['first_name'] = $nome;
            }
            if ($us['username'] != $new_username) {
                $query_params[] .= "username = :u";
                $query_args['u'] = $new_username;
                $us['username'] = $new_username;
            }
            if (!$us['attivo']) {
                $query_params[] .= "attivo = 1";
                $us['attivo'] = 1;
            }

            // Update se necessario
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
        $user_tag = getUserTag($userID, $nome, $username ?? '');


        // UTENTI BLOCCATI
        $bot_blocked_users = secure("SELECT * FROM blocked_users WHERE user_id = :id AND enabled = 1 ORDER BY ID DESC", ['id' => $userID], 1);
        if (isset($bot_blocked_users['user_id'])) {

            // Permanent block
            if ($bot_blocked_users['blocked']) {
                die();
            }
        }
    }



    // REDIRECT A PANNELLI GRUPPI E CANALI
    if ($chatID < 0) {
        if (isset($userID)) require_once 'other/sections/groups.php';
        else require_once 'other/sections/channels.php';
        exit();
    }
}



// Gestione dei messaggi di testo
if (isset($msg)) {

    // CONTROLLARE CHE TUTTI I COMANDI DENTRO GLI HARD MENU SIANO QUI PRESENTI
    $COMMANDS_ALIAS = [
        '/command_one' => '/start',
        '/command_two' => '/miao',
    ];


    // Controllo se il messaggio è un alias di un comando
    if (strlen($msg) < $LIMIT_MSG_ALIAS_LENGTH and isset($COMMANDS_ALIAS[$msg])) $msg = $COMMANDS_ALIAS[$msg];


    // SEZIONE DEI COMANDI
    if (str_starts_with($msg, '/')) {


        // Comando iniziala, start
        if ($msg == "/start") {

            $text = [];
            $text[] = "<b>Benvenuto <a href='tg://user?id=$userID'>$nome</a>!</b>";
            $text[] = "";
            $text[] = "Il bot funziona...";
            $text[] = date("Y-m-d H:i:s");

            $inline_menu = [];
            $inline_menu[] = [
                ['text' => "Bottone uno", 'callback_data' => '/command_one'],
            ];
            $inline_menu[] = [
                ['text' => "Bottone due", 'callback_data' => '/command_two'],
                ['text' => "Bottone tre", 'callback_data' => '/miao']
            ];


            if (isset($cbmid)) {
                cb_reply($text, $inline_menu);
            }
            else {
                smg($chatID, $text, $inline_menu);
            }

            temp();
        }

        // Comando miao
        elseif ($msg == "/miao") {
            lock_non_callback();

            $inline_menu = [];
            $inline_menu[] = [['text' => $bc, 'callback_data' => '/start']];

            $text = "<b>Miao Miao 🐱</b>";
            cb_reply($text, $inline_menu);

            temp();
        }


        // Altri comandi qui...


        //Sezione Amministratori
        elseif ($is_admin) {

            require_once 'other/sections/admin_commands.php';
            die();
        }


        // Comando non riconosciuto
        else {
            error("0. Comando non riconosciuto.");
        }
    }


    // SEZIONE DEGLI INPUT di testo (Non comandi)
    elseif ($us['temp']) {
        $temp = $us['temp'];



        // Sezione Amministratori
        if ($is_admin) {

            require_once 'other/sections/admin_commands.php';
            die();
        }

        die();
    }


    // Messaggio generico non riconosciuto
    else {
        error("1. Comando non riconosciuto.");
    }
}

// Gestione dei media (CHAT e ADMIN)
else {

    // Ricezione dei media input per gli admin
    if ($is_admin and $us['temp']) {

        require_once 'other/sections/admin_commands.php';
        die();
    }


    // Messaggio generico non riconosciuto
    else {
        error("2. Comando non riconosciuto.");
    }
}

