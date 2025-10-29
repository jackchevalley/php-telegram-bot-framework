<?php
date_default_timezone_set('Europe/Rome');
const MAINSTART = true;     # This constant is used to verify that the included files are accessed in the correct way



####################################################################
# | Close connection with Telegram
if(isset($_SERVER['REMOTE_ADDR'])) {
	$ip = $_SERVER['REMOTE_ADDR'];
	require_once 'public/access.php';	// Verify that the web request comes from Telegram itself

	set_time_limit(300);
	ignore_user_abort(true);

	$out = json_encode([
		'ok' => True,
		'text' => "Developed by <a href='https://t.me/JacksWork'>@JackChevalley</a>"
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



####################################################################
# | Get the update and bot API
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Get bot API from the URL
$api = $_GET["api"] ?? die('NO_API_PROVIDED');



####################################################################
# | Initialize the client for HTTP requests
require_once 'public/libs/vendor/autoload.php';
use GuzzleHttp\Client;

$client = new Client([
	'base_uri' => 'https://api.telegram.org/'. $api .'/',
	'timeout'  => 0
]);

// unset for security, we don't need it anymore since is already in base_uri, better not having it around
unset($api);



####################################################################
# | Get basic update variables and Functions
require_once 'public/functions.php';

// Security checks, probably not necessary but better be safe than sorry
if (!(isset($userID) and is_numeric($userID) and $userID > 0)) exit();
if (!(isset($chatID) and is_numeric($chatID))) exit();



#####################################################################
# | Extra: redis connection
// Enable this if you need redis, remember to uncomment also the related lines at the end of the file
//require_once 'public/redis_functions.php';



####################################################################
# | Load environment variables and database functions
// we do it at this point since we are sure that the request is from telegram and with valid parameters
require_once 'public/env_loader.php';

// Load database connection functions
require_once 'public/database.php';



####################################################################
# | Command Processing
try {
    require_once 'comandi.php';
}
finally {
    closeDbConnection();
//    closeRedisConnection();
}
