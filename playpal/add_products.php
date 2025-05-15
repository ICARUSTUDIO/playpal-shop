<?php
require_once 'config.php';

$products = [
    [
        'name' => 'Legendary Nikto Account',
        'description' => 'Premium Call of Duty account with Nikto Dark Side skin, Legendary rank, and exclusive weapons.',
        'image' => 'nikto-account.jpg',
        'price' => 99.99,
        'sale_discount' => 20,
        'game' => 'Call of Duty'
    ],
    [
        'name' => 'Scorpion Skin',
        'description' => 'Exclusive Scorpion skin with maxed-out abilities for Mortal Kombat.',
        'image' => 'scorpion-skin.jpg',
        'price' => 49.99,
        'sale_discount' => 0,
        'game' => 'Mortal Kombat'
    ]
];

foreach ($products as $product) {
    $stmt = $conn->prepare("INSERT INTO products (name, description, image, price, sale_discount, game) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdds", $product['name'], $product['description'], $product['image'], $product['price'], $product['sale_discount'], $product['game']);
    if ($stmt->execute()) {
        echo "Added: {$product['name']}<br>";
    } else {
        echo "Error adding: {$product['name']} - " . $conn->error . "<br>";
    }
    $stmt->close();
}

echo "Done.";
?>