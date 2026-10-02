<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';

/* ── ROUTER ─────────────────────────────────────────────── */
$req = $_SERVER['REQUEST_URI'];
$base = BASE;
$rel = '/' . ltrim(substr(parse_url($req, PHP_URL_PATH), strlen($base)), '/');
if ($rel !== '/' && str_ends_with($rel, '/')) $rel = rtrim($rel, '/');

$method = $_SERVER['REQUEST_METHOD'];

match (true) {
    /* static/assets */
    (bool)preg_match('#^/assets/pimg/(\d+)\.svg$#', $rel, $m) => serve_pimg((int)$m[1]),
    (bool)preg_match('#^/assets/pimg/default\.svg$#', $rel)   => serve_default_svg(),

    /* POST actions — checked BEFORE GET pages to avoid duplicate-path shadowing */
    $rel === '/cart/add'          && $method === 'POST' => action_cart_add(),
    $rel === '/cart/remove'       && $method === 'POST' => action_cart_remove(),
    $rel === '/cart/update'       && $method === 'POST' => action_cart_update(),
    $rel === '/cart/apply-coupon' && $method === 'POST' => action_apply_coupon(),
    $rel === '/cart/remove-coupon'&& $method === 'POST' => action_remove_coupon(),
    $rel === '/checkout/submit'   && $method === 'POST' => action_checkout_submit(),
    $rel === '/payment/process'   && $method === 'POST' => action_payment_process(),
    $rel === '/profile/update'    && $method === 'POST' => action_profile_update(),
    $rel === '/login'             && $method === 'POST' => action_login(),
    $rel === '/register'          && $method === 'POST' => action_register(),
    $rel === '/logout'            && $method === 'POST' => action_logout(),

    /* GET pages */
    $rel === '/'                                               => page_home(),
    $rel === '/products'                                       => page_products(),
    (bool)preg_match('#^/category/([a-z0-9_-]+)$#', $rel, $m) => page_category($m[1]),
    (bool)preg_match('#^/product/(\d+)$#', $rel, $m)          => page_product((int)$m[1]),
    $rel === '/cart'                                           => page_cart(),
    $rel === '/checkout'                                       => page_checkout(),
    (bool)preg_match('#^/payment/(\d+)$#', $rel, $m)          => page_payment((int)$m[1]),
    (bool)preg_match('#^/order/(\d+)$#', $rel, $m)            => page_order((int)$m[1]),
    $rel === '/orders'                                         => page_orders(),
    $rel === '/dashboard'                                      => page_dashboard(),
    $rel === '/profile'                                        => page_profile(),
    $rel === '/login'                                          => page_login(),
    $rel === '/register'                                       => page_register(),
    $rel === '/admin'                                          => page_admin(),
    $rel === '/admin/products'                                 => page_admin_products(),
    $rel === '/admin/orders'                                   => page_admin_orders(),
    $rel === '/admin/users'                                    => page_admin_users(),
    $rel === '/admin/coupons'                                  => page_admin_coupons(),
    $rel === '/install'                                        => page_install(),

    default => page_404(),
};

/* ══════════════════════════════════════════════════════════
   ASSET HANDLERS
══════════════════════════════════════════════════════════ */
function serve_pimg(int $id): void {
    $p = product($id);
    if (!$p) { serve_default_svg(); return; }

    // Use loomhelix.com for realistic product photos based on category
    // Each product ID gets a unique image, angles can be changed with ?angle=2,3,4
    $category_seeds = [
        'gpu' => 'gpu-graphics-card',
        'cpu' => 'cpu-processor',
        'ram' => 'ram-memory',
        'ssd' => 'ssd-solid-state',
        'hdd' => 'hdd-hard-drive',
        'mb' => 'motherboard',
        'psu' => 'power-supply',
        'case' => 'pc-case',
        'cooler' => 'cpu-cooler',
        'accessories' => 'pc-accessories'
    ];

    $seed = $category_seeds[$p['cat_slug']] ?? $category_seeds['gpu'];
    $image_url = "https://img.loomhelix.com/seed/{$seed}-{$id}/400x400";

    header('Location: ' . $image_url);
    header('Cache-Control: public, max-age=86400');
    exit;
}

function serve_default_svg(): void {
    header('Location: https://cdn-icons-png.flaticon.com/512/2784/2784572.png');
    header('Cache-Control: public, max-age=86400');
    exit;
}

