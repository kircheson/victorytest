<?php
require_once 'config.php';
$db = get_pg_connection();

// 1. Получаем общее количество заказов
$sql_count = "SELECT COUNT(*) as total FROM orders";
$res_count = pg_query($db, $sql_count);
if (!$res_count) {
    http_response_code(500);
    die("Ошибка получения общего количества заказов: " . pg_last_error($db));
}
$row_count = pg_fetch_assoc($res_count);
$total_orders = (int)$row_count['total'];

// 2. Получаем минимальное и максимальное время заказа по всей таблице
$sql_range = "SELECT MIN(purchase_time) AS min_time, MAX(purchase_time) AS max_time FROM orders";
$res_range = pg_query($db, $sql_range);
if (!$res_range) {
    http_response_code(500);
    die("Ошибка получения интервала времени заказов: " . pg_last_error($db));
}
$row_range = pg_fetch_assoc($res_range);
$first_time = $row_range['min_time'];
$last_time = $row_range['max_time'];

// 3. Получаем статистику по категориям для всех заказов
$sql_cats = "SELECT c.name as category_name, SUM(o.quantity) as qty
             FROM orders o
             JOIN products p ON o.product_id = p.id
             JOIN categories c ON p.category_id = c.id
             GROUP BY c.name";
$res_cats = pg_query($db, $sql_cats);
if (!$res_cats) {
    http_response_code(500);
    die("Ошибка получения статистики по категориям: " . pg_last_error($db));
}

$categories = [];
while ($row = pg_fetch_assoc($res_cats)) {
    $categories[$row['category_name']] = (int)$row['qty'];
}

// 4. Вычисляем интервал времени между первым и последним заказом
$interval = '';
if ($first_time && $last_time && $first_time != $last_time) {
    $dt1 = strtotime($first_time);
    $dt2 = strtotime($last_time);
    $interval = ($dt2 - $dt1) . " сек.";
}

$result = [
    'orders_count' => $total_orders,
    'categories' => $categories,
    'time_between' => $interval ?: "Недостаточно данных"
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>

