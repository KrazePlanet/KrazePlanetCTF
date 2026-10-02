<?php
// STITCHLY — modern clothing boutique
// A realistic e-commerce site used for hands-on web-security practice.
// Students register real accounts, browse the catalog, add items to their cart,
// and check out with a shipping address — exactly like a real clothing store.
//
// The intended flaw (classic price-tampering business logic bug): the product
// page's "Add to Cart" form carries the item's price in a hidden field so the
// server doesn't have to look it up again. The server then trusts that hidden
// field completely instead of re-reading the authoritative price from its own
// product catalog. A shopper who intercepts the add-to-cart request (DevTools /
// Burp Suite) and edits the hidden price field can add a $189 coat to their cart
// for $1.00 — and every later step (cart, checkout, order total) just carries
// that tampered number forward, because nothing ever re-validates it against
// the catalog.

session_start();

$db_host     = getenv('DB_HOST') ?: 'localhost';
$db_username = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASS') ?: '';
$db_name     = getenv('DB_NAME') ?: 'KrazePlanet';

mysqli_report(MYSQLI_REPORT_OFF);
$db = @new mysqli($db_host, $db_username, $db_password);
if ($db->connect_error) { die('Service temporarily unavailable.'); }
$db->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$db->select_db($db_name);
$db->set_charset('utf8mb4');

$db->query("CREATE TABLE IF NOT EXISTS cl_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    address_line1 VARCHAR(160) DEFAULT '',
    city VARCHAR(80) DEFAULT '',
    state VARCHAR(10) DEFAULT '',
    zip VARCHAR(15) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS cl_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(64) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(400) DEFAULT '',
    price_cents INT NOT NULL,
    category VARCHAR(40) NOT NULL DEFAULT 'Men',
    color VARCHAR(30) DEFAULT '',
    sizes VARCHAR(60) NOT NULL DEFAULT 'S,M,L,XL',
    image_url VARCHAR(255) DEFAULT '',
    icon VARCHAR(10) DEFAULT '',
    brand VARCHAR(60) DEFAULT '',
    rating DECIMAL(2,1) DEFAULT 4.0,
    mrp_cents INT DEFAULT 0,
    INDEX idx_category (category),
    UNIQUE KEY uniq_name (name)
)") or die('init error');
$db->query("ALTER TABLE cl_products ADD COLUMN IF NOT EXISTS color VARCHAR(30) DEFAULT ''");
$db->query("ALTER TABLE cl_products ADD COLUMN IF NOT EXISTS icon VARCHAR(10) DEFAULT ''");
$db->query("ALTER TABLE cl_products ADD COLUMN IF NOT EXISTS brand VARCHAR(60) DEFAULT ''");
$db->query("ALTER TABLE cl_products ADD COLUMN IF NOT EXISTS rating DECIMAL(2,1) DEFAULT 4.0");
$db->query("ALTER TABLE cl_products ADD COLUMN IF NOT EXISTS mrp_cents INT DEFAULT 0");

$db->query("CREATE TABLE IF NOT EXISTS cl_cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    size VARCHAR(10) NOT NULL DEFAULT 'M',
    qty INT NOT NULL DEFAULT 1,
    unit_price_cents INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

$db->query("CREATE TABLE IF NOT EXISTS cl_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_uuid VARCHAR(64) NOT NULL,
    items_json TEXT NOT NULL,
    subtotal_cents INT NOT NULL,
    shipping_cents INT NOT NULL DEFAULT 800,
    total_cents INT NOT NULL,
    ship_name VARCHAR(150) NOT NULL,
    ship_address VARCHAR(200) NOT NULL,
    ship_city VARCHAR(80) NOT NULL,
    ship_state VARCHAR(10) NOT NULL,
    ship_zip VARCHAR(15) NOT NULL,
    phone VARCHAR(30) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)") or die('init error');

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money($cents) { return '$' . number_format(((int)$cents) / 100, 2); }
function discount_pct($price, $mrp) { $mrp=(int)$mrp; $price=(int)$price; if ($mrp <= $price) return 0; return (int)round((1 - $price/$mrp) * 100); }
$PLACEHOLDER_GRADIENTS = [
    'linear-gradient(135deg,#f3ede2,#e8ddc7)',
    'linear-gradient(135deg,#e8f0ec,#d3e3da)',
    'linear-gradient(135deg,#f0e8e2,#e3d3c7)',
    'linear-gradient(135deg,#e8e4f0,#d3cbe3)',
    'linear-gradient(135deg,#f0ece2,#e0d5c0)',
    'linear-gradient(135deg,#e4ece8,#cfe0d6)',
];
function render_thumb($p, $alt = null, $class = '') {
    global $PLACEHOLDER_GRADIENTS;
    $alt = $alt ?? $p['name'];
    if (!empty($p['image_url'])) {
        // DummyJSON shots are transparent product cut-outs — contain-fit them on a
        // light backdrop (like a real product tile); Unsplash lifestyle shots cover-fill.
        $isProductShot = strpos($p['image_url'], 'dummyjson.com') !== false;
        $cls = trim($class . ($isProductShot ? ' pshot' : ''));
        echo '<img class="' . esc($cls) . '" src="' . esc($p['image_url']) . '" alt="' . esc($alt) . '" loading="lazy">';
        return;
    }
    $grad = $PLACEHOLDER_GRADIENTS[(int)$p['id'] % count($PLACEHOLDER_GRADIENTS)];
    echo '<div class="ph-tile ' . esc($class) . '" style="background:' . esc($grad) . ';">'
       . '<span class="ph-icon">' . esc($p['icon'] ?: '🏷️') . '</span>'
       . '<span class="ph-label">No photo yet</span>'
       . '</div>';
}
function uuidv4() {
    $d = random_bytes(16);
    $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
    $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d), 4));
}
function first_name($name) { $p = preg_split('/\s+/', trim($name)); return $p[0] ?: $name; }
function avatar_letter($name) { return strtoupper(mb_substr(trim($name), 0, 1) ?: 'U'); }

