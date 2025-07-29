<?php
require_once 'config.php';

// --- "Затычка" на блокировку. Вместо Redis — простейший файловый лок на время работы:
$lock_file = __DIR__ . '/alpha.lock';
if (file_exists($lock_file) && (time() - filemtime($lock_file)) < 5) {
    http_response_code(429);
    die("Alpha уже выполняется, попробуйте позже.");
}
file_put_contents($lock_file, "lock");

// Подключение к БД
$db = get_pg_connection();

// Пример генерации данных для заказа (выберите свои столбцы)
$category_id = rand(1, 3); // Например, 3 категории
$product_id = rand(1, 10);
$quantity = rand(1, 5);
$price = rand(100, 1000);
$now = date('Y-m-d H:i:s');

// Пример вставки заказа
$sql = "INSERT INTO orders (product_id, quantity, buyer_info, purchase_time)
        VALUES ($1, $2, $3, $4)";
$res = pg_query_params($db, $sql, [$product_id, $quantity, $buyer_info, $now]);
if (!$res) {
    @unlink($lock_file);
    http_response_code(500);
    die("Ошибка вставки: " . pg_last_error($db));
}

// Имитация длительной обработки
sleep(1);

@unlink($lock_file);

echo "Заказ успешно добавлен!";
?>

