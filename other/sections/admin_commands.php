<?php
if (!defined('MAINSTART')) { die(); }
if (!isset($userID)) die();


// Security check, even if redundant
if (!$is_admin and !in_array($chatID, $ADMIN_CHATS)) {
    die();
}


// Text messages
if (isset($msg)) {

    // Commands
    if (str_starts_with($msg, '/')) {


        // generic replace /admin_ init
        if (str_starts_with($msg, '/admin_')) {
            $msg = str_replace('/admin_', '/', $msg);
        }


        // Commands reserved for ADMINISTRATORS
        if ($is_admin) {

            // Command to display the list of admin commands
            if ($msg == '/admin_commands' or $msg == '/help_admin') {

                $text = [];
                $text[] = "🔧 <b>Administrator Commands</b>";
                $text[] = "";
                $text[] = "To be implemented...";

                $inline_menu = [$hide_button_row];
                sm($chatID, $text, $inline_menu);
                die();
            }


            // more admin commands here...
        }


        // Commands reserved for all members of the GENERIC_ADMIN_CHAT group
        if ($userID == $adm or $chatID == $GENERIC_ADMIN_CHAT_ID) {

            // Server and request status
            if ($msg == '/status') {
                require_once __DIR__ . '/../../public/metrics_functions.php';

                // Collect all metrics
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

                // Build message
                $text = [];
                $text[] = "🤖 <b>System Status</b>";
                $text[] = "";

                // Server Section
                $text[] = "📊 <b>SERVER</b>";
                $cpuEmoji = $cpuUsage < 60 ? '✅' : ($cpuUsage < 90 ? '⚠️' : '🔴');
                $text[] = "• CPU: $cpuEmoji <code>$cpuUsage%</code> ($cpuCores cores)";

                if ($memInfo) {
                    $ramEmoji = $memInfo['percent'] < 55 ? '✅' : ($memInfo['percent'] < 75 ? '⚠️' : '🔴');
                    $text[] = "• RAM: $ramEmoji <code>{$memInfo['used']} MB / {$memInfo['total']} MB ({$memInfo['percent']}%)</code>";
                }

                $diskEmoji = $diskUsage['percent'] < 55 ? '✅' : ($diskUsage['percent'] < 75 ? '⚠️' : '🔴');
                $text[] = "• Disk: $diskEmoji <code>{$diskUsage['used']} / {$diskUsage['total']} ({$diskUsage['percent']}%)</code>";

                $text[] = "• Uptime: <code>$systemUptime</code>";
                $text[] = "• Load Avg: <code>{$loadAvg['1min']} | {$loadAvg['5min']} | {$loadAvg['15min']}</code>";
                $text[] = "";

                // Database Section
                $text[] = "💾 <b>DATABASE</b>";
                $text[] = "• Status: {$dbPerf['status']} <code>{$dbPerf['text']}</code>";
                $text[] = "  Query Test: <code>{$dbPerf['time']} ms</code>";
                $text[] = "";

                // Bot Section
                $text[] = "🔧 <b>BOT INFO</b>";
                $text[] = "• Peak memory: <code>". formatBytes(memory_get_peak_usage()) ."</code>";
                $text[] = "• API Connectivity: {$requestsPerf['status']} <code>{$requestsPerf['text']}</code>";
                $text[] = "  Request Test: <code>{$requestsPerf['time']} ms</code>";

                $inline_menu = [$hide_button_row];
                sm($chatID, $text, $inline_menu);
            }


            // more commands in the admin group here...
        }

    }

    // Input
    else {

        // Reply to feedback
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