// ── Seed catalog (authoritative prices) ──────────────────────────────────────────
// Every row below is backed by its own individually-verified, distinct real photo —
// no product shares an image with another. (An earlier version of this catalog
// multiplied one photo across 17 fake "color" labels; that looked exactly as
// broken as it was and has been removed entirely.)
$products = [
    ['Classic Oxford Shirt', 'Crisp cotton oxford, tailored fit, mother-of-pearl buttons.', 5800, 'Men', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&fit=crop&q=80'],
    ['Striped Dress Shirt', 'Cotton poplin dress shirt with a spread collar.', 6400, 'Men', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1620012253295-c15cc3e65df4?w=600&fit=crop&q=80'],
    ['Navy Print Shirt', 'Micro-print button-down, tailored through the body.', 5600, 'Men', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1563630423918-b58f07336ac9?w=600&fit=crop&q=80'],
    ['Chambray Heart Print Shirt', 'Lightweight chambray shirt with allover heart print.', 4800, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&fit=crop&q=80'],
    ['Vintage Wash Denim Jacket', 'Stonewashed denim trucker jacket with embroidered detail.', 8900, 'Unisex', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1601333144130-8cbb312386b6?w=600&fit=crop&q=80'],
    ['Red Silk Evening Gown', 'Floor-length silk gown with a flowing pleated skirt.', 14500, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600&fit=crop&q=80'],
    ['Essential Crewneck Sweater', 'Heavyweight brushed-cotton crewneck, relaxed fit.', 5200, 'Unisex', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600&fit=crop&q=80'],
    ['Pullover Hoodie', 'Fleece-lined hoodie with kangaroo pocket.', 6400, 'Unisex', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&fit=crop&q=80'],
    ['Orange Pullover Hoodie', 'Heavyweight fleece hoodie in a bold colorway.', 6200, 'Unisex', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1509942774463-acf339cf87d5?w=600&fit=crop&q=80'],
    ['Slim Fit Chinos', 'Stretch cotton twill chinos, tapered leg.', 6800, 'Men', '28,30,32,34,36', 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?w=600&fit=crop&q=80'],
    ['Camel Belted Wool Coat', 'Wool-blend wrap coat with a self-tie belt.', 18900, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1539533018447-63fcce2678e3?w=600&fit=crop&q=80'],
    ['Leather Dress Belt', 'Full-grain leather belt with a polished buckle.', 3400, 'Accessories', '30,32,34,36,38', 'https://images.unsplash.com/photo-1624222247344-550fb60583dc?w=600&fit=crop&q=80'],
    ['Classic Low-Top Sneakers', 'Canvas and leather sneakers with cushioned sole.', 9500, 'Unisex', '6,7,8,9,10,11', 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&fit=crop&q=80'],
    ['White Trucker Cap', 'Structured five-panel cap with mesh back.', 2200, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600&fit=crop&q=80'],
    ['Distressed Boyfriend Jeans', 'Relaxed high-rise jeans with hand-distressing.', 7200, 'Women', '24,26,28,30,32', 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?w=600&fit=crop&q=80'],
    ['Skinny Jeans', 'Mid-rise stretch denim, tapered through the ankle.', 6800, 'Women', '24,26,28,30,32', 'https://images.unsplash.com/photo-1582552938357-32b906df40cb?w=600&fit=crop&q=80'],
    ['Pleated Midi Skirt', 'Lightweight pleated skirt that moves with you.', 5600, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1577900232427-18219b9166a0?w=600&fit=crop&q=80'],
    ['Wrap Midi Skirt', 'Windowpane check wrap skirt with tie waist.', 7400, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1550639525-c97d455acf70?w=600&fit=crop&q=80'],
    ['Classic White Tee', 'Soft-washed 100% cotton crewneck tee.', 2800, 'Unisex', 'XS,S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600&fit=crop&q=80'],
    ['Tie-Dye Button-Down Shirt', 'Hand-dyed viscose shirt, relaxed collar.', 6400, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1626497764746-6dc36546b388?w=600&fit=crop&q=80'],
    ['Windowpane Three-Piece Suit', 'Wool-blend suit jacket, vest, and trousers.', 32000, 'Men', '36,38,40,42,44', 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&fit=crop&q=80'],
    ['Formal Blazer', 'Single-breasted wool-blend blazer, notch lapel.', 14500, 'Men', '36,38,40,42,44', 'https://images.unsplash.com/photo-1598808503746-f34c53b9323e?w=600&fit=crop&q=80'],
    ['Denim Shorts', 'High-rise distressed denim shorts with raw hem.', 4400, 'Women', '24,26,28,30,32', 'https://images.unsplash.com/photo-1591195853828-11db59a44f6b?w=600&fit=crop&q=80'],
    ['Relaxed Pull-On Trousers', 'Elastic-waist woven trousers, tapered fit.', 5800, 'Women', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1506629082955-511b1aa562c8?w=600&fit=crop&q=80'],
    ['Blush Jogger Pants', 'Satin-feel joggers with an elastic cuff.', 5800, 'Women', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?w=600&fit=crop&q=80'],
    ['Graphic Print Tee', 'Heavyweight cotton tee with front graphic print.', 3200, 'Unisex', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1503341504253-dff4815485f1?w=600&fit=crop&q=80'],
    ['Graphic Logo Tee', 'Cotton tee with a bold front logo print.', 3400, 'Unisex', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1583743814966-8936f5b7be1a?w=600&fit=crop&q=80'],
    ['Circle Logo Tee', 'Soft cotton tee with a chest logo graphic.', 3000, 'Unisex', 'S,M,L,XL,XXL', 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=600&fit=crop&q=80'],
    ['Corduroy-Collar Trucker Jacket', 'Raw denim jacket with a contrast corduroy collar.', 9400, 'Unisex', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1611312449408-fcece27cdbb7?w=600&fit=crop&q=80'],
    ['Rust Bomber Jacket', 'Lightweight bomber with ribbed cuffs and hem.', 9800, 'Unisex', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?w=600&fit=crop&q=80'],
    ['Quilted Puffer Jacket', 'Insulated puffer jacket with a full-zip front.', 11000, 'Unisex', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1548126032-079a0fb0099d?w=600&fit=crop&q=80'],
    ['Donut Patch Denim Jacket', 'Light-wash denim jacket with novelty patches.', 9200, 'Unisex', 'XS,S,M,L,XL', 'https://images.unsplash.com/photo-1495105787522-5334e3ffa0ef?w=600&fit=crop&q=80'],
    ['White Eyelet Sundress', 'Off-shoulder cotton eyelet dress with tiered hem.', 7800, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?w=600&fit=crop&q=80'],
    ['Floral Fit-and-Flare Dress', 'Sleeveless floral dress with a full skirt.', 9200, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1572804013309-59a88b7e92f1?w=600&fit=crop&q=80'],
    ['Coral Maxi Dress', 'Flowing jersey maxi dress with flutter sleeves.', 8800, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1596783074918-c84cb06531ca?w=600&fit=crop&q=80'],
    ['Denim Shirt Dress', 'Button-front denim dress with a fitted waist.', 8200, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1591369822096-ffd140ec948f?w=600&fit=crop&q=80'],
    ['Chunky Knit Sweater Dress', 'Cable-knit sweater dress in heavyweight yarn.', 8600, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1580331451062-99ff652288d7?w=600&fit=crop&q=80'],
    ['Animal Print Blouse', 'Relaxed short-sleeve blouse in animal print.', 5800, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1614251055880-ee96e4803393?w=600&fit=crop&q=80'],
    ['Tie-Neck Blouse', 'Crepe blouse with a self-tie neck detail.', 5200, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1583846717393-dc2412c95ed7?w=600&fit=crop&q=80'],
    ['Embroidered Kaftan Dress', 'Floor-length embroidered kaftan with sheer overlay.', 21000, 'Women', 'XS,S,M,L', 'https://images.unsplash.com/photo-1571908599407-cdb918ed83bf?w=600&fit=crop&q=80'],
    ['Silk Necktie', 'Woven silk necktie, 3-inch blade.', 3800, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1589756823695-278bc923f962?w=600&fit=crop&q=80'],
    ['Wayfarer Sunglasses', 'Acetate frame sunglasses with UV400 lenses.', 4600, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=600&fit=crop&q=80'],
    ['Leather Bifold Wallet', 'Full-grain leather bifold with card slots.', 4200, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=600&fit=crop&q=80'],
    ['Everyday Backpack', 'Water-resistant backpack with laptop sleeve.', 6800, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&fit=crop&q=80'],
    ['Leather Strap Watch', 'Stainless case watch on a leather strap.', 17500, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=600&fit=crop&q=80'],
    ['Statement Drop Earrings', 'Crystal statement earrings with vintage-style settings.', 3600, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=600&fit=crop&q=80'],
    ['Layered Pendant Necklace', 'Two-strand chain necklace with gemstone pendants.', 4200, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?w=600&fit=crop&q=80'],
    ['Leather Lace-Up Boots', 'Full-grain leather work boots, rubber sole.', 14800, 'Men', '7,8,9,10,11,12', 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?w=600&fit=crop&q=80'],
    ['Two-Strap Sandals', 'Adjustable buckle sandals with cork footbed.', 5800, 'Unisex', '6,7,8,9,10,11', 'https://images.unsplash.com/photo-1603487742131-4160ec999306?w=600&fit=crop&q=80'],
    ['Novelty Print Socks', 'Combed cotton crew socks, allover print.', 1400, 'Accessories', 'One Size', 'https://images.unsplash.com/photo-1586350977771-b3b0abd50c82?w=600&fit=crop&q=80'],
    ['Brown Wingtip Oxfords', 'Leather wingtip dress shoes, leather sole.', 16500, 'Men', '7,8,9,10,11,12', 'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=600&fit=crop&q=80'],
    ['Monk-Strap Dress Shoes', 'Double monk-strap leather dress shoes.', 13500, 'Men', '7,8,9,10,11,12', 'https://images.unsplash.com/photo-1533867617858-e7b97e060509?w=600&fit=crop&q=80'],
    ['Floral Pointed Pumps', 'Satin pointed-toe pumps in floral print.', 9800, 'Women', '5,6,7,8,9,10', 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=600&fit=crop&q=80'],
    ['Embellished Evening Heels', 'Crystal-embellished mesh evening heels.', 11800, 'Women', '5,6,7,8,9,10', 'https://images.unsplash.com/photo-1518049362265-d5b2a6467637?w=600&fit=crop&q=80'],
];

// ── Additional real-photo products from the DummyJSON demo catalog ───────────────
// DummyJSON is a free, purpose-built demo API that hosts real product photos for
// developers to populate practice stores. These 30 are its generic, no-visible-
// brand fashion items (branded ones like Nike/Rolex/Prada were filtered out and
// every survivor was reviewed by eye). Each carries a real MRP, rating, and price.
// Format: [name, description, price_cents, mrp_cents, rating, category, sizes, brand, image_url]
$djProducts = [
    ['Blue & Black Check Shirt', 'The Blue & Black Check Shirt is a stylish and comfortable men\'s shirt featuring a classic check pattern.', 2999, 3543, 3.6, 'Men', 'S,M,L,XL,XXL', 'Stitchly Essentials', 'https://cdn.dummyjson.com/product-images/mens-shirts/blue-&-black-check-shirt/thumbnail.webp'],
    ['Men\'s Plaid Flannel Shirt', 'The Man Plaid Shirt is a timeless and versatile men\'s shirt with a classic plaid pattern.', 3499, 4347, 3.7, 'Men', 'S,M,L,XL,XXL', 'Nova Row', 'https://cdn.dummyjson.com/product-images/mens-shirts/man-plaid-shirt/thumbnail.webp'],
    ['Tropical Print Short-Sleeve Shirt', 'The Man Short Sleeve Shirt is a breezy and stylish option for warm days.', 1999, 2146, 3.8, 'Men', 'S,M,L,XL,XXL', 'Maison Clover', 'https://cdn.dummyjson.com/product-images/mens-shirts/man-short-sleeve-shirt/thumbnail.webp'],
    ['Men\'s Check Overshirt', 'The Men Check Shirt is a classic and versatile shirt featuring a stylish check pattern.', 2799, 3158, 3.9, 'Men', 'S,M,L,XL,XXL', 'Oakridge', 'https://cdn.dummyjson.com/product-images/mens-shirts/men-check-shirt/thumbnail.webp'],
    ['Brown Leather Strap Watch', 'The Brown Leather Belt Watch is a stylish timepiece with a classic design.', 8999, 9572, 4.2, 'Accessories', 'One Size', 'Vela', 'https://cdn.dummyjson.com/product-images/mens-watches/brown-leather-belt-watch/thumbnail.webp'],
    ['Floral Print Evening Gown', 'The Black Women\'s Gown is an elegant and timeless evening gown.', 12999, 14521, 3.6, 'Women', 'XS,S,M,L', 'Ember & Co', 'https://cdn.dummyjson.com/product-images/womens-dresses/black-women%27s-gown/thumbnail.webp'],
    ['Red Corset Maxi Dress', 'The Corset Leather With Skirt is a bold and edgy ensemble that combines a stylish corset with a matching skirt.', 8999, 10746, 4.2, 'Women', 'XS,S,M,L', 'Northbound', 'https://cdn.dummyjson.com/product-images/womens-dresses/corset-leather-with-skirt/thumbnail.webp'],
    ['Black Corset Midi Dress', 'The Corset With Black Skirt is a chic and versatile outfit that pairs a fashionable corset with a classic black skirt.', 7999, 9417, 4.5, 'Women', 'XS,S,M,L', 'Lumen', 'https://cdn.dummyjson.com/product-images/womens-dresses/corset-with-black-skirt/thumbnail.webp'],
    ['Polka Dot Party Dress', 'The Dress Pea is a stylish and comfortable dress with a pea pattern.', 4999, 6073, 4.9, 'Women', 'XS,S,M,L', 'Wildroot', 'https://cdn.dummyjson.com/product-images/womens-dresses/dress-pea/thumbnail.webp'],
    ['Two-Tone Pointed Heels', 'The Black & Brown Slipper is a comfortable and stylish choice for casual wear.', 1999, 2068, 3.8, 'Women', '5,6,7,8,9,10', 'Marlowe', 'https://cdn.dummyjson.com/product-images/womens-shoes/black-&-brown-slipper/thumbnail.webp'],
    ['Golden Pointed Heels', 'The Golden Shoes for Women are a glamorous choice for special occasions.', 4999, 5808, 3.9, 'Women', '5,6,7,8,9,10', 'Stitchly Essentials', 'https://cdn.dummyjson.com/product-images/womens-shoes/golden-shoes-woman/thumbnail.webp'],
    ['Red Ballet Flats', 'The Red Shoes make a bold statement with their vibrant red color.', 3499, 4251, 4.0, 'Women', '5,6,7,8,9,10', 'Nova Row', 'https://cdn.dummyjson.com/product-images/womens-shoes/red-shoes/thumbnail.webp'],
    ['Gold-Tone Women\'s Watch', 'The Gold Women\'s Watch is a stunning accessory that combines luxury and style.', 79999, 97966, 4.2, 'Accessories', 'One Size', 'Maison Clover', 'https://cdn.dummyjson.com/product-images/womens-watches/watch-gold-for-women/thumbnail.webp'],
    ['Silver Minimalist Watch', 'The Women\'s Wrist Watch is a versatile and fashionable timepiece for everyday wear.', 12999, 14873, 4.2, 'Accessories', 'One Size', 'Oakridge', 'https://cdn.dummyjson.com/product-images/womens-watches/women%27s-wrist-watch/thumbnail.webp'],
    ['Blue Structured Handbag', 'The Blue Women\'s Handbag is a stylish and spacious accessory for everyday use.', 4999, 6087, 3.6, 'Accessories', 'One Size', 'Vela', 'https://cdn.dummyjson.com/product-images/womens-bags/blue-women%27s-handbag/thumbnail.webp'],
    ['White Faux-Leather Backpack', 'The White Faux Leather Backpack is a trendy and practical backpack for the modern woman.', 3999, 4716, 3.7, 'Accessories', 'One Size', 'Ember & Co', 'https://cdn.dummyjson.com/product-images/womens-bags/white-faux-leather-backpack/thumbnail.webp'],
    ['Black Structured Handbag', 'The Women Handbag in Black is a classic and versatile accessory that complements various outfits.', 5999, 6789, 3.8, 'Accessories', 'One Size', 'Northbound', 'https://cdn.dummyjson.com/product-images/womens-bags/women-handbag-black/thumbnail.webp'],
    ['Green Crystal Drop Earrings', 'The Green Crystal Earring is a dazzling accessory that features a vibrant green crystal.', 2999, 3538, 4.0, 'Accessories', 'One Size', 'Lumen', 'https://cdn.dummyjson.com/product-images/womens-jewellery/green-crystal-earring/thumbnail.webp'],
    ['Green Oval Drop Earrings', 'The Green Oval Earring is a stylish and versatile accessory with a unique oval shape.', 2499, 2946, 3.6, 'Accessories', 'One Size', 'Wildroot', 'https://cdn.dummyjson.com/product-images/womens-jewellery/green-oval-earring/thumbnail.webp'],
    ['Tropical Leaf Earrings', 'The Tropical Earring is a fun and playful accessory inspired by tropical elements.', 1999, 2014, 4.4, 'Accessories', 'One Size', 'Marlowe', 'https://cdn.dummyjson.com/product-images/womens-jewellery/tropical-earring/thumbnail.webp'],
    ['Blue Polka Dot Dress', 'The Blue Frock is a charming and stylish dress for various occasions.', 2999, 3413, 4.2, 'Women', 'XS,S,M,L', 'Stitchly Essentials', 'https://cdn.dummyjson.com/product-images/tops/blue-frock/thumbnail.webp'],
    ['Palm Print Summer Dress', 'The Girl Summer Dress is a cute and breezy dress designed for warm weather.', 1999, 2474, 4.8, 'Women', 'XS,S,M,L', 'Nova Row', 'https://cdn.dummyjson.com/product-images/tops/girl-summer-dress/thumbnail.webp'],
    ['Grey Button Midi Dress', 'The Gray Dress is a versatile and chic option for various occasions.', 3499, 4082, 3.7, 'Women', 'XS,S,M,L', 'Maison Clover', 'https://cdn.dummyjson.com/product-images/tops/gray-dress/thumbnail.webp'],
    ['Grey Pinafore Dress', 'The Short Frock is a playful and trendy dress with a shorter length.', 2499, 2887, 3.8, 'Women', 'XS,S,M,L', 'Oakridge', 'https://cdn.dummyjson.com/product-images/tops/short-frock/thumbnail.webp'],
    ['Monochrome Tartan Wrap Dress', 'The Tartan Dress features a classic tartan pattern, bringing a timeless and sophisticated touch to your wardrobe.', 3999, 4594, 4.0, 'Women', 'XS,S,M,L', 'Vela', 'https://cdn.dummyjson.com/product-images/tops/tartan-dress/thumbnail.webp'],
    ['Round Tortoiseshell Sunglasses', 'The Black Sun Glasses are a classic and stylish choice, featuring a sleek black frame and tinted lenses.', 2999, 3155, 4.4, 'Accessories', 'One Size', 'Ember & Co', 'https://cdn.dummyjson.com/product-images/sunglasses/black-sun-glasses/thumbnail.webp'],
    ['Classic Aviator Sunglasses', 'The Classic Sun Glasses offer a timeless design with a neutral frame and UV-protected lenses.', 2499, 2629, 3.9, 'Accessories', 'One Size', 'Northbound', 'https://cdn.dummyjson.com/product-images/sunglasses/classic-sun-glasses/thumbnail.webp'],
    ['Green Tinted Aviators', 'The Green and Black Glasses feature a bold combination of green and black colors, adding a touch of vibrancy to your eyewear collection.', 3499, 3535, 4.5, 'Accessories', 'One Size', 'Lumen', 'https://cdn.dummyjson.com/product-images/sunglasses/green-and-black-glasses/thumbnail.webp'],
    ['Pixel Party Sunglasses', 'The Party Glasses are designed to add flair to your party outfit.', 1999, 2252, 3.6, 'Accessories', 'One Size', 'Wildroot', 'https://cdn.dummyjson.com/product-images/sunglasses/party-glasses/thumbnail.webp'],
    ['Oversized Flat-Top Sunglasses', 'The Sunglasses offer a classic and simple design with a focus on functionality.', 2299, 2334, 3.7, 'Accessories', 'One Size', 'Marlowe', 'https://cdn.dummyjson.com/product-images/sunglasses/sunglasses/thumbnail.webp'],
];

// Fictional store brands + deterministic rating/discount for the Unsplash photo set.
$storeBrands = ['Stitchly Essentials','Nova Row','Maison Clover','Oakridge','Vela','Ember & Co','Northbound','Lumen','Wildroot','Marlowe','Aster & Vale','Cove'];
$ratingSet   = [4.1, 4.5, 3.9, 4.7, 4.2, 3.8, 4.6, 4.0, 4.4, 4.3, 3.7, 4.8];
$discountSet = [20, 35, 15, 50, 10, 45, 25, 40, 30, 55, 18, 33];

$check = $db->query("SELECT COUNT(*) AS c FROM cl_products")->fetch_assoc();
if ((int)$check['c'] < 60 || (int)$check['c'] > 120) {
    $db->query("DELETE FROM cl_products");
    $st = $db->prepare("INSERT IGNORE INTO cl_products (uuid, name, description, price_cents, mrp_cents, rating, category, sizes, brand, image_url) VALUES (?,?,?,?,?,?,?,?,?,?)");
    // 54 real-photo products (Unsplash) — assign a store brand, rating, and MRP.
    foreach ($products as $i => $p) {
        [$name, $desc, $price, $cat, $sizes, $img] = $p;
        $brand  = $storeBrands[$i % count($storeBrands)];
        $rating = $ratingSet[$i % count($ratingSet)];
        $disc   = $discountSet[$i % count($discountSet)];
        $mrp    = (int)round($price / (1 - $disc / 100));
        $uuid   = uuidv4();
        $st->bind_param('sssiidssss', $uuid, $name, $desc, $price, $mrp, $rating, $cat, $sizes, $brand, $img);
        $st->execute();
    }
    // 30 real-photo products (DummyJSON demo catalog) — already carry MRP, rating, brand.
    foreach ($djProducts as $p) {
        [$name, $desc, $price, $mrp, $rating, $cat, $sizes, $brand, $img] = $p;
        $uuid = uuidv4();
        $st->bind_param('sssiidssss', $uuid, $name, $desc, $price, $mrp, $rating, $cat, $sizes, $brand, $img);
        $st->execute();
    }
}

function current_user($db) {
    if (empty($_SESSION['uid'])) return null;
    $st = $db->prepare("SELECT * FROM cl_users WHERE id=?");
    $st->bind_param('i', $_SESSION['uid']); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function query_products($db, $cat = 'All', $search = '', $sort = 'featured', $page = 1, $perPage = 24) {
    $where = [];
    $params = []; $types = '';
    if ($cat !== 'All') { $where[] = 'category=?'; $params[] = $cat; $types .= 's'; }
    if ($search !== '') { $where[] = 'name LIKE ?'; $params[] = '%' . $search . '%'; $types .= 's'; }
    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $orderSql = match($sort) {
        'price_asc'  => 'ORDER BY price_cents ASC, id ASC',
        'price_desc' => 'ORDER BY price_cents DESC, id ASC',
        'name_asc'   => 'ORDER BY name ASC',
        default      => 'ORDER BY id ASC',
    };

    $countSt = $db->prepare("SELECT COUNT(*) AS c FROM cl_products {$whereSql}");
    if ($types !== '') $countSt->bind_param($types, ...$params);
    $countSt->execute();
    $total = (int)$countSt->get_result()->fetch_assoc()['c'];

    $totalPages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT * FROM cl_products {$whereSql} {$orderSql} LIMIT ? OFFSET ?";
    $st = $db->prepare($sql);
    $allTypes = $types . 'ii';
    $allParams = array_merge($params, [$perPage, $offset]);
    $st->bind_param($allTypes, ...$allParams);
    $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row;

    return ['items' => $out, 'total' => $total, 'page' => $page, 'total_pages' => $totalPages, 'per_page' => $perPage];
}
function category_counts($db) {
    $out = ['All' => 0];
    $r = $db->query("SELECT category, COUNT(*) AS c FROM cl_products GROUP BY category");
    $sum = 0;
    while ($row = $r->fetch_assoc()) { $out[$row['category']] = (int)$row['c']; $sum += (int)$row['c']; }
    $out['All'] = $sum;
    return $out;
}
function product_by_id($db, $id) {
    $st = $db->prepare("SELECT * FROM cl_products WHERE id=?");
    $st->bind_param('i', $id); $st->execute();
    return $st->get_result()->fetch_assoc();
}
function cart_items($db, $userId) {
    $st = $db->prepare("SELECT c.*, p.name, p.image_url, p.category, p.icon, p.id AS pid FROM cl_cart_items c JOIN cl_products p ON p.id=c.product_id WHERE c.user_id=? ORDER BY c.added_at");
    $st->bind_param('i', $userId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function cart_count($db, $userId) {
    $st = $db->prepare("SELECT COALESCE(SUM(qty),0) AS c FROM cl_cart_items WHERE user_id=?");
    $st->bind_param('i', $userId); $st->execute();
    return (int)$st->get_result()->fetch_assoc()['c'];
}
function user_orders($db, $userId) {
    $st = $db->prepare("SELECT * FROM cl_orders WHERE user_id=? ORDER BY id DESC");
    $st->bind_param('i', $userId); $st->execute();
    $out = []; $r = $st->get_result(); while ($row = $r->fetch_assoc()) $out[] = $row; return $out;
}
function get_order($db, $id, $userId) {
    $st = $db->prepare("SELECT * FROM cl_orders WHERE id=? AND user_id=?");
    $st->bind_param('ii', $id, $userId); $st->execute();
    return $st->get_result()->fetch_assoc();
}

$action = $_GET['action'] ?? 'shop';
$me = current_user($db);

// ── Auth ──────────────────────────────────────────────────────────────────────
if ($action === 'logout') { session_destroy(); header('Location: index.php?action=login'); exit; }

$authError = '';
if ($action === 'register') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name  = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $authError = 'Enter your name, a valid email, and a password of at least 6 characters.';
        } else {
            $st = $db->prepare("SELECT id FROM cl_users WHERE email=?");
            $st->bind_param('s', $email); $st->execute();
            if ($st->get_result()->fetch_row()) {
                $authError = 'An account with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $st = $db->prepare("INSERT INTO cl_users (name, email, password_hash) VALUES (?,?,?)");
                $st->bind_param('sss', $name, $email, $hash); $st->execute();
                $_SESSION['uid'] = $db->insert_id;
                header('Location: index.php'); exit;
            }
        }
    }
    render_auth('register', $authError);
    exit;
}
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $st = $db->prepare("SELECT * FROM cl_users WHERE email=?");
        $st->bind_param('s', $email); $st->execute();
        $u = $st->get_result()->fetch_assoc();
        if ($u && password_verify($pass, $u['password_hash'])) {
            $_SESSION['uid'] = $u['id'];
            header('Location: ' . ($_GET['next'] ?? 'index.php')); exit;
        }
        $authError = 'Invalid email or password.';
    }
    render_auth('login', $authError);
    exit;
}

// Browsing (shop + product pages) is public, exactly like a real storefront —
// only cart/checkout/orders require an account. Each of those actions checks
// $me for itself below instead of one blanket gate on the whole site.
$myId = $me ? (int)$me['id'] : 0;
$cartActions = ['add-to-cart', 'update-cart', 'remove-from-cart', 'cart', 'checkout', 'order', 'orders'];
if (in_array($action, $cartActions, true) && !$me) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header('Location: index.php?action=login&next=' . $next);
    exit;
}

// ── VULNERABLE ENDPOINT — add to cart ────────────────────────────────────────────
// The product page renders the real price into a hidden <input>. This handler
// trusts that hidden field verbatim instead of re-reading cl_products.price_cents
// for $productId, so whatever price a shopper submits (or a proxy rewrites) is
// exactly what gets stored on the cart line — and later charged at checkout.
if ($action === 'add-to-cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $size      = trim($_POST['size'] ?? 'M');
    $qty       = max(1, min(20, (int)($_POST['qty'] ?? 1)));
    $unitPrice = (int)($_POST['unit_price_cents'] ?? 0); // <-- THE BUG: trusted as-is

    $product = product_by_id($db, $productId);
    if ($product && $unitPrice > 0) {
        $st = $db->prepare("INSERT INTO cl_cart_items (user_id, product_id, size, qty, unit_price_cents) VALUES (?,?,?,?,?)");
        $st->bind_param('iisii', $myId, $productId, $size, $qty, $unitPrice);
        $st->execute();
    }
    header('Location: index.php?action=shop&added=1'); exit;
}

if ($action === 'update-cart' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['qty'] ?? [] as $itemId => $qty) {
        $itemId = (int)$itemId; $qty = max(0, (int)$qty);
        if ($qty === 0) {
            $st = $db->prepare("DELETE FROM cl_cart_items WHERE id=? AND user_id=?");
            $st->bind_param('ii', $itemId, $myId); $st->execute();
        } else {
            $st = $db->prepare("UPDATE cl_cart_items SET qty=? WHERE id=? AND user_id=?");
            $st->bind_param('iii', $qty, $itemId, $myId); $st->execute();
        }
    }
    header('Location: index.php?action=cart'); exit;
}
if ($action === 'remove-from-cart') {
    $itemId = (int)($_GET['id'] ?? 0);
    $st = $db->prepare("DELETE FROM cl_cart_items WHERE id=? AND user_id=?");
    $st->bind_param('ii', $itemId, $myId); $st->execute();
    header('Location: index.php?action=cart'); exit;
}

// ── Checkout ──────────────────────────────────────────────────────────────────
if ($action === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = cart_items($db, $myId);
    if (!empty($items)) {
        $subtotal = 0;
        $lines = [];
        foreach ($items as $it) {
            $lineTotal = $it['unit_price_cents'] * $it['qty'];
            $subtotal += $lineTotal;
            $lines[] = ['name' => $it['name'], 'size' => $it['size'], 'qty' => (int)$it['qty'], 'unit_price' => (int)$it['unit_price_cents'], 'line_total' => $lineTotal];
        }
        $shipping = 800;
        $total = $subtotal + $shipping;
        $name = trim($_POST['ship_name'] ?? $me['name']);
        $address = trim($_POST['ship_address'] ?? '');
        $city = trim($_POST['ship_city'] ?? '');
        $state = trim($_POST['ship_state'] ?? '');
        $zip = trim($_POST['ship_zip'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $orderUuid = uuidv4();
        $itemsJson = json_encode($lines);
        $st = $db->prepare("INSERT INTO cl_orders (user_id, order_uuid, items_json, subtotal_cents, shipping_cents, total_cents, ship_name, ship_address, ship_city, ship_state, ship_zip, phone) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $st->bind_param('issiiissssss', $myId, $orderUuid, $itemsJson, $subtotal, $shipping, $total, $name, $address, $city, $state, $zip, $phone);
        $st->execute();
        $newId = $db->insert_id;
        $st = $db->prepare("DELETE FROM cl_cart_items WHERE user_id=?");
        $st->bind_param('i', $myId); $st->execute();
        header('Location: index.php?action=order&id=' . $newId); exit;
    }
    header('Location: index.php?action=cart'); exit;
}

$cCount = cart_count($db, $myId);

// =========================================================================
//  RENDER
// =========================================================================
function render_auth($mode, $error) {
    $isReg = ($mode === 'register');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $isReg ? 'Sign up' : 'Sign in'; ?> — STITCHLY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:wght@500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#faf7f2;color:#1a1a1a;font-family:'Inter',sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.auth{width:400px;max-width:92vw;}
.logo-row{display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:700;font-size:1.7rem;letter-spacing:1px;margin-bottom:1.75rem;}
.card{background:#fff;border:1px solid #e8e1d6;border-radius:4px;padding:2.25rem;}
.card h1{font-size:1.3rem;margin:0 0 0.35rem;font-family:'Fraunces',serif;}
.card p.s{color:#7a7267;font-size:0.88rem;margin:0 0 1.5rem;}
label{display:block;font-size:0.78rem;color:#4a453d;margin:0.9rem 0 0.35rem;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;}
input{width:100%;background:#faf7f2;border:1px solid #ddd3c3;color:#1a1a1a;border-radius:2px;padding:0.7rem 0.8rem;font-size:0.92rem;}
input:focus{outline:none;border-color:#1f4d3d;}
.btn-primary{width:100%;margin-top:1.4rem;background:#1a1a1a;border:none;color:#fff;padding:0.8rem;border-radius:2px;font-size:0.9rem;font-weight:600;cursor:pointer;text-transform:uppercase;letter-spacing:1px;}
.btn-primary:hover{background:#1f4d3d;}
.alt{text-align:center;margin-top:1.25rem;font-size:0.86rem;color:#7a7267;}
.alt a{color:#1f4d3d;text-decoration:none;font-weight:600;}
.err{background:#fdf1ee;border:1px solid #e8b8a8;color:#a13a1f;padding:0.7rem 0.9rem;border-radius:2px;font-size:0.85rem;margin-bottom:1rem;}
</style></head><body>
<div class="auth">
  <div class="logo-row">STITCHLY</div>
  <div class="card">
    <h1><?php echo $isReg ? 'Create an account' : 'Welcome back'; ?></h1>
    <p class="s"><?php echo $isReg ? 'Join for faster checkout and order tracking.' : 'Sign in to your account.'; ?></p>
    <?php if ($error): ?><div class="err"><?php echo esc($error); ?></div><?php endif; ?>
    <form method="POST" action="index.php?action=<?php echo $isReg ? 'register' : 'login'; ?><?php echo isset($_GET['next']) ? '&next=' . esc($_GET['next']) : ''; ?>">
      <?php if ($isReg): ?><label>Full name</label><input name="name" required><?php endif; ?>
      <label>Email</label><input name="email" type="email" placeholder="you@example.com" required>
      <label>Password</label><input name="password" type="password" placeholder="••••••••" required>
      <button class="btn-primary" type="submit"><?php echo $isReg ? 'Create account' : 'Sign in'; ?></button>
    </form>
    <div class="alt">
      <?php if ($isReg): ?>Already have an account? <a href="index.php?action=login">Sign in</a>
      <?php else: ?>New to STITCHLY? <a href="index.php?action=register">Create an account</a><?php endif; ?>
    </div>
  </div>
</div>
</body></html>
<?php
}

function render_head($title) {
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc($title); ?> — STITCHLY</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Fraunces:wght@500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;}
body{margin:0;background:#faf7f2;color:#1a1a1a;font-family:'Inter',sans-serif;}
a{color:inherit;}
h1,h2{font-family:'Fraunces',serif;}
::-webkit-scrollbar{width:8px;}
::-webkit-scrollbar-thumb{background:#ddd3c3;border-radius:4px;}
.topbar{background:#fff;border-bottom:1px solid #e8e1d6;position:sticky;top:0;z-index:20;}
.topbar-in{max-width:1140px;margin:0 auto;padding:1rem 1.5rem;display:flex;align-items:center;justify-content:space-between;}
.brand{font-family:'Fraunces',serif;font-weight:700;font-size:1.4rem;letter-spacing:1px;text-decoration:none;color:#1a1a1a;}
.nav{display:flex;gap:1.6rem;align-items:center;}
.nav a{font-size:0.85rem;color:#4a453d;text-decoration:none;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;}
.nav a.active,.nav a:hover{color:#1f4d3d;}
.nav .cart-link{position:relative;}
.nav .cart-badge{position:absolute;top:-9px;right:-15px;background:#1f4d3d;color:#fff;font-size:0.62rem;font-weight:700;border-radius:10px;padding:0.05rem 0.4rem;}
.acct{display:flex;align-items:center;gap:0.6rem;}
.acct .av{width:28px;height:28px;border-radius:50%;background:#1a1a1a;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.78rem;font-weight:700;}
.btn{background:#fff;border:1px solid #ddd3c3;color:#1a1a1a;padding:0.45rem 1rem;border-radius:2px;font-size:0.82rem;cursor:pointer;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;}
.btn:hover{background:#f3ede2;}
.btn-primary{background:#1a1a1a;border:none;color:#fff;text-transform:uppercase;letter-spacing:0.8px;font-weight:600;}
.btn-primary:hover{background:#1f4d3d;}
.wrap{max-width:1140px;margin:0 auto;padding:2rem 1.5rem;}
.hero{position:relative;color:#fff;overflow:hidden;}
.hero img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(0.5);}
.hero-in{position:relative;max-width:1140px;margin:0 auto;padding:5rem 1.5rem;}
.hero h1{font-size:2.6rem;margin:0 0 0.5rem;}
.hero p{opacity:0.92;margin:0 0 1.5rem;font-size:1rem;}
.cat-tabs{display:flex;gap:0.5rem;margin:1.5rem 0;flex-wrap:wrap;}
.cat-tab{padding:0.5rem 1.1rem;border:1px solid #ddd3c3;border-radius:20px;font-size:0.82rem;text-decoration:none;color:#4a453d;font-weight:500;}
.cat-tab.active,.cat-tab:hover{background:#1a1a1a;color:#fff;border-color:#1a1a1a;}
.cat-tab .cat-count{opacity:0.65;font-size:0.74rem;}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;}
.search-form{display:flex;gap:0.5rem;flex:1;min-width:220px;max-width:420px;}
.search-form input{background:#fff;border:1px solid #ddd3c3;border-radius:2px;padding:0.55rem 0.8rem;font-size:0.85rem;flex:1;}
.search-form button{background:#1a1a1a;border:none;color:#fff;padding:0.55rem 1.1rem;border-radius:2px;font-size:0.82rem;cursor:pointer;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;}
.search-form button:hover{background:#1f4d3d;}
.sort-form select{background:#fff;border:1px solid #ddd3c3;border-radius:2px;padding:0.55rem 0.8rem;font-size:0.85rem;}
.results-count{font-size:0.82rem;color:#7a7267;margin-bottom:1rem;}
.pagination{display:flex;gap:0.4rem;justify-content:center;align-items:center;margin-top:2.5rem;flex-wrap:wrap;}
.page-link{padding:0.5rem 0.9rem;border:1px solid #ddd3c3;border-radius:2px;text-decoration:none;color:#4a453d;font-size:0.85rem;}
.page-link.active{background:#1a1a1a;color:#fff;border-color:#1a1a1a;}
.page-link:hover{border-color:#1f4d3d;color:#1f4d3d;}
.page-link.active:hover{color:#fff;}
.page-dots{color:#a8a095;font-size:0.85rem;padding:0 0.2rem;}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1.5rem;}
.pcard{background:#fff;border:1px solid #e8e1d6;overflow:hidden;text-decoration:none;color:inherit;display:block;transition:box-shadow .15s;}
.pcard:hover{box-shadow:0 6px 24px rgba(0,0,0,0.08);}
.pcard .thumb{height:280px;overflow:hidden;background:#f3ede2;position:relative;}
.pcard .thumb img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s;}
.pcard .thumb img.pshot{object-fit:contain;padding:1.25rem;background:#fbf8f3;}
.pcard:hover .thumb img{transform:scale(1.04);}
.rating-pill{position:absolute;left:8px;bottom:8px;background:rgba(255,255,255,0.94);color:#1a1a1a;font-size:0.72rem;font-weight:700;padding:0.15rem 0.45rem;border-radius:3px;box-shadow:0 1px 3px rgba(0,0,0,0.12);}
.rating-pill .star{color:#1f8a4c;}
.pcard .brand{font-weight:700;font-size:0.85rem;color:#1a1a1a;}
.pcard .price-row{display:flex;align-items:baseline;gap:0.4rem;margin-top:0.4rem;flex-wrap:wrap;}
.pcard .price-row .price{color:#1a1a1a;font-weight:700;font-size:0.95rem;}
.pcard .price-row .mrp{color:#a8a095;text-decoration:line-through;font-size:0.8rem;}
.pcard .price-row .disc{color:#d1642b;font-weight:600;font-size:0.8rem;}
.ph-tile{width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:0.5rem;}
.ph-tile .ph-icon{font-size:2.6rem;line-height:1;filter:saturate(0.75);}
.ph-tile .ph-label{font-size:0.68rem;color:#8a8272;text-transform:uppercase;letter-spacing:0.6px;font-weight:600;}
.pd-image .ph-tile{min-height:420px;}
.pd-image .ph-tile .ph-icon{font-size:5rem;}
.cart-thumb.ph-tile{width:72px;height:88px;flex-shrink:0;border-radius:2px;}
.cart-thumb.ph-tile .ph-icon{font-size:1.6rem;}
.cart-thumb.ph-tile .ph-label{display:none;}
.pcard .body{padding:0.85rem 1rem;}
.pcard .cat{font-size:0.7rem;color:#a8a095;text-transform:uppercase;letter-spacing:0.8px;}
.pcard .nm{font-weight:400;font-size:0.85rem;color:#7a7267;margin-top:0.1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.empty{color:#7a7267;font-size:0.9rem;padding:2.5rem 0;text-align:center;}
.notice{background:#eaf3ee;border:1px solid #b9d9c6;color:#1f4d3d;padding:0.9rem 1.1rem;border-radius:4px;font-size:0.85rem;margin-bottom:1.25rem;}
.warn{background:#fdf1ee;border:1px solid #e8b8a8;color:#a13a1f;padding:0.9rem 1.1rem;border-radius:4px;font-size:0.85rem;margin-bottom:1.25rem;}
/* Product detail */
.pd-layout{display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:start;}
@media(max-width:820px){.pd-layout{grid-template-columns:1fr;}}
.pd-image{background:#f3ede2;overflow:hidden;}
.pd-image img{width:100%;height:auto;display:block;}
.pd-image img.pshot{object-fit:contain;background:#fbf8f3;padding:2.5rem;max-height:520px;}
.pd-cat{font-size:0.75rem;color:#a8a095;text-transform:uppercase;letter-spacing:1px;}
.pd-brand{font-size:0.9rem;font-weight:700;color:#1a1a1a;text-transform:uppercase;letter-spacing:0.5px;}
.pd-name{font-size:1.6rem;margin:0.3rem 0 0.5rem;color:#4a453d;font-weight:500;}
.pd-rating{display:inline-block;background:#1f8a4c;color:#fff;font-size:0.8rem;font-weight:700;padding:0.15rem 0.5rem;border-radius:3px;margin-bottom:0.9rem;}
.pd-rating span{font-weight:400;opacity:0.85;font-size:0.72rem;}
.pd-price-row{display:flex;align-items:baseline;gap:0.7rem;margin-bottom:1rem;flex-wrap:wrap;}
.pd-price{font-size:1.6rem;color:#1a1a1a;font-weight:700;}
.pd-mrp{font-size:1rem;color:#a8a095;text-decoration:line-through;}
.pd-disc{font-size:1rem;color:#d1642b;font-weight:700;}
.pd-desc{color:#4a453d;line-height:1.7;margin-bottom:1.5rem;}
.size-grid{display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.25rem;}
.size-opt{border:1px solid #ddd3c3;padding:0.5rem 0.9rem;border-radius:2px;cursor:pointer;font-size:0.85rem;}
.size-opt input{display:none;}
.size-opt.sel,.size-opt:has(input:checked){background:#1a1a1a;color:#fff;border-color:#1a1a1a;}
.qty-row{display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem;}
.qty-row input{width:64px;text-align:center;border:1px solid #ddd3c3;border-radius:2px;padding:0.5rem;font-size:0.9rem;}
/* Cart / checkout */
.cart-line{display:flex;align-items:center;gap:1.1rem;background:#fff;border:1px solid #e8e1d6;padding:1rem;margin-bottom:0.7rem;}
.cart-line img{width:72px;height:88px;object-fit:cover;flex-shrink:0;}
.cart-line img.pshot{object-fit:contain;background:#fbf8f3;border-radius:2px;}
.cart-line .nm{font-weight:600;}
.cart-line .meta{font-size:0.8rem;color:#7a7267;margin-top:0.2rem;}
.cart-line input{width:56px;text-align:center;border:1px solid #ddd3c3;border-radius:2px;padding:0.35rem;}
.cart-line .lt{font-weight:700;width:90px;text-align:right;}
.cart-line .rm{color:#a13a1f;font-size:0.8rem;text-decoration:none;}
.layout{display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start;}
@media(max-width:860px){.layout{grid-template-columns:1fr;}}
.summary{background:#fff;border:1px solid #e8e1d6;padding:1.5rem;position:sticky;top:90px;}
.summary .row{display:flex;justify-content:space-between;font-size:0.9rem;margin-bottom:0.6rem;color:#4a453d;}
.summary .row.total{font-weight:700;color:#1a1a1a;font-size:1.15rem;border-top:1px solid #e8e1d6;padding-top:0.75rem;margin-top:0.75rem;}
label{display:block;font-size:0.78rem;color:#4a453d;margin:0.9rem 0 0.35rem;font-weight:500;text-transform:uppercase;letter-spacing:0.5px;}
input,select{width:100%;background:#faf7f2;border:1px solid #ddd3c3;color:#1a1a1a;border-radius:2px;padding:0.65rem 0.8rem;font-size:0.9rem;font-family:inherit;}
input:focus,select:focus{outline:none;border-color:#1f4d3d;}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:0.9rem;}
.three-col{display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.9rem;}
/* Receipt */
.receipt{background:#fff;border:1px solid #e8e1d6;padding:1.75rem;}
.receipt table{width:100%;border-collapse:collapse;margin-top:1rem;}
.receipt td{padding:0.5rem 0;font-size:0.9rem;border-bottom:1px solid #f3ede2;}
.receipt td.r{text-align:right;}
.order-card{background:#fff;border:1px solid #e8e1d6;padding:1.1rem 1.3rem;margin-bottom:0.8rem;text-decoration:none;color:inherit;display:block;}
.order-card .hd{display:flex;justify-content:space-between;font-weight:600;}
.order-card .meta{font-size:0.8rem;color:#7a7267;margin-top:0.25rem;}
</style></head><body>
<?php
}

function render_topbar($me, $cCount) {
?>
<div class="topbar"><div class="topbar-in">
  <a href="index.php?action=shop" class="brand">STITCHLY</a>
  <div class="nav">
    <a href="index.php?action=shop">Shop</a>
    <a href="index.php?action=orders">Orders</a>
    <a href="index.php?action=cart" class="cart-link">Cart<?php if ($cCount>0): ?><span class="cart-badge"><?php echo (int)$cCount; ?></span><?php endif; ?></a>
  </div>
  <div class="acct">
    <?php if ($me): ?>
      <span class="av"><?php echo esc(avatar_letter($me['name'])); ?></span>
      <span style="font-size:0.85rem;"><?php echo esc(first_name($me['name'])); ?></span>
      <a href="index.php?action=logout" class="btn">Sign out</a>
    <?php else: ?>
      <a href="index.php?action=login" class="btn">Sign in</a>
      <a href="index.php?action=register" class="btn btn-primary">Sign up</a>
    <?php endif; ?>
  </div>
</div></div>
<?php
}

// =========================================================================
//  PAGES
// =========================================================================

if ($action === 'product') {
    $id = (int)($_GET['id'] ?? 0);
    $p = product_by_id($db, $id);
    render_head($p['name'] ?? 'Product'); render_topbar($me, $cCount);
    if (!$p) { echo '<div class="wrap"><div class="empty">Product not found.</div></div></body></html>'; exit; }
    $sizes = explode(',', $p['sizes']);
    ?>
    <div class="wrap">
      <a href="index.php?action=shop" class="btn" style="margin-bottom:1.5rem;">← Back to shop</a>
      <div class="pd-layout">
        <div class="pd-image"><?php render_thumb($p); ?></div>
        <div>
          <div class="pd-brand"><?php echo esc($p['brand'] ?: $p['category']); ?></div>
          <h1 class="pd-name"><?php echo esc($p['name']); ?></h1>
          <?php if ((float)$p['rating'] > 0): ?><div class="pd-rating"><?php echo number_format((float)$p['rating'], 1); ?> ★ <span>rating</span></div><?php endif; ?>
          <div class="pd-price-row">
            <span class="pd-price"><?php echo money($p['price_cents']); ?></span>
            <?php if ((int)$p['mrp_cents'] > (int)$p['price_cents']): ?>
              <span class="pd-mrp"><?php echo money($p['mrp_cents']); ?></span>
              <span class="pd-disc"><?php echo discount_pct($p['price_cents'], $p['mrp_cents']); ?>% OFF</span>
            <?php endif; ?>
          </div>
          <p class="pd-desc"><?php echo esc($p['description']); ?></p>
          <form method="POST" action="index.php?action=add-to-cart">
            <input type="hidden" name="product_id" value="<?php echo (int)$p['id']; ?>">
            <!-- The server trusts this hidden price field verbatim when the cart line is created. -->
            <input type="hidden" name="unit_price_cents" value="<?php echo (int)$p['price_cents']; ?>">
            <label>Size</label>
            <div class="size-grid">
              <?php foreach ($sizes as $i => $sz): ?>
              <label class="size-opt <?php echo $i===0?'sel':''; ?>">
                <input type="radio" name="size" value="<?php echo esc($sz); ?>" <?php echo $i===0?'checked':''; ?> onchange="document.querySelectorAll('.size-opt').forEach(e=>e.classList.remove('sel'));this.closest('.size-opt').classList.add('sel');">
                <?php echo esc($sz); ?>
              </label>
              <?php endforeach; ?>
            </div>
            <label>Quantity</label>
            <div class="qty-row"><input type="number" name="qty" value="1" min="1" max="20"></div>
            <button class="btn btn-primary" type="submit" style="padding:0.85rem 2rem;">Add to cart</button>
          </form>
        </div>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'cart') {
    $items = cart_items($db, $myId);
    $subtotal = 0;
    foreach ($items as $it) $subtotal += $it['unit_price_cents'] * $it['qty'];
    $shipping = !empty($items) ? 800 : 0;
    $total = $subtotal + $shipping;
    render_head('Your cart'); render_topbar($me, $cCount);
    ?>
    <div class="wrap">
      <h1 style="font-size:1.6rem;">Your cart</h1>
      <?php if (empty($items)): ?>
        <div class="empty">Your cart is empty. <a href="index.php?action=shop" style="color:#1f4d3d;">Continue shopping</a>.</div>
      <?php else: ?>
      <div class="layout">
        <div>
          <form method="POST" action="index.php?action=update-cart">
            <?php foreach ($items as $it): ?>
            <div class="cart-line">
              <?php render_thumb(['id' => $it['pid'], 'image_url' => $it['image_url'], 'icon' => $it['icon'], 'name' => $it['name']], '', 'cart-thumb'); ?>
              <div style="flex:1;">
                <div class="nm"><?php echo esc($it['name']); ?></div>
                <div class="meta">Size <?php echo esc($it['size']); ?> · <?php echo money($it['unit_price_cents']); ?> each</div>
              </div>
              <input type="number" name="qty[<?php echo (int)$it['id']; ?>]" value="<?php echo (int)$it['qty']; ?>" min="1" max="20">
              <div class="lt"><?php echo money($it['unit_price_cents'] * $it['qty']); ?></div>
              <a class="rm" href="index.php?action=remove-from-cart&id=<?php echo (int)$it['id']; ?>">Remove</a>
            </div>
            <?php endforeach; ?>
            <button class="btn" type="submit">Update cart</button>
          </form>
        </div>
        <div class="summary">
          <div style="font-weight:700;margin-bottom:0.9rem;">Order summary</div>
          <div class="row"><span>Subtotal</span><span><?php echo money($subtotal); ?></span></div>
          <div class="row"><span>Shipping</span><span><?php echo money($shipping); ?></span></div>
          <div class="row total"><span>Total</span><span><?php echo money($total); ?></span></div>
          <a class="btn btn-primary" href="index.php?action=checkout" style="width:100%;justify-content:center;margin-top:1rem;padding:0.8rem;">Proceed to checkout</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'checkout') {
    $items = cart_items($db, $myId);
    $subtotal = 0;
    foreach ($items as $it) $subtotal += $it['unit_price_cents'] * $it['qty'];
    render_head('Checkout'); render_topbar($me, $cCount);
    if (empty($items)) { echo '<div class="wrap"><div class="empty">Your cart is empty.</div></div></body></html>'; exit; }
    $shipping = 800;
    $total = $subtotal + $shipping;
    ?>
    <div class="wrap">
      <h1 style="font-size:1.6rem;">Checkout</h1>
      <div class="layout">
        <div>
          <h2 style="font-size:1.1rem;">Shipping information</h2>
          <form method="POST" action="index.php?action=checkout" id="checkoutForm">
            <label>Full name</label><input name="ship_name" value="<?php echo esc($me['name']); ?>" required>
            <label>Address</label><input name="ship_address" placeholder="Street address" required>
            <div class="three-col">
              <div><label>City</label><input name="ship_city" required></div>
              <div><label>State</label><input name="ship_state" maxlength="2" required></div>
              <div><label>ZIP</label><input name="ship_zip" required></div>
            </div>
            <label>Phone</label><input name="phone" placeholder="555-555-5555">
            <label>Card number</label><input placeholder="4242 4242 4242 4242">
            <div class="two-col">
              <div><label>Expiry</label><input placeholder="MM/YY"></div>
              <div><label>CVC</label><input placeholder="123"></div>
            </div>
            <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:1.5rem;padding:0.85rem;">Place order — <?php echo money($total); ?></button>
          </form>
        </div>
        <div class="summary">
          <div style="font-weight:700;margin-bottom:0.9rem;">Order summary</div>
          <?php foreach ($items as $it): ?>
          <div class="row"><span><?php echo (int)$it['qty']; ?>× <?php echo esc($it['name']); ?> (<?php echo esc($it['size']); ?>)</span><span><?php echo money($it['unit_price_cents'] * $it['qty']); ?></span></div>
          <?php endforeach; ?>
          <div class="row"><span>Shipping</span><span><?php echo money($shipping); ?></span></div>
          <div class="row total"><span>Total</span><span><?php echo money($total); ?></span></div>
        </div>
      </div>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'order') {
    $id = (int)($_GET['id'] ?? 0);
    $o = get_order($db, $id, $myId);
    render_head('Order confirmation'); render_topbar($me, $cCount);
    if (!$o) { echo '<div class="wrap"><div class="empty">Order not found.</div></div></body></html>'; exit; }
    $lines = json_decode($o['items_json'], true) ?: [];
    ?>
    <div class="wrap" style="max-width:600px;">
      <div class="notice">✅ Order placed! A confirmation has been "emailed" to you. <?php echo money($o['total_cents']); ?> was charged to your card.</div>
      <div class="receipt">
        <div style="font-weight:700;font-size:1.1rem;">Order #<?php echo (int)$o['id']; ?></div>
        <div style="font-size:0.8rem;color:#7a7267;">Ref <?php echo esc($o['order_uuid']); ?> · <?php echo esc($o['created_at']); ?></div>
        <div style="font-size:0.85rem;color:#4a453d;margin-top:0.75rem;">Shipping to: <?php echo esc($o['ship_name']); ?>, <?php echo esc($o['ship_address']); ?>, <?php echo esc($o['ship_city']); ?>, <?php echo esc($o['ship_state']); ?> <?php echo esc($o['ship_zip']); ?></div>
        <table>
          <?php foreach ($lines as $l): ?>
          <tr><td><?php echo (int)$l['qty']; ?>× <?php echo esc($l['name']); ?> (<?php echo esc($l['size']); ?>) @ <?php echo money($l['unit_price']); ?></td><td class="r"><?php echo money($l['line_total']); ?></td></tr>
          <?php endforeach; ?>
          <tr><td>Shipping</td><td class="r"><?php echo money($o['shipping_cents']); ?></td></tr>
          <tr><td style="font-weight:700;">Total charged</td><td class="r" style="font-weight:700;"><?php echo money($o['total_cents']); ?></td></tr>
        </table>
      </div>
      <a href="index.php?action=orders" class="btn" style="margin-top:1rem;">← Back to orders</a>
    </div>
    </body></html>
    <?php
    exit;
}

if ($action === 'orders') {
    $orders = user_orders($db, $myId);
    render_head('Your orders'); render_topbar($me, $cCount);
    ?>
    <div class="wrap" style="max-width:640px;">
      <h1 style="font-size:1.6rem;">Your orders</h1>
      <?php if (empty($orders)): ?>
        <div class="empty">No orders yet. <a href="index.php?action=shop" style="color:#1f4d3d;">Start shopping.</a></div>
      <?php else: foreach ($orders as $o): ?>
      <a class="order-card" href="index.php?action=order&id=<?php echo (int)$o['id']; ?>">
        <div class="hd"><span>Order #<?php echo (int)$o['id']; ?></span><span><?php echo money($o['total_cents']); ?></span></div>
        <div class="meta"><?php echo esc($o['created_at']); ?> · Shipped to <?php echo esc($o['ship_city']); ?>, <?php echo esc($o['ship_state']); ?></div>
      </a>
      <?php endforeach; endif; ?>
    </div>
    </body></html>
    <?php
    exit;
}

// ── Default: Shop ─────────────────────────────────────────────────────────────
$cat = $_GET['cat'] ?? 'All';
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'featured';
$page = max(1, (int)($_GET['page'] ?? 1));
$added = isset($_GET['added']);

$counts = category_counts($db);
$categories = ['All', 'Men', 'Women', 'Unisex', 'Accessories'];
$result = query_products($db, $cat, $search, $sort, $page, 24);

function build_shop_url($overrides = []) {
    $params = array_merge(['action' => 'shop', 'cat' => $_GET['cat'] ?? 'All', 'q' => $_GET['q'] ?? '', 'sort' => $_GET['sort'] ?? 'featured', 'page' => $_GET['page'] ?? 1], $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return 'index.php?' . http_build_query($params);
}

render_head('Shop'); render_topbar($me, $cCount);
?>
<div class="hero">
  <img src="https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=1400&fit=crop&q=80" alt="">
  <div class="hero-in">
    <h1>New Season Arrivals</h1>
    <p>Considered clothing for everyday wear. <?php echo number_format($counts['All']); ?> products and counting.</p>
  </div>
</div>
<div class="wrap">
  <?php if ($added): ?><div class="notice">Added to your cart.</div><?php endif; ?>
  <div class="cat-tabs">
    <?php foreach ($categories as $c): ?>
    <a href="<?php echo esc(build_shop_url(['cat' => $c === 'All' ? null : $c, 'page' => 1])); ?>" class="cat-tab <?php echo $cat===$c?'active':''; ?>"><?php echo esc($c); ?> <span class="cat-count">(<?php echo number_format($counts[$c] ?? 0); ?>)</span></a>
    <?php endforeach; ?>
  </div>
  <div class="toolbar">
    <form method="GET" action="index.php" class="search-form">
      <input type="hidden" name="action" value="shop">
      <input type="hidden" name="cat" value="<?php echo esc($cat); ?>">
      <input type="hidden" name="sort" value="<?php echo esc($sort); ?>">
      <input type="text" name="q" placeholder="Search products…" value="<?php echo esc($search); ?>">
      <button type="submit">Search</button>
    </form>
    <form method="GET" action="index.php" class="sort-form">
      <input type="hidden" name="action" value="shop">
      <input type="hidden" name="cat" value="<?php echo esc($cat); ?>">
      <input type="hidden" name="q" value="<?php echo esc($search); ?>">
      <select name="sort" onchange="this.form.submit()">
        <option value="featured" <?php echo $sort==='featured'?'selected':''; ?>>Featured</option>
        <option value="price_asc" <?php echo $sort==='price_asc'?'selected':''; ?>>Price: Low to High</option>
        <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>Price: High to Low</option>
        <option value="name_asc" <?php echo $sort==='name_asc'?'selected':''; ?>>Name: A–Z</option>
      </select>
    </form>
  </div>
  <div class="results-count">
    <?php if ($result['total'] > 0): ?>
      Showing <?php echo number_format((($result['page']-1)*$result['per_page'])+1); ?>–<?php echo number_format(min($result['page']*$result['per_page'], $result['total'])); ?> of <?php echo number_format($result['total']); ?> products
    <?php else: ?>
      No products found<?php echo $search !== '' ? ' for "' . esc($search) . '"' : ''; ?>.
    <?php endif; ?>
  </div>
  <div class="grid">
    <?php foreach ($result['items'] as $p): ?>
    <a class="pcard" href="index.php?action=product&id=<?php echo (int)$p['id']; ?>">
      <div class="thumb">
        <?php render_thumb($p); ?>
        <?php if ((float)$p['rating'] > 0): ?><span class="rating-pill"><?php echo number_format((float)$p['rating'], 1); ?> <span class="star">★</span></span><?php endif; ?>
      </div>
      <div class="body">
        <div class="brand"><?php echo esc($p['brand'] ?: $p['category']); ?></div>
        <div class="nm"><?php echo esc($p['name']); ?></div>
        <div class="price-row">
          <span class="price"><?php echo money($p['price_cents']); ?></span>
          <?php if ((int)$p['mrp_cents'] > (int)$p['price_cents']): ?>
            <span class="mrp"><?php echo money($p['mrp_cents']); ?></span>
            <span class="disc"><?php echo discount_pct($p['price_cents'], $p['mrp_cents']); ?>% OFF</span>
          <?php endif; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php if ($result['total_pages'] > 1): ?>
  <div class="pagination">
    <?php if ($result['page'] > 1): ?><a href="<?php echo esc(build_shop_url(['page' => $result['page']-1])); ?>" class="page-link">← Previous</a><?php endif; ?>
    <?php
    $startP = max(1, $result['page'] - 2);
    $endP = min($result['total_pages'], $result['page'] + 2);
    if ($startP > 1) echo '<a href="' . esc(build_shop_url(['page'=>1])) . '" class="page-link">1</a><span class="page-dots">…</span>';
    for ($pn = $startP; $pn <= $endP; $pn++):
    ?>
      <a href="<?php echo esc(build_shop_url(['page' => $pn])); ?>" class="page-link <?php echo $pn===$result['page']?'active':''; ?>"><?php echo $pn; ?></a>
    <?php endfor;
    if ($endP < $result['total_pages']) echo '<span class="page-dots">…</span><a href="' . esc(build_shop_url(['page'=>$result['total_pages']])) . '" class="page-link">' . $result['total_pages'] . '</a>';
    ?>
    <?php if ($result['page'] < $result['total_pages']): ?><a href="<?php echo esc(build_shop_url(['page' => $result['page']+1])); ?>" class="page-link">Next →</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
</body></html>
