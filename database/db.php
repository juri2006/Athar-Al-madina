<?php

$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');
$port = (int)(getenv('MYSQLPORT') ?: 3306);

/*
 * إذا لم توجد بيانات قاعدة البيانات
 * لا توقف الموقع.
 */
if (!$host || !$user) {
    $conn = null;
    return;
}

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
    $conn = null;
    return;
}

mysqli_set_charset($conn, 'utf8mb4');

if ($conn) {
    @$conn->query("INSERT INTO site_visits () VALUES ()");
}
?>
