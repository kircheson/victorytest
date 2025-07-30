<?php
require_once 'config.php';

// Подключаем Redis
$redis = new Redis();
$redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', getenv('REDIS_PORT') ?: 6379);

// Уникальный ключ блокировки и значение
$lockKey = 'alpha_lock';
$lockValue = uniqid('', true);

// Пытаемся получить lock (setnx аналог)
$lockAcquired = $redis->set($lockKey, $lockValue, ['nx', 'ex' => 5]); // expire 5 сек

if (!$lockAcquired) {
    http_response_code(429);
    die("Alpha уже выполняется, попробуйте позже.");
}

try {
    // Подключение к БД
    $db = get_pg_connection();

    // Генерация данных
    $category_id = rand(1, 3);
    $product_id = rand(1, 10);
    $quantity = rand(1, 5);
    $buyer_info = 'Test buyer';
    $price = rand(100, 1000);
    $now = date('Y-m-d H:i:s');

    // Вставка в orders
    $sql = "INSERT INTO orders (product_id, quantity, buyer_info, purchase_time)
            VALUES ($1, $2, $3, $4)";
    $res = pg_query_params($db, $sql, [$product_id, $quantity, $buyer_info, $now]);

    if (!$res) {
        throw new Exception("Ошибка вставки: " . pg_last_error($db));
    }

    // Имитируем длительную работу
    sleep(1);

    echo "Заказ успешно добавлен!";

} catch (Exception $e) {
    http_response_code(500);
    echo $e->getMessage();
} finally {
    // Снимаем блокировку аккуратно, только если значение совпадает
    $luaScript = <<<LUA
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;
    $redis->eval($luaScript, [$lockKey, $lockValue], 1);
}
?>
