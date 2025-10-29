<?php
# run every minute
const BASE_ROOT = __DIR__ . '/../../../..';
const MAINSTART = true;

require_once BASE_ROOT . '/public/access.php';
require_once BASE_ROOT . '/public/libs/vendor/autoload.php';

require_once BASE_ROOT . '/public/database.php';
require_once BASE_ROOT . '/public/functions.php';

require_once BASE_ROOT . '/other/private/cron/resources/configs.php';
require_once BASE_ROOT . '/other/private/cron/resources/functions.php';

// Create the request client
use GuzzleHttp\Client;
$client = new Client([
    'base_uri' => 'https://api.telegram.org/'. $API .'/',
    'timeout'  => 0
]);





############################################################################
# | Actual Script
# Insert what to do


logger("[SUCCESS] Done", break_line: True);
logger(break_line: True);

exit();