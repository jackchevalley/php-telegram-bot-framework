<?php
if(!defined('MAINSTART')) { die("<b>The sender's IP has not been recognised.</br>All the actions were stopped</b>"); }

if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
    $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
}

$client  = @$_SERVER['HTTP_CLIENT_IP'];
$forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];

if(isset($_SERVER['REMOTE_ADDR']))
    $remote  = $_SERVER['REMOTE_ADDR'];
else
    return;

if(filter_var($client, FILTER_VALIDATE_IP)) { $ip = $client; }
elseif(filter_var($forward, FILTER_VALIDATE_IP)) { $ip = $forward; }
else { $ip = $remote; }

// Telegram IP ranges
$telegram_ipv4_ranges = [
    '91.108.56.0/22',
    '91.108.4.0/22',
    '91.108.8.0/22',
    '91.108.16.0/22',
    '91.108.12.0/22',
    '149.154.160.0/20',
    '91.105.192.0/23',
    '91.108.20.0/22',
    '185.76.151.0/24'
];

$telegram_ipv6_ranges = [
    '2001:b28:f23d::/48',
    '2001:b28:f23f::/48',
    '2001:67c:4e8::/48',
    '2001:b28:f23c::/48',
    '2a0a:f280::/32'
];

function ip_in_range($ip, $range): bool
{
    list($range, $netmask) = explode('/', $range);
    $range_decimal = ip2long($range);
    $ip_decimal = ip2long($ip);
    $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
    $netmask_decimal = ~ $wildcard_decimal;
    return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
}

function ipv6_in_range($ip, $range): bool
{
    list($range_addr, $netmask) = explode('/', $range);
    $ip = inet_pton($ip);
    $range = inet_pton($range_addr);
    $netmask = intval($netmask);

    $bytes = $netmask / 8;
    $bits = $netmask % 8;

    for ($i = 0; $i < $bytes; $i++) {
        if ($ip[$i] != $range[$i]) return false;
    }

    if ($bits > 0) {
        $mask = chr(255 << (8 - $bits));
        return ($ip[$bytes] & $mask) == ($range[$bytes] & $mask);
    }

    return true;
}

$is_telegram_ip = false;

if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    foreach ($telegram_ipv4_ranges as $range) {
        if (ip_in_range($ip, $range)) {
            $is_telegram_ip = true;
            break;
        }
    }
}
elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    foreach ($telegram_ipv6_ranges as $range) {
        if (ipv6_in_range($ip, $range)) {
            $is_telegram_ip = true;
            break;
        }
    }
}

if(isset($ip) and !$is_telegram_ip) {
    die("<b>The sender's IP has not been recognised.</br>All the actions were stopped</b>");
}
