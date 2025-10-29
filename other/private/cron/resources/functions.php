<?php
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }


/**
 * Log debug and information messages
 * Centralized logging function for the matching system
 *
 * @param string $message The message to log
 * @param bool $force_echo Whether to force output even if not in test mode
 * @param bool $break_line Whether to append a newline after the message
 * @return void
 */
function logger(string $message = '', bool $force_echo = true, bool $break_line = false): void {
    if ($force_echo) {
        echo $message;
        if ($break_line) {
            echo PHP_EOL;
        }
    }
}

