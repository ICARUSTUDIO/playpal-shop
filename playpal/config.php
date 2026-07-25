<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Read a value from the deployment environment without committing credentials.
 */
function playpal_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

define('DB_HOST', playpal_env('PLAYPAL_DB_HOST', 'localhost'));
define('DB_USER', playpal_env('PLAYPAL_DB_USER', 'root'));
define('DB_PASS', playpal_env('PLAYPAL_DB_PASS'));
define('DB_NAME', playpal_env('PLAYPAL_DB_NAME', 'playpal_db'));

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    error_log('Playpal database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('The application could not connect to its database.');
}

$conn->set_charset('utf8mb4');

$upload_dir = __DIR__ . '/Uploads';
if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
    error_log('Playpal could not create its upload directory.');
}

date_default_timezone_set(playpal_env('PLAYPAL_TIMEZONE', 'UTC'));
