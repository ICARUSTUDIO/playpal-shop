<?php
$type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'purchase';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Playpal</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full text-center">
        <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">Thank You!</h1>
        <p class="text-gray-600 mb-6">
            Your <?php echo $type; ?> request has been received. Our team will contact you shortly with further details.
        </p>
        <a href="index.php" class="bg-orange-500 text-white font-semibold py-2 px-6 rounded hover:bg-orange-600 inline-block">
            Back to Home
        </a>
    </div>
</body>
</html>