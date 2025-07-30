<?php
$n = isset($_GET['n']) ? intval($_GET['n']) : 10;
$n = max(1, min($n, 100)); // ограничиваем до 100 для безопасности

$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/alpha.php";

// Инициализация cURL Multi
$multiHandle = curl_multi_init();
$curlHandles = [];

// Создаем параллельные cURL запросы
for ($i = 0; $i < $n; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_multi_add_handle($multiHandle, $ch);
    $curlHandles[] = $ch;
}

$running = null;
// Запускаем многопоточный запуск
do {
    curl_multi_exec($multiHandle, $running);
    curl_multi_select($multiHandle);
} while ($running > 0);

// Собираем ответы
$responses = [];
foreach ($curlHandles as $ch) {
    $response = curl_multi_getcontent($ch);
    $responses[] = $response;
    curl_multi_remove_handle($multiHandle, $ch);
    curl_close($ch);
}

curl_multi_close($multiHandle);

// Возвращаем JSON массив ответов
header('Content-Type: application/json; charset=utf-8');
echo json_encode($responses, JSON_UNESCAPED_UNICODE);
