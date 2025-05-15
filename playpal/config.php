<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Change if needed
define('DB_PASS', '');     // Change if needed
define('DB_NAME', 'playpal_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure Uploads directory exists
$upload_dir = 'Uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Set timezone if needed
date_default_timezone_set('UTC');
?>