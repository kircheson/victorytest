<?php
require_once __DIR__ . '/vendor/autoload.php';

$redisUrl = getenv('REDIS_URL');

if (!$redisUrl) {
    die("REDIS_URL не задан в окружении\n");
}

try {
    $client = new Predis\Client($redisUrl);
    $client->set('test_key', 'test_value', 'EX', 10);  // записать ключ на 10 секунд
    $value = $client->get('test_key');

    echo "Redis успешно подключился. Значение test_key = " . $value . "\n";
} catch (Exception $e) {
    echo "Ошибка подключения к Redis: " . $e->getMessage() . "\n";
}

