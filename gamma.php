<?php
require_once 'config.php';
$db = get_pg_connection();

// 1. Получить последние 100 заказов по времени:
$sql = "SELECT o.*, c.name as category_name
        FROM orders o
        JOIN products p ON o.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        ORDER BY o.purchase_time DESC
        LIMIT 100";
$res = pg_query($db, $sql);

if (!$res) {
    http_response_code(500);
    die("Ошибка выборки: " . pg_last_error($db));
}

$orders = [];
$categories = [];
$first_time = null;
$last_time = null;
while ($row = pg_fetch_assoc($res)) {
    $orders[] = $row;
    $cat = $row['category_name'];
    $categories[$cat] = ($categories[$cat] ?? 0) + $row['quantity'];
    if (!$last_time) $last_time = $row['purchase_time'];
    $first_time = $row['purchase_time'];
}
$interval = '';
if ($first_time && $last_time && $first_time != $last_time) {
    $dt1 = strtotime($last_time);
    $dt2 = strtotime($first_time);
    $interval = ($dt2 - $dt1) . " сек.";
}

$result = [
    'orders_count' => count($orders),
    'categories' => $categories,
    'time_between' => $interval ?: "Недостаточно данных"
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>

