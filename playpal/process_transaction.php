<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = filter_var($_POST['product_id'], FILTER_SANITIZE_NUMBER_INT);
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'] ?? '', FILTER_SANITIZE_STRING);
    $type = filter_var($_POST['transaction_type'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || empty($product_id) || empty($type)) {
        die('All required fields must be filled.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format.');
    }

    try {
        $stmt = $conn->prepare("INSERT INTO transactions (product_id, first_name, last_name, email, phone, type) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $product_id, $first_name, $last_name, $email, $phone, $type);
        
        if ($stmt->execute()) {
            header("Location: index.php?section=products&transaction=success");
            exit();
        } else {
            throw new Exception('Failed to process transaction.');
        }
    } catch (Exception $e) {
        die('Error: ' . $e->getMessage());
    }
} else {
    die('Invalid request method.');
}

    // Insert transaction
    $stmt = $conn->prepare("INSERT INTO transactions (product_id, first_name, last_name, email, phone, type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $product_id, $first_name, $last_name, $email, $phone, $type);
    
    if ($stmt->execute()) {
        // Get product details for email
        $product_stmt = $conn->prepare("SELECT p.*, g.name AS game_name FROM products p JOIN games g ON p.game_id = g.id WHERE p.id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
        $product_stmt->close();
        
        // Send email to admin
        $to = "admin@playpal.com";
        $subject = "New " . ucfirst($type) . " Order - " . $product['name'];
        $message = "A new order has been placed:\n\n";
        $message .= "Product: " . $product['name'] . " (" . $product['game_name'] . ")\n";
        $message .= "Type: " . ucfirst($type) . "\n";
        $message .= "Customer: " . $first_name . " " . $last_name . "\n";
        $message .= "Email: " . $email . "\n";
        $message .= "Phone: " . ($phone ? $phone : "Not provided") . "\n\n";
        $message .= "Please process this order as soon as possible.";
        
        $headers = "From: no-reply@playpal.com\r\n";
        $headers .= "Reply-To: no-reply@playpal.com\r\n";
        
        mail($to, $subject, $message, $headers);
        
        // Redirect to thank you page
        header("Location: thank_you.php?type=" . urlencode($type));
        exit();
    } else {
        die('Failed to process transaction. Please try again.');
    }
    
    $stmt->close();
}

header("Location: index.php");
exit();
?>