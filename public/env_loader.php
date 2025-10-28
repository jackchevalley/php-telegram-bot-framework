<?php
if (!defined('MAINSTART')) { die(); }
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

    $file_path = __DIR__ . '/../data/.env';
    if (!file_exists($file_path)) {
        die("Environment file .env not found at $file_path");
    }

    $dotenv = Dotenv::createImmutable($file_path);
    $dotenv->load();

    $env_loaded = true;
}
