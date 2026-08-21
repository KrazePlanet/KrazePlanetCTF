<?php
require_once __DIR__ . '/config/db.php';

// Fetch settings
$settings = $pdo->query("SELECT * FROM restaurant_settings WHERE id = 1")->fetch() ?: [
    'restaurant_name' => 'Buffet Box Cloud Kitchen',
    'tagline' => 'Artisanal Flavors & Express Gourmet Dining',
    'currency' => '$',
    'phone' => '+1 (555) 890-FOOD',
    'address' => '742 Culinary Boulevard, Suite 400'
];

// Fetch categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();

// Fetch all menu items
$menu_items = $pdo->query("
    SELECT m.*, c.name AS category_name, c.slug AS category_slug 
    FROM menu_items m 
    JOIN categories c ON m.category_id = c.id 
    WHERE m.is_available = 1 
    ORDER BY c.display_order ASC, m.is_featured DESC, m.name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['restaurant_name']) ?> — Digital Menu</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #f95724;
            --primary-hover: #e04413;
            --primary-light: #fff2ed;
            --dark: #121826;
            --gray: #64748b;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --veg: #10b981;
            --non-veg: #ef4444;
            --radius: 16px;
            --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.03);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--light-bg);
            color: var(--dark);
            min-height: 100vh;
            padding-bottom: 90px;
        }

        /* Top Navigation */
        header {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--primary), #ff8a65);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(249, 87, 36, 0.3);
        }

        .logo-text h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.1;
            color: var(--dark);
        }

        .logo-text span {
            font-size: 11px;
            color: var(--gray);
            font-weight: 500;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .table-pill {
            background: var(--light-bg);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-trigger {
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(249, 87, 36, 0.35);
        }

        .cart-trigger:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .cart-count {
            background: #ffffff;
            color: var(--primary);
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 800;
        }

        /* Banner Hero */
        .hero-banner {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: #ffffff;
            padding: 35px 20px;
            text-align: center;
            border-radius: 0 0 24px 24px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .hero-banner::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(249,87,36,0.2) 0%, rgba(249,87,36,0) 70%);
            border-radius: 50%;
        }

        .hero-banner h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .hero-banner p {
            color: #94a3b8;
            font-size: 15px;
            max-width: 600px;
            margin: 0 auto 20px;
        }

        .search-bar-wrap {
            max-width: 550px;
            margin: 0 auto;
            position: relative;
        }

        .search-bar-wrap input {
            width: 100%;
            padding: 14px 20px 14px 46px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.1);
            color: #ffffff;
            font-size: 15px;
            backdrop-filter: blur(10px);
            outline: none;
            transition: all 0.2s;
        }

        .search-bar-wrap input::placeholder {
            color: #94a3b8;
        }

        .search-bar-wrap input:focus {
            background: #ffffff;
            color: var(--dark);
            border-color: #ffffff;
        }

        .search-bar-wrap input:focus + i {
            color: var(--primary);
        }

        .search-bar-wrap i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
        }

        /* Container Layout */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Category Nav */
        .category-scroll {
            display: flex;
            gap: 12px;
            overflow-x: auto;
            padding-bottom: 15px;
            margin-bottom: 25px;
            scrollbar-width: none;
        }

        .category-scroll::-webkit-scrollbar {
            display: none;
        }

        .cat-btn {
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 10px 18px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .cat-btn.active, .cat-btn:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(249, 87, 36, 0.3);
        }

        /* Filter Pills */
        .filter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-tags {
            display: flex;
            gap: 10px;
        }

        .tag-pill {
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            color: var(--gray);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .tag-pill.active {
            background: var(--dark);
            color: #ffffff;
            border-color: var(--dark);
        }

        /* Grid */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        /* Card */
        .menu-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .card-img-wrap {
            position: relative;
            width: 100%;
            height: 180px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .card-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .menu-card:hover .card-img-wrap img {
            transform: scale(1.05);
        }

        .badge-diet {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #ffffff;
            backdrop-filter: blur(4px);
        }

        .badge-veg { background: rgba(16, 185, 129, 0.9); }
        .badge-non-veg { background: rgba(239, 68, 68, 0.9); }

        .badge-spicy {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border-radius: 20px;
            padding: 4px 8px;
            font-size: 11px;
            font-weight: 700;
        }

        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .category-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--primary);
        }

        .card-rating {
            font-size: 12px;
            font-weight: 700;
            color: #f59e0b;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .item-title {
            font-family: 'Outfit', sans-serif;
            font-size: 17px;
            font-weight: 700;
            margin-bottom: 6px;
            color: var(--dark);
            line-height: 1.3;
        }

        .item-desc {
            font-size: 13px;
            color: var(--gray);
            line-height: 1.5;
            margin-bottom: 14px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
            margin-top: auto;
        }

        .price-wrap {
            display: flex;
            align-items: baseline;
            gap: 6px;
        }

        .main-price {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
        }

        .strike-price {
            font-size: 14px;
            color: #94a3b8;
            text-decoration: line-through;
        }

        .add-btn {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid rgba(249, 87, 36, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .add-btn:hover {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(249, 87, 36, 0.3);
        }

        /* Cart Drawer (Slide-Over) */
        .cart-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .cart-overlay.open {
            opacity: 1;
            visibility: visible;
        }

        .cart-drawer {
            position: fixed;
            top: 0;
            right: -450px;
            width: 100%;
            max-width: 420px;
            height: 100%;
            background: #ffffff;
            z-index: 1000;
            box-shadow: -10px 0 30px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .cart-overlay.open .cart-drawer {
            right: 0;
        }

        .cart-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .cart-header h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 700;
        }

        .close-cart {
            background: var(--light-bg);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--gray);
        }

        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }

        .cart-item-info h4 {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .cart-item-info span {
            font-size: 13px;
            color: var(--primary);
            font-weight: 700;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--light-bg);
            padding: 4px 8px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .qty-btn {
            background: none;
            border: none;
            width: 24px;
            height: 24px;
            font-size: 14px;
            font-weight: bold;
            color: var(--dark);
            cursor: pointer;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .cart-footer {
            padding: 20px;
            border-top: 1px solid var(--border);
            background: #fafafa;
        }

        .bill-row {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 8px;
        }

        .bill-row.total {
            font-size: 18px;
            font-weight: 800;
            color: var(--dark);
            border-top: 1px dashed var(--border);
            padding-top: 10px;
            margin-top: 10px;
        }

        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 12px;
            outline: none;
        }

        .form-input:focus {
            border-color: var(--primary);
        }

        .checkout-btn {
            width: 100%;
            background: var(--primary);
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(249, 87, 36, 0.4);
            transition: background 0.2s;
        }

        .checkout-btn:hover {
            background: var(--primary-hover);
        }

        /* Order Success Modal */
        .modal-success {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: #ffffff;
            border-radius: 20px;
            max-width: 440px;
            width: 100%;
            padding: 30px;
            text-align: center;
            animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes popIn {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-icon {
            width: 70px;
            height: 70px;
            background: #ecfdf5;
            color: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 16px;
        }

        .ticket-box {
            background: var(--light-bg);
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            text-align: left;
        }

        /* Footer */
        footer {
            margin-top: 60px;
            border-top: 1px solid var(--border);
            padding: 25px 20px;
            background: #ffffff;
            text-align: center;
            color: var(--gray);
            font-size: 13px;
        }

        footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo-area">
                <div class="logo-icon"><i class="fas fa-utensils"></i></div>
                <div class="logo-text">
                    <h1><?= htmlspecialchars($settings['restaurant_name']) ?></h1>
                    <span><?= htmlspecialchars($settings['tagline']) ?></span>
                </div>
            </a>
            
            <div class="header-actions">
                <div class="table-pill">
                    <i class="fas fa-chair text-primary" style="color: var(--primary);"></i>
                    <select id="tableSelector" style="border:none; background:transparent; font-weight:700; outline:none; cursor:pointer;">
                        <option value="Table 1">Table 1</option>
                        <option value="Table 2">Table 2</option>
                        <option value="Table 3">Table 3</option>
                        <option value="Table 4" selected>Table 4</option>
                        <option value="Table 5">Table 5</option>
                        <option value="Takeaway">Takeaway Order</option>
                    </select>
                </div>

                <button class="cart-trigger" onclick="toggleCart()">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Cart</span>
                    <span class="cart-count" id="cartCount">0</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Search -->
    <div class="hero-banner">
        <h2>What are you craving today?</h2>
        <p>Explore freshly curated recipes from our executive kitchen, delivered directly to your dining table or doorstep.</p>
        <div class="search-bar-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="menuSearch" placeholder="Search dishes, burgers, appetizers, desserts..." onkeyup="filterMenu()">
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="main-container">

        <!-- Category Horizontal Scroll -->
        <div class="category-scroll">
            <button class="cat-btn active" onclick="selectCategory('all', this)">
                <i class="fas fa-border-all"></i> All Items
            </button>
            <?php foreach ($categories as $cat): ?>
                <button class="cat-btn" onclick="selectCategory('<?= $cat['slug'] ?>', this)">
                    <i class="<?= htmlspecialchars($cat['icon']) ?>"></i> <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Filter Row -->
        <div class="filter-row">
            <div class="filter-tags">
                <button class="tag-pill active" onclick="setDietFilter('all', this)"><i class="fas fa-check"></i> All</button>
                <button class="tag-pill" onclick="setDietFilter('veg', this)"><span style="color:#10b981;">●</span> Veg Only</button>
                <button class="tag-pill" onclick="setDietFilter('nonveg', this)"><span style="color:#ef4444;">●</span> Non-Veg</button>
                <button class="tag-pill" onclick="setDietFilter('spicy', this)">🌶️ Spicy</button>
            </div>
            <div style="font-size:13px; color:var(--gray); font-weight:600;">
                Showing <span id="itemCount" style="color:var(--dark); font-weight:800;"><?= count($menu_items) ?></span> items
            </div>
        </div>

        <!-- Menu Cards Grid -->
        <div class="menu-grid" id="menuGrid">
            <?php foreach ($menu_items as $item): ?>
                <div class="menu-card" 
                     data-name="<?= strtolower(htmlspecialchars($item['name'])) ?>"
                     data-category="<?= htmlspecialchars($item['category_slug']) ?>"
                     data-veg="<?= $item['is_veg'] ?>"
                     data-spicy="<?= $item['is_spicy'] ?>"
                     data-desc="<?= strtolower(htmlspecialchars($item['description'])) ?>">
                    
                    <div class="card-img-wrap">
                        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" loading="lazy">
                        
                        <?php if ($item['is_veg']): ?>
                            <span class="badge-diet badge-veg">🌱 VEG</span>
                        <?php else: ?>
                            <span class="badge-diet badge-non-veg">🍗 NON-VEG</span>
                        <?php endif; ?>

                        <?php if ($item['is_spicy']): ?>
                            <span class="badge-spicy">🌶️ Spicy</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <div class="card-meta">
                            <span class="category-label"><?= htmlspecialchars($item['category_name']) ?></span>
                            <span class="card-rating"><i class="fas fa-star"></i> <?= number_format($item['rating'], 1) ?> (<?= $item['rating_count'] ?>)</span>
                        </div>

                        <h3 class="item-title"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="item-desc"><?= htmlspecialchars($item['description']) ?></p>

                        <div class="card-footer">
                            <div class="price-wrap">
                                <?php $eff_price = $item['discount_price'] ?? $item['price']; ?>
                                <span class="main-price"><?= $settings['currency'] . number_format($eff_price, 2) ?></span>
                                <?php if (!empty($item['discount_price'])): ?>
                                    <span class="strike-price"><?= $settings['currency'] . number_format($item['price'], 2) ?></span>
                                <?php endif; ?>
                            </div>

                            <button class="add-btn" onclick="addToCart(<?= $item['id'] ?>, '<?= addslashes(htmlspecialchars($item['name'])) ?>', <?= (float)$eff_price ?>, '<?= htmlspecialchars($item['image']) ?>')">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cart Slide-Over Drawer -->
    <div class="cart-overlay" id="cartOverlay" onclick="closeCartOnBg(event)">
        <div class="cart-drawer">
            <div class="cart-header">
                <h3><i class="fas fa-receipt" style="color:var(--primary);"></i> Your Order Ticket</h3>
                <button class="close-cart" onclick="toggleCart()"><i class="fas fa-times"></i></button>
            </div>

            <div class="cart-items" id="cartItemsList">
                <!-- Dynamically Rendered -->
            </div>

            <div class="cart-footer">
                <input type="text" id="custName" class="form-input" placeholder="Your Name (e.g. John Doe)" value="Guest Diner">
                <input type="tel" id="custPhone" class="form-input" placeholder="Phone Number (e.g. +1 555 123 4567)">
                <input type="text" id="custNotes" class="form-input" placeholder="Special Requests (e.g. No onions, extra dip)">

                <div class="bill-row">
                    <span>Subtotal</span>
                    <span id="billSubtotal">$0.00</span>
                </div>
                <div class="bill-row">
                    <span>Kitchen Tax (5%)</span>
                    <span id="billTax">$0.00</span>
                </div>
                <div class="bill-row total">
                    <span>Total Amount</span>
                    <span id="billTotal" style="color:var(--primary);">$0.00</span>
                </div>

                <button class="checkout-btn" onclick="submitOrder()">
                    <i class="fas fa-paper-plane"></i> Send to Kitchen
                </button>
            </div>
        </div>
    </div>

    <!-- Order Success Modal -->
    <div class="modal-success" id="successModal">
        <div class="modal-content">
            <div class="success-icon"><i class="fas fa-check-circle"></i></div>
            <h3 style="font-family:'Outfit',sans-serif; font-size:24px; margin-bottom:8px;">Order Placed!</h3>
            <p style="color:var(--gray); font-size:14px;">The kitchen has received your order and is preparing it now.</p>
            
            <div class="ticket-box">
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <strong>Order Token:</strong>
                    <span id="resOrderCode" style="color:var(--primary); font-weight:800;">ORD-0000</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                    <strong>Table / Location:</strong>
                    <span id="resTable">Table 4</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <strong>Total Charged:</strong>
                    <span id="resTotal" style="font-weight:800;">$0.00</span>
                </div>
            </div>

            <button class="checkout-btn" onclick="closeSuccessModal()">
                Continue Browsing Menu
            </button>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>© 2026 <?= htmlspecialchars($settings['restaurant_name']) ?> — All rights reserved.</p>
        <p style="margin-top:6px;">
            <a href="admin/login.php"><i class="fas fa-lock"></i> Kitchen Display & POS Admin Panel</a>
        </p>
    </footer>

    <!-- Interactive JS Application -->
    <script>
        let cart = [];
        let currentCategory = 'all';
        let currentDiet = 'all';

        function addToCart(id, name, price, img) {
            const existing = cart.find(item => item.id === id);
            if (existing) {
                existing.qty++;
            } else {
                cart.push({ id, name, price, img, qty: 1 });
            }
            updateCartUI();
            
            // Visual feedback
            const countElem = document.getElementById('cartCount');
            countElem.style.transform = 'scale(1.3)';
            setTimeout(() => { countElem.style.transform = 'scale(1)'; }, 200);
        }

        function updateCartQty(id, delta) {
            const item = cart.find(i => i.id === id);
            if (item) {
                item.qty += delta;
                if (item.qty <= 0) {
                    cart = cart.filter(i => i.id !== id);
                }
            }
            updateCartUI();
        }

        function updateCartUI() {
            const countElem = document.getElementById('cartCount');
            const listElem = document.getElementById('cartItemsList');
            const subtotalElem = document.getElementById('billSubtotal');
            const taxElem = document.getElementById('billTax');
            const totalElem = document.getElementById('billTotal');

            const totalQty = cart.reduce((sum, item) => sum + item.qty, 0);
            countElem.innerText = totalQty;

            if (cart.length === 0) {
                listElem.innerHTML = `
                    <div style="text-align:center; padding:50px 20px; color:var(--gray);">
                        <i class="fas fa-shopping-basket" style="font-size:48px; color:#cbd5e1; margin-bottom:12px;"></i>
                        <p style="font-weight:600;">Your cart is empty.</p>
                        <span style="font-size:13px;">Add delicious dishes from the menu to start ordering!</span>
                    </div>
                `;
                subtotalElem.innerText = '$0.00';
                taxElem.innerText = '$0.00';
                totalElem.innerText = '$0.00';
                return;
            }

            let subtotal = 0;
            let html = '';
            cart.forEach(item => {
                const itemSub = item.price * item.qty;
                subtotal += itemSub;
                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.name}</h4>
                            <span>$${item.price.toFixed(2)} x ${item.qty} = $${itemSub.toFixed(2)}</span>
                        </div>
                        <div class="qty-controls">
                            <button class="qty-btn" onclick="updateCartQty(${item.id}, -1)">−</button>
                            <span style="font-weight:bold; font-size:13px; min-width:16px; text-align:center;">${item.qty}</span>
                            <button class="qty-btn" onclick="updateCartQty(${item.id}, 1)">+</button>
                        </div>
                    </div>
                `;
            });

            const tax = subtotal * 0.05;
            const grandTotal = subtotal + tax;

            listElem.innerHTML = html;
            subtotalElem.innerText = '$' + subtotal.toFixed(2);
            taxElem.innerText = '$' + tax.toFixed(2);
            totalElem.innerText = '$' + grandTotal.toFixed(2);
        }

        function toggleCart() {
            document.getElementById('cartOverlay').classList.toggle('open');
        }

        function closeCartOnBg(e) {
            if (e.target.id === 'cartOverlay') {
                toggleCart();
            }
        }

        function selectCategory(slug, btn) {
            currentCategory = slug;
            document.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterMenu();
        }

        function setDietFilter(type, btn) {
            currentDiet = type;
            document.querySelectorAll('.tag-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filterMenu();
        }

        function filterMenu() {
            const query = document.getElementById('menuSearch').value.toLowerCase().trim();
            const cards = document.querySelectorAll('.menu-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const desc = card.getAttribute('data-desc');
                const cat = card.getAttribute('data-category');
                const isVeg = card.getAttribute('data-veg') === '1';
                const isSpicy = card.getAttribute('data-spicy') === '1';

                const matchesQuery = name.includes(query) || desc.includes(query);
                const matchesCat = (currentCategory === 'all' || cat === currentCategory);
                
                let matchesDiet = true;
                if (currentDiet === 'veg') matchesDiet = isVeg;
                if (currentDiet === 'nonveg') matchesDiet = !isVeg;
                if (currentDiet === 'spicy') matchesDiet = isSpicy;

                if (matchesQuery && matchesCat && matchesDiet) {
                    card.style.display = 'flex';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            document.getElementById('itemCount').innerText = visibleCount;
        }

        function submitOrder() {
            if (cart.length === 0) {
                alert('Please add items to your cart first.');
                return;
            }

            const table = document.getElementById('tableSelector').value;
            const name = document.getElementById('custName').value.trim() || 'Guest Diner';
            const phone = document.getElementById('custPhone').value.trim() || 'N/A';
            const notes = document.getElementById('custNotes').value.trim();

            const payload = {
                table_number: table,
                customer_name: name,
                customer_phone: phone,
                special_instructions: notes,
                items: cart
            };

            fetch('api/place_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('resOrderCode').innerText = data.order_code;
                    document.getElementById('resTable').innerText = data.table;
                    document.getElementById('resTotal').innerText = '$' + data.total;
                    
                    cart = [];
                    updateCartUI();
                    toggleCart();
                    document.getElementById('successModal').style.display = 'flex';
                } else {
                    alert(data.message || 'Error creating order.');
                }
            })
            .catch(err => {
                alert('Server connection error. Please try again.');
            });
        }

        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }
    </script>
</body>
</html>
