<?php
$n = isset($_GET['n']) ? intval($_GET['n']) : 10;
$n = max(1, min($n, 100));

$responses = [];
$url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/alpha.php";

for ($i = 0; $i < $n; $i++) {
    $resp = file_get_contents($url);
    $responses[] = $resp;
}
echo json_encode($responses, JSON_UNESCAPED_UNICODE);
?>

