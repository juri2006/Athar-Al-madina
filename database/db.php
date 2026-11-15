<?php

$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$database = getenv('DB_NAME') ?: 'city';
$port = (int)(getenv('DB_PORT') ?: 4000);

/*
 * إذا لم توجد بيانات قاعدة البيانات
 * لا توقف الموقع.
 */
if (!$host || !$user) {
    $conn = null;
    return;
}

$conn = mysqli_init();

mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

// mysqli_ssl_set(
//     $conn,
//     null,
//     null,
//     '/etc/ssl/certs/ca-certificates.crt',
//     null,
//     null
// );

if (!mysqli_real_connect(
    $conn,
    $host,
    $user,
    $password,
    $database,
    $port,
    null,
    0
)) {
    $conn = null;
    return;
}

mysqli_set_charset($conn, 'utf8mb4');

if ($conn) {
    @$conn->query("INSERT INTO site_visits () VALUES ()");
}
?>
