<?php
require_once 'config.php';

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log file setup
$log_file = 'admin_errors.log';
ini_set('log_errors', 1);
ini_set('error_log', $log_file);

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $login_error = '';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Compare plain text passwords
            if ($password === $user['password']) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_username'] = $user['username'];
                header("Location: admin.php");
                exit;
            } else {
                $login_error = 'Incorrect password';
            }
        } else {
            $login_error = 'Admin account not found';
        }
        $stmt->close();
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login - Playpal</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    </head>
    <body class="bg-gray-100 font-sans flex items-center justify-center min-h-screen">
        <div class="container mx-auto px-4 py-8 max-w-md w-full">
            <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">Admin Login</h1>
            <?php if ($login_error): ?>
                <p class="text-red-500 text-center mb-4"><?php echo htmlspecialchars($login_error); ?></p>
            <?php endif; ?>
            <form method="POST" class="bg-white p-6 rounded-lg shadow">
                <div class="mb-4">
                    <label class="block text-gray-700 font-semibold mb-2" for="username">Username</label>
                    <input type="text" id="username" name="username" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2" for="password">Password</label>
                    <input type="password" id="password" name="password" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <button type="submit" name="login" class="w-full bg-orange-500 text-white font-semibold py-3 rounded-lg hover:bg-orange-600 focus:ring-2 focus:ring-orange-500 focus:outline-none text-lg">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Log user visit
if (isset($_SESSION['admin_id'])) {
    $user_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("INSERT INTO user_visits (user_id) VALUES (?)");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Get number of unique visitors
$unique_visitors = 0;
$result = $conn->query("SELECT COUNT(DISTINCT user_id) AS unique_count FROM user_visits");
if ($result && $result->num_rows > 0) {
    $unique_visitors = $result->fetch_assoc()['unique_count'];
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Clear edit modes if requested
if (isset($_GET['clear_edit'])) {
    unset($_GET['edit']);
    $edit_product = null;
    $edit_images = [];
}
if (isset($_GET['clear_giveaway_edit'])) {
    unset($_GET['edit_giveaway']);
    $edit_giveaway = null;
}

// Handle product addition
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $sale_discount = filter_var($_POST['sale_discount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $game_id = filter_var($_POST['game_id'], FILTER_SANITIZE_NUMBER_INT);
    $is_rentable = isset($_POST['is_rentable']) ? 1 : 0;
    $rent_price = $is_rentable ? filter_var($_POST['rent_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;

    // Validate inputs
    if (empty($name) || empty($description) || empty($price) || empty($game_id)) {
        $errors[] = 'All fields are required.';
    }
    if ($is_rentable && (empty($rent_price) || $rent_price <= 0)) {
        $errors[] = 'Rent price is required and must be greater than 0.';
    }

    // Handle multiple image uploads
    $image_names = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $files = $_FILES['images'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $files['type'][$i];
                $file_size = $files['size'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $image_name = uniqid() . '.' . $file_ext;
                $upload_path = 'Uploads/' . $image_name;

                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = 'Invalid file type for image ' . ($i + 1) . '. Only JPG, PNG, and GIF are allowed.';
                } elseif ($file_size > $max_size) {
                    $errors[] = 'File size for image ' . ($i + 1) . ' exceeds 5MB limit.';
                } elseif (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Failed to upload image ' . ($i + 1) . '.';
                } else {
                    $image_names[] = $image_name;
                }
            }
        }
    } else {
        $errors[] = 'Please upload at least one image.';
    }

    if (empty($errors)) {
        // Insert product
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, sale_discount, game_id, is_rentable, rent_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssddiid", $name, $description, $price, $sale_discount, $game_id, $is_rentable, $rent_price);
        if ($stmt->execute()) {
            $product_id = $conn->insert_id;
            // Insert images
            foreach ($image_names as $image_name) {
                $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, image) VALUES (?, ?)");
                $stmt_img->bind_param("is", $product_id, $image_name);
                $stmt_img->execute();
                $stmt_img->close();
            }
            $success = 'Product added successfully.';
        } else {
            $errors[] = 'Failed to add product.';
        }
        $stmt->close();
    }
}

// Handle product update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $price = filter_var($_POST['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $sale_discount = filter_var($_POST['sale_discount'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $game_id = filter_var($_POST['game_id'], FILTER_SANITIZE_NUMBER_INT);
    $is_rentable = isset($_POST['is_rentable']) ? 1 : 0;
    $rent_price = $is_rentable ? filter_var($_POST['rent_price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null;

    // Validate inputs
    if (empty($name) || empty($description) || empty($price) || empty($game_id)) {
        $errors[] = 'All fields are required.';
    }
    if ($is_rentable && (empty($rent_price) || $rent_price <= 0)) {
        $errors[] = 'Rent price is required and must be greater than 0.';
    }

    // Handle multiple image uploads
    $image_names = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $files = $_FILES['images'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file_type = $files['type'][$i];
                $file_size = $files['size'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $image_name = uniqid() . '.' . $file_ext;
                $upload_path = 'Uploads/' . $image_name;

                if (!in_array($file_type, $allowed_types)) {
                    $errors[] = 'Invalid file type for image ' . ($i + 1) . '. Only JPG, PNG, and GIF are allowed.';
                } elseif ($file_size > $max_size) {
                    $errors[] = 'File size for image ' . ($i + 1) . ' exceeds 5MB limit.';
                } elseif (!move_uploaded_file($file_tmp, $upload_path)) {
                    $errors[] = 'Failed to upload image ' . ($i + 1) . '.';
                } else {
                    $image_names[] = $image_name;
                }
            }
        }
    }

    if (empty($errors)) {
        // Update product
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, sale_discount = ?, game_id = ?, is_rentable = ?, rent_price = ? WHERE id = ?");
        $stmt->bind_param("ssddiidi", $name, $description, $price, $sale_discount, $game_id, $is_rentable, $rent_price, $id);
        if ($stmt->execute()) {
            // Update images if new ones were uploaded
            if (!empty($image_names)) {
                // Delete existing images
                $stmt_img = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
                $stmt_img->bind_param("i", $id);
                $stmt_img->execute();
                $result = $stmt_img->get_result();
                while ($row = $result->fetch_assoc()) {
                    if (file_exists('Uploads/' . $row['image'])) {
                        unlink('Uploads/' . $row['image']);
                    }
                }
                $stmt_img->close();

                $stmt_del = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
                $stmt_del->bind_param("i", $id);
                $stmt_del->execute();
                $stmt_del->close();

                // Insert new images
                foreach ($image_names as $image_name) {
                    $stmt_img = $conn->prepare("INSERT INTO product_images (product_id, image) VALUES (?, ?)");
                    $stmt_img->bind_param("is", $id, $image_name);
                    $stmt_img->execute();
                    $stmt_img->close();
                }
            }
            $success = 'Product updated successfully.';
        } else {
            $errors[] = 'Failed to update product.';
        }
        $stmt->close();
    }
}

// Handle product deletion
if (isset($_GET['delete'])) {
    $id = filter_var($_GET['delete'], FILTER_SANITIZE_NUMBER_INT);
    // Delete associated images from filesystem
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (file_exists('Uploads/' . $row['image'])) {
            unlink('Uploads/' . $row['image']);
        }
    }
    $stmt->close();

    // Delete product (images are automatically deleted due to ON DELETE CASCADE)
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = 'Product deleted successfully.';
    } else {
        $errors[] = 'Failed to delete product.';
    }
    $stmt->close();
}

