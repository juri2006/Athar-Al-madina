<?php
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME') ?: 'city';
$port = (int) (getenv('DB_PORT') ?: 4000);

$conn = mysqli_init();

mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, true);
mysqli_ssl_set(
    $conn,
    null,
    null,
    '/etc/ssl/certs/ca-certificates.crt',
    null,
    null
);

if (!mysqli_real_connect(
    $conn,
    $host,
    $user,
    $password,
    $database,
    $port,
    null,
    MYSQLI_CLIENT_SSL
)) {
    die('تعذر الاتصال بقاعدة البيانات.');
}

mysqli_set_charset($conn, 'utf8mb4');
?>
