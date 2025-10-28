<?php
const MAINSTART = true;
echo "Inizio processo". PHP_EOL;


// Get the configurations for the bot
require_once 'public/env_loader.php';
load_env();


// Token del bot
if (!isset($_ENV['TELEGRAM_BOT_TOKEN'])) {
    die("TELEGRAM_BOT_TOKEN not found in the file .env!". PHP_EOL);
}
if (!isset($_ENV['WEBHOOK_URL'])) {
    die("WEBHOOK_URL not found in the file .env!". PHP_EOL);
}


// Api token e dominio che punta al file index.php
$api = "bot" . $_ENV['TELEGRAM_BOT_TOKEN'];
$webhook = $_ENV['WEBHOOK_URL'] ."?api=" . $api;



// ESEGUI LE OPERAZIONI

echo "Elimino webhook... ";
file_get_contents("https://api.telegram.org/$api/deleteWebhook");
echo "OK". PHP_EOL;

echo "Elimino updates... ";
file_get_contents("https://api.telegram.org/$api/getUpdates?offset=-1");
echo "OK". PHP_EOL;

echo "Imposto webhook... ";
file_get_contents("https://api.telegram.org/$api/setWebhook?url=$webhook&max_connections=100");
echo "OK". PHP_EOL;
