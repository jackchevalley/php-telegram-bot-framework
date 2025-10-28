<?php
const BASE_ROOT = __DIR__ . '/../../..';
const MAINSTART = true;

require_once BASE_ROOT . '/public/access.php';
require_once BASE_ROOT . '/other/private/cron/resources/configs.php';
require_once BASE_ROOT . '/other/private/cron/resources/functions.php';


const ONE_MINUTE = 60;

$FILES_RUN_TIME = [
    'filename_one.php'              =>  ONE_MINUTE,         // This script runs every minute
    'filename_two.php'              =>  "00:01",            // This script runs at 00:01 every day
    'filename_three.php'            =>  ["12:00", "18:00"], // This script runs at 12:00 and 18:00 every day
];


############################################################################
# | Actual Script
# Run all cron jobs in the folder every minute

$execution_memory = [];
while (MAINSTART) {
    foreach ($FILES_RUN_TIME as $file => $time) {
        if (!isset($execution_memory[$file])) {
            $execution_memory[$file] = 0;
        }

        // Check if the time has passed since last execution
        $paddedFile = str_pad($file, 35, ' ', STR_PAD_RIGHT);
        $lastExecuted = $execution_memory[$file] ?
            date('Y-m-d H:i:s', $execution_memory[$file])
            : 'first run';
        logger(" [DEBUG] Checking cron job: $paddedFile Last executed at [$lastExecuted] -> ", force_echo: True);

        if (
            (   // Execute every X seconds
                is_numeric($time)
                && time() - $execution_memory[$file] >= $time
            )
            or
            (   // Execute at specific time (HH:MM)
                !is_numeric($time)
                && date("H:i") == $time

                // check if not executed in the last 60 seconds to avoid multiple executions in the same minute
                && (time() - $execution_memory[$file] >= 60 || $execution_memory[$file] == 0)
            )
            or
            (   // Execute multiple times specific times (array of HH:MM)
                is_array($time)
                && in_array(date("H:i"), $time)

                // check if not executed in the last 60 seconds to avoid multiple executions in the same minute
                && (time() - $execution_memory[$file] >= 60 || $execution_memory[$file] == 0)
            )
        ) {
            logger("EXECUTING", force_echo: True, break_line: True);

            // Run in executor to avoid wait time, no need for output
            $module_path = BASE_ROOT . '/other/private/cron/modules/'. $file;
            $module_path = realpath($module_path);

            // Execute the command in the background
            $cmd = "php " . $module_path . " > /dev/null 2>&1 &";
            exec($cmd);

            $execution_memory[$file] = time();
        }
        else {

            // Calculate next run time
            if (is_numeric($time)) {
                $nextRunIn = ($execution_memory[$file] + $time) - time();
            }
            else {
                if (is_array($time)) {
                    $nextRuns = [];
                    foreach ($time as $t) {
                        $nextRunTimestamp = strtotime(date('Y-m-d') . ' ' . $t);
                        if ($nextRunTimestamp <= time()) {
                            $nextRunTimestamp = strtotime(date('Y-m-d', strtotime('+1 day')) . ' ' . $t);
                        }
                        $nextRuns[] = $nextRunTimestamp;
                    }
                    $nextRunIn = min($nextRuns) - time();
                }
                else {
                    $nextRunTimestamp = strtotime(date('Y-m-d') . ' ' . $time);
                    if ($nextRunTimestamp <= time()) {
                        $nextRunTimestamp = strtotime(date('Y-m-d', strtotime('+1 day')) . ' ' . $time);
                    }
                    $nextRunIn = $nextRunTimestamp - time();
                }
            }

            logger("SKIPPING (next run in $nextRunIn seconds)", force_echo: True, break_line: True);
        }
    }

    logger(break_line: True);
    sleep(5);
}
