<?php
require_once 'config.php';

// Get selected game and section from query parameters
$selected_game = isset($_GET['game']) ? filter_var($_GET['game'], FILTER_SANITIZE_STRING) : '';
$section = isset($_GET['section']) ? filter_var($_GET['section'], FILTER_SANITIZE_STRING) : 'home';

// Handle giveaway entry
$entry_success = '';
$entry_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_giveaway_entry'])) {
    $giveaway_id = filter_var($_POST['giveaway_id'], FILTER_SANITIZE_NUMBER_INT);
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
    $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || empty($giveaway_id)) {
        $entry_errors[] = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $entry_errors[] = 'Invalid email format.';
    }

    if (empty($entry_errors)) {
        // Insert entry
        $stmt = $conn->prepare("INSERT INTO giveaway_entries (giveaway_id, first_name, last_name, email) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $giveaway_id, $first_name, $last_name, $email);
        if ($stmt->execute()) {
            $entry_success = 'Thank you for entering the giveaway!';
        } else {
            $entry_errors[] = 'Failed to submit entry. You may have already entered this giveaway.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playpal - Gaming Service</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <style>
        .fade-in { opacity: 0; transition: opacity 1s; }
        .fade-in.aos-animate { opacity: 1; }
        header {
            z-index: 1000; /* Ensure header establishes a high stacking context */
        }
        .dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 100%;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
            z-index: 1001; /* High z-index to appear above other content */
        }
        .menu-open .dropdown {
            max-height: 300px;
            opacity: 1;
            z-index: 1001; /* Reinforce high z-index when open */
        }
        .dropdown a {
            transition: background-color 0.3s, color 0.3s;
            text-align: center;
        }
        .nested-dropdown {
            background: white;
            padding-left: 1rem;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .nested-open .nested-dropdown {
            max-height: 100px;
            opacity: 1;
        }
        .nested-dropdown a {
            transition: background-color 0.3s, color 0.3s;
            text-align: center;
        }
        .footer-nested-dropdown {
            background: #1a202c;
            padding-left: 0.5rem;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
        .footer-nested-open .footer-nested-dropdown {
            max-height: 100px;
            opacity: 1;
        }
        .footer-nested-dropdown a {
            display: block;
            padding: 0.5rem 0;
            color: #a0aec0;
            transition: color 0.3s;
        }
        .footer-nested-dropdown a:hover {
            color: #f6ad55;
        }
        .page-section {
            display: none;
        }
        .page-section.active {
            display: block;
        }
        .sale-badge {
            @apply bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full;
            z-index: 11; /* Above product card, but below menu */
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 2000; /* Higher than menu to appear above it */
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 600px;
            width: 90%;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            cursor: pointer;
        }
        /* Slider Styles */
        .slider-container {
            position: relative;
            width: 100%;
            height: 192px;
            overflow: hidden;
        }
        .slider {
            display: flex;
            transition: transform 0.3s ease-in-out;
        }
        .slide {
            flex: 0 0 100%;
            height: 192px;
        }
        .slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .slider-nav {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
            z-index: 12; /* Above product card, but below menu */
        }
        .slider-nav button {
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 8px;
            cursor: pointer;
        }
        .slider-dots {
            position: absolute;
            bottom: 8px;
            width: 100%;
            text-align: center;
            z-index: 12; /* Above product card, but below menu */
        }
        .dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin: 0 4px;
            background: rgba(0,0,0,0.5);
            border-radius: 50%;
            cursor: pointer;
        }
        .dot.active {
            background: #f6ad55;
        }
        .modal-slider-container {
            position: relative;
            width: 100%;
            max-height: 400px;
            overflow: hidden;
        }
        .modal-slide {
            flex: 0 0 100%;
            max-height: 400px;
        }
        .modal-slide img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        /* Desktop Dropdown Hover */
        .desktop-games:hover .nested-dropdown {
            max-height: 100px;
            opacity: 1;
        }
        .product-card {
            position: relative;
            z-index: 10; /* Low z-index to stay below menu */
        }
        /* Giveaway Banner Styles */
        .giveaway-banner {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: black;
            
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem;
        }
        .giveaway-banner img {
            width: 2000px;;
            height: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 0.25rem;
        }
        .giveaway-banner button {
            background: yellow;
            color: black;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            transition: background-color 0.3s;
        }
        .giveaway-banner button:hover {
            background: #f3f4f6;
        }

        .product-card {
    transition: opacity 0.3s ease;
    opacity: 1;
}
        /* Countdown Styles */
#countdown {
    font-family: monospace;
    letter-spacing: 1px;
}
        /* Tabs styling */
.tab-btn {
    transition: all 0.3s ease;
    position: relative;
    margin-bottom: -1px; /* Align with border */
    
}

.tab-btn:hover {
    color: #4b5563; /* gray-600 */
}

.tab-btn.active {
    color: #ea580c; /* orange-600 */
    border-bottom-color: #ea580c;
}

.modal-content {
    max-height: 90vh; /* Limit modal height */
    overflow-y: auto; /* Make content scrollable */
    width: 90%;
    max-width: 600px;
    position: relative;
}

/* Form container styling */
.transaction-form-container {
    max-height: 60vh;
    overflow-y: auto;
    padding-right: 8px; /* Space for scrollbar */
}

/* Scrollbar styling */
.transaction-form-container::-webkit-scrollbar {
    width: 6px;
}

.transaction-form-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.transaction-form-container::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.transaction-form-container::-webkit-scrollbar-thumb:hover {
    background: #555;
}


    </style>

<link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <!-- Header -->
    <header class="bg-white shadow py-4 px-6 flex justify-between items-center relative" data-aos="fade-down">
        <div class="text-2xl font-bold text-orange-500">Playpal.</div>
        <button id="menu-toggle" class="text-gray-600 focus:outline-none md:hidden">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>
        <nav class="hidden md:flex md:space-x-6">
            <a href="#" data-section="home" class="text-gray-800 hover:text-orange-500 font-bold">Home</a>
            <a href="#" data-section="about" class="text-gray-800 hover:text-orange-500 font-bold">About</a>
            <div class="relative desktop-games">
                <a href="#" id="desktop-games-toggle" class="text-gray-800 hover:text-orange-500 font-bold flex items-center" aria-expanded="false" aria-controls="desktop-games-menu">Games <i class="fas fa-caret-down ml-1"></i></a>
                <div class="nested-dropdown" id="desktop-games-menu">
                    <a href="?section=products&game=Call of Duty" class="block px-6 py-2 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">Call of Duty</a>
                    <a href="?section=products&game=Mortal Kombat" class="block px-6 py-2 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">Mortal Kombat</a>
                </div>
            </div>
            <a href="#" data-section="faqs" class="text-gray-800 hover:text-orange-500 font-bold">FAQs</a>
            <a href="#" data-section="contact" class="text-gray-800 hover:text-orange-500 font-bold">Contact</a>
        </nav>
        <div class="dropdown" id="mobile-menu">
            <a href="#" data-section="home" class="block px-6 py-3 text-gray-800 bg-orange-500 text-white font-bold text-center">Home</a>
            <a href="#" data-section="about" class="block px-6 py-3 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">About</a>
            <div class="relative">
                <a href="#" id="games-toggle" class="block px-6 py-3 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center flex items-center justify-center" aria-expanded="false" aria-controls="games-menu">Games <i class="fas fa-caret-down ml-2"></i></a>
                <div class="nested-dropdown" id="games-menu">
                    <a href="?section=products&game=Call of Duty" class="block px-6 py-2 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">Call of Duty</a>
                    <a href="?section=products&game=Mortal Kombat" class="block px-6 py-2 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">Mortal Kombat</a>
                </div>
            </div>
            <a href="#" data-section="faqs" class="block px-6 py-3 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">FAQs</a>
            <a href="#" data-section="contact" class="block px-6 py-3 text-gray-800 hover:bg-orange-500 hover:text-white font-bold text-center">Contact</a>
        </div>
    </header>

    <!-- Main Content Sections -->
    <main>
        <!-- Home Section -->
        <section id="home" class="page-section <?php echo $section === 'home' ? 'active' : ''; ?>">
            <!-- Hero Section -->
            <section class="bg-white text-center py-16" data-aos="fade-in">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">Your Ultimate Gaming Experience</h1>
                <p class="text-gray-600 mb-8 max-w-2xl mx-auto">
                    At Playpal, we top-notch gaming services tailored just for you. Dive into our extensive range of accounts and in-game items to elevate your gaming journey.
                </p>
                <button class="bg-orange-500 text-white px-6 py-3 rounded-full font-semibold hover:bg-orange-600">Explore</button>
                <div class="mt-8">
                    <img src='https://lh3.googleusercontent.com/blogger_img_proxy/AEn0k_tS71pLyhCmGCYfLKrwFRUVuOaVgdcWzWGeq6jNO-8AaFwc7FhzhkQY2nntXFURnXH1UAKvKCZj4dRfcfCaV6gYIpTG34KYMoskgamdYAG09YzjvRCGzsoX62ZO8FbiqTm-BZkXAylB9ZlJomswhpRf949ZF8a3VomrFm-8g3I0pw=w919-h516-p-k-no-nu' alt="CODM Codename Lazarus S10 Skin" class="w-64 h-64 object-cover mx-auto rounded-lg">
                </div>
            </section>

            <!-- How It Works Section (Epic Gaming Adventures) -->
            <section class="py-16 text-center" data-aos="fade-in">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Epic Gaming Adventures</h2>
                <div class="max-w-4xl mx-auto space-y-12">
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                            <i class="fas fa-gamepad text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Choose a Game & Service</h3>
                            <p class="text-gray-600">Browse our extensive library of games and services tailored for you.</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                            <i class="fas fa-paper-plane text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">Submit a Request</h3>
                            <p class="text-gray-600">Submit your services request easily and let us handle the rest.</p>
                        </div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                            <i class="fas fa-check-circle text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-800">We Get It Done!</h3>
                            <p class="text-gray-600">Sit back, relax, and let us level up your gaming experience for you!</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Trending Games Section -->
            <section class="bg-white py-16 text-center" data-aos="fade-in">
                <h2 class="text-3xl font-bold text-orange-500 mb-8">Trending Games</h2>
                <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
                    <a href="?section=products&game=Call of Duty" class="block">
                        <div class="bg-gray-100 rounded-lg overflow-hidden" data-aos="fade-up">
                            <img src='https://lh3.googleusercontent.com/blogger_img_proxy/AEn0k_uQnubLCNMKWx-RJpyGDG2KZEhx-XoMpx0-XsQ3o1YU6VRKW_S-qJD_mxxnq2_Zs7dpkWryd-AZIBhsLAY875E_YTpZwsCokrkDqH7xGZUV0jhfHS8Kzwz_bYlPIVXLV4UHP4WbqFjWQpEIsOLeRa0H=w919-h516-p-k-no-nu' alt="Call of Duty" class="w-full h-48 object-cover">
                            <h3 class="text-xl font-semibold text-orange-500 py-4">Call of Duty</h3>
                        </div>
                    </a>
                    <a href="?section=products&game=Mortal Kombat" class="block">
                        <div class="bg-gray-100 rounded-lg overflow-hidden" data-aos="fade-up">
                            <img src='https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEgzwd-KXPRxTWf1j6SdN2GQW1Xxq0wVsieThFVfv1MaFDiZZcbVWpwDYgkmBjenaw0Psb2U5TpNIJWgjl8179Ntqd8CKLGS9_8NFoeelbDozk3m4Ua8-MWgQfC-nADb6XW2oXoR_AkMSOF4/w919-h516-p-k-no-nu/mortal-kombat-movie-characters-poster-uhdpaper.com-4K-7.3531-wp.thumbnail.jpg' alt="Mortal Kombat" class="w-full h-48 object-cover">
                            <h3 class="text-xl font-semibold text-orange-500 py-4">Mortal Kombat</h3>
                        </div>
                    </a>
                </div>
            </section>

            <!-- Why Gamers Trust Us Section -->
            <section class.ConcurrentHashMap="py-16 text-center" data-aos="fade-in">
                <h2 class="text-3xl font-bold text-gray-800 mb-8">Our analytics that feels like it’s from the future</h2>
                <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-8">
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                                <i class="fas fa-shield-alt text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">Guarantees</h3>
                                <p class="text-gray-600">We guarantee that all services will be delivered on time and without any problem.</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                                <i class="fas fa-bolt text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">Fast</h3>
                                <p class="text-gray-600">We handle 500+ souls per hour over 10k souls per day.</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                                <i class="fas fa-fire text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">Lit</h3>
                                <p class="text-gray-600">Over 2 years in work completed, 30k regular customers and 90%+ accounts sold.</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                                <i class="fas fa-dollar-sign text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">Cheap Prices</h3>
                                <p class="text-gray-600">We will find cheaper trusted seller will do it same price.</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                                <i class="fas fa-lock text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">Secure</h3>
                                <p class="text-gray-600">We don’t share your account details to anyone. We selling our own accounts only.</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-center">
                            <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center text-white mb-4">
                                <i class="fas fa-gift text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-semibold text-gray-800">Bonuses</h3>
                                <p class="text-gray-600">There are many bonuses for regular customers (up to 50% extra) and many rare equipment cards every order.</p>
                            </div>
                        </div>
                    </div>
                    <div data-aos="fade-up">
                        <img src='https://blogger.googleusercontent.com/img/b/R29vZ2xl/AVvXsEifjx8gq9ybU8iAD-qdBr0zxPcr5605Gk1wrhyphenhypheneZYOu1LatfRkHK-NKg2AFYYnFBznAebR9LuVd6YpXW3n6yInV8wP9fdsxYzqO0chGf8hdrg8UtHUP2uTO_h079n139b0b6lu9lFPlfd2c/w919-h516-p-k-no-nu/cod-mobile-nikto-dark-side-season-12-skin-uhdpaper.com-4K-8.1944-wp.thumbnail.jpg' alt="COD Mobile Nikto Dark Side Season 12 Skin" class="w-full h-64 object-cover rounded-lg mb-4">
                        <h3 class="text-xl font-semibold text-gray-800">Account on SALE</h3>
                        <p class="text-gray-600">Save Activation - GUN LEGEND</p>
                        <p class="text-gray-500 text-sm">Unlock the ultimate power with this exclusive COD Mobile account, featuring the rare Nikto Dark Side skin from Season 12. Packed with premium weapons and upgrades, this account is your key to dominating the battlefield.</p>
                        <p class="text-red-500 font-semibold mt-2">20% off</p>
                        <button class="bg-orange-500 text-white px-6 py-2 rounded-full font-semibold hover:bg-orange-600 mt-4">View Offer</button>
                    </div>
                </div>
            </section>

            <!-- Learn More Section -->
            <section class="bg-white py-16" data-aos="fade-in">
                <h2 class="text-3xl font-bold text-gray-800 text-center mb-8">Learn more for Gaming</h2>
                <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-gray-100 rounded-lg overflow-hidden" data-aos="fade-up">
                        <div class="w-full h-48 bg-gray-300"></div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800">Unlock Diamond Camo in Call of Duty Mobile – Is It Worth the Grind?</h3>
                            <p class="text-gray-600 text-sm">March 3, 2025</p>
                            <p class="text-gray-600 mt-2">Introduction Weapon camos in Call of Duty Mobile are more than just cosmetics—they showcase dedication and skill. The Diamond Camo, in...</p>
                        </div>
                    </div>
                    <div class="bg-gray-100 rounded-lg overflow-hidden" data-aos="fade-up">
                        <div class="w-full h-48 bg-gray-300"></div>
                        <div class="p-4">
                            <h3 class="text-xl font-semibold text-gray-800">Why Leveling Up Matters – The Benefits of Reaching Legendary Status In Call of Duty Mobile</h3>
                            <p class="text-gray-600 text-sm">March 3, 2025</p>
                            <p class="text-gray-600 mt-2">Introduction For many gamers, reaching the Legendary Rank in Call of Duty Mobile is the flex. It’s not just about bragging rights—it’s a...</p>
                        </div>
                    </div>
                </div>
            </section>
        </section>

        <!-- About Section -->
        <section id="about" class="page-section <?php echo $section === 'about' ? 'active' : ''; ?>">
            <nav class="bg-gray-100 p-2 text-sm">
                <div class="container mx-auto flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-gray-600">Home / About</span>
                </div>
            </nav>
            <div class="container mx-auto px-4 py-2 max-w-4xl" data-aos="fade-in">
                <div class="bg-white rounded-md mb-6 p-4">
                    <div class="flex flex-col items-center justify-center text-center">
                        <img src="https://lh3.googleusercontent.com/blogger_img_proxy/AEn0k_sur0qmi3G4X_Lt2Dyuyg-rt4ado8zkDtOLyBfL96GRSJIHlvG0X6StX87U6UBzCGwr1tk11GdLYq-rMheoEf5mNi7-XrrlhTDK3gXC7dZmQa6nCxIazWv7xapEagAREG5HekxyySohCa074p9a8xR-=w919-h516-p-k-no-nu" alt="Gaming characters" class="w-full rounded-md mb-4">
                        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome to Our Gaming Channel!</h1>
                        <p class="text-gray-600 text-sm mb-4">At The Gaming Channel, we specialize in providing premium gaming services to players who want to take their gameplay to the next level. Whether you're striving for Legendary rank in Call Of Duty Mobile or unlocking elite skins in Mortal Kombat, we offer professional solutions tailored to your gaming goals.</p>
                        <p class="text-gray-600 text-sm">Our mission is simple: to make high-tier gaming services accessible, affordable, and trustworthy. We understand the grind, and that's why we've designed a simple process—submit a service request, and let us handle the details, all while providing a smooth, secure experience and fast turnaround times, so you can focus on what matters most: enjoying the game.</p>
                    </div>
                </div>
                <div class="flex justify-between mb-4">
                    <div class="w-1/3 bg-white border rounded-md p-4 flex flex-col items-center justify-center mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="text-xl font-bold">10.5k</span>
                        <span class="text-xs text-gray-600">Active buyers our site</span>
                    </div>
                    <div class="w-1/3 bg-orange-500 border rounded-md p-4 flex flex-col items-center justify-center mr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        <span class="text-xl font-bold text-white">31k</span>
                        <span class="text-xs text-white">Monthly Production</span>
                    </div>
                    <div class="w-1/3 bg-white border rounded-md p-4 flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="text-xl font-bold">45.5k</span>
                        <span class="text-xs text-gray-600">Customer active in our site</span>
                    </div>
                </div>
                <div class="flex justify-center mb-6">
                    <div class="w-full bg-white border rounded-md p-4 flex flex-col items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <span class="text-xl font-bold">25k</span>
                        <span class="text-xs text-gray-600">Annual gross sale in our site</span>
                    </div>
                </div>
                <div class="text-center mb-8">
                    <p class="text-orange-500 font-semibold">Why Gamers Trust Us</p>
                    <h2 class="text-xl font-bold mb-4"><span class="text-orange-500">Our</span> analytics that feels like it's from the future</h2>
                </div>
                <div class="space-y-6 mb-10">
                    <div class="flex items-center">
                        <div class="mr-4">
                            <div class="bg-orange-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Guarantees</h3>
                            <p class="text-sm text-gray-600">We guarantee that all secure account will be automated free and without any problems.</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-4">
                            <div class="bg-orange-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Fast</h3>
                            <p class="text-sm text-gray-600">We can do over 20,500 tasks per hour and over 200,000 calls per day</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-4">
                            <div class="bg-orange-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Over 2 years in work</h3>
                            <p class="text-sm text-gray-600">1000+ completed orders, 30 regular customers and 100+ accounts sold</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-4">
                            <div class="bg-orange-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Cheap prices</h3>
                            <p class="text-sm text-gray-600">If you will find cheaper from trusted seller we will do it for same price.</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-4">
                            <div class="bg-orange-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Secure</h3>
                            <p class="text-sm text-gray-600">We don't share your account details to anyone. We selling our accounts only.</p>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div class="mr-4">
                            <div class="bg-orange-100 rounded-full p-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg">Bonuses</h3>
                            <p class="text-sm text-gray-600">There are many bonuses for regular customers too. So DM and check next rank advancement can be cheaper.</p>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 mb-8">
                    <div class="bg-gray-100 p-4 rounded-md flex items-center">
                        <div class="mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">FREE AND FAST DELIVERY</h3>
                            <p class="text-sm text-gray-600">Free delivery for all orders</p>
                        </div>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-md flex items-center">
                        <div class="mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">24/7 CUSTOMER SERVICE</h3>
                            <p class="text-sm text-gray-600">Friendly 24/7 customer support</p>
                        </div>
                    </div>
                    <div class="bg-gray-100 p-4 rounded-md flex items-center">
                        <div class="mr-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold">MONEY BACK GUARANTEE</h3>
                            <p class="text-sm text-gray-600"></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="page-section <?php echo $section === 'products' ? 'active' : ''; ?>">
            <div class="container mx-auto px-4 py-8 max-w-4xl">
                <h1 class="text-5xl font-bold text-gray-800 mb-10">
                    <?php echo $selected_game ? htmlspecialchars($selected_game) . ' Products' : 'Our Products'; ?>
                </h1>
                 <!-- Tabs Navigation -->
<div class="flex border-b border-gray-200 mb-6" data-aos="fade-up">
    <button class="tab-btn px-6 py-3 font-medium text-sm border-b-2 border-orange-500 text-yellow-500 bg-black" data-tab="all">
        All Products
    </button>
    <button class="tab-btn px-6 py-3 font-medium text-sm text-yellow-500 bg-black hover:text-gray-700" data-tab="buy">
        Buy Accounts
    </button>
    <button class="tab-btn px-6 py-3 font-medium text-sm text-yellow-500 bg-black hover:text-gray-700" data-tab="rent">
        Rent Accounts
    </button>
</div>

                <?php
                // Fetch active giveaway for the selected game
                $giveaway = null;
                if ($selected_game) {
                    $stmt = $conn->prepare("
                        SELECT g.* 
                        FROM giveaways g 
                        JOIN games ON g.game_id = games.id 
                        WHERE games.name = ? 
                        AND g.status = 'active' 
                        AND CURDATE() BETWEEN g.start_date AND g.end_date 
                        LIMIT 1
                    ");
                    $stmt->bind_param("s", $selected_game);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        $giveaway = $result->fetch_assoc();
                    }
                    $stmt->close();
                }

                if ($giveaway): ?>
<div class="giveaway-banner mb-8 overflow-hidden" data-aos="fade-up">
    <!-- Title Section -->
    <div class="relative bg-gradient-to-r from-yellow-500 to-yellow-400 py-4 px-6">
        <div class="absolute inset-0 border-b-4 border-orange-800/40"></div>
        <h3 class="text-center text-xl font-bold uppercase tracking-wider text-black  drop-shadow-md banner-txt" style="font-family: 'Anton', Impact, sans-serif; font-size: 28px; color: #000; text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">
            <?php echo htmlspecialchars($giveaway['title']); ?>
        </h3>
    </div>
                        
                        <div class="flex flex-col md:flex-row">
                            <!-- Left side - image -->
                            <div class="md:w-1/2 p-4 flex items-center justify-center">
                                <img src="Uploads/<?php echo htmlspecialchars($giveaway['thumbnail']); ?>" 
                                     alt="<?php echo htmlspecialchars($giveaway['title']); ?>" 
                                     class="w-full h-auto max-h-[500px] object-contain">
                            </div>
                            
                            <!-- Middle - details -->
                            <div class="md:w-1/3 p-4 border-t md:border-t-0 md:border-r border-orange-400">
    <div class="space-y-2">
        <?php
        $stmt_prizes = $conn->prepare("SELECT description FROM giveaway_prizes WHERE giveaway_id = ?");
        $stmt_prizes->bind_param("i", $giveaway['id']);
        $stmt_prizes->execute();
        $prizes = $stmt_prizes->get_result();
        
        if ($prizes->num_rows > 0) {
            while ($prize = $prizes->fetch_assoc()) {
                echo '<p class="text-sm">' . htmlspecialchars($prize['description']) . '</p>';
            }
        } else {
            echo '<p class="text-sm">No prize details available.</p>';
        }
        $stmt_prizes->close();
        ?>
    </div>
</div>
                            
                            <!-- Right side - countdown and button -->
                            <div class="md:w-1/3 p-4 flex flex-col items-center justify-center">
                                <!-- Countdown -->
                                <div class="mb-4 text-center">
                                    <p class="text-sm mb-1">ENDS IN</p>
                                    <div class="text-2xl font-bold countdown-timer" 
                                         data-end-date="<?php echo $giveaway['end_date']; ?>"
                                         id="countdown-<?php echo $giveaway['id']; ?>">
                                        <?php 
                                        $end_date = new DateTime($giveaway['end_date']);
                                        $now = new DateTime();
                                        $interval = $now->diff($end_date);
                                        echo sprintf(
                                            "%02dd:%02dh:%02dm:%02ds", 
                                            $interval->d, 
                                            $interval->h, 
                                            $interval->m,
                                            $interval->s
                                        );
                                        ?>
                                    </div>
                                </div>
                                
                                <button onclick="openGiveawayModal(<?php echo $giveaway['id']; ?>)" 
                                        class="bg-white text-orange-500 px-6 py-2 rounded-lg font-bold hover:bg-gray-100 transition">
                                    ENTER NOW
                                </button>
                            </div>
                        </div>
                    </div>
                
                    <script>
                    // Initialize countdown for this giveaway
                    document.addEventListener('DOMContentLoaded', function() {
                        const countdownElement = document.getElementById('countdown-<?php echo $giveaway['id']; ?>');
                        const endDate = countdownElement.getAttribute('data-end-date');
                        
                        function updateCountdown() {
                            const now = new Date();
                            const end = new Date(endDate);
                            const diff = end - now;
                            
                            if (diff <= 0) {
                                countdownElement.textContent = "00d:00h:00m:00s";
                                return;
                            }
                            
                            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                            
                            countdownElement.textContent = 
                                `${String(days).padStart(2, '0')}d:${String(hours).padStart(2, '0')}h:${String(minutes).padStart(2, '0')}m:${String(seconds).padStart(2, '0')}s`;
                        }
                        
                        // Update immediately
                        updateCountdown();
                        
                        // Update every second
                        setInterval(updateCountdown, 1000);
                    });
                    </script>
                    <!-- After the existing giveaway banner, add this new section -->


<!-- Add this modal for fullscreen viewing -->
<div id="fullscreen-modal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden">
    <div class="absolute top-4 right-4">
        <button onclick="closeFullscreen()" class="text-white text-4xl">&times;</button>
    </div>
    <div class="flex items-center justify-center h-full">
        <img id="fullscreen-image" src="" class="max-w-full max-h-full object-contain">
    </div>
</div>

<script>
// Make image clickable for fullscreen
document.querySelectorAll('.giveaway-banner img, .bg-black img').forEach(img => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', function() {
        document.getElementById('fullscreen-image').src = this.src;
        document.getElementById('fullscreen-modal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });
});

function closeFullscreen() {
    document.getElementById('fullscreen-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}
</script>
                <?php endif; ?>

                <?php
                // Build SQL query based on game filter
                $sql = "SELECT p.*, g.name AS game_name FROM products p JOIN games g ON p.game_id = g.id";
                $params = [];
                $types = "";
                
                if (!empty($selected_game)) {
                    $sql .= " WHERE g.name = ?";
                    $params[] = $selected_game;
                    $types .= "s";
                }
                
                $stmt = $conn->prepare($sql);
                
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-8">';
                    
                    while ($row = $result->fetch_assoc()) {

                        // 🚀 Debugging: Print product details to console
                        echo '<script>console.log("Product: ' . 
                        htmlspecialchars($row['name']) . 
                        ' | Rentable: ' . ($row['is_rentable'] ? 'true' : 'false') . 
                        ' | Rent Price: ' . $row['rent_price'] . '");</script>';
                    

                         $is_rentable = ($row['is_rentable'] == 1 && $row['rent_price'] > 0);

                        // Fetch images for this product
                        $stmt_img = $conn->prepare("SELECT image FROM product_images WHERE product_id = ?");
                        $stmt_img->bind_param("i", $row['id']);
                        $stmt_img->execute();
                        $images = $stmt_img->get_result()->fetch_all(MYSQLI_ASSOC);
                        $stmt_img->close();
                        
                        $discounted_price = $row['sale_discount'] > 0 
                            ? $row['price'] * (1 - $row['sale_discount'] / 100) 
                            : $row['price'];
                        ?>
                        <div class="bg-gray-100 rounded-lg overflow-hidden relative cursor-pointer product-card" 
                            data-product-id="<?php echo $row['id']; ?>" 
                            data-game="<?php echo htmlspecialchars($row['game_name']); ?>"
                            data-rentable="<?php echo $row['is_rentable'] ? '1' : '0'; ?>"
                            data-rent-price="<?php echo $row['rent_price'] ? $row['rent_price'] : '0'; ?>"
                            data-aos="fade-up">
                            <?php if ($row['sale_discount'] > 0): ?>
                                <span class="sale-badge absolute top-2 left-2">
                                    Sale <?php echo number_format($row['sale_discount'], 0); ?>% Off
                                </span>
                            <?php endif; ?>

                            <!-- Slider Container -->
                            <div class="slider-container">
                                <div class="slider" id="slider-<?php echo $row['id']; ?>">
                                    <?php if ($images): ?>
                                        <?php foreach ($images as $index => $image): ?>
                                            <div class="slide">
                                                <img src="Uploads/<?php echo htmlspecialchars($image['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($row['name']); ?>">
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="slide">
                                            <img src="https://via.placeholder.com/300x192" alt="No image available">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if (count($images) > 1): ?>
                                    <div class="slider-nav">
                                        <button class="prev" data-slider-id="<?php echo $row['id']; ?>">❮</button>
                                        <button class="next" data-slider-id="<?php echo $row['id']; ?>">❯</button>
                                    </div>
                                    <div class="slider-dots" id="dots-<?php echo $row['id']; ?>">
                                        <?php foreach ($images as $index => $image): ?>
                                            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                  data-slide="<?php echo $index; ?>" 
                                                  data-slider-id="<?php echo $row['id']; ?>"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Product Details -->
                            <div class="p-4">
                                <h3 class="text-xl font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </h3>
                                <div class="mt-2">
                                    <?php if ($row['sale_discount'] > 0): ?>
                                        <span class="text-gray-500 line-through">
                                            $<?php echo number_format($row['price'], 2); ?>
                                        </span>
                                        <span class="text-orange-500 font-semibold">
                                            $<?php echo number_format($discounted_price, 2); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-orange-500 font-semibold">
                                            $<?php echo number_format($row['price'], 2); ?>
                                        </span>
                                    <?php endif; ?>
                                    <!-- 🚀 Show Rent Price if available -->
                            <?php if ($is_rentable): ?>
                                <div class="mt-2 text-blue-500 font-semibold">
                                    Rent: $<?php echo number_format($row['rent_price'], 2); ?>
                                </div>
                            <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Modal for Product -->
<div class="modal" id="modal-<?php echo $row['id']; ?>">
    <div class="modal-content">
        <span class="modal-close">×</span>
        <h2 class="text-2xl font-bold text-gray-800 mb-4"><?php echo htmlspecialchars($row['name']); ?></h2>
        <div class="modal-slider-container">
            <div class="slider" id="modal-slider-<?php echo $row['id']; ?>">
                <?php if ($images): ?>
                    <?php foreach ($images as $index => $image): ?>
                        <div class="modal-slide">
                            <img src="Uploads/<?php echo htmlspecialchars($image['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name']); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="modal-slide">
                        <img src="https://via.placeholder.com/600x400" alt="No image available">
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($images) > 1): ?>
                <div class="slider-nav">
                    <button class="prev" data-slider-id="modal-<?php echo $row['id']; ?>">❮</button>
                    <button class="next" data-slider-id="modal-<?php echo $row['id']; ?>">❯</button>
                </div>
                <div class="slider-dots" id="modal-dots-<?php echo $row['id']; ?>">
                    <?php foreach ($images as $index => $image): ?>
                        <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                              data-slide="<?php echo $index; ?>" 
                              data-slider-id="modal-<?php echo $row['id']; ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <p class="text-gray-600 mt-4"><?php echo htmlspecialchars($row['description']); ?></p>
        <div class="mt-4">
            <?php if ($row['sale_discount'] > 0): ?>
                <span class="text-gray-500 line-through">
                    $<?php echo number_format($row['price'], 2); ?>
                </span>
                <span class="text-orange-500 font-semibold">
                    $<?php echo number_format($discounted_price, 2); ?>
                </span>
            <?php else: ?>
                <span class="text-orange-500 font-semibold">
                    $<?php echo number_format($row['price'], 2); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($row['is_rentable'] && $row['rent_price']): ?>
                <div class="mt-2">
                    <span class="text-gray-700">Rent Price:</span>
                    <span class="text-orange-500 font-semibold">
                        $<?php echo number_format($row['rent_price'], 2); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
         <!-- Transaction Form -->
<div id="transaction-form-<?php echo $row['id']; ?>" class="mt-6 hidden transaction-form-container">
    <form method="POST" action="process_transaction.php">
        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
        <input type="hidden" name="transaction_type" id="transaction-type-<?php echo $row['id']; ?>">
        
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">First Name</label>
            <input type="text" name="first_name" class="w-full border border-gray-300 rounded p-2" required>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Last Name</label>
            <input type="text" name="last_name" class="w-full border border-gray-300 rounded p-2" required>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Email</label>
            <input type="email" name="email" class="w-full border border-gray-300 rounded p-2" required>
        </div>
        
        <div class="mb-4">
            <label class="block text-gray-700 font-semibold mb-2">Phone (Optional)</label>
            <input type="tel" name="phone" class="w-full border border-gray-300 rounded p-2">
        </div>
        
        <button type="submit" class="w-full bg-orange-500 text-white font-semibold py-2 rounded hover:bg-orange-600 mb-4">
            <span id="submit-text-<?php echo $row['id']; ?>">Submit</span>
        </button>
        
        <!-- Add a back button to return to action buttons -->
        <button type="button" 
                onclick="hideTransactionForm(<?php echo $row['id']; ?>)" 
                class="w-full bg-gray-300 text-gray-700 font-semibold py-2 rounded hover:bg-gray-400">
            Back to Options
        </button>
    </form>
</div>
        <script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab-btn');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab styles
            tabs.forEach(t => {
                t.classList.remove('border-orange-500', 'text-orange-600');
                t.classList.add('text-gray-500');
            });
            this.classList.add('border-orange-500', 'text-orange-600');
            this.classList.remove('text-gray-500');
            
            // Get filter type
            const filter = this.dataset.tab;
            
            // Filter products with animation handling
            document.querySelectorAll('.product-card').forEach(card => {
                const isRentable = card.dataset.rentable === '1';
                const hasRentPrice = parseFloat(card.dataset.rentPrice) > 0;
                
                let shouldShow = false;
                
                if (filter === 'all') {
                    shouldShow = true;
                } else if (filter === 'buy' && !isRentable) {
                    shouldShow = true;
                } else if (filter === 'rent' && isRentable && hasRentPrice) {
                    shouldShow = true;
                }
                
                // Reset animation properties before showing/hiding
                card.style.opacity = '0';
                card.style.transition = 'none';
                
                // Force reflow to ensure reset takes effect
                void card.offsetHeight;
                
                // Apply the actual visibility with transition
                card.style.transition = 'opacity 0.3s ease';
                
                if (shouldShow) {
                    card.style.display = 'block';
                    setTimeout(() => {
                        card.style.opacity = '1';
                    }, 10);
                } else {
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.style.display = 'none';
                    }, 300); // Match this with your transition duration
                }
            });
            
            // Refresh AOS animations after filtering
            setTimeout(() => {
                AOS.refresh();
            }, 350);
        });
    });
    
    // Activate default tab
    document.querySelector('.tab-btn[data-tab="all"]').click();
});
</script>
        <!-- Action Buttons -->
        <div class="flex space-x-4 mt-4" id="action-buttons-<?php echo $row['id']; ?>">
            <button onclick="showTransactionForm(<?php echo $row['id']; ?>, 'buy')" 
                class="flex-1 bg-orange-500 text-white font-semibold py-2 rounded hover:bg-orange-600">
                Buy Now
            </button>
            <?php if ($row['is_rentable'] && $row['rent_price']): ?>
                <button onclick="showTransactionForm(<?php echo $row['id']; ?>, 'rent')" 
                    class="flex-1 bg-blue-500 text-white font-semibold py-2 rounded hover:bg-blue-600">
                    Rent Now
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
                        <?php
                    }
                    
                    echo '</div>';
                } else {
                    echo '<p class="text-gray-600 text-center col-span-2 no-products-message">';
                    echo $selected_game 
                        ? 'No products available for ' . htmlspecialchars($selected_game) 
                        : 'No products available';
                    echo '.</p>';
                }
                
                $stmt->close();
                ?>
            </div>
        </section>

        <!-- FAQs Section -->
        <section id="faqs" class="page-section <?php echo $section === 'faqs' ? 'active' : ''; ?>">
            <div class="container mx-auto px-4 py-8 max-w-4xl" data-aos="fade-in">
                <h1 class="text-5xl font-bold text-gray-800 mb-10">FAQs</h1>
                <div class="mb-1 faq-item" data-open="true">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">−</span>
                            <h3 class="text-xl font-semibold">How does your gaming service work?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 faq-content">
                        <p class="mb-2">We offer professional <span class="font-semibold">game progression services</span>, where we help you reach specific ranks or unlock skins in <span class="font-semibold">Mortal Kombat</span> and <span class="font-semibold">Call of Duty Mobile</span>. You select a service, fill out a request form, and we take care of the rest while keeping you updated.</p>
                    </div>
                </div>
                <div class="mb-1 faq-item" data-open="false">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">+</span>
                            <h3 class="text-xl font-semibold">Is my gaming account safe with you?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 hidden faq-content">
                        <p class="mb-2">Yes, your account is completely safe with us. We use secure methods and never share your login details with anyone. Our team consists of professional players who understand the importance of account security. We also use VPNs matched to your region to prevent any suspicious activity flags on your account.</p>
                    </div>
                </div>
                <div class="mb-1 faq-item" data-open="false">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">+</span>
                            <h3 class="text-xl font-semibold">How long does it take to reach Legendary rank in Call of Duty Mobile?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 hidden faq-content">
                        <p class="mb-2">The time to reach Legendary rank depends on your starting rank and the package you choose. On average, it takes 3-7 days from Master rank to Legendary. We offer both standard and express services, with the express option completing your order in half the standard time.</p>
                    </div>
                </div>
                <div class="mb-1 faq-item" data-open="false">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">+</span>
                            <h3 class="text-xl font-semibold">Can I still play while my order is being processed?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 hidden faq-content">
                        <p class="mb-2">We recommend not playing on the account while we're working on it to avoid conflicts and ensure faster completion. However, if you need to play, please notify us in advance so we can coordinate schedules. Playing during an active order may extend the completion time.</p>
                    </div>
                </div>
                <div class="mb-1 faq-item" data-open="false">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">+</span>
                            <h3 class="text-xl font-semibold">Do you offer refunds?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 hidden faq-content">
                        <p class="mb-2">Yes, we offer a money-back guarantee if we cannot complete your order for any reason. If you're not satisfied with our service, please contact our support team within 48 hours of order completion, and we'll work to resolve the issue or provide a refund according to our refund policy.</p>
                    </div>
                </div>
                <div class="mb-1 faq-item" data-open="false">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">+</span>
                            <h3 class="text-xl font-semibold">Can I track the progress of my order?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 hidden faq-content">
                        <p class="mb-2">Yes, we provide regular updates on your order progress. You'll receive notifications when your order starts, at key milestone points, and when it's completed. You can also check your order status through your account dashboard or by contacting our 24/7 customer support team.</p>
                    </div>
                </div>
                <div class="mb-1 faq-item " data-open="false">
                    <div class="bg-gray-100 p-4 cursor-pointer faq-header">
                        <div class="flex items-center">
                            <span class="text-2xl font-bold mr-2 faq-icon">+</span>
                            <h3 class="text-xl font-semibold">What payment methods do you accept?</h3>
                        </div>
                    </div>
                    <div class="bg-white p-4 border-t border-gray-200 hidden faq-content">
                        <p class="mb-2">We accept a variety of payment methods including credit/debit cards (Visa, Mastercard, American Express), PayPal, cryptocurrency (Bitcoin, Ethereum), and various mobile payment options. All payments are processed securely, and we never store your complete payment information.</p>
                    </div>
                </div>
                <div class="fixed bottom-4 right-4">
                    <button class="bg-blue-600 text-white p-3 rounded-md shadow-md" id="scrollToTop">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                </div>
            </section>

        <!-- Contact Section -->
        <section id="contact" class="page-section <?php echo $section === 'contact' ? 'active' : ''; ?>">
            <div class="container mx-auto px-4 py-8 max-w-4xl" data-aos="fade-in">
                <h1 class="text-5xl font-bold text-gray-800 mb-10">Contact Us</h1>

                <!-- Suggestions Box -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-2xl font-semibold text-gray-800 mb-4">Suggestions & Feedback</h2>
            <p class="text-gray-600 mb-4">We'd love to hear your suggestions for new products or improvements!</p>
            <form id="suggestion-form" class="space-y-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="suggestion-name">Name</label>
                    <input type="text" id="suggestion-name" class="w-full border border-gray-300 rounded p-2">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="suggestion-email">Email</label>
                    <input type="email" id="suggestion-email" class="w-full border border-gray-300 rounded p-2">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2" for="suggestion-message">Your Suggestion</label>
                    <textarea id="suggestion-message" rows="4" class="w-full border border-gray-300 rounded p-2"></textarea>
                </div>
                <button type="button" onclick="submitSuggestion()" class="bg-orange-500 text-white font-semibold py-2 px-6 rounded hover:bg-orange-600">
                    Submit Suggestion
                </button>
            </form>
        </div>

                <div id="form-message" class="hidden mb-4 p-4 rounded"></div>
                <div class="mb-8">
                    <div class="flex items-center mb-2">
                        <div class="bg-orange-400 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-semibold">Join Our Communities</h2>
                    </div>
                    <p class="mb-2">Connect with us and other gamers on our Discord communities!</p>
                    <p class="mb-8">
                        <a href="https://discord.gg/NP5jDm33" class="text-orange-500 hover:underline" target="_blank">Call of Duty Mobile Discord</a><br>
                        <a href="https://discord.gg/ECQzhf6Qzx" class="text-orange-500 hover:underline" target="_blank">Mortal Kombat Discord</a>
                    </p>
                </div>
                <hr class="my-8">
                <div class="mb-8">
                    <div class="flex items-center mb-2">
                        <div class="bg-orange-400 rounded-full p-2 mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-semibold">Write to Us</h2>
                    </div>
                    <p class="mb-4">Fill out our form and we will contact you within 24 hours.</p>
                    <p class="mb-8">Emails: <a href="mailto:sales@playpal.com" class="text-orange-500 hover:underline">sales@playpal.com</a></p>
                </div>
                <form id="contact-form">
                    <div class="grid grid-cols-1 gap-6 mb-6">
                        <div>
                            <label class="text-xl font-semibold mb-1 inline-block" for="name">Name <span class="text-red-500">*</span></label>
                            <input type="text" id="name" name="name" placeholder="John" class="w-full border border-gray-300 rounded p-3" required>
                        </div>
                        <div>
                            <label class="text-xl font-semibold mb-1 inline-block" for="email">Email <span class="text-red-500">*</span></label>
                            <input type="email" id="email" name="email" placeholder="John@mysite.com" class="w-full border border-gray-300 rounded p-3" required>
                        </div>
                        <div>
                            <label class="text-xl font-semibold mb-1 inline-block" for="message">Message</label>
                            <textarea id="message" name="message" rows="10" placeholder="Type your text here" class="w-full border border-gray-300 rounded p-3"></textarea>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-orange-500 text-white font-semibold py-4 px-4 rounded text-xl hover:bg-orange-600">Send Message</button>
                </form>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="bg-black text-white py-12 text-center">
        <div class="text-3xl font-bold text-orange-400 mb-4">Playpal.</div>
        <p class="text-gray-300 mb-8 max-w-2xl mx-auto">
            Your go-to digital marketplace for premium in-game accounts, exclusive skins, and gaming gear. We offer secure, instant delivery and a trusted shopping experience for passionate gamers.
        </p>
        <div class="flex justify-center mb-8">
            <input type="email" placeholder="Email" class="px-4 py-2 rounded-l-full bg-gray-800 text-white border-none focus:outline-none" style="width: 200px;">
            <button class="bg-orange-400 text-white px-6 py-2 rounded-r-full font-semibold hover:bg-orange-500">Subscribe</button>
        </div>
        <div class="flex justify-center space-x-16">
            <div>
                <h3 class="text-xl font-bold mb-4">Quick Link</h3>
                <ul class="space-y-2">
                    <li><a href="#" data-section="home" class="text-gray-300 hover:text-orange-400">Home</a></li>
                    <li><a href="#" data-section="about" class="text-gray-300 hover:text-orange-400">About</a></li>
                    <li>
                        <div class="relative">
                            <a href="#" id="footer-games-toggle" class="text-gray-300 hover:text-orange-400 flex items-center" aria-expanded="false" aria-controls="footer-games-menu">Games <i class="fas fa-caret-down ml-1"></i></a>
                            <div class="footer-nested-dropdown" id="footer-games-menu">
                                <a href="?section=products&game=Call of Duty" class="block px-6 py-2 text-gray-300 hover:text-orange-400">Call of Duty</a>
                                <a href="?section=products&game=Mortal Kombat" class="block px-6 py-2 text-gray-300 hover:text-orange-400">Mortal Kombat</a>
                            </div>
                        </div>
                    </li>
                    <li><a href="#" data-section="faqs" class="text-gray-300 hover:text-orange-400">FAQs</a></li>
                    <li><a href="#" data-section="contact" class="text-gray-300 hover:text-orange-400">Contact</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Support</h3>
                <ul class="space-y-2">
                    <li><a href="https://discord.gg/NP5jDm33" class="text-gray-300 hover:text-orange-400" target="_blank">Call of Duty Mobile Discord</a></li>
                    <li><a href="https://discord.gg/ECQzhf6Qzx" class="text-gray-300 hover:text-orange-400" target="_blank">Mortal Kombat Discord</a></li>
                    <li><a href="mailto:sales@playpal.com" class="text-gray-300 hover:text-orange-400">Emails: sales@playpal.com</a></li>
                </ul>
            </div>
        </div>
    </footer>

    <!-- Giveaway Entry Modal -->
    <div id="giveaway-modal" class="modal">
        <div class="modal-content" data-aos="zoom-in">
            <span class="modal-close" onclick="closeGiveawayModal()">×</span>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Enter Giveaway</h2>
            <?php if ($entry_success): ?>
                <p class="bg-green-100 text-green-700 p-4 rounded mb-4"><?php echo htmlspecialchars($entry_success); ?></p>
            <?php endif; ?>
            <?php if (!empty($entry_errors)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <?php foreach ($entry_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="giveaway_id" id="giveaway-id">
                <div class="mb-4">
                    <label for="first_name" class="block text-gray-700 font-semibold mb-2">First Name <span class="text-red-500">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="w-full border border-gray-300 rounded p-3" required>
                </div>
                <div class="mb-4">
                    <label for="last_name" class="block text-gray-700 font-semibold mb-2">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="w-full border border-gray-300 rounded p-3" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-gray-700 font-semibold mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" class="w-full border border-gray-300 rounded p-3" required>
                </div>
                <button type="submit" name="submit_giveaway_entry" class="w-full bg-orange-500 text-white font-semibold py-3 rounded hover:bg-orange-600">Submit Entry</button>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init();

        // Menu Toggle
        const menuToggle = document.getElementById('menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');
        menuToggle.addEventListener('click', () => {
            mobileMenu.parentElement.classList.toggle('menu-open');
        });

        // Games Dropdown Toggle (Mobile)
        const gamesToggle = document.getElementById('games-toggle');
        const gamesMenu = document.getElementById('games-menu');
        gamesToggle.addEventListener('click', (e) => {
            e.preventDefault();
            gamesToggle.parentElement.classList.toggle('nested-open');
            gamesToggle.setAttribute('aria-expanded', gamesToggle.parentElement.classList.contains('nested-open'));
        });

        // Footer Games Dropdown Toggle
        const footerGamesToggle = document.getElementById('footer-games-toggle');
        const footerGamesMenu = document.getElementById('footer-games-menu');
        footerGamesToggle.addEventListener('click', (e) => {
            e.preventDefault();
            footerGamesToggle.parentElement.classList.toggle('footer-nested-open');
            footerGamesToggle.setAttribute('aria-expanded', footerGamesToggle.parentElement.classList.contains('footer-nested-open'));
        });

        // Section Navigation
        document.querySelectorAll('a[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionId = link.getAttribute('data-section');
                document.querySelectorAll('.page-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(sectionId).classList.add('active');
                mobileMenu.parentElement.classList.remove('menu-open');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });

        // FAQ Toggle
        document.querySelectorAll('.faq-item').forEach(item => {
            const header = item.querySelector('.faq-header');
            const content = item.querySelector('.faq-content');
            const icon = item.querySelector('.faq-icon');
            header.addEventListener('click', () => {
                const isOpen = !content.classList.contains('hidden');
                document.querySelectorAll('.faq-content').forEach(c => c.classList.add('hidden'));
                document.querySelectorAll('.faq-icon').forEach(i => i.textContent = '+');
                if (!isOpen) {
                    content.classList.remove('hidden');
                    icon.textContent = '−';
                }
            });
        });

        // Scroll to Top
        document.getElementById('scrollToTop').addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Slider Functionality
function initSlider(sliderId, dotsId) {
    const slider = document.getElementById(sliderId);
    const dots = document.getElementById(dotsId);
    let currentSlide = 0;

    function showSlide(index) {
        const slides = slider.querySelectorAll('.slide, .modal-slide');
        if (slides.length === 0) return; // Exit if no slides
        if (index >= slides.length) currentSlide = 0;
        if (index < 0) currentSlide = slides.length - 1;
        slider.style.transform = `translateX(-${currentSlide * 100}%)`;
        if (dots) {
            dots.querySelectorAll('.dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === currentSlide);
            });
        }
    }

    const slides = slider.querySelectorAll('.slide, .modal-slide');
    if (slides.length > 1) {
        const sliderIdParts = sliderId.split('-');
        const id = sliderIdParts[sliderIdParts.length - 1]; // Extract product ID
        document.querySelectorAll(`[data-slider-id="${id}"], [data-slider-id="modal-${id}"]`).forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.classList.contains('prev')) {
                    currentSlide--;
                } else if (btn.classList.contains('next')) {
                    currentSlide++;
                } else if (btn.classList.contains('dot')) {
                    currentSlide = parseInt(btn.getAttribute('data-slide'));
                }
                showSlide(currentSlide);
            });
        });
    }

    showSlide(currentSlide);
}

