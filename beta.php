<?php
$n = isset($_GET['n']) ? intval($_GET['n']) : 10;
$n = max(1, min($n, 100)); // ограничение для безопасности

$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $baseDir . "/alpha.php";

$multiHandle = curl_multi_init();
$curlHandles = [];

for ($i = 0; $i < $n; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = $ch;
}

$running = null;
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

$responses = [];
foreach ($curlHandles as $ch) {
    $response = curl_multi_getcontent($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $responses[] = [
        'http_code' => $httpCode,
        'body' => $response,
    ];

    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($responses, JSON_UNESCAPED_UNICODE);
