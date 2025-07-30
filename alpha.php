<?php
require_once 'config.php';

// Подключаем автозагрузку composer's
require_once __DIR__ . '/vendor/autoload.php';

// Получаем URL Redis из переменных окружения (REDIS_URL с TLS)
$redisUrl = getenv('REDIS_URL');
if (!$redisUrl) {
    http_response_code(500);
    die("REDIS_URL не задан в окружении");
}

try {
    $client = new Predis\Client($redisUrl);

    $lockKey = 'alpha_lock';
    $lockValue = uniqid('', true);

    // setnx + expire в одной команде для atomic блокировки
    $lockAcquired = $client->set($lockKey, $lockValue, 'NX', 'EX', 5);

    if (!$lockAcquired) {
        http_response_code(429);
        die("Alpha уже выполняется, попробуйте позже.");
    }

    // Подключаем PostgreSQL
    $db = get_pg_connection();

    // Генерация данных
    $category_id = rand(1, 3);
    $product_id = rand(1, 10);
    $quantity = rand(1, 5);
    $buyer_info = 'Test buyer';
    $price = rand(100, 1000);
    $now = date('Y-m-d H:i:s');

    $sql = "INSERT INTO orders (product_id, quantity, buyer_info, purchase_time)
            VALUES ($1, $2, $3, $4)";
    $res = pg_query_params($db, $sql, [$product_id, $quantity, $buyer_info, $now]);

    if (!$res) {
        throw new Exception("Ошибка вставки: " . pg_last_error($db));
    }

    sleep(1);

    echo "Заказ успешно добавлен!";

} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
} finally {
    // Снимаем блокировку, если владелец блокировки совпадает
    $script = '
        if redis.call("GET", KEYS[1]) == ARGV[1] then
            return redis.call("DEL", KEYS[1])
        else
            return 0
        end
    ';
    if (isset($client, $lockKey, $lockValue)) {
        $client->eval($script, 1, $lockKey, $lockValue);
    }
}