// Handle giveaway addition
$giveaway_errors = [];
$giveaway_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_giveaway'])) {
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $game_id = filter_var($_POST['game_id'], FILTER_SANITIZE_NUMBER_INT);
    $start_date = filter_var($_POST['start_date'], FILTER_SANITIZE_STRING);
    $end_date = filter_var($_POST['end_date'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($title) || empty($description) || empty($game_id) || empty($start_date) || empty($end_date) || empty($status)) {
        $giveaway_errors[] = 'All fields are required.';
    }

    // Validate dates
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
        $giveaway_errors[] = 'Invalid date format.';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $giveaway_errors[] = 'End date must be after start date.';
    }

    // Handle thumbnail upload
    $thumbnail_name = '';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_type = $_FILES['thumbnail']['type'];
        $file_size = $_FILES['thumbnail']['size'];
        $file_tmp = $_FILES['thumbnail']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $thumbnail_name = uniqid() . '.' . $file_ext;
        $upload_path = 'Uploads/' . $thumbnail_name;

        if (!in_array($file_type, $allowed_types)) {
            $giveaway_errors[] = 'Invalid file type for thumbnail. Only JPG, PNG, and GIF are allowed.';
        } elseif ($file_size > $max_size) {
            $giveaway_errors[] = 'Thumbnail size exceeds 5MB limit.';
        } elseif (!move_uploaded_file($file_tmp, $upload_path)) {
            $giveaway_errors[] = 'Failed to upload thumbnail.';
        }
    } else {
        $giveaway_errors[] = 'Please upload a thumbnail image.';
    }

    if (empty($giveaway_errors)) {
        // Insert giveaway
        $stmt = $conn->prepare("INSERT INTO giveaways (title, description, game_id, thumbnail, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", $title, $description, $game_id, $thumbnail_name, $start_date, $end_date, $status);
        if ($stmt->execute()) {
            $giveaway_id = $conn->insert_id;
            
            // Save prize details
            if (!empty($_POST['prize_details'])) {
                $prizes = explode("\n", $_POST['prize_details']);
                foreach ($prizes as $prize) {
                    $prize = trim($prize);
                    if (!empty($prize)) {
                        $stmt_prize = $conn->prepare("INSERT INTO giveaway_prizes (giveaway_id, description) VALUES (?, ?)");
                        $stmt_prize->bind_param("is", $giveaway_id, $prize);
                        $stmt_prize->execute();
                        $stmt_prize->close();
                    }
                }
            }
            
            $giveaway_success = 'Giveaway added successfully.';
        } else {
            $giveaway_errors[] = 'Failed to add giveaway.';
        }
        $stmt->close();
    }
}

// Handle giveaway update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_giveaway'])) {
    $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $description = filter_var($_POST['description'], FILTER_SANITIZE_STRING);
    $game_id = filter_var($_POST['game_id'], FILTER_SANITIZE_NUMBER_INT);
    $start_date = filter_var($_POST['start_date'], FILTER_SANITIZE_STRING);
    $end_date = filter_var($_POST['end_date'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($title) || empty($description) || empty($game_id) || empty($start_date) || empty($end_date) || empty($status)) {
        $giveaway_errors[] = 'All fields are required.';
    }

    // Validate dates
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
        $giveaway_errors[] = 'Invalid date format.';
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $giveaway_errors[] = 'End date must be after start date.';
    }

    // Handle thumbnail upload
    $thumbnail_name = null;
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        $file_type = $_FILES['thumbnail']['type'];
        $file_size = $_FILES['thumbnail']['size'];
        $file_tmp = $_FILES['thumbnail']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION));
        $thumbnail_name = uniqid() . '.' . $file_ext;
        $upload_path = 'Uploads/' . $thumbnail_name;

        if (!in_array($file_type, $allowed_types)) {
            $giveaway_errors[] = 'Invalid file type for thumbnail. Only JPG, PNG, and GIF are allowed.';
        } elseif ($file_size > $max_size) {
            $giveaway_errors[] = 'Thumbnail size exceeds 5MB limit.';
        } elseif (!move_uploaded_file($file_tmp, $upload_path)) {
            $giveaway_errors[] = 'Failed to upload thumbnail.';
        }
    }

    if (empty($giveaway_errors)) {
        // Update giveaway
        if ($thumbnail_name) {
            $stmt = $conn->prepare("UPDATE giveaways SET title = ?, description = ?, game_id = ?, thumbnail = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssissssi", $title, $description, $game_id, $thumbnail_name, $start_date, $end_date, $status, $id);
        } else {
            $stmt = $conn->prepare("UPDATE giveaways SET title = ?, description = ?, game_id = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->bind_param("ssisssi", $title, $description, $game_id, $start_date, $end_date, $status, $id);
        }
        
        if ($stmt->execute()) {
            // Delete existing prizes
            $stmt_del = $conn->prepare("DELETE FROM giveaway_prizes WHERE giveaway_id = ?");
            $stmt_del->bind_param("i", $id);
            $stmt_del->execute();
            $stmt_del->close();
            
            // Save new prize details
            if (!empty($_POST['prize_details'])) {
                $prizes = explode("\n", $_POST['prize_details']);
                foreach ($prizes as $prize) {
                    $prize = trim($prize);
                    if (!empty($prize)) {
                        $stmt_prize = $conn->prepare("INSERT INTO giveaway_prizes (giveaway_id, description) VALUES (?, ?)");
                        $stmt_prize->bind_param("is", $id, $prize);
                        $stmt_prize->execute();
                        $stmt_prize->close();
                    }
                }
            }
            
            $giveaway_success = 'Giveaway updated successfully.';
        } else {
            $giveaway_errors[] = 'Failed to update giveaway.';
        }
        $stmt->close();
    }
}

// Handle giveaway deletion
if (isset($_GET['delete_giveaway'])) {
    $id = filter_var($_GET['delete_giveaway'], FILTER_SANITIZE_NUMBER_INT);
    // Delete thumbnail from filesystem
    $stmt = $conn->prepare("SELECT thumbnail FROM giveaways WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (file_exists('Uploads/' . $row['thumbnail'])) {
            unlink('Uploads/' . $row['thumbnail']);
        }
    }
    $stmt->close();

    // Delete giveaway (entries are automatically deleted due to ON DELETE CASCADE)
    $stmt = $conn->prepare("DELETE FROM giveaways WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $giveaway_success = 'Giveaway deleted successfully.';
    } else {
        $giveaway_errors[] = 'Failed to delete giveaway.';
    }
    $stmt->close();
}

// Handle bulk email sending
$email_success = '';
$email_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk_email'])) {
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $body = filter_var($_POST['body'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($subject) || empty($body)) {
        $email_errors[] = 'Subject and body are required.';
    }

    if (empty($email_errors)) {
        // Fetch all user emails
        $result = $conn->query("SELECT email, username FROM users");
        $sent_count = 0;
        $failed_count = 0;

        if ($result->num_rows > 0) {
            // Email headers
            $from = "no-reply@playpal.com";
            $headers = "From: Playpal <$from>\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "List-Unsubscribe: <mailto:unsubscribe@playpal.com>\r\n";

            while ($user = $result->fetch_assoc()) {
                $to = $user['email'];
                $personalized_body = "Hello " . htmlspecialchars($user['username']) . ",\n\n" . $body . "\n\nBest regards,\nThe Playpal Team\n\nTo unsubscribe, reply to this email with 'Unsubscribe' or contact us at unsubscribe@playpal.com.";

                // Send email
                if (mail($to, $subject, $personalized_body, $headers)) {
                    $sent_count++;
                } else {
                    $failed_count++;
                    $email_errors[] = "Failed to send email to $to.";
                }
            }

            if ($sent_count > 0) {
                $email_success = "Successfully sent emails to $sent_count user(s).";
            }
            if ($failed_count > 0) {
                $email_errors[] = "Failed to send emails to $failed_count user(s).";
            }
        } else {
            $email_errors[] = "No users found to send emails to.";
        }
    }
}

