<?php
if (!defined('MAINSTART')) { die(); }
date_default_timezone_set('Europe/Rome');

ini_set("log_errors", 1);
ini_set("error_log", "/var/log/nginx/anonime.error.log");
error_reporting(E_ALL & ~E_DEPRECATED);




#################################################################
# | CONFIGURATIONS
require_once __DIR__ . '/../../../../public/configs.php';
require_once __DIR__ .'/../../../../public/env_loader.php';

load_env();
if (!isset($_ENV['TELEGRAM_BOT_TOKEN'])) {
    die('API key not found in environment variables when loading payments_callback.php');
}

$API = $_ENV['TELEGRAM_BOT_TOKEN'];
$API = "bot". $API;