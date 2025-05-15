<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid name and email.']);
        exit;
    }

    // Here you would typically send an email or save to a database
    // For demo, we'll simulate success
    echo json_encode(['success' => true, 'message' => 'Thank you for your message! We will get back to you within 24 hours.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>