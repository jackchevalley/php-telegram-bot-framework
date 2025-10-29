<?php
const MAINSTART = true;
echo "Starting process". PHP_EOL;


// Get the configurations for the bot
require_once 'public/env_loader.php';
load_env();


// Bot token
if (!isset($_ENV['TELEGRAM_BOT_TOKEN'])) {
    die("TELEGRAM_BOT_TOKEN not found in the file .env!". PHP_EOL);
}
if (!isset($_ENV['WEBHOOK_URL'])) {
    die("WEBHOOK_URL not found in the file .env!". PHP_EOL);
}


// API token and domain pointing to index.php
$api = "bot" . $_ENV['TELEGRAM_BOT_TOKEN'];
$webhook = $_ENV['WEBHOOK_URL'] ."?api=" . $api;



// EXECUTE OPERATIONS

echo "Deleting webhook... ";
file_get_contents("https://api.telegram.org/$api/deleteWebhook");
echo "OK". PHP_EOL;

echo "Deleting updates... ";
file_get_contents("https://api.telegram.org/$api/getUpdates?offset=-1");
echo "OK". PHP_EOL;

echo "Setting webhook... ";
file_get_contents("https://api.telegram.org/$api/setWebhook?url=$webhook&max_connections=100");
echo "OK". PHP_EOL;
