<?php
if(!defined('MAINSTART')) { die("<b>The request source has not been recognized. Make sure to execute from the provided entry point</b>"); }
require_once __DIR__ . '/../public/libs/vendor/autoload.php';

use Dotenv\Dotenv;
$env_loaded = false;

/**
 * Load environment variables from .env file
 * Ensures environment is loaded only once
 */
function load_env(): void {
    global $env_loaded;
    if ($env_loaded) return;

    $file_directory = __DIR__ . '/../data/';
    if (!file_exists($file_directory)) {
        die("Environment file .env not found at $file_directory");
    }

    $file_directory = realpath($file_directory);
    $dotenv = Dotenv::createImmutable($file_directory);
    $dotenv->load();

    $env_loaded = true;
}


load_env();