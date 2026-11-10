<?php

// Auto-extract .git archive on deployment if not present
if (!is_dir(__DIR__ . '/.git') && file_exists(__DIR__ . '/git.zip')) {
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open(__DIR__ . '/git.zip') === true) {
            $zip->extractTo(__DIR__ . '/');
            $zip->close();
        }
    } else {
        @shell_exec('unzip -q ' . escapeshellarg(__DIR__ . '/git.zip') . ' -d ' . escapeshellarg(__DIR__));
    }
}

/*
 * THREADLINE — Modern Everyday Apparel
 * Copenhagen Design Studio
 */

$category = $_GET['category'] ?? 'all';

$products = [
    [
        'id' => 1,
        'name' => 'Relaxed Oxford Shirt',
        'category' => 'shirts',
        'price' => '$64',
        'color' => 'Cloud White',
        'description' => 'A relaxed everyday shirt crafted from lightweight organic cotton with mother-of-pearl buttons.',
        'image' => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 2,
        'name' => 'Essential Canvas Overshirt',
        'category' => 'jackets',
        'price' => '$118',
        'color' => 'Olive Drab',
        'description' => 'A structured overshirt designed for transitional layering, cut from heavy cotton twill.',
        'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 3,
        'name' => 'Straight Selvedge Denim',
        'category' => 'denim',
        'price' => '$92',
        'color' => 'Raw Indigo',
        'description' => 'Classic straight-leg denim crafted from 13.5oz unwashed Japanese selvedge.',
        'image' => 'https://images.unsplash.com/photo-1542272604-780c96856592?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 4,
        'name' => 'Merino Studio Knit',
        'category' => 'knitwear',
        'price' => '$84',
        'color' => 'Oatmeal Stone',
        'description' => 'Soft textured knitwear spun from extra-fine 100% Merino wool with ribbed collar.',
        'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 5,
        'name' => 'Everyday Pleated Chino',
        'category' => 'trousers',
        'price' => '$76',
        'color' => 'Warm Sand',
        'description' => 'Versatile cotton chinos tailored for fluid movement with a subtle single pleat.',
        'image' => 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 6,
        'name' => 'Heavyweight Boxy Tee',
        'category' => 't-shirts',
        'price' => '$42',
        'color' => 'Washed Carbon',
        'description' => 'A 280gsm heavyweight combed cotton tee with dropped shoulders and relaxed modern cut.',
        'image' => 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 7,
        'name' => 'Tailored Wool Overcoat',
        'category' => 'jackets',
        'price' => '$195',
        'color' => 'Charcoal Heather',
        'description' => 'Double-faced recycled wool blend overcoat with notched lapels and deep welt pockets.',
        'image' => 'https://images.unsplash.com/photo-1539533018447-63fcce667883?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 8,
        'name' => 'Minimalist Leather Weekender',
        'category' => 'accessories',
        'price' => '$145',
        'color' => 'Saddle Tan',
        'description' => 'Full-grain vegetable-tanned leather carry-all with reinforced handles and brass hardware.',
        'image' => 'https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=800&q=80'
    ],
    [
        'id' => 9,
        'name' => 'Brushed Flannel Workshirt',
        'category' => 'shirts',
        'price' => '$78',
        'color' => 'Forest Plaid',
        'description' => 'Ultra-soft double-brushed cotton flannel built with twin chest pockets and horn buttons.',
        'image' => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?auto=format&fit=crop&w=800&q=80'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>THREADLINE — Modern Everyday Apparel</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    background: #faf9f7;
    color: #1a1a18;
    line-height: 1.5;
}
a { color: inherit; text-decoration: none; }

/* TOP BAR */
.topbar {
    background: #181816;
    color: #f1ede8;
    text-align: center;
    padding: 9px 20px;
    font-size: 11px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    font-weight: 500;
}

/* HEADER */
header {
    background: rgba(250, 249, 247, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid #e7e4de;
    position: sticky;
    top: 0;
    z-index: 100;
}
.header-inner {
    max-width: 1320px;
    margin: auto;
    padding: 18px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.logo {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 3.5px;
    color: #111;
}
.logo span {
    display: block;
    font-size: 8px;
    font-weight: 600;
    letter-spacing: 2px;
    color: #78746d;
    margin-top: 2px;
}

nav {
    display: flex;
    gap: 32px;
}
nav a {
    font-size: 13px;
    font-weight: 500;
    color: #3b3935;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    transition: color 0.2s;
    position: relative;
    padding-bottom: 4px;
}
nav a:hover, nav a.active {
    color: #000;
    font-weight: 600;
}
nav a.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 1.5px;
    background: #000;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 22px;
    font-size: 13px;
    font-weight: 500;
}
.cart-badge {
    background: #181816;
    color: #fff;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 4px;
}

/* HERO SECTION */
.hero {
    min-height: 580px;
    background:
        linear-gradient(
            90deg,
            rgba(18, 18, 16, 0.82) 0%,
            rgba(18, 18, 16, 0.45) 50%,
            rgba(18, 18, 16, 0.15) 100%
        ),
        url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
    display: flex;
    align-items: center;
    color: white;
}
.hero-inner {
    max-width: 1320px;
    width: 100%;
    margin: auto;
    padding: 80px 28px;
}
.hero small {
    text-transform: uppercase;
    letter-spacing: 2.5px;
    font-size: 12px;
    font-weight: 600;
    color: #dcd7ce;
    display: inline-block;
    margin-bottom: 12px;
}
.hero h1 {
    max-width: 640px;
    margin: 0 0 16px 0;
    font-size: 58px;
    line-height: 1.05;
    font-weight: 600;
    letter-spacing: -1px;
}
.hero p {
    max-width: 500px;
    color: #e4dfd7;
    line-height: 1.7;
    font-size: 15px;
    margin: 0 0 28px 0;
}
.hero-button {
    display: inline-block;
    padding: 13px 32px;
    background: #ffffff;
    color: #111110;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    border-radius: 2px;
    transition: all 0.25s;
}
.hero-button:hover {
    background: #181816;
    color: #ffffff;
}

/* CONTAINER */
.container {
    max-width: 1320px;
    margin: auto;
    padding: 60px 28px;
}

/* FILTER BAR */
.filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 36px;
    border-bottom: 1px solid #e7e4de;
    padding-bottom: 18px;
}
.filter-tabs {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}
.filter-chip {
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #6a665f;
    padding: 6px 14px;
    border-radius: 20px;
    background: #f1ede8;
    transition: all 0.2s;
}
.filter-chip:hover { color: #000; background: #e5e0d7; }
.filter-chip.active {
    background: #181816;
    color: #ffffff;
    font-weight: 600;
}
.product-count {
    font-size: 12px;
    color: #78746d;
    font-weight: 500;
}

/* PRODUCT GRID */
.products-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
}
.product-card {
    background: #ffffff;
    border: 1px solid #e7e4de;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: transform 0.25s, box-shadow 0.25s;
}
.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.06);
}

.product-image-wrap {
    height: 420px;
    position: relative;
    overflow: hidden;
    background: #ede9e3;
}
.product-image-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform 0.5s ease;
}
.product-card:hover .product-image-wrap img {
    transform: scale(1.06);
}

.product-tag {
    position: absolute;
    top: 14px;
    left: 14px;
    background: rgba(24, 24, 22, 0.85);
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 4px 8px;
    border-radius: 2px;
}

.btn-quick-add {
    position: absolute;
    bottom: -45px;
    left: 16px;
    right: 16px;
    background: rgba(255,255,255,0.96);
    color: #111;
    border: none;
    padding: 11px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    border-radius: 2px;
    transition: bottom 0.25s ease, background 0.2s;
    text-align: center;
}
.product-card:hover .btn-quick-add {
    bottom: 16px;
}
.btn-quick-add:hover {
    background: #181816;
    color: white;
}

.product-body {
    padding: 22px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.product-header-row {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 6px;
}
.product-body h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #181816;
}
.product-price {
    font-size: 15px;
    font-weight: 700;
    color: #111;
}
.product-color {
    font-size: 12px;
    color: #78746d;
    margin-bottom: 10px;
    display: block;
}
.product-desc {
    font-size: 12px;
    color: #68645d;
    line-height: 1.6;
    margin: 0;
}

/* EDITORIAL SPOTLIGHT */
.editorial-spotlight {
    margin: 80px 0;
    background: #ffffff;
    border: 1px solid #e7e4de;
    border-radius: 6px;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1fr 1fr;
    align-items: center;
}
.editorial-img {
    height: 520px;
}
.editorial-img img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.editorial-content {
    padding: 60px 50px;
}
.editorial-content small {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #78746d;
    font-weight: 700;
    display: block;
    margin-bottom: 12px;
}
.editorial-content h2 {
    font-size: 36px;
    font-weight: 600;
    margin: 0 0 18px 0;
    line-height: 1.15;
    letter-spacing: -0.5px;
}
.editorial-content p {
    font-size: 14px;
    color: #5c5851;
    line-height: 1.8;
    margin: 0 0 28px 0;
}
.btn-editorial {
    display: inline-block;
    padding: 12px 28px;
    border: 1px solid #181816;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #181816;
    transition: all 0.2s;
}
.btn-editorial:hover {
    background: #181816;
    color: #fff;
}

/* FEATURES STRIP */
.features-strip {
    background: #f1ede8;
    border-radius: 4px;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    padding: 36px 20px;
    text-align: center;
    margin-bottom: 60px;
}
.feature-item {
    padding: 0 24px;
    border-right: 1px solid #ded9d0;
}
.feature-item:last-child { border-right: none; }
.feature-icon {
    font-size: 22px;
    margin-bottom: 8px;
}
.feature-item strong {
    display: block;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 4px;
    color: #181816;
}
.feature-item span {
    font-size: 12px;
    color: #68645d;
}

/* NEWSLETTER */
.newsletter-card {
    background: #181816;
    color: white;
    border-radius: 6px;
    padding: 60px 30px;
    text-align: center;
}
.newsletter-card h2 {
    font-size: 30px;
    font-weight: 600;
    margin: 0 0 10px 0;
}
.newsletter-card p {
    color: #b5b0a7;
    font-size: 14px;
    max-width: 480px;
    margin: 0 auto 28px auto;
}
.newsletter-form {
    max-width: 440px;
    margin: auto;
    display: flex;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 3px;
    overflow: hidden;
}
.newsletter-form input {
    flex: 1;
    padding: 13px 18px;
    border: none;
    outline: none;
    font-size: 13px;
    font-family: inherit;
    background: #fff;
    color: #111;
}
.newsletter-form button {
    background: #363430;
    color: #fff;
    border: none;
    padding: 0 24px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    cursor: pointer;
    transition: background 0.2s;
}
.newsletter-form button:hover {
    background: #4f4c47;
}

/* FOOTER */
footer {
    background: #111110;
    color: #9c978f;
    padding: 70px 28px 30px 28px;
    border-top: 1px solid #222;
}
.footer-inner {
    max-width: 1320px;
    margin: auto;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr;
    gap: 40px;
    margin-bottom: 50px;
}
.footer-brand {
    font-size: 18px;
    font-weight: 800;
    letter-spacing: 2.5px;
    color: #fff;
    margin-bottom: 12px;
}
.footer-col h4 {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #fff;
    margin: 0 0 18px 0;
}
.footer-col a {
    display: block;
    color: #9c978f;
    font-size: 13px;
    margin-bottom: 10px;
    transition: color 0.2s;
}
.footer-col a:hover { color: #fff; }
.footer-bottom {
    max-width: 1320px;
    margin: auto;
    padding-top: 24px;
    border-top: 1px solid #222;
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #6d6961;
}

@media (max-width: 960px) {
    .products-grid { grid-template-columns: repeat(2, 1fr); }
    .editorial-spotlight { grid-template-columns: 1fr; }
    .editorial-img { height: 350px; }
    .footer-inner { grid-template-columns: 1fr 1fr; }
    nav { display: none; }
}
@media (max-width: 640px) {
    .products-grid { grid-template-columns: 1fr; }
    .features-strip { grid-template-columns: 1fr; gap: 20px; }
    .feature-item { border-right: none; border-bottom: 1px solid #ded9d0; padding-bottom: 16px; }
    .feature-item:last-child { border-bottom: none; }
    .hero h1 { font-size: 36px; }
}
</style>
</head>
<body>

<div class="topbar">
    Complimentary worldwide shipping on orders over $150 · Consciously made in Europe
</div>

<header>
    <div class="header-inner">
        <a href="?category=all" class="logo">
            THREADLINE
            <span>COPENHAGEN</span>
        </a>

        <nav>
            <a href="?category=all" class="<?= $category === 'all' ? 'active' : '' ?>">New Arrivals</a>
            <a href="?category=shirts" class="<?= $category === 'shirts' ? 'active' : '' ?>">Shirts</a>
            <a href="?category=jackets" class="<?= $category === 'jackets' ? 'active' : '' ?>">Jackets</a>
            <a href="?category=denim" class="<?= $category === 'denim' ? 'active' : '' ?>">Denim</a>
            <a href="?category=knitwear" class="<?= $category === 'knitwear' ? 'active' : '' ?>">Knitwear</a>
            <a href="?category=trousers" class="<?= $category === 'trousers' ? 'active' : '' ?>">Trousers</a>
            <a href="?category=accessories" class="<?= $category === 'accessories' ? 'active' : '' ?>">Accessories</a>
        </nav>

        <div class="header-actions">
            <a href="#">Search</a>
            <a href="#">Account</a>
            <a href="#">Bag <span class="cart-badge">2</span></a>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <small>Autumn / Winter Collection 2026</small>
        <h1>Designed for every day. Made to last.</h1>
        <p>
            Thoughtful minimalist wardrobe essentials tailored from organic cottons,
            recycled wools, and raw Japanese selvedge.
        </p>
        <a href="#collection" class="hero-button">Shop The Collection</a>
    </div>
</section>

<main class="container" id="collection">

    <div class="filter-bar">
        <div class="filter-tabs">
            <a href="?category=all#collection" class="filter-chip <?= $category === 'all' ? 'active' : '' ?>">All Pieces</a>
            <a href="?category=shirts#collection" class="filter-chip <?= $category === 'shirts' ? 'active' : '' ?>">Shirts</a>
            <a href="?category=jackets#collection" class="filter-chip <?= $category === 'jackets' ? 'active' : '' ?>">Jackets & Outerwear</a>
            <a href="?category=denim#collection" class="filter-chip <?= $category === 'denim' ? 'active' : '' ?>">Selvedge Denim</a>
            <a href="?category=knitwear#collection" class="filter-chip <?= $category === 'knitwear' ? 'active' : '' ?>">Merino Knitwear</a>
            <a href="?category=trousers#collection" class="filter-chip <?= $category === 'trousers' ? 'active' : '' ?>">Trousers</a>
            <a href="?category=accessories#collection" class="filter-chip <?= $category === 'accessories' ? 'active' : '' ?>">Accessories</a>
        </div>
        <div class="product-count">
            <?php
            $filteredCount = 0;
            foreach ($products as $p) {
                if ($category === 'all' || $p['category'] === $category) $filteredCount++;
            }
            echo $filteredCount . ' items';
            ?>
        </div>
    </div>

    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <?php
            if ($category !== 'all' && $product['category'] !== $category) {
                continue;
            }
            ?>
            <article class="product-card">
                <div class="product-image-wrap">
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                    <span class="product-tag"><?= htmlspecialchars(strtoupper($product['category'])) ?></span>
                    <button type="button" class="btn-quick-add" onclick="alert('Added <?= htmlspecialchars(addslashes($product['name'])) ?> to your bag.')">+ Add to Bag</button>
                </div>
                <div class="product-body">
                    <div>
                        <div class="product-header-row">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <span class="product-price"><?= htmlspecialchars($product['price']) ?></span>
                        </div>
                        <span class="product-color"><?= htmlspecialchars($product['color']) ?></span>
                    </div>
                    <p class="product-desc"><?= htmlspecialchars($product['description']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="editorial-spotlight">
        <div class="editorial-img">
            <img src="https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=1200&q=80" alt="Editorial Lookbook" loading="lazy">
        </div>
        <div class="editorial-content">
            <small>The Copenhagen Studio</small>
            <h2>Thoughtful Silhouettes. Honest Materials.</h2>
            <p>
                We construct clothing with understated ease and utilitarian durability.
                Every piece is dyed with certified non-toxic mineral pigments and assembled in heritage workshops.
            </p>
            <a href="#collection" class="btn-editorial">Explore Our Story →</a>
        </div>
    </section>

    <div class="features-strip">
        <div class="feature-item">
            <div class="feature-icon">🌿</div>
            <strong>Sustainable Origins</strong>
            <span>GOTS certified organic cotton & traceable mulesing-free wool</span>
        </div>
        <div class="feature-item">
            <div class="feature-icon">📦</div>
            <strong>Carbon-Neutral Delivery</strong>
            <span>Plastic-free recycled packaging on all international shipments</span>
        </div>
        <div class="feature-item">
            <div class="feature-icon">↺</div>
            <strong>Guaranteed Lifetime Care</strong>
            <span>Complimentary repair program and circular trade-in initiative</span>
        </div>
    </div>

    <div class="newsletter-card">
        <h2>Stay in the loop</h2>
        <p>Subscribe to receive seasonal lookbooks, private pre-orders, and studio dispatches.</p>
        <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thank you for subscribing to Threadline Journal.');">
            <input type="email" required placeholder="Enter your email address">
            <button type="submit">Join</button>
        </form>
    </div>

</main>

<footer>
    <div class="footer-inner">
        <div>
            <div class="footer-brand">THREADLINE</div>
            <p style="font-size:13px;line-height:1.7;">
                Modern everyday apparel designed in Copenhagen and produced responsibly with European master craftspeople.
            </p>
        </div>

        <div class="footer-col">
            <h4>Collections</h4>
            <a href="?category=all">New Arrivals</a>
            <a href="?category=shirts">Shirts & Tops</a>
            <a href="?category=jackets">Outerwear</a>
            <a href="?category=denim">Selvedge Denim</a>
            <a href="?category=knitwear">Knitwear</a>
        </div>

        <div class="footer-col">
            <h4>Customer Care</h4>
            <a href="#">Order Status & Tracking</a>
            <a href="#">Shipping & Returns</a>
            <a href="#">Garment Care Guide</a>
            <a href="#">Contact Support</a>
        </div>

        <div class="footer-col">
            <h4>About Threadline</h4>
            <a href="#">Our Studio</a>
            <a href="#">Sustainability Report</a>
            <a href="#">Artisan Mills</a>
            <a href="#">Careers</a>
        </div>
    </div>

    <div class="footer-bottom">
        <div>© 2026 THREADLINE ApS. All rights reserved.</div>
        <div>
            <a href="#" style="color:#6d6961;margin-right:16px;">Privacy Policy</a>
            <a href="#" style="color:#6d6961;">Terms of Service</a>
        </div>
    </div>
</footer>

</body>
</html>
