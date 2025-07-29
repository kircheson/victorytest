<?php
function get_pg_connection() {
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT');
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $password = getenv('DB_PASS');
    $sslmode = 'require';
    $sslrootcert = __DIR__ . '/ca.pem';

    $connStr = "host=$host port=$port dbname=$dbname user=$user password=$password sslmode=$sslmode sslrootcert=$sslrootcert";
    if (file_exists($sslrootcert)) {
        $connStr .= " sslrootcert=$sslrootcert";
    }

    $db = pg_connect($connStr);
    if (!$db) {
        http_response_code(500);
        die("Ошибка подключения к БД: " . pg_last_error());
    }
    return $db;
}