// Fetch giveaway for editing
$edit_giveaway = null;
if (isset($_GET['edit_giveaway'])) {
    $id = filter_var($_GET['edit_giveaway'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT * FROM giveaways WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_giveaway = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Fetch product for editing
$edit_product = null;
$edit_images = [];
if (isset($_GET['edit'])) {
    $id = filter_var($_GET['edit'], FILTER_SANITIZE_NUMBER_INT);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch existing images
    $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $edit_images[] = $row['image'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Playpal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        /* Debugging CSS to highlight the button */
        .add-product-btn {
            border: 2px solid red !important; /* Temporary red border for visibility */
            outline: 2px solid yellow !important; /* Yellow outline for debugging */
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Admin Panel</h1>
            <a href="?logout=true" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Logout</a>
        </div>

        <!-- Unique Visitors Section -->
        <!-- Visitor Statistics Section -->
<div class="bg-white p-6 rounded-lg shadow mb-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Visitor Statistics</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-100 p-4 rounded-lg">
            <p class="text-gray-600">Unique Visitors</p>
            <p class="text-2xl font-bold"><?php echo $unique_visitors; ?></p>
        </div>
        <div class="bg-gray-100 p-4 rounded-lg">
            <p class="text-gray-600">Total Suggestions</p>
            <p class="text-2xl font-bold">
                <?php 
                $result = $conn->query("SELECT COUNT(*) AS total FROM suggestions");
                echo $result->fetch_assoc()['total'];
                ?>
            </p>
        </div>
        <div class="bg-gray-100 p-4 rounded-lg">
            <p class="text-gray-600">Pending Suggestions</p>
            <p class="text-2xl font-bold">
                <?php 
                $result = $conn->query("SELECT COUNT(*) AS pending FROM suggestions WHERE status = 'pending'");
                echo $result->fetch_assoc()['pending'];
                ?>
            </p>
        </div>
    </div>
</div>

        <!-- Suggestions Management Section -->
<div class="bg-white p-6 rounded-lg shadow mb-8">
    <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manage Suggestions</h2>
    
    <?php
    // Handle suggestion status update
    if (isset($_GET['update_suggestion'])) {
        $id = filter_var($_GET['update_suggestion'], FILTER_SANITIZE_NUMBER_INT);
        $status = filter_var($_GET['status'], FILTER_SANITIZE_STRING);
        
        $stmt = $conn->prepare("UPDATE suggestions SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            echo '<p class="bg-green-100 text-green-700 p-4 rounded mb-4">Suggestion status updated successfully.</p>';
        } else {
            echo '<p class="bg-red-100 text-red-700 p-4 rounded mb-4">Failed to update suggestion status.</p>';
        }
        $stmt->close();
    }
    
    
// Handle suggestion deletion
if (isset($_GET['delete_suggestion'])) {
    $id = filter_var($_GET['delete_suggestion'], FILTER_SANITIZE_NUMBER_INT);
    
    // Verify the suggestion exists first
    $check_stmt = $conn->prepare("SELECT id FROM suggestions WHERE id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo '<p class="bg-red-100 text-red-700 p-4 rounded mb-4">Suggestion not found.</p>';
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM suggestions WHERE id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                echo '<p class="bg-green-100 text-green-700 p-4 rounded mb-4">Suggestion deleted successfully.</p>';
            } else {
                echo '<p class="bg-red-100 text-red-700 p-4 rounded mb-4">No rows were deleted (suggestion may have already been deleted).</p>';
            }
        } else {
            // Get detailed error information
            $error = $delete_stmt->error;
            echo '<p class="bg-red-100 text-red-700 p-4 rounded mb-4">Failed to delete suggestion. Error: ' . htmlspecialchars($error) . '</p>';
        }
        $delete_stmt->close();
    }
    $check_stmt->close();
}
?>
    
    <div class="overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2 text-left">ID</th>
                    <th class="px-4 py-2 text-left">Name</th>
                    <th class="px-4 py-2 text-left">Email</th>
                    <th class="px-4 py-2 text-left">Message</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM suggestions ORDER BY created_at DESC");
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $status_color = '';
                        switch ($row['status']) {
                            case 'pending':
                                $status_color = 'bg-yellow-100 text-yellow-800';
                                break;
                            case 'reviewed':
                                $status_color = 'bg-blue-100 text-blue-800';
                                break;
                            case 'completed':
                                $status_color = 'bg-green-100 text-green-800';
                                break;
                        }
                        ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo $row['id']; ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($row['message']); ?></td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $status_color; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2"><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                            <td class="px-4 py-2">
                                <div class="flex space-x-2">
                                    <select onchange="updateSuggestionStatus(<?php echo $row['id']; ?>, this.value)" 
                                            class="border rounded p-1 text-sm">
                                        <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="reviewed" <?php echo $row['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                        <option value="completed" <?php echo $row['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                    <a href="#" 
                                    onclick="confirmDelete(<?php echo $row['id']; ?>)" 
                                    class="text-red-500 hover:text-red-700">
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="7" class="px-4 py-2 text-center">No suggestions found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function updateSuggestionStatus(id, status) {
    window.location.href = `?update_suggestion=${id}&status=${status}`;
}
</script>
        <!-- Bulk Email Section -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Send Bulk Email</h2>
            <?php if ($email_success): ?>
                <p class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo htmlspecialchars($email_success); ?></p>
            <?php endif; ?>
            <?php if (!empty($email_errors)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <?php foreach ($email_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="subject">Email Subject</label>
                    <input type="text" id="subject" name="subject" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="body">Email Body</label>
                    <textarea id="body" name="body" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" rows="6" required></textarea>
                </div>
                <button type="submit" name="send_bulk_email" class="w-full sm:w-auto bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-orange-600 focus:ring-2 focus:ring-orange-500 focus:outline-none" style="display: block; visibility: visible; opacity: 1; z-index: 10 !important; background-color: rgb(249, 115, 22) !important; color: white !important;">Send Bulk Email</button>
            </form>
            <p class="text-gray-600 text-sm mt-4">Note: This feature uses PHP's mail() function, which may have deliverability issues for large volumes. For production, consider using a dedicated email service like SendGrid or Mailgun.</p>
        </div>
        
        <!-- Giveaway Management Section -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo $edit_giveaway ? 'Edit Giveaway' : 'Add New Giveaway'; ?></h2>
            <?php if ($giveaway_success): ?>
                <p class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo htmlspecialchars($giveaway_success); ?></p>
            <?php endif; ?>
            <?php if (!empty($giveaway_errors)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <?php foreach ($giveaway_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id" value="<?php echo $edit_giveaway ? $edit_giveaway['id'] : ''; ?>">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="title">Giveaway Title</label>
                    <input type="text" id="title" name="title" value="<?php echo $edit_giveaway ? htmlspecialchars($edit_giveaway['title']) : ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="description">Description</label>
                    <textarea id="description" name="description" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required><?php echo $edit_giveaway ? htmlspecialchars($edit_giveaway['description']) : ''; ?></textarea>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="game_id">Game</label>
                    <select id="game_id" name="game_id" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                        <?php
                        $result = $conn->query("SELECT id, name FROM games");
                        while ($game = $result->fetch_assoc()) {
                            echo '<option value="' . $game['id'] . '" ' . ($edit_giveaway && $edit_giveaway['game_id'] == $game['id'] ? 'selected' : '') . '>' . htmlspecialchars($game['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="thumbnail">Thumbnail Image</label>
                    <input type="file" id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/gif" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" <?php echo $edit_giveaway ? '' : 'required'; ?>>
                    <?php if ($edit_giveaway && $edit_giveaway['thumbnail']): ?>
                        <p class="text-gray-600 text-sm mt-2">Current thumbnail:</p>
                        <img src="Uploads/<?php echo htmlspecialchars($edit_giveaway['thumbnail']); ?>" alt="Giveaway Thumbnail" class="w-32 h-32 object-cover rounded">
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $edit_giveaway ? htmlspecialchars($edit_giveaway['start_date']) : ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $edit_giveaway ? htmlspecialchars($edit_giveaway['end_date']) : ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="status">Status</label>
                    <select id="status" name="status" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                        <option value="active" <?php echo $edit_giveaway && $edit_giveaway['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $edit_giveaway && $edit_giveaway['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="mb-6">
    <label class="block text-gray-700 font-semibold mb-2">Prize Details (One per line)</label>
    <textarea name="prize_details" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" rows="4"><?php 
        if ($edit_giveaway) {
            $stmt = $conn->prepare("SELECT description FROM giveaway_prizes WHERE giveaway_id = ?");
            $stmt->bind_param("i", $edit_giveaway['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                echo htmlspecialchars($row['description']) . "\n";
            }
            $stmt->close();
        }
    ?></textarea>
</div>
                <button type="submit" name="<?php echo $edit_giveaway ? 'update_giveaway' : 'add_giveaway'; ?>" class="w-full sm:w-auto bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-orange-600 focus:ring-2 focus:ring-orange-500 focus:outline-none" style="display: block; visibility: visible; opacity: 1; z-index: 10 !important; background-color: rgb(249, 115, 22) !important; color: white !important;">Save Giveaway</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manage Giveaways</h2>
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-4 py-2 text-left">Thumbnail</th>
                        <th class="px-4 py-2 text-left">Title</th>
                        <th class="px-4 py-2 text-left">Game</th>
                        <th class="px-4 py-2 text-left">Start Date</th>
                        <th class="px-4 py-2 text-left">End Date</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT g.*, games.name AS game_name FROM giveaways g JOIN games ON g.game_id = games.id";
                    $result = $conn->query($sql);
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            ?>
                            <tr class="border-b">
                                <td class="px-4 py-2">
                                    <img src="Uploads/<?php echo htmlspecialchars($row['thumbnail']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>" class="w-16 h-16 object-cover rounded">
                                </td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['title']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['game_name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['start_date']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['end_date']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['status']); ?></td>
                                <td class="px-4 py-2">
                                    <a href="?edit_giveaway=<?php echo $row['id']; ?>" class="text-blue-500 hover:underline mr-2">Edit</a>
                                    <a href="?delete_giveaway=<?php echo $row['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this giveaway?')">Delete</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="px-4 py-2 text-center">No giveaways found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($success): ?>
            <p class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mb-6">
            <a href="admin.php?clear_edit=true" class="bg-blue-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-blue-600 focus:ring-2 focus:ring-blue-500 focus:outline-none inline-block">Add New Product</a>
        </div>

        <div class="bg-white p-6 rounded-lg shadow mb-8" style="display: block !important; visibility: visible !important; min-height: 200px;">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4"><?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4" style="display: block !important; visibility: visible !important;">
                <input type="hidden" name="id" value="<?php echo $edit_product ? $edit_product['id'] : ''; ?>">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="name">Product Name</label>
                    <input type="text" id="name" name="name" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="description">Description</label>
                    <textarea id="description" name="description" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="price">Price ($)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $edit_product ? htmlspecialchars($edit_product['price']) : ''; ?>" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="sale_discount">Sale Discount (%)</label>
                    <input type="number" id="sale_discount" name="sale_discount" step="0.01" min="0" max="100" value="<?php echo $edit_product ? htmlspecialchars($edit_product['sale_discount']) : '0'; ?>" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="game_id">Game</label>
                    <select id="game_id" name="game_id" class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" required>
                        <?php
                        $result = $conn->query("SELECT id, name FROM games");
                        while ($game = $result->fetch_assoc()) {
                            echo '<option value="' . $game['id'] . '" ' . ($edit_product && $edit_product['game_id'] == $game['id'] ? 'selected' : '') . '>' . htmlspecialchars($game['name']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <!-- Rentable Section -->
        <div>
            <label class="block text-gray-700 font-semibold mb-2">
                <input type="checkbox" id="is_rentable" name="is_rentable" value="1" 
                    <?php echo $edit_product && $edit_product['is_rentable'] ? 'checked' : ''; ?> 
                    class="mr-2">
                This product can be rented
            </label>
        </div>
        
        <div id="rent_price_container" style="display: <?php echo ($edit_product && $edit_product['is_rentable']) ? 'block' : 'none'; ?>;">
            <label class="block text-gray-700 font-semibold mb-2" for="rent_price">Rent Price ($)</label>
            <input type="number" id="rent_price" name="rent_price" step="0.01" min="0" 
                value="<?php echo $edit_product ? htmlspecialchars($edit_product['rent_price']) : ''; ?>" 
                class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500"
                <?php echo ($edit_product && $edit_product['is_rentable']) ? 'required' : ''; ?>>
        </div>
        <script>
// Toggle Rent Price Visibility
document.getElementById('is_rentable').addEventListener('change', function() {
    const rentContainer = document.getElementById('rent_price_container');
    rentContainer.style.display = this.checked ? 'block' : 'none';
    document.getElementById('rent_price').required = this.checked;
});
</script>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="images">Product Images (Select multiple)</label>
                    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/gif" multiple class="w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-orange-500" <?php echo $edit_product ? '' : 'required'; ?>>
                    <?php if ($edit_product && !empty($edit_images)): ?>
                        <p class="text-gray-600 text-sm mt-2">Current images:</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($edit_images as $image): ?>
                                <img src="Uploads/<?php echo htmlspecialchars($image); ?>" alt="Product Image" class="w-16 h-16 object-cover rounded">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="<?php echo $edit_product ? 'update_product' : 'add_product'; ?>" class="add-product-btn w-full sm:w-auto bg-orange-500 text-white font-semibold py-3 px-6 rounded-lg hover:bg-orange-600 focus:ring-2 focus:ring-orange-500 focus:outline-none min-h-[48px]" style="display: block !important; visibility: visible !important; opacity: 1 !important; z-index: 10 !important; background-color: #f97316 !important; color: white !important;">Add Product</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Manage Products</h2>
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="px-4 py-2 text-left">Images</th>
                        <th class="px-4 py-2 text-left">Name</th>
                        <th class="px-4 py-2 text-left">Description</th>
                        <th class="px-4 py-2 text-left">Price</th>
                        <th class="px-4 py-2 text-left">Sale Discount</th>
                        <th class="px-4 py-2 text-left">Game</th>
                        <th class="px-4 py-2 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Join products with games to get game name
                    $sql = "SELECT p.*, g.name AS game_name FROM products p JOIN games g ON p.game_id = g.id";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Fetch images for this product
                            $stmt = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
                            $stmt->bind_param("i", $row['id']);
                            $stmt->execute();
                            $images = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                            $stmt->close();
                            ?>
                            <tr class="border-b">
                                <td class="px-4 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <?php if ($images): ?>
                                            <?php foreach ($images as $image): ?>
                                                <img src="Uploads/<?php echo htmlspecialchars($image['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="w-16 h-16 object-cover rounded">
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-gray-600">No images</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['description']); ?></td>
                                <td class="px-4 py-2">$<?php echo number_format($row['price'], 2); ?></td>
                                <td class="px-4 py-2"><?php echo $row['sale_discount'] > 0 ? number_format($row['sale_discount'], 2) . '%' : 'None'; ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['game_name']); ?></td>
                                <td class="px-4 py-2">
                                    <a href="?edit=<?php echo $row['id']; ?>" class="text-blue-500 hover:underline mr-2">Edit</a>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="text-red-500 hover:underline" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="px-4 py-2 text-center">No products found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Debugging Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addProductBtn = document.querySelector('.add-product-btn');
            if (addProductBtn) {
                console.log('Add Product button found:', addProductBtn);
                console.log('Button computed styles:', window.getComputedStyle(addProductBtn));
                console.log('Button visibility:', addProductBtn.offsetParent !== null ? 'Visible' : 'Hidden');
                console.log('Parent form styles:', window.getComputedStyle(addProductBtn.closest('form')));
                console.log('Parent div styles:', window.getComputedStyle(addProductBtn.closest('.bg-white')));
                // Force visibility
                addProductBtn.style.display = 'block';
                addProductBtn.style.visibility = 'visible';
                addProductBtn.style.opacity = '1';
            } else {
                console.error('Add Product button not found in DOM');
            }
        });
        function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this suggestion?')) {
        window.location.href = `?delete_suggestion=${id}`;
    }
    return false;
}
    </script>

    <?php $conn->close(); ?>
</body>
</html>