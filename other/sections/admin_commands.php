<?php
if (!defined('MAINSTART')) { die(); }
if (!isset($userID)) die();


// Controllo di sicurezza, anche se ridondante
if (!$is_admin and !in_array($chatID, $ADMIN_CHATS)) {
    die();
}


// Messaggi di testo
if (isset($msg)) {

    // Comandi
    if (str_starts_with($msg, '/')) {


        // generic replace /admin_ init
        if (str_starts_with($msg, '/admin_')) {
            $msg = str_replace('/admin_', '/', $msg);
        }


        // Comandi riservati agli AMMINISTRATORI
        if ($is_admin) {

            // Comando per visualizzare l'elenco dei comandi admin
            if ($msg == '/admin_commands' or $msg == '/help_admin') {

                $text = [];
                $text[] = "🔧 <b>Comandi Amministratore</b>";
                $text[] = "";
                $text[] = "Da implementare...";

                $inline_menu = [$hide_button_row];
                sm($chatID, $text, $inline_menu);
                die();
            }

            // Status del server e delle richieste
            elseif ($msg == '/status') {
                require_once __DIR__ . '/../../public/metrics_functions.php';

                // Raccogli tutte le metriche
                $cpuUsage = getCpuUsage();
                $cpuCores = getCpuCores();
                $memInfo = getMemoryInfo();
                $systemUptime = getSystemUptime();
                $loadAvg = getLoadAverage();
                $diskUsage = getDiskUsage();

                // Test database
                $dbPerf = testDatabasePerformance();

                // Test telegram connectivity
                $requestsPerf = testRequestsPerformance();

                // Costruisci messaggio
                $text = [];
                $text[] = "🤖 <b>Stato Sistema</b>";
                $text[] = "";

                // Sezione Server
                $text[] = "📊 <b>SERVER</b>";
                $cpuEmoji = $cpuUsage < 60 ? '✅' : ($cpuUsage < 90 ? '⚠️' : '🔴');
                $text[] = "• CPU: $cpuEmoji <code>$cpuUsage%</code> ($cpuCores core)";

                if ($memInfo) {
                    $ramEmoji = $memInfo['percent'] < 55 ? '✅' : ($memInfo['percent'] < 75 ? '⚠️' : '🔴');
                    $text[] = "• RAM: $ramEmoji <code>{$memInfo['used']} MB / {$memInfo['total']} MB ({$memInfo['percent']}%)</code>";
                }

                $diskEmoji = $diskUsage['percent'] < 55 ? '✅' : ($diskUsage['percent'] < 75 ? '⚠️' : '🔴');
                $text[] = "• Disco: $diskEmoji <code>{$diskUsage['used']} / {$diskUsage['total']} ({$diskUsage['percent']}%)</code>";

                $text[] = "• Uptime: <code>$systemUptime</code>";
                $text[] = "• Load Avg: <code>{$loadAvg['1min']} | {$loadAvg['5min']} | {$loadAvg['15min']}</code>";
                $text[] = "";

                // Sezione Database
                $text[] = "💾 <b>DATABASE</b>";
                $text[] = "• Status: {$dbPerf['status']} <code>{$dbPerf['text']}</code>";
                $text[] = "  Query Test: <code>{$dbPerf['time']} ms</code>";
                $text[] = "";

                // Sezione Bot
                $text[] = "🔧 <b>BOT INFO</b>";
                $text[] = "• Picco memoria: <code>". formatBytes(memory_get_peak_usage()) ."</code>";
                $text[] = "• Connettività API: {$requestsPerf['status']} <code>{$requestsPerf['text']}</code>";
                $text[] = "  Request Test: <code>{$requestsPerf['time']} ms</code>";

                $inline_menu = [$hide_button_row];
                sm($chatID, $text, $inline_menu);
            }

        }


        // Comandi riservati a tutti i componenti del gruppo GENERIC_ADMIN_CHAT
        if ($userID == $adm or $chatID == $GENERIC_ADMIN_CHAT_ID) {
            echo "Implement admin group commands here";
        }

    }

    // Input
    else {

        // Risposta ai feedback
        if (isset($update['message']['reply_to_message'])) {
            $replyMessage = $update['message']['reply_to_message'];
            $replyText = $replyMessage['text'] ?? '';
        }


        // Remove the admin_input| part for cleaner processing
        if (isset($us['temp']) and str_starts_with($us['temp'], 'admin_input|')) {
            $us['temp'] = str_replace('admin_input|', '', $us['temp']);
        }
        $temp = $us['temp'];

    }
}
