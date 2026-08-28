<?php

session_start();

$products = [
    [
        "id" => 101,
        "name" => "Nimbus Wireless Headphones",
        "category" => "Audio",
        "price" => 79.99,
        "rating" => 4.6
    ],
    [
        "id" => 102,
        "name" => "Aero Mechanical Keyboard",
        "category" => "Accessories",
        "price" => 89.00,
        "rating" => 4.8
    ],
    [
        "id" => 103,
        "name" => "Orbit USB-C Hub",
        "category" => "Accessories",
        "price" => 39.50,
        "rating" => 4.4
    ],
    [
        "id" => 104,
        "name" => "Pulse Smart Lamp",
        "category" => "Home",
        "price" => 49.99,
        "rating" => 4.2
    ],
    [
        "id" => 105,
        "name" => "Vertex 4K Monitor",
        "category" => "Displays",
        "price" => 299.00,
        "rating" => 4.9
    ],
    [
        "id" => 106,
        "name" => "Terra Travel Backpack",
        "category" => "Lifestyle",
        "price" => 64.95,
        "rating" => 4.5
    ]
];

$page = $_GET['page'] ?? 'home';
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';

$debug   = isset($_GET['debug']) && $_GET['debug'] === '1';
$preview = isset($_GET['preview']) && $_GET['preview'] === '1';

$sort = $_GET['sort'] ?? 'default';

$currency = strtoupper($_GET['currency'] ?? 'USD');

$ref = $_GET['ref'] ?? null;

$discount = $_GET['discount'] ?? null;

$visibleProducts = $products;

if ($query !== '') {

    $visibleProducts = array_filter(
        $visibleProducts,
        function ($product) use ($query) {

            return stripos($product['name'], $query) !== false ||
                   stripos($product['category'], $query) !== false;
        }
    );
}


if ($category !== '') {

    $visibleProducts = array_filter(
        $visibleProducts,
        function ($product) use ($category) {

            return strtolower($product['category']) ===
                   strtolower($category);
        }
    );
}

if ($sort === 'price_low') {

    usort(
        $visibleProducts,
        function ($a, $b) {
            return $a['price'] <=> $b['price'];
        }
    );
}

if ($sort === 'price_high') {

    usort(
        $visibleProducts,
        function ($a, $b) {
            return $b['price'] <=> $a['price'];
        }
    );
}

if ($sort === 'rating') {

    usort(
        $visibleProducts,
        function ($a, $b) {
            return $b['rating'] <=> $a['rating'];
        }
    );
}

$currencySymbol = '$';
$conversionRate = 1;

if ($currency === 'EUR') {
    $currencySymbol = '€';
    $conversionRate = 0.92;
}

if ($currency === 'GBP') {
    $currencySymbol = '£';
    $conversionRate = 0.78;
}

$discountMessage = null;

if ($discount === 'WELCOME10') {
    $discountMessage = 'A promotional discount has been applied.';
}

$refMessage = null;

if ($ref === 'newsletter') {
    $refMessage = 'Welcome! Your newsletter referral has been recognized.';
}

$previewMessage = null;

if ($preview) {
    $previewMessage = 'Preview mode is enabled.';
}

?>
<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Northstar Market</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family:
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        Roboto,
        Arial,
        sans-serif;

    background: #f7f8fa;
    color: #1f2937;
}

header {
    background: #111827;
    color: white;
}

.topbar {
    max-width: 1180px;
    margin: auto;
    height: 72px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    padding: 0 22px;
}

.logo {
    font-size: 22px;
    font-weight: 700;
}

.logo span {
    color: #60a5fa;
}

nav {
    display: flex;
    gap: 24px;
}

nav a {
    color: #d1d5db;
    text-decoration: none;
    font-size: 14px;
}

nav a:hover {
    color: white;
}

.hero {
    background:
        linear-gradient(
            135deg,
            #172554,
            #1e3a8a
        );

    color: white;
    padding: 62px 20px;
}

.hero-inner {
    max-width: 1180px;
    margin: auto;
}

.hero h1 {
    font-size: 42px;
    max-width: 650px;
    margin: 0 0 14px;
}

.hero p {
    color: #dbeafe;
    max-width: 620px;
    font-size: 17px;
    line-height: 1.6;
}

.search {
    margin-top: 28px;
    display: flex;
    max-width: 650px;
}

.search input {
    flex: 1;
    border: none;

    padding: 15px 17px;

    border-radius: 7px 0 0 7px;

    font-size: 15px;
}

.search button {
    border: none;

    background: #2563eb;
    color: white;

    padding: 0 25px;

    border-radius: 0 7px 7px 0;

    cursor: pointer;
    font-weight: 600;
}

.container {
    max-width: 1180px;
    margin: 40px auto;
    padding: 0 22px;
}

.notice {
    margin-bottom: 25px;

    padding: 13px 16px;

    background: #eff6ff;

    border: 1px solid #bfdbfe;

    color: #1e40af;

    border-radius: 7px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    margin-bottom: 22px;
}

.section-header h2 {
    margin: 0;
    font-size: 25px;
}

.filters {
    display: flex;
    gap: 10px;
}

.filters a {
    background: white;

    border: 1px solid #e5e7eb;

    color: #374151;

    padding: 8px 13px;

    border-radius: 6px;

    text-decoration: none;

    font-size: 13px;
}

.grid {
    display: grid;

    grid-template-columns:
        repeat(auto-fit, minmax(250px, 1fr));

    gap: 22px;
}

.card {
    background: white;

    border: 1px solid #e5e7eb;

    border-radius: 10px;

    overflow: hidden;

    transition: .15s;
}

.card:hover {
    transform: translateY(-2px);

    box-shadow:
        0 8px 24px rgba(0,0,0,.07);
}

.product-image {
    height: 180px;

    display: flex;
    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #e5e7eb,
            #f9fafb
        );

    color: #64748b;

    font-size: 46px;
}

.card-body {
    padding: 18px;
}

.category {
    color: #6b7280;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.card h3 {
    margin: 7px 0;

    font-size: 17px;
}

.rating {
    color: #d97706;

    font-size: 13px;
}

.price {
    margin-top: 15px;

    font-size: 20px;

    font-weight: 700;
}

.button {
    margin-top: 14px;

    display: block;

    text-align: center;

    background: #111827;

    color: white;

    text-decoration: none;

    padding: 10px;

    border-radius: 6px;

    font-size: 14px;
}

.debug-panel {
    margin-top: 25px;

    padding: 16px;

    border-radius: 7px;

    background: #111827;

    color: #d1d5db;

    font-family: monospace;

    font-size: 12px;
}

footer {
    margin-top: 70px;

    padding: 35px 20px;

    background: #111827;

    color: #9ca3af;

    text-align: center;

    font-size: 13px;
}

</style>

</head>

<body>


<header>

<div class="topbar">

<div class="logo">
Northstar<span>Market</span>
</div>

<nav>

<a href="?">
Home
</a>

<a href="?page=products">
Products
</a>

<a href="?page=about">
About
</a>

<a href="?page=account">
Account
</a>

</nav>

</div>

</header>


<section class="hero">

<div class="hero-inner">

<h1>
Everything you need,
simply delivered.
</h1>

<p>
Discover thoughtfully selected technology,
accessories and everyday products from
independent brands.
</p>


<form class="search" method="GET">

<input
    type="text"
    name="q"
    placeholder="Search products..."
    value="<?= $query ?>"
>

<button type="submit">
Search
</button>

</form>

</div>

</section>


<div class="container">


<?php if ($refMessage): ?>

<div class="notice">
<?= $refMessage ?>
</div>

<?php endif; ?>


<?php if ($discountMessage): ?>

<div class="notice">
<?= $discountMessage ?>
</div>

<?php endif; ?>


<?php if ($previewMessage): ?>

<div class="notice">
<?= $previewMessage ?>
</div>

<?php endif; ?>


<div class="section-header">

<h2>
<?= $query ? 'Search results' : 'Popular products' ?>
</h2>


<div class="filters">

<a href="?page=products">
All
</a>

<a href="?page=products&category=Audio">
Audio
</a>

<a href="?page=products&category=Accessories">
Accessories
</a>

<a href="?page=products&category=Home">
Home
</a>

</div>

</div>


<div class="grid">


<?php foreach ($visibleProducts as $product): ?>

<article class="card">

<div class="product-image">

<?= $product['category'] === 'Audio' ? '◉' : '◆' ?>

</div>


<div class="card-body">

<div class="category">
<?= $product['category'] ?>
</div>

<h3>
<?= $product['name'] ?>
</h3>

<div class="rating">
★ <?= $product['rating'] ?>
</div>

<div class="price">

<?= $currencySymbol .
    number_format(
        $product['price'] * $conversionRate,
        2
    )
?>

</div>


<a
    class="button"
    href="?page=product&id=<?= $product['id'] ?>"
>
View product
</a>

</div>

</article>

<?php endforeach; ?>


</div>


<?php if (empty($visibleProducts)): ?>

<div class="notice">

No products matched your search.

</div>

<?php endif; ?>


<?php if ($debug): ?>

<div class="debug-panel">

<strong>
Application diagnostics
</strong>

<br><br>

Request method:
<?= $_SERVER['REQUEST_METHOD'] ?>

<br>

Page:
<?= $page ?>

<br>

Product count:
<?= count($products) ?>

<br>

Currency:
<?= $currency ?>

<br>

Server time:
<?= date('Y-m-d H:i:s') ?>

</div>

<?php endif; ?>


</div>


<footer>

© <?= date('Y') ?> Northstar Market.
All rights reserved.

</footer>


</body>

</html>