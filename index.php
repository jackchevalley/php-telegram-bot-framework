<?php
const MAINSTART = true;
date_default_timezone_set('Europe/Rome');


$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Close connection with Telegram
if(isset($_SERVER['REMOTE_ADDR'])) {
	$ip = $_SERVER['REMOTE_ADDR'];
	require_once 'public/access.php';	// Verify that the web request comes from Telegram itself

	set_time_limit(300);
	ignore_user_abort(true);

	$out = json_encode([
		'ok' => True,
		'text' => "Bot developed by <a href='https://t.me/JacksWork'>@JackChevalley</a>"
	]);

	header('Connection: close');
	header("Content-type:application/json");

	echo $out;
	flush();

	// Terminate the Telegram request before processing to speed up the process
	if (function_exists('fastcgi_finish_request')) {
		fastcgi_finish_request();
	}
}


// Create connection with the request library
require_once 'public/libs/vendor/autoload.php';
use GuzzleHttp\Client;


// Create the request client
$api = $_GET["api"] ?? die('NO_API_PROVIDED');
$client = new Client([
	'base_uri' => 'https://api.telegram.org/'. $api .'/',
	'timeout'  => 0
]);
unset($api);    // unset for security, we don't need it anymore since is already in base_uri


// Create functions and variables
require_once 'public/functions.php';
//require_once 'public/redis_functions.php';        // Enable this if you need redis, remember to uncomment also the related lines at the end of the file


// Security checks
if (!(isset($userID) and is_numeric($userID) and $userID > 0)) exit();
if (!(isset($chatID) and is_numeric($chatID))) exit();


// Load environment variables
require_once 'public/env_loader.php';
load_env();

// Create database connection
require_once 'public/database.php';

try {
    require_once 'comandi.php';
} finally {
    closeDbConnection();
//    closeRedisConnection();       // Enable this if you need redis, remember to uncomment also the related lines at the beginning of the file
}