/* ══════════════════════════════════════════════════════════
   PAGE: HOME
══════════════════════════════════════════════════════════ */
function page_home(): void {
    $cats = db()->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();
    $featured = db()->query("SELECT p.*, c.slug AS cat_slug, c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY p.rating DESC, p.review_count DESC LIMIT 12")->fetchAll();
    $deals    = db()->query("SELECT p.*, c.slug AS cat_slug, c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.original_price IS NOT NULL ORDER BY (1 - p.price/p.original_price) DESC LIMIT 8")->fetchAll();

    page_open('Home');
    ?>
<div class="hero" style="background: linear-gradient(rgba(24,24,31,0.85), rgba(24,24,31,0.85)), url('https://cdn2.scenesku.com/banners/electronics-tech.jpg') center/cover no-repeat;">
  <div class="hero-title">Build Your Dream Rig<br><span>with PCHub</span></div>
  <p class="hero-sub">India's #1 destination for PC components. GPUs, CPUs, RAM, SSDs and more — at unbeatable prices.</p>
  <div class="hero-chips">
    <span class="hero-chip"><img src="https://cdn-icons-png.flaticon.com/512/3082/3082383.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> Free Shipping ₹999+</span>
    <span class="hero-chip"><img src="https://cdn-icons-png.flaticon.com/512/190/190411.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> Genuine Products</span>
    <span class="hero-chip"><img src="https://cdn-icons-png.flaticon.com/512/5968/5968299.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> EMI Available</span>
    <span class="hero-chip"><img src="https://cdn-icons-png.flaticon.com/512/2662/2662503.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> Use Code WELCOME20</span>
  </div>
</div>

<div class="cat-strip">
  <a href="<?= url('products') ?>" class="cat-pill active">All</a>
  <?php
  $cat_images = [
    'gpu' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'cpu' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'ram' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'ssd' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'hdd' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'mb' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'psu' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'case' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'cooler' => 'https://cdn2.scenesku.com/resources/electronics.png',
    'accessories' => 'https://cdn2.scenesku.com/resources/electronics.png'
  ];
  foreach ($cats as $c):
    $img_url = $cat_images[$c['slug']] ?? $cat_images['gpu'];
  ?>
  <a href="<?= url("category/{$c['slug']}") ?>" class="cat-pill">
    <img src="<?= $img_url ?>" alt="<?= h($c['name']) ?>" style="width:24px;height:24px;object-fit:contain;border-radius:4px;vertical-align:middle;margin-right:6px;">
    <?= h($c['name']) ?>
  </a>
  <?php endforeach; ?>
</div>

<div class="main">
  <div class="flex-between mb-24">
    <div>
      <div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/1828/1828884.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Top Picks</div>
      <div class="section-sub">Highest-rated components loved by builders</div>
    </div>
    <a href="<?= url('products') ?>" class="btn btn-secondary btn-sm">View All →</a>
  </div>
  <div class="product-grid">
    <?php foreach ($featured as $p) product_card($p); ?>
  </div>

  <?php if ($deals): ?>
  <hr class="divider" style="margin:40px 0">
  <div class="flex-between mb-24">
    <div>
      <div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/786/786205.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Hot Deals</div>
      <div class="section-sub">Best discounts on premium components right now</div>
    </div>
  </div>
  <div class="product-grid">
    <?php foreach ($deals as $p) product_card($p); ?>
  </div>
  <?php endif; ?>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ALL PRODUCTS
══════════════════════════════════════════════════════════ */
function page_products(): void {
    $sort = in_array($_GET['sort'] ?? '', ['price_asc','price_desc','rating','newest']) ? $_GET['sort'] : 'rating';
    $order_map = ['price_asc'=>'p.price ASC','price_desc'=>'p.price DESC','rating'=>'p.rating DESC, p.review_count DESC','newest'=>'p.id DESC'];
    $products = db()->query("SELECT p.*, c.slug AS cat_slug, c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY {$order_map[$sort]}")->fetchAll();
    $cats = db()->query("SELECT * FROM categories ORDER BY sort_order")->fetchAll();

    page_open('All Products');
    echo '<div class="main">';
    echo '<div class="page-header flex-between">';
    echo '<div><div class="section-title">All Products</div><div class="section-sub">'.count($products).' products across 10 categories</div></div>';
    echo '<form method="GET"><select name="sort" class="form-control" style="width:auto" onchange="this.form.submit()">';
    foreach (['rating'=>'Top Rated','price_asc'=>'Price: Low–High','price_desc'=>'Price: High–Low','newest'=>'Newest'] as $v=>$l)
        echo "<option value='$v'".($sort===$v?' selected':'').">$l</option>";
    echo '</select></form></div>';
    echo '<div class="cat-strip" style="margin:0 0 24px;padding:0">';
    echo '<a href="'.url('products').'" class="cat-pill active">All</a>';
    $cat_images = [
        'gpu' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'cpu' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'ram' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'ssd' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'hdd' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'mb' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'psu' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'case' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'cooler' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'accessories' => 'https://cdn2.scenesku.com/resources/electronics.png'
    ];
    foreach ($cats as $c) {
        $img_url = $cat_images[$c['slug']] ?? $cat_images['gpu'];
        echo '<a href="'.url("category/{$c['slug']}").'" class="cat-pill"><img src="'.$img_url.'" alt="'.h($c['name']).'" style="width:24px;height:24px;object-fit:contain;border-radius:4px;vertical-align:middle;margin-right:6px;"> '.h($c['name']).'</a>';
    }
    echo '</div>';
    echo '<div class="product-grid">';
    foreach ($products as $p) product_card($p);
    echo '</div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CATEGORY
══════════════════════════════════════════════════════════ */
function page_category(string $slug): void {
    $cat = db()->prepare("SELECT * FROM categories WHERE slug=?")->execute([$slug]) ? db()->prepare("SELECT * FROM categories WHERE slug=?")->execute([$slug]) || true : null;
    $st = db()->prepare("SELECT * FROM categories WHERE slug=?");
    $st->execute([$slug]);
    $cat = $st->fetch();
    if (!$cat) { page_404(); return; }

    $products = db()->prepare("SELECT p.*, c.slug AS cat_slug, c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.category_id=? ORDER BY p.rating DESC");
    $products->execute([$cat['id']]);
    $products = $products->fetchAll();

    page_open(h($cat['name']));
    echo '<div class="main">';
    $cat_images = [
        'gpu' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'cpu' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'ram' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'ssd' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'hdd' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'mb' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'psu' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'case' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'cooler' => 'https://cdn2.scenesku.com/resources/electronics.png',
        'accessories' => 'https://cdn2.scenesku.com/resources/electronics.png'
    ];
    $img_url = $cat_images[$cat['slug']] ?? $cat_images['gpu'];
    echo '<div class="page-header"><div class="section-title"><img src="'.$img_url.'" alt="'.h($cat['name']).'" style="width:40px;height:40px;object-fit:contain;border-radius:6px;vertical-align:middle;margin-right:8px;"> '.h($cat['name']).'</div><div class="section-sub">'.count($products).' products available</div></div>';
    echo '<div class="product-grid">';
    foreach ($products as $p) product_card($p);
    echo '</div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PRODUCT DETAIL
══════════════════════════════════════════════════════════ */
function page_product(int $id): void {
    $p = product($id);
    if (!$p) { page_404(); return; }
    $discount = $p['original_price'] ? (int)round((1-$p['price']/$p['original_price'])*100) : 0;
    $specs = array_filter(array_map('trim', explode('|', $p['specs'])));

    page_open(h($p['name']));
    ?>
<div class="main">
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start">
    <div class="card">
      <div class="product-img-wrap" style="aspect-ratio:1;font-size:5rem">
        <img src="<?= url("assets/pimg/{$p['id']}.svg") ?>" alt="<?= h($p['name']) ?>" style="padding:32px">
      </div>
    </div>
    <div>
      <div class="cat-badge mb-8"><?= h($p['cat_name']) ?></div>
      <div class="product-brand mt-8" style="font-size:.85rem"><?= h($p['brand']) ?></div>
      <h1 style="font-size:1.5rem;font-weight:800;margin:8px 0;line-height:1.25"><?= h($p['name']) ?></h1>
      <div class="product-rating mb-16">
        <span class="stars"><?= stars((float)$p['rating']) ?></span>
        <span style="font-weight:600;color:var(--text)"><?= $p['rating'] ?></span>
        <span class="text-muted">(<?= number_format($p['review_count']) ?> reviews)</span>
      </div>
      <div class="flex gap-12 mb-16">
        <span style="font-size:2rem;font-weight:800"><?= inr((int)$p['price']) ?></span>
        <?php if ($p['original_price']): ?>
        <span style="font-size:1rem;color:var(--text3);text-decoration:line-through;align-self:flex-end;margin-bottom:6px"><?= inr((int)$p['original_price']) ?></span>
        <span class="tag tag-success" style="align-self:flex-end;margin-bottom:6px"><?= $discount ?>% OFF</span>
        <?php endif; ?>
      </div>
      <p style="color:var(--text2);line-height:1.6;margin-bottom:20px"><?= h($p['description']) ?></p>
      <?php if ($specs): ?>
      <div class="card card-pad mb-16" style="background:var(--bg3)">
        <div class="text-xs text-muted" style="text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:10px">Specifications</div>
        <?php foreach ($specs as $spec): ?>
        <?php $parts = explode(':', $spec, 2); ?>
        <div class="flex-between" style="padding:6px 0;border-bottom:1px solid var(--border)">
          <span class="text-sm text-muted"><?= h(trim($parts[0])) ?></span>
          <span class="text-sm" style="font-weight:500"><?= h(trim($parts[1] ?? '')) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <form method="POST" action="<?= url('cart/add') ?>">
        <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
        <div class="flex gap-8">
          <input type="number" name="quantity" value="1" min="1" max="10" class="form-control" style="width:80px">
          <button class="btn btn-primary btn-lg" style="flex:1"><img src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Add to Cart</button>
        </div>
      </form>
      <div class="flex gap-8 mt-12">
        <span class="tag tag-success">✓ In Stock (<?= $p['stock'] ?>)</span>
        <span class="tag tag-info"><img src="https://cdn-icons-png.flaticon.com/512/3082/3082383.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> Free Shipping</span>
        <span class="tag tag-info"><img src="https://cdn-icons-png.flaticon.com/512/1005/1005147.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> 1-Year Warranty</span>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CART
══════════════════════════════════════════════════════════ */
function page_cart(): void {
    $u = require_login();
    $lines = cart_lines($u['id']);
    $subtotal = cart_subtotal($lines);
    $coupon = get_coupon();
    $discount = coupon_discount($subtotal);
    $total = max(0, $subtotal - $discount);
    $shipping = $total >= 99900 ? 0 : 9900;
    $grand = $total + $shipping;

    page_open('Cart');
    ?>
<div class="main">
  <div class="page-header">
    <div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Shopping Cart</div>
    <div class="section-sub"><?= count($lines) ?> item(s)</div>
  </div>
  <?php if (!$lines): ?>
  <div class="card card-pad" style="text-align:center;padding:60px">
    <div style="font-size:4rem;margin-bottom:16px"><img src="https://cdn-icons-png.flaticon.com/512/3081/3081559.png" style="width:64px;height:64px;"></div>
    <div class="section-title">Your cart is empty</div>
    <a href="<?= url() ?>" class="btn btn-primary mt-24">Browse Products</a>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
    <div class="card">
      <table class="table">
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($lines as $l): ?>
          <tr>
            <td>
              <div class="flex gap-12">
                <div style="width:52px;height:52px;background:var(--bg3);border-radius:8px;overflow:hidden;flex-shrink:0">
                  <img src="<?= url("assets/pimg/{$l['product_id']}.svg") ?>" alt="" style="width:100%;height:100%;object-fit:contain;padding:6px">
                </div>
                <div>
                  <div style="font-weight:600;font-size:.875rem"><?= h($l['name']) ?></div>
                  <div style="font-size:.72rem;color:var(--text3)"><?= h($l['brand']) ?></div>
                </div>
              </div>
            </td>
            <td><?= inr((int)$l['price']) ?></td>
            <td>
              <form method="POST" action="<?= url('cart/update') ?>" style="display:flex;gap:6px;align-items:center">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= $l['product_id'] ?>">
                <input type="number" name="quantity" value="<?= $l['quantity'] ?>" min="1" max="99" class="form-control" style="width:68px">
                <button class="btn btn-secondary btn-sm">↺</button>
              </form>
            </td>
            <td style="font-weight:700"><?= inr((int)$l['subtotal']) ?></td>
            <td>
              <form method="POST" action="<?= url('cart/remove') ?>">
                <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="product_id" value="<?= $l['product_id'] ?>">
                <button class="btn btn-danger btn-sm">×</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
      <!-- COUPON BOX — intentionally vulnerable endpoint -->
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:12px"><img src="https://cdn-icons-png.flaticon.com/512/2662/2662503.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Apply Coupon</div>
        <?php if ($coupon): ?>
        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:12px;margin-bottom:12px">
          <div style="font-weight:700;color:#34d399">✓ <?= h($coupon['code']) ?> applied</div>
          <div class="text-sm text-muted mt-4"><?= h($coupon['pct']) ?>% off — applied <?= h($coupon['count']) ?>×</div>
          <div class="text-sm mt-4" style="color:#34d399">Effective discount: <?= round((1 - $coupon['factor'])*100, 1) ?>%</div>
        </div>
        <?php endif; ?>
        <!-- POST /cart/apply-coupon with coupon_code=WELCOME20 — apply multiple times to stack -->
        <form method="POST" action="<?= url('cart/apply-coupon') ?>">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <div class="flex gap-8">
            <input type="text" name="coupon_code" value="<?= $coupon ? h($coupon['code']) : '' ?>"
                   placeholder="Enter coupon code" class="form-control" style="flex:1">
            <button class="btn btn-secondary">Apply</button>
          </div>
        </form>
        <?php if ($coupon): ?>
        <form method="POST" action="<?= url('cart/remove-coupon') ?>" style="margin-top:8px">
          <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
          <button class="btn btn-danger btn-sm" style="width:100%">Remove Coupon</button>
        </form>
        <?php endif; ?>
      </div>

      <!-- ORDER SUMMARY -->
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:16px">Order Summary</div>
        <div class="flex-between text-sm mb-8"><span class="text-muted">Subtotal</span><span><?= inr($subtotal) ?></span></div>
        <?php if ($discount > 0): ?>
        <div class="flex-between text-sm mb-8" style="color:var(--green)"><span>Coupon Discount</span><span>−<?= inr($discount) ?></span></div>
        <?php endif; ?>
        <div class="flex-between text-sm mb-8">
          <span class="text-muted">Shipping</span>
          <span><?= $shipping === 0 ? '<span style="color:var(--green)">FREE</span>' : inr($shipping) ?></span>
        </div>
        <hr class="divider">
        <div class="flex-between" style="font-size:1.1rem;font-weight:800"><span>Total</span><span><?= inr($grand) ?></span></div>
        <a href="<?= url('checkout') ?>" class="btn btn-primary btn-lg mt-16" style="width:100%">Checkout →</a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: CHECKOUT
══════════════════════════════════════════════════════════ */
function page_checkout(): void {
    $u = require_login();
    $lines = cart_lines($u['id']);
    if (!$lines) { flash('error','Your cart is empty.'); redirect('cart'); }

    $subtotal = cart_subtotal($lines);
    $discount = coupon_discount($subtotal);
    $total    = max(0, $subtotal - $discount);
    $shipping = $total >= 99900 ? 0 : 9900;
    $grand    = $total + $shipping;
    $coupon   = get_coupon();

    page_open('Checkout');
    ?>
<div class="main">
  <div class="page-header"><div class="section-title">Checkout</div></div>
  <form method="POST" action="<?= url('checkout/submit') ?>">
    <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="card card-pad">
          <div style="font-weight:700;margin-bottom:16px">Shipping Details</div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Full Name</label>
              <input type="text" name="customer_name" required class="form-control" value="<?= h($u['name']) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input type="email" name="email" required class="form-control" value="<?= h($u['email']) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" required class="form-control" placeholder="+91 XXXXX XXXXX">
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <input type="text" name="address" required class="form-control" placeholder="House no., Street, Area">
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">City</label>
              <input type="text" name="city" required class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">State</label>
              <input type="text" name="state" required class="form-control">
            </div>
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">PIN Code</label>
              <input type="text" name="postal_code" required class="form-control" pattern="[0-9]{6}">
            </div>
            <div class="form-group">
              <label class="form-label">Country</label>
              <input type="text" name="country" value="India" class="form-control">
            </div>
          </div>
        </div>

        <div class="card card-pad">
          <div style="font-weight:700;margin-bottom:16px">Payment Method</div>
          <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach (['card'=>'<img src="https://cdn-icons-png.flaticon.com/512/5968/5968299.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Credit / Debit Card','upi'=>'<img src="https://cdn-icons-png.flaticon.com/512/5968/5968144.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> UPI (GPay / PhonePe)','cod'=>'<img src="https://cdn-icons-png.flaticon.com/512/3082/3082402.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Cash on Delivery'] as $v=>$l): ?>
            <label style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--bg3);border:1px solid var(--border);border-radius:8px;cursor:pointer">
              <input type="radio" name="payment_method" value="<?= $v ?>" <?= $v==='card'?'checked':'' ?> style="accent-color:var(--accent);width:18px;height:18px">
              <span style="font-weight:500"><?= $l ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:16px">Order Summary</div>
        <?php foreach ($lines as $l): ?>
        <div class="flex-between text-sm mb-8">
          <span class="text-muted" style="max-width:200px"><?= h($l['name']) ?> ×<?= $l['quantity'] ?></span>
          <span><?= inr((int)$l['subtotal']) ?></span>
        </div>
        <?php endforeach; ?>
        <hr class="divider">
        <div class="flex-between text-sm mb-8"><span class="text-muted">Subtotal</span><span><?= inr($subtotal) ?></span></div>
        <?php if ($discount > 0): ?>
        <div class="flex-between text-sm mb-8" style="color:var(--green)">
          <span>Coupon (<?= h($coupon['code']) ?> ×<?= h($coupon['count']) ?>)</span>
          <span>−<?= inr($discount) ?></span>
        </div>
        <?php endif; ?>
        <div class="flex-between text-sm mb-8">
          <span class="text-muted">Shipping</span>
          <span><?= $shipping ? inr($shipping) : '<span style="color:var(--green)">FREE</span>' ?></span>
        </div>
        <hr class="divider">
        <div class="flex-between" style="font-size:1.15rem;font-weight:800"><span>Total</span><span><?= inr($grand) ?></span></div>
        <button type="submit" class="btn btn-primary btn-lg mt-16" style="width:100%">Place Order →</button>
      </div>
    </div>
  </form>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PAYMENT
══════════════════════════════════════════════════════════ */
function page_payment(int $oid): void {
    $u = require_login();
    $st = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=? AND status='Pending Payment'");
    $st->execute([$oid, $u['id']]);
    $order = $st->fetch();
    if (!$order) { flash('error','Order not found or already paid.'); redirect('orders'); }

    page_open("Pay Order #$oid");
    ?>
<div class="main" style="max-width:640px;margin-left:auto;margin-right:auto">
  <div class="page-header"><div class="section-title">Complete Payment</div><div class="section-sub">Order #<?= $oid ?></div></div>
  <div class="card card-pad" style="text-align:center;margin-bottom:20px">
    <div style="font-size:.875rem;color:var(--text3);margin-bottom:4px">Amount Due</div>
    <div style="font-size:2.5rem;font-weight:900"><?= inr((int)$order['total']) ?></div>
    <div class="tag tag-info mt-8">Payment Method: <?= strtoupper(h($order['payment_method'])) ?></div>
  </div>
  <div class="card card-pad">
    <?php if ($order['payment_method'] === 'card'): ?>
    <div style="font-weight:700;margin-bottom:16px"><img src="https://cdn-icons-png.flaticon.com/512/5968/5968299.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Card Details</div>
    <form method="POST" action="<?= url('payment/process') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="order_id" value="<?= $oid ?>">
      <div class="form-group">
        <label class="form-label">Card Number</label>
        <input type="text" name="card_number" class="form-control" placeholder="4111 1111 1111 1111" maxlength="19">
      </div>
      <div class="grid-2">
        <div class="form-group">
          <label class="form-label">Expiry</label>
          <input type="text" name="card_expiry" class="form-control" placeholder="MM/YY" maxlength="5">
        </div>
        <div class="form-group">
          <label class="form-label">CVV</label>
          <input type="text" name="card_cvv" class="form-control" placeholder="123" maxlength="4">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Name on Card</label>
        <input type="text" name="card_name" class="form-control" placeholder="<?= h($order['customer_name']) ?>">
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Pay <?= inr((int)$order['total']) ?></button>
    </form>
    <?php elseif ($order['payment_method'] === 'upi'): ?>
    <div style="font-weight:700;margin-bottom:16px"><img src="https://cdn-icons-png.flaticon.com/512/5968/5968144.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> UPI Payment</div>
    <div style="text-align:center;padding:20px">
      <div style="font-size:5rem;margin-bottom:12px"><img src="https://cdn-icons-png.flaticon.com/512/5968/5968144.png" style="width:80px;height:80px;"></div>
      <div style="font-size:.875rem;color:var(--text2);margin-bottom:20px">Scan with any UPI app or enter VPA</div>
      <div style="background:var(--bg3);border:1px dashed var(--border);border-radius:8px;padding:12px;font-family:monospace;font-size:1.1rem;margin-bottom:20px;color:var(--accent2)">pchub@labpay</div>
    </div>
    <form method="POST" action="<?= url('payment/process') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="order_id" value="<?= $oid ?>">
      <div class="form-group">
        <label class="form-label">UTR / Reference Number</label>
        <input type="text" name="upi_ref" class="form-control" placeholder="XXXXXXXXXXXXX">
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Confirm Payment</button>
    </form>
    <?php else: ?>
    <div style="text-align:center;padding:32px">
      <div style="font-size:4rem;margin-bottom:16px"><img src="https://cdn-icons-png.flaticon.com/512/3082/3082402.png" style="width:64px;height:64px;"></div>
      <div style="font-weight:700;font-size:1.1rem;margin-bottom:8px">Cash on Delivery</div>
      <p style="color:var(--text2);margin-bottom:24px">Pay <?= inr((int)$order['total']) ?> when your order arrives.</p>
    </div>
    <form method="POST" action="<?= url('payment/process') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <input type="hidden" name="order_id" value="<?= $oid ?>">
      <button class="btn btn-primary btn-lg" style="width:100%">Confirm COD Order</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDER DETAIL
══════════════════════════════════════════════════════════ */
function page_order(int $oid): void {
    $u = require_login();
    $st = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $st->execute([$oid, $u['id']]);
    $order = $st->fetch();
    if (!$order) { page_404(); return; }

    $items = db()->prepare("SELECT * FROM order_items WHERE order_id=?")->execute([$oid]) ? [] : [];
    $ist = db()->prepare("SELECT * FROM order_items WHERE order_id=?");
    $ist->execute([$oid]);
    $items = $ist->fetchAll();

    $flag_shown = (bool)$order['coupon_code'] && (int)$order['coupon_applications'] >= 3;

    page_open("Order #$oid");
    ?>
<div class="main">
  <?php if ($flag_shown): ?>
  <div class="flag-banner">
    <h2><img src="https://cdn-icons-png.flaticon.com/512/1005/1005147.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Vulnerability Exploited!</h2>
    <p style="color:#86efac;margin-bottom:8px">You successfully exploited the coupon stacking business-logic vulnerability by applying <strong><?= h($order['coupon_code']) ?></strong> <?= $order['coupon_applications'] ?>× times.</p>
    <p style="color:#6ee7b7;font-size:.875rem;margin-bottom:12px">This reduced your cart value far below the intended 20% discount threshold.</p>
    <div>Your Flag:</div>
    <div class="flag-val"><?= LAB_FLAG ?></div>
  </div>
  <?php endif; ?>
  <div class="page-header flex-between">
    <div>
      <div class="section-title">Order #<?= $oid ?></div>
      <div class="section-sub"><?= date('d M Y, g:i A', strtotime($order['created_at'])) ?></div>
    </div>
    <span class="tag <?= match($order['status']){
      'Paid','Delivered'=>'tag-success',
      'Pending Payment'=>'tag-warning',
      default=>'tag-info'
    } ?>"><?= h($order['status']) ?></span>
  </div>

  <div style="display:grid;grid-template-columns:1fr 340px;gap:24px">
    <div class="card">
      <div style="padding:20px;border-bottom:1px solid var(--border);font-weight:700">Items Ordered</div>
      <table class="table">
        <thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td>
              <div style="font-weight:600;font-size:.875rem"><?= h($item['product_name']) ?></div>
              <div style="font-size:.72rem;color:var(--text3)"><?= h($item['brand']) ?></div>
            </td>
            <td><?= inr((int)$item['price']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td style="font-weight:700"><?= inr((int)$item['subtotal']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div style="display:flex;flex-direction:column;gap:16px">
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:12px">Payment Summary</div>
        <div class="flex-between text-sm mb-8"><span class="text-muted">Subtotal</span><span><?= inr((int)$order['subtotal']) ?></span></div>
        <?php if ($order['discount'] > 0): ?>
        <div class="flex-between text-sm mb-8" style="color:var(--green)">
          <span>Coupon (<?= h($order['coupon_code'] ?? '') ?> ×<?= $order['coupon_applications'] ?>)</span>
          <span>−<?= inr((int)$order['discount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="flex-between text-sm mb-8"><span class="text-muted">Shipping</span><span><?= $order['shipping'] ? inr((int)$order['shipping']) : '<span style="color:var(--green)">FREE</span>' ?></span></div>
        <hr class="divider">
        <div class="flex-between" style="font-weight:800;font-size:1.1rem"><span>Total</span><span><?= inr((int)$order['total']) ?></span></div>
      </div>

      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:12px">Shipping Address</div>
        <div class="text-sm" style="color:var(--text2);line-height:1.8">
          <?= h($order['customer_name']) ?><br>
          <?= h($order['address']) ?><br>
          <?= h($order['city']) ?>, <?= h($order['state']) ?> – <?= h($order['postal_code']) ?><br>
          <?= h($order['country']) ?>
        </div>
      </div>

      <?php if ($order['status'] === 'Pending Payment'): ?>
      <a href="<?= url("payment/$oid") ?>" class="btn btn-primary btn-lg" style="width:100%;text-align:center">Complete Payment</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ORDERS LIST
══════════════════════════════════════════════════════════ */
function page_orders(): void {
    $u = require_login();
    $st = db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC");
    $st->execute([$u['id']]);
    $orders = $st->fetchAll();

    page_open('My Orders');
    echo '<div class="main">';
    echo '<div class="page-header"><div class="section-title">My Orders</div><div class="section-sub">'.count($orders).' orders</div></div>';
    if (!$orders) {
        echo '<div class="card card-pad" style="text-align:center;padding:60px"><div style="font-size:3rem;margin-bottom:12px"><img src="https://cdn-icons-png.flaticon.com/512/2784/2784572.png" style="width:48px;height:48px;"></div><div class="section-title">No orders yet</div><a href="'.url().'" class="btn btn-primary mt-16">Start Shopping</a></div>';
    } else {
        echo '<div class="card"><table class="table"><thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Coupon</th><th>Status</th><th></th></tr></thead><tbody>';
        foreach ($orders as $o) {
            $badge = match($o['status']){'Paid','Delivered'=>'tag-success','Pending Payment'=>'tag-warning',default=>'tag-info'};
            echo "<tr><td>#".h($o['id'])."</td><td>".date('d M Y',strtotime($o['created_at']))."</td><td style='font-weight:700'>".inr((int)$o['total'])."</td>";
            echo "<td>".($o['coupon_code']?"<span class='tag tag-info'>".h($o['coupon_code'])." ×".h($o['coupon_applications'])."</span>":"—")."</td>";
            echo "<td><span class='tag $badge'>".h($o['status'])."</span></td><td><a href='".url("order/{$o['id']}")."' class='btn btn-secondary btn-sm'>View</a></td></tr>";
        }
        echo '</tbody></table></div>';
    }
    echo '</div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: DASHBOARD
══════════════════════════════════════════════════════════ */
function page_dashboard(): void {
    $u = require_login();
    $st = db()->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total),0) AS spent FROM orders WHERE user_id=?");
    $st->execute([$u['id']]);
    $stats = $st->fetch();
    $recent_st = db()->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY id DESC LIMIT 5");
    $recent_st->execute([$u['id']]);
    $recent = $recent_st->fetchAll();

    page_open('Dashboard');
    ?>
<div class="main">
  <div class="page-header">
    <div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Welcome, <?= h($u['name']) ?>!</div>
    <div class="section-sub">Member since <?= date('M Y', strtotime($u['created_at'])) ?></div>
  </div>
  <div class="grid-3 mb-24">
    <?php foreach ([['https://cdn-icons-png.flaticon.com/512/2784/2784572.png','Total Orders',$stats['cnt']],['https://cdn-icons-png.flaticon.com/512/3082/3082402.png','Total Spent',inr((int)$stats['spent'])],['https://cdn-icons-png.flaticon.com/512/1828/1828884.png','Loyalty Points','0']] as [$icon,$label,$val]): ?>
    <div class="card card-pad" style="text-align:center">
      <div style="font-size:2rem;margin-bottom:8px"><img src="<?= $icon ?>" style="width:32px;height:32px;"></div>
      <div class="text-muted text-sm"><?= $label ?></div>
      <div style="font-size:1.5rem;font-weight:800;margin-top:4px"><?= $val ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:24px">
    <div class="card">
      <div style="padding:20px;border-bottom:1px solid var(--border);font-weight:700">Recent Orders</div>
      <?php if ($recent): ?>
      <table class="table">
        <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recent as $o): ?>
          <tr>
            <td>#<?= $o['id'] ?></td>
            <td><?= date('d M Y',strtotime($o['created_at'])) ?></td>
            <td style="font-weight:700"><?= inr((int)$o['total']) ?></td>
            <td><span class="tag <?= match($o['status']){'Paid','Delivered'=>'tag-success','Pending Payment'=>'tag-warning',default=>'tag-info'} ?>"><?= h($o['status']) ?></span></td>
            <td><a href="<?= url("order/{$o['id']}") ?>" class="btn btn-secondary btn-sm">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php else: ?>
      <div style="padding:40px;text-align:center;color:var(--text3)">No orders yet</div>
      <?php endif; ?>
    </div>

    <div style="display:flex;flex-direction:column;gap:12px">
      <div class="card card-pad">
        <div style="font-weight:700;margin-bottom:12px">Quick Links</div>
        <?php foreach ([[url('orders'),'My Orders'],
                        [url('cart'),'View Cart'],
                        [url('profile'),'Edit Profile'],
                        [url('products'),'Shop Now']] as [$href,$label]): ?>
        <a href="<?= $href ?>" class="btn btn-secondary" style="width:100%;margin-bottom:8px;justify-content:flex-start"><?= $label ?></a>
        <?php endforeach; ?>
      </div>
      <div class="card card-pad" style="background:rgba(124,58,237,.08);border-color:rgba(124,58,237,.2)">
        <div style="font-size:.72rem;font-weight:700;color:var(--accent3);margin-bottom:8px"><img src="https://cdn-icons-png.flaticon.com/512/2991/2991108.png" style="width:16px;height:16px;vertical-align:middle;margin-right:4px;"> Lab Hint</div>
        <p class="text-sm" style="color:var(--text2);line-height:1.6">Try applying coupon <code style="background:var(--bg3);padding:2px 6px;border-radius:4px;color:var(--accent3)">WELCOME20</code> multiple times via Burp Suite. POST to <code style="background:var(--bg3);padding:2px 6px;border-radius:4px;color:var(--accent3)">/cart/apply-coupon</code> with <code style="background:var(--bg3);padding:2px 6px;border-radius:4px;color:var(--accent3)">coupon_code=WELCOME20</code>.</p>
      </div>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: PROFILE
══════════════════════════════════════════════════════════ */
function page_profile(): void {
    $u = require_login();
    page_open('Edit Profile');
    ?>
<div class="main" style="max-width:560px;margin-left:auto;margin-right:auto">
  <div class="page-header"><div class="section-title">Edit Profile</div></div>
  <div class="card card-pad">
    <form method="POST" action="<?= url('profile/update') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" value="<?= h($u['name']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= h($u['email']) ?>" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password <span class="text-muted">(leave blank to keep)</span></label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" minlength="6">
      </div>
      <button class="btn btn-primary" style="width:100%">Save Changes</button>
    </form>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGES: AUTH
══════════════════════════════════════════════════════════ */
function page_login(): void {
    if (user()) redirect('dashboard');
    page_open('Sign In');
    ?>
<div class="main" style="max-width:440px;margin-left:auto;margin-right:auto">
  <div class="card card-pad">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-size:2.5rem;margin-bottom:8px"><img src="https://cdn-icons-png.flaticon.com/512/295/295128.png" style="width:40px;height:40px;"></div>
      <div style="font-size:1.3rem;font-weight:800">Sign In</div>
      <div class="text-muted text-sm mt-4">Welcome back to PCHub</div>
    </div>
    <form method="POST" action="<?= url('login') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required autofocus placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required placeholder="••••••••">
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Sign In</button>
    </form>
    <div style="text-align:center;margin-top:20px;color:var(--text3);font-size:.85rem">
      Don't have an account? <a href="<?= url('register') ?>">Register</a>
    </div>
    <div style="margin-top:20px;padding:12px;background:var(--bg3);border-radius:8px;font-size:.78rem;color:var(--text3)">
      <strong style="color:var(--accent3)">Lab Demo Accounts:</strong><br>
      Admin: admin@pchub.lab / admin123<br>
      Student: student@pchub.lab / student123
    </div>
  </div>
</div>
<?php page_close();
}

function page_register(): void {
    if (user()) redirect('dashboard');
    page_open('Register');
    ?>
<div class="main" style="max-width:440px;margin-left:auto;margin-right:auto">
  <div class="card card-pad">
    <div style="text-align:center;margin-bottom:24px">
      <div style="font-size:2.5rem;margin-bottom:8px"><img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" style="width:40px;height:40px;"></div>
      <div style="font-size:1.3rem;font-weight:800">Create Account</div>
    </div>
    <form method="POST" action="<?= url('register') ?>">
      <input type="hidden" name="_csrf" value="<?= h(csrf_token()) ?>">
      <div class="form-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required minlength="6">
      </div>
      <button class="btn btn-primary btn-lg" style="width:100%">Create Account</button>
    </form>
    <div style="text-align:center;margin-top:20px;color:var(--text3);font-size:.85rem">
      Already have an account? <a href="<?= url('login') ?>">Sign In</a>
    </div>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: ADMIN
══════════════════════════════════════════════════════════ */
function require_admin(): array {
    $u = require_login();
    if (!$u['is_admin']) { flash('error','Access denied.'); redirect('dashboard'); }
    return $u;
}

function page_admin(): void {
    require_admin();
    $stats = [
        'products' => (int)db()->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'orders'   => (int)db()->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'users'    => (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'revenue'  => (int)db()->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='Paid'")->fetchColumn(),
    ];
    page_open('Admin Panel');
    ?>
<div class="main">
  <div class="page-header"><div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/126/126472.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Admin Panel</div></div>
  <div class="grid-3 mb-24" style="grid-template-columns:repeat(4,1fr)">
    <?php foreach ([['https://cdn-icons-png.flaticon.com/512/2784/2784556.png',$stats['products'],'Products'],['https://cdn-icons-png.flaticon.com/512/2784/2784572.png',$stats['orders'],'Orders'],['https://cdn-icons-png.flaticon.com/512/1077/1077114.png',$stats['users'],'Users'],['https://cdn-icons-png.flaticon.com/512/3082/3082402.png',inr($stats['revenue']),'Revenue']] as [$i,$v,$l]): ?>
    <div class="card card-pad" style="text-align:center"><div style="font-size:2rem"><img src="<?= $i ?>" style="width:32px;height:32px;"></div><div style="font-size:1.4rem;font-weight:800;margin:6px 0"><?= $v ?></div><div class="text-muted text-sm"><?= $l ?></div></div>
    <?php endforeach; ?>
  </div>
  <div class="grid-2">
    <?php foreach ([['admin/products','<img src="https://cdn-icons-png.flaticon.com/512/2784/2784556.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Products','Manage product catalog'],['admin/orders','<img src="https://cdn-icons-png.flaticon.com/512/2784/2784572.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Orders','View and manage orders'],['admin/users','<img src="https://cdn-icons-png.flaticon.com/512/1077/1077114.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Users','View registered users'],['admin/coupons','<img src="https://cdn-icons-png.flaticon.com/512/2662/2662503.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Coupons','Manage discount codes']] as [$href,$title,$sub]): ?>
    <a href="<?= url($href) ?>" class="card card-pad" style="display:block;transition:.2s;text-decoration:none;color:var(--text)">
      <div style="font-size:1.1rem;font-weight:700"><?= $title ?></div>
      <div class="text-muted text-sm mt-4"><?= $sub ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php page_close();
}

function page_admin_products(): void {
    require_admin();
    $products = db()->query("SELECT p.*,c.name AS cat_name FROM products p JOIN categories c ON c.id=p.category_id ORDER BY c.sort_order,p.id")->fetchAll();
    page_open('Admin: Products');
    echo '<div class="main"><div class="page-header"><div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/2784/2784556.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Products</div></div><div class="card"><table class="table"><thead><tr><th>ID</th><th>Name</th><th>Brand</th><th>Category</th><th>Price</th><th>Stock</th><th>Rating</th></tr></thead><tbody>';
    foreach ($products as $p)
        echo "<tr><td>#{$p['id']}</td><td style='font-weight:600'>".h($p['name'])."</td><td>".h($p['brand'])."</td><td><span class='tag tag-info'>".h($p['cat_name'])."</span></td><td style='font-weight:700'>".inr((int)$p['price'])."</td><td>{$p['stock']}</td><td><img src='https://cdn-icons-png.flaticon.com/512/1828/1828884.png' style='width:16px;height:16px;vertical-align:middle;margin-right:4px;'> {$p['rating']}</td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

function page_admin_orders(): void {
    require_admin();
    $orders = db()->query("SELECT o.*,u.name AS user_name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.id DESC")->fetchAll();
    page_open('Admin: Orders');
    echo '<div class="main"><div class="page-header"><div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/2784/2784572.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Orders</div></div><div class="card"><table class="table"><thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Discount</th><th>Coupon</th><th>Apps</th><th>Status</th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $badge = match($o['status']){'Paid','Delivered'=>'tag-success','Pending Payment'=>'tag-warning',default=>'tag-info'};
        echo "<tr><td><a href='".url("order/{$o['id']}")."'>#".h($o['id'])."</a></td><td>".h($o['user_name'])."</td><td style='font-weight:700'>".inr((int)$o['total'])."</td><td style='color:var(--green)'>".($o['discount']?'−'.inr((int)$o['discount']):'—')."</td><td>".($o['coupon_code']?"<span class='tag tag-info'>".h($o['coupon_code'])."</span>":'—')."</td><td>".h($o['coupon_applications'])."</td><td><span class='tag $badge'>".h($o['status'])."</span></td></tr>";
    }
    echo '</tbody></table></div></div>';
    page_close();
}

function page_admin_users(): void {
    require_admin();
    $users = db()->query("SELECT u.*,(SELECT COUNT(*) FROM orders WHERE user_id=u.id) AS order_count FROM users u ORDER BY u.id")->fetchAll();
    page_open('Admin: Users');
    echo '<div class="main"><div class="page-header"><div class="section-title">👥 Users</div></div><div class="card"><table class="table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Orders</th><th>Role</th><th>Joined</th></tr></thead><tbody>';
    foreach ($users as $u)
        echo "<tr><td>#{$u['id']}</td><td style='font-weight:600'>".h($u['name'])."</td><td>".h($u['email'])."</td><td>{$u['order_count']}</td><td>".($u['is_admin']?"<span class='tag tag-warning'>Admin</span>":"<span class='tag tag-info'>User</span>")."</td><td>".date('d M Y',strtotime($u['created_at']))."</td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

function page_admin_coupons(): void {
    require_admin();
    $coupons = db()->query("SELECT c.*,cat.name AS cat_name FROM coupons c LEFT JOIN categories cat ON cat.id=c.intended_category_id ORDER BY c.id")->fetchAll();
    page_open('Admin: Coupons');
    echo '<div class="main"><div class="page-header"><div class="section-title"><img src="https://cdn-icons-png.flaticon.com/512/2662/2662503.png" style="width:24px;height:24px;vertical-align:middle;margin-right:8px;"> Coupons</div></div>';
    echo '<div class="card card-pad mb-16" style="background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.2)"><div style="font-weight:700;color:#f87171;margin-bottom:6px"><img src="https://cdn-icons-png.flaticon.com/512/1005/1005147.png" style="width:20px;height:20px;vertical-align:middle;margin-right:6px;"> Lab Note — Intentional Vulnerability</div><p class="text-sm" style="color:var(--text2);line-height:1.6">Coupon <code style="background:var(--bg3);padding:2px 6px;border-radius:4px;color:var(--accent3)">WELCOME20</code> is intended for Accessories only, but no server-side category check is enforced. It can also be applied multiple times (stacking). This is the training vulnerability.</p></div>';
    echo '<div class="card"><table class="table"><thead><tr><th>Code</th><th>Discount</th><th>Intended For</th><th>Enforced?</th><th>Stackable?</th><th>Active</th></tr></thead><tbody>';
    foreach ($coupons as $c)
        echo "<tr><td style='font-family:monospace;font-weight:700;color:var(--accent3)'>".h($c['code'])."</td><td style='font-weight:700;color:var(--green)'>{$c['discount_pct']}%</td><td>".($c['cat_name']?"<span class='tag tag-info'>".h($c['cat_name'])."</span>":'<span class="text-muted">All categories</span>')."</td><td><span class='tag tag-danger'><img src='https://cdn-icons-png.flaticon.com/512/1828/1828698.png' style='width:16px;height:16px;vertical-align:middle;margin-right:4px;'> No</span></td><td><span class='tag tag-danger'><img src='https://cdn-icons-png.flaticon.com/512/190/190411.png' style='width:16px;height:16px;vertical-align:middle;margin-right:4px;'> Yes (Bug!)</span></td><td>".($c['active']?"<span class='tag tag-success'>Active</span>":"<span class='tag tag-danger'>Inactive</span>")."</td></tr>";
    echo '</tbody></table></div></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: INSTALL / RESET
══════════════════════════════════════════════════════════ */
function page_install(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        install_schema(db(), true);
        flash('success','Database reset and reseeded successfully.');
        redirect('');
    }
    page_open('Reset Database');
    ?>
<div class="main" style="max-width:500px;margin-left:auto;margin-right:auto">
  <div class="card card-pad">
    <div style="text-align:center;margin-bottom:20px">
      <div style="font-size:3rem;margin-bottom:8px"><img src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png" style="width:48px;height:48px;"></div>
      <div style="font-size:1.3rem;font-weight:800">Reset Lab Database</div>
      <p class="text-muted text-sm mt-8">This will drop and recreate all tables, losing all orders and user data.</p>
    </div>
    <form method="POST">
      <button class="btn btn-danger btn-lg" style="width:100%">Reset Database</button>
    </form>
    <a href="<?= url() ?>" class="btn btn-secondary" style="width:100%;text-align:center;margin-top:10px">Cancel</a>
  </div>
</div>
<?php page_close();
}

/* ══════════════════════════════════════════════════════════
   PAGE: 404
══════════════════════════════════════════════════════════ */
function page_404(): void {
    http_response_code(404);
    page_open('404 Not Found');
    echo '<div class="main" style="text-align:center;padding:80px 20px"><div style="font-size:4rem;margin-bottom:16px">🔍</div><div class="section-title">404 — Page Not Found</div><a href="'.url().'" class="btn btn-primary mt-24">Go Home</a></div>';
    page_close();
}

/* ══════════════════════════════════════════════════════════
   ACTIONS
══════════════════════════════════════════════════════════ */
function action_login(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('login'); }
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $st = db()->prepare("SELECT * FROM users WHERE email=?");
    $st->execute([$email]);
    $u = $st->fetch();
    if (!$u || !password_verify($pass, $u['password_hash'])) {
        flash('error','Invalid email or password.');
        redirect('login');
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = $u['id'];
    flash('success', 'Welcome back, '.$u['name'].'!');
    redirect('dashboard');
}

function action_register(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('register'); }
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$name || !$email || !$pass || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
        flash('error','Please fill in all fields correctly.'); redirect('register');
    }
    if (strlen($pass) < 6) { flash('error','Password must be at least 6 characters.'); redirect('register'); }
    try {
        $st = db()->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)");
        $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $uid = (int)db()->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['uid'] = $uid;
        flash('success','Account created! Welcome to PCHub.');
        redirect('dashboard');
    } catch (PDOException $e) {
        flash('error','Email already in use.'); redirect('register');
    }
}

function action_logout(): void {
    verify_csrf();
    session_destroy();
    header('Location: '.url('login')); exit;
}

function action_cart_add(): void {
    $u = require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, min(99, (int)($_POST['quantity'] ?? 1)));
    $p = product($pid);
    if (!$p) { flash('error','Product not found.'); redirect(''); }
    db()->prepare("INSERT INTO cart_items (user_id,product_id,quantity) VALUES (?,?,?)
                   ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)")
        ->execute([$u['id'], $pid, $qty]);
    flash('success', h($p['name']).' added to cart.');
    redirect('cart');
}

function action_cart_update(): void {
    $u = require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, min(99, (int)($_POST['quantity'] ?? 1)));
    db()->prepare("UPDATE cart_items SET quantity=? WHERE user_id=? AND product_id=?")
        ->execute([$qty, $u['id'], $pid]);
    redirect('cart');
}

function action_cart_remove(): void {
    $u = require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    db()->prepare("DELETE FROM cart_items WHERE user_id=? AND product_id=?")->execute([$u['id'], $pid]);
    flash('success','Item removed from cart.');
    redirect('cart');
}

/*
 * INTENTIONALLY VULNERABLE ENDPOINT
 * POST /cart/apply-coupon
 * param: coupon_code
 *
 * Bug 1 (Stacking): No check whether coupon already applied — applies again, multiplying factor.
 * Bug 2 (Scope bypass): No category restriction enforced despite intended_category_id in DB.
 */
function action_apply_coupon(): void {
    $u = require_login();
    verify_csrf();
    $code = strtoupper(trim($_POST['coupon_code'] ?? ''));
    if (!$code) { flash('error','Please enter a coupon code.'); redirect('cart'); }

    $st = db()->prepare("SELECT * FROM coupons WHERE code=? AND active=1");
    $st->execute([$code]);
    $coupon = $st->fetch();
    if (!$coupon) { flash('error','Invalid or expired coupon code.'); redirect('cart'); }

    // --- INTENTIONAL BUG: no "already applied" check, no category restriction check ---
    $existing = $_SESSION['coupon'] ?? [];
    if ($existing && $existing['code'] !== $code) {
        // Different coupon — replace it
        $_SESSION['coupon'] = ['code' => $code, 'pct' => (int)$coupon['discount_pct'], 'factor' => 1 - ($coupon['discount_pct']/100), 'count' => 1];
        flash('success', "Coupon $code applied! {$coupon['discount_pct']}% discount.");
    } else {
        // Same coupon (or first time) — STACK IT (each application multiplies by 0.8)
        $prev_factor = (float)($existing['factor'] ?? 1.0);
        $new_factor  = $prev_factor * (1 - ($coupon['discount_pct']/100));
        $count = (int)($existing['count'] ?? 0) + 1;
        $_SESSION['coupon'] = ['code' => $code, 'pct' => (int)$coupon['discount_pct'], 'factor' => $new_factor, 'count' => $count];
        $eff_discount = round((1 - $new_factor) * 100, 1);
        flash('success', "Coupon $code applied (×$count)! Effective discount: $eff_discount%");
    }
    redirect('cart');
}

function action_remove_coupon(): void {
    require_login();
    verify_csrf();
    unset($_SESSION['coupon']);
    flash('info','Coupon removed.');
    redirect('cart');
}

function action_checkout_submit(): void {
    $u = require_login();
    verify_csrf();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('checkout');

    $lines = cart_lines($u['id']);
    if (!$lines) { flash('error','Your cart is empty.'); redirect('cart'); }

    $subtotal = cart_subtotal($lines);
    $discount = coupon_discount($subtotal);
    $coupon   = get_coupon();
    $total    = max(0, $subtotal - $discount);
    $shipping = $total >= 99900 ? 0 : 9900;
    $grand    = $total + $shipping;

    $required = ['customer_name','email','phone','address','city','state','postal_code','country'];
    foreach ($required as $f) {
        if (empty(trim($_POST[$f] ?? ''))) { flash('error','Please fill all shipping fields.'); redirect('checkout'); }
    }
    $pm = in_array($_POST['payment_method']??'', ['card','upi','cod']) ? $_POST['payment_method'] : 'card';

    $oid_st = db()->prepare(
        "INSERT INTO orders (user_id,subtotal,discount,coupon_code,coupon_applications,shipping,total,
         customer_name,email,phone,address,city,state,postal_code,country,payment_method,status)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $oid_st->execute([
        $u['id'], $subtotal, $discount,
        $coupon['code'] ?? null, $coupon['count'] ?? 0,
        $shipping, $grand,
        trim($_POST['customer_name']), trim($_POST['email']), trim($_POST['phone']),
        trim($_POST['address']), trim($_POST['city']), trim($_POST['state']),
        trim($_POST['postal_code']), trim($_POST['country']), $pm, 'Pending Payment'
    ]);
    $oid = (int)db()->lastInsertId();

    $ii = db()->prepare("INSERT INTO order_items (order_id,product_id,product_name,brand,price,quantity,subtotal) VALUES (?,?,?,?,?,?,?)");
    foreach ($lines as $l)
        $ii->execute([$oid, $l['product_id'], $l['name'], $l['brand'], $l['price'], $l['quantity'], $l['subtotal']]);

    // Clear cart and coupon
    db()->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$u['id']]);
    unset($_SESSION['coupon']);

    flash('success','Order placed! Please complete your payment.');
    redirect("payment/$oid");
}

function action_payment_process(): void {
    $u = require_login();
    verify_csrf();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('orders');

    $oid = (int)($_POST['order_id'] ?? 0);
    $st = db()->prepare("SELECT * FROM orders WHERE id=? AND user_id=? AND status='Pending Payment'");
    $st->execute([$oid, $u['id']]);
    $order = $st->fetch();
    if (!$order) { flash('error','Order not found or already paid.'); redirect('orders'); }

    db()->prepare("UPDATE orders SET status='Paid', paid_at=NOW() WHERE id=?")->execute([$oid]);
    flash('success','Payment successful! Your order is confirmed.');
    redirect("order/$oid");
}

function action_profile_update(): void {
    $u = require_login();
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL))
        { flash('error','Invalid name or email.'); redirect('profile'); }
    try {
        if ($pass && strlen($pass) >= 6) {
            db()->prepare("UPDATE users SET name=?,email=?,password_hash=? WHERE id=?")
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $u['id']]);
        } else {
            db()->prepare("UPDATE users SET name=?,email=? WHERE id=?")->execute([$name, $email, $u['id']]);
        }
        flash('success','Profile updated.');
        redirect('profile');
    } catch (PDOException) {
        flash('error','Email already in use.'); redirect('profile');
    }
}
