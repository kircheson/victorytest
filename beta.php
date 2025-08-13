<?php

$baseDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$url = "https://" . $_SERVER['HTTP_HOST'] . $baseDir . "/alpha.php";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10); // ограничение ожидания
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'http_code' => $httpCode,
    'body' => $response
], JSON_UNESCAPED_UNICODE);