// Initialize Sliders
document.querySelectorAll('.slider').forEach(slider => {
    const id = slider.id;
    const isModal = id.startsWith('modal-slider-');
    const productId = id.split('-').pop();
    const dotsId = isModal ? `modal-dots-${productId}` : `dots-${productId}`;
    initSlider(id, dotsId);
});

        // Product Modal - Modified to properly reset form state
document.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', () => {
        const modalId = `modal-${card.getAttribute('data-product-id')}`;
        const modal = document.getElementById(modalId);
        
        // Reset form state
        const form = modal.querySelector('.transaction-form-container');
        if (form) {
            form.querySelector('form').reset();
            form.classList.add('hidden');
        }
        
        // Reset action buttons
        const actionButtons = modal.querySelector('#action-buttons-' + card.getAttribute('data-product-id'));
        if (actionButtons) {
            actionButtons.classList.remove('hidden');
        }
        
        modal.style.display = 'flex';
        AOS.refresh();
    });
});

// Show transaction form
function showTransactionForm(productId, type) {
    const form = document.getElementById(`transaction-form-${productId}`);
    const actionButtons = document.getElementById(`action-buttons-${productId}`);
    const transactionType = document.getElementById(`transaction-type-${productId}`);
    const submitText = document.getElementById(`submit-text-${productId}`);
    
    // Set transaction type and button text
    transactionType.value = type;
    submitText.textContent = type === 'buy' ? 'Buy Now' : 'Rent Now';
    
    // Show form and hide action buttons
    form.classList.remove('hidden');
    actionButtons.classList.add('hidden');
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Hide transaction form and show action buttons
function hideTransactionForm(productId) {
    const form = document.getElementById(`transaction-form-${productId}`);
    const actionButtons = document.getElementById(`action-buttons-${productId}`);
    
    // Reset form
    if (form) {
        form.querySelector('form').reset();
        form.classList.add('hidden');
    }
    
    // Show action buttons
    if (actionButtons) {
        actionButtons.classList.remove('hidden');
        actionButtons.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

// Close Modals - Updated to reset forms
document.querySelectorAll('.modal-close').forEach(closeBtn => {
    closeBtn.addEventListener('click', () => {
        const modal = closeBtn.closest('.modal');
        const productId = modal.id.replace('modal-', '');
        
        // Reset any open forms
        const form = modal.querySelector('.transaction-form-container');
        if (form) {
            form.querySelector('form').reset();
            form.classList.add('hidden');
        }
        
        // Show action buttons if they exist
        const actionButtons = modal.querySelector('#action-buttons-' + productId);
        if (actionButtons) {
            actionButtons.classList.remove('hidden');
        }
        
        modal.style.display = 'none';
    });
});

        // Giveaway Modal Functions
        function openGiveawayModal(giveawayId) {
            document.getElementById('giveaway-id').value = giveawayId;
            document.getElementById('giveaway-modal').style.display = 'flex';
            AOS.refresh();
        }

        function closeGiveawayModal() {
            document.getElementById('giveaway-modal').style.display = 'none';
        }

        // Contact Form Submission
        document.getElementById('contact-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const formData = new FormData(form);
            const messageDiv = document.getElementById('form-message');

            try {
                const response = await fetch('/submit-contact', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                messageDiv.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700');
                if (result.success) {
                    messageDiv.classList.add('bg-green-100', 'text-green-700');
                    messageDiv.textContent = 'Your message has been sent successfully!';
                    form.reset();
                } else {
                    messageDiv.classList.add('bg-red-100', 'text-red-700');
                    messageDiv.textContent = result.message || 'An error occurred. Please try again.';
                }
            } catch (error) {
                messageDiv.classList.remove('hidden');
                messageDiv.classList.add('bg-red-100', 'text-red-700');
                messageDiv.textContent = 'An error occurred. Please try again.';
            }
        });
        function showTransactionForm(productId, type) {
    const form = document.getElementById(`transaction-form-${productId}`);
    const actionButtons = document.getElementById(`action-buttons-${productId}`);
    const transactionType = document.getElementById(`transaction-type-${productId}`);
    const submitText = document.getElementById(`submit-text-${productId}`);
    
    // Set transaction type and button text
    transactionType.value = type;
    submitText.textContent = type === 'buy' ? 'Buy Now' : 'Rent Now';
    
    // Show form and hide action buttons
    form.classList.remove('hidden');
    actionButtons.classList.add('hidden');
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function submitSuggestion() {
    const name = document.getElementById('suggestion-name').value;
    const email = document.getElementById('suggestion-email').value;
    const message = document.getElementById('suggestion-message').value;
    
    if (!name || !email || !message) {
        alert('Please fill in all fields');
        return;
    }
    
    // Send to server
    fetch('submit_suggestion.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&message=${encodeURIComponent(message)}`
    })
    .then(response => response.text())
    .then(data => {
        alert('Thank you for your suggestion! We appreciate your feedback.');
        document.getElementById('suggestion-form').reset();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('There was an error submitting your suggestion. Please try again.');
    });
}

// Countdown function
function initializeCountdown() {
    const countdownElements = document.querySelectorAll('.countdown-timer');
    
    countdownElements.forEach(element => {
        const endDate = new Date(element.getAttribute('data-end-date'));
        
        function updateCountdown() {
            const now = new Date();
            const diff = endDate - now;
            
            if (diff <= 0) {
                element.textContent = "00d:00h:00m:00s";
                return;
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            element.textContent = 
                `${String(days).padStart(2, '0')}d:${String(hours).padStart(2, '0')}h:${String(minutes).padStart(2, '0')}m:${String(seconds).padStart(2, '0')}s`;
        }
        
        // Initial update
        updateCountdown();
        
        // Update every second
        setInterval(updateCountdown, 1000);
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeCountdown();
    // ... your other initialization code ...
});
// Show transaction form
function showTransactionForm(productId, type) {
    const form = document.getElementById(`transaction-form-${productId}`);
    const actionButtons = document.getElementById(`action-buttons-${productId}`);
    const transactionType = document.getElementById(`transaction-type-${productId}`);
    const submitText = document.getElementById(`submit-text-${productId}`);
    
    // Set transaction type and button text
    transactionType.value = type;
    submitText.textContent = type === 'buy' ? 'Buy Now' : 'Rent Now';
    
    // Show form and hide action buttons
    form.classList.remove('hidden');
    actionButtons.classList.add('hidden');
    
    // Scroll to form
    form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Hide transaction form and show action buttons
function hideTransactionForm(productId) {
    const form = document.getElementById(`transaction-form-${productId}`);
    const actionButtons = document.getElementById(`action-buttons-${productId}`);
    
    // Reset form
    if (form) {
        form.querySelector('form').reset();
        form.classList.add('hidden');
    }
    
    // Show action buttons
    if (actionButtons) {
        actionButtons.classList.remove('hidden');
        actionButtons.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
    </script>
</body>
</html>