<?php
// Database connection
//
// SECURITY NOTE:
// - Do NOT hardcode production credentials in git.
// - For production, create a local file `php/db_connect.local.php` (ignored by git)
//   that defines $host, $user, $pass, $db.
// - Or set environment variables: DB_HOST, DB_USER, DB_PASS, DB_NAME.

$localConfig = __DIR__ . '/db_connect.local.php';
if (file_exists($localConfig)) {
    require $localConfig;
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db = getenv('DB_NAME') ?: 'ppms_database';
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>