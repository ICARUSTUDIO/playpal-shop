<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if all required fields are present
if (!isset($_POST['current_username'], $_POST['current_password'], $_POST['new_username'], $_POST['new_password'])) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$currentUsername = trim($_POST['current_username']);
$currentPassword = trim($_POST['current_password']);
$newUsername = trim($_POST['new_username']);
$newPassword = trim($_POST['new_password']);

// Validate current credentials
$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
$stmt->bind_param("s", $currentUsername);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid current username']);
    exit;
}

$user = $result->fetch_assoc();

// Compare plain text passwords (since your admin.php uses plain text comparison)
if ($currentPassword !== $user['password']) {
    echo json_encode(['success' => false, 'message' => 'Invalid current password']);
    exit;
}

// Update credentials
$stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
$stmt->bind_param("ssi", $newUsername, $newPassword, $user['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Credentials updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update credentials']);
}

$stmt->close();
$conn->close();
?>