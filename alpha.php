<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

$redisUrl = getenv('REDIS_URL');
if (!$redisUrl) {
    http_response_code(500);
    die("REDIS_URL не задан в окружении");
}

try {
    $client = new Predis\Client($redisUrl);
    $lockKey = 'alpha_lock';
    $lockValue = uniqid('', true);

    // Пытаемся заблокировать выполнение (атомарно, блокировка 5 сек)
    $lockAcquired = $client->set($lockKey, $lockValue, 'NX', 'EX', 5);

    if (!$lockAcquired) {
        http_response_code(429);
        die("Alpha уже выполняется, попробуйте позже.");
    }

    // Подключаем PostgreSQL
    $db = get_pg_connection();

    // Генерация данных
    $product_id = rand(1, 10);
    $quantity = rand(1, 5);
    $buyer_info = 'Test buyer';
    $now = date('Y-m-d H:i:s');

    // Вставляем заказ
    $sql = "INSERT INTO orders (product_id, quantity, buyer_info, purchase_time)
            VALUES ($1, $2, $3, $4)";
    $res = pg_query_params($db, $sql, [$product_id, $quantity, $buyer_info, $now]);

    if (!$res) {
        $errorMsg = "Ошибка вставки: " . pg_last_error($db);
        error_log($errorMsg);
        throw new Exception($errorMsg);
    }

    pg_close($db); // Закрываем соединение с БД

    // Имитируем длительную обработку (можно убрать или отрегулировать)
    sleep(1);

    echo "Заказ успешно добавлен!";

} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
} finally {
    // Снимаем блокировку, только если владелец совпадает
    $script = '
        if redis.call("GET", KEYS[1]) == ARGV[1] then
            return redis.call("DEL", KEYS[1])
        else
            return 0
        end
    ';
    if (isset($client, $lockKey, $lockValue)) {
        try {
            $client->eval($script, 1, $lockKey, $lockValue);
        } catch (Exception $redisEx) {
            error_log("Ошибка снятия блокировки Redis: " . $redisEx->getMessage());
            // Не выбрасываем исключение в finally для корректного завершения
        }
    }
}
