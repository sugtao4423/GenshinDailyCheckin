<?php

declare(strict_types=1);

if (!isset($argv[1])) {
    echo "Please set HOYOLAB_COOKIE\n";
    exit(1);
}
define('HOYOLAB_COOKIE', $argv[1]);

define('CHECKIN_API_URL', 'https://hk4e-api-os.mihoyo.com/event/sol');
$query = http_build_query([
    'lang' => 'en-us',
    'act_id' => 'e202102251931481',
]);
define('URL_MONTH_CHECKIN_ITEMS', CHECKIN_API_URL . "/home?${query}");
define('URL_CURRENT_SIGNIN_INFO', CHECKIN_API_URL . "/info?${query}");
define('URL_SIGNIN', CHECKIN_API_URL . "/sign?${query}");

define('CHECKIN_HEADER', [
    'Accept: application/json, text/plain, */*',
    'Accept-Language: en-US;q=0.8,en;q=0.7',
    'Connection: keep-alive',
    'Origin: https://webstatic-sea.mihoyo.com',
    "Referer: https://webstatic-sea.mihoyo.com/ys/event/signin-sea/index.html?${query}",
    'Cache-Control: max-age=0',
]);
define('HTTP_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36');


$info = getSigninInfo();
if ($info['data']) {
    $totalCheckin = $info['data']['total_sign_day'];
    $today = $info['data']['today'];
    $status = $info['data']['is_sign'] ? 'Already' : 'Not yet';
    echo "Total check-in: ${totalCheckin} day\n";
    echo "Today date: ${today}\n";
    echo "Status: ${status}\n";

    if ($info['data']['is_sign']) {
        echo "Nothing to do\n";
        exit(0);
    }
} else {
    $message = $info['message'];
    echo "Status: ${message}\n";
    exit(1);
}

$signIn = sendSignIn();
$signInMessage = $signIn['message'];
echo "Check-in status: ${signInMessage}\n";

$items = getCheckinItems();
if ($items['data'] && isset($items['data']['awards'])) {
    $totalSignDayIndex = $info['data']['total_sign_day'];
    if (isset($items['data']['awards'][$totalSignDayIndex])) {
        $todayItem = $items['data']['awards'][$totalSignDayIndex];
        $itemObtained = $todayItem['cnt'] . ' ' . $todayItem['name'];
        echo "===========================\n";
        echo "Item obtained: ${itemObtained}\n";
        echo "===========================\n";
    }
}
echo "All done.\n";
exit(0);


function httpJsonRequest(string $url, bool $isAddContentType = false, array $postData = []): array
{
    $header = array_values(CHECKIN_HEADER);
    if ($isAddContentType) {
        $header[] = 'Content-Type: application/json;charset=UTF-8';
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, HTTP_AGENT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_COOKIE, HOYOLAB_COOKIE);
    if ($postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    }
    $output = curl_exec($ch);
    curl_close($ch);
    return json_decode($output, true);
}

function getCheckinItems(): array
{
    return httpJsonRequest(URL_MONTH_CHECKIN_ITEMS);
}

function getSigninInfo(): array
{
    return httpJsonRequest(URL_CURRENT_SIGNIN_INFO);
}

function sendSignIn(): array
{
    return httpJsonRequest(URL_SIGNIN, true, [
        'act_id' => 'e202102251931481',
    ]);
}
