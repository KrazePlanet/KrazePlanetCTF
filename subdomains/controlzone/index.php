<?php
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rel = '/' . ltrim(substr($uri, strlen(BASE)), '/');
if ($rel !== '/') $rel = rtrim($rel, '/');
$method = $_SERVER['REQUEST_METHOD'];

match (true) {
    $rel === '/'                                              => page_home(),
    $rel === '/register' && $method === 'GET'                => page_register(),
    $rel === '/register' && $method === 'POST'               => action_register(),
    $rel === '/login'    && $method === 'GET'                 => page_login(),
    $rel === '/login'    && $method === 'POST'                => action_login(),
    $rel === '/logout'                                        => action_logout(),
    (bool)preg_match('#^/product/(\d+)$#', $rel, $m)        => page_product((int)$m[1]),
    $rel === '/cart'                                          => page_cart(),
    $rel === '/cart/add'    && $method === 'POST'             => action_cart_add(),
    $rel === '/cart/update' && $method === 'POST'             => action_cart_update(),
    $rel === '/cart/remove' && $method === 'POST'             => action_cart_remove(),
    $rel === '/checkout'    && $method === 'GET'              => page_checkout(),
    $rel === '/checkout'    && $method === 'POST'             => action_checkout(),
    (bool)preg_match('#^/payment-page/(\d+)$#', $rel, $m)   => page_payment((int)$m[1]),
    $rel === '/payment'     && $method === 'POST'             => action_payment(),
    (bool)preg_match('#^/order/(\d+)$#', $rel, $m)          => page_order((int)$m[1]),
    $rel === '/orders'                                        => page_orders(),
    $rel === '/profile'                                       => page_profile(),
    default                                                   => page_404(),
};

// ═══════════════════ PAGES ═══════════════════

function page_home(): void {
    $q   = trim($_GET['q'] ?? '');
    $cat = trim($_GET['cat'] ?? '');
    $sql = 'SELECT * FROM products';
    $where = []; $args = [];
    if ($q  !== '') { $where[] = 'name LIKE ?'; $args[] = "%$q%"; }
    if ($cat !== '') { $where[] = 'category = ?'; $args[] = $cat; }
    if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
    $sql .= ' ORDER BY id';
    $st = db()->prepare($sql); $st->execute($args);
    $products = $st->fetchAll();
    $categories = db()->query('SELECT DISTINCT category FROM products ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

    layout_head('Gaming Controllers Store');

    // Hero
    $hero_p = $products[0] ?? null;
    echo '<div class="hero">';
    echo '<div>';
    echo '<div class="hero-eyebrow">🔥 New Arrivals 2025</div>';
    echo '<h1>Level Up Your<br><span>Game Control</span></h1>';
    echo '<p class="hero-sub">India\'s best gaming controllers — wireless, wired & pro-grade. Free delivery on all orders.</p>';
    echo '<a href="' . url() . '" class="hero-cta">Shop All Controllers →</a>';
    echo '</div>';
    if ($hero_p) {
        echo '<div class="hero-img-wrap">' . product_img($hero_p['image'], $hero_p['name'], '260px') . '</div>';
    }
    echo '</div>';

    // Trust bar
    echo '<div class="trust-bar">';
    $trust = [
        ['🚚', 'Free Delivery', 'On all orders above ₹999'],
        ['🔄', 'Easy Returns', '7-day hassle-free returns'],
        ['✅', '1-Year Warranty', 'On all controllers'],
        ['🔒', 'Secure Checkout', '100% safe payments'],
    ];
    foreach ($trust as [$icon, $title, $sub]) {
        echo '<div class="trust-item"><div class="trust-icon">' . $icon . '</div>';
        echo '<div class="trust-text"><strong>' . $title . '</strong>' . $sub . '</div></div>';
    }
    echo '</div>';

    // Filter + grid
    echo '<div class="section-hd"><div class="section-title">All Controllers</div>';
    echo '<div class="text-muted" style="font-size:.85rem">' . count($products) . ' products</div></div>';

    echo '<div class="filter-bar">';
    echo '<button class="filter-pill active" data-cat="">All categories</button>';
    foreach ($categories as $c) echo '<button class="filter-pill" data-cat="' . h($c) . '">' . h($c) . '</button>';
    echo '</div>';

    echo '<div class="product-grid">';
    foreach ($products as $p) {
        $disc  = $p['old_price'] ? round((1 - $p['price'] / $p['old_price']) * 100) : 0;
        $isNew = $p['id'] >= 13;
        echo '<div class="p-item" data-cat="' . h($p['category']) . '">';
        echo '<div class="p-card">';
        echo '<div class="p-img-wrap">';
        if ($disc >= 30) echo '<div class="p-badge">-' . $disc . '% OFF</div>';
        elseif ($isNew)  echo '<div class="p-badge p-badge-new">NEW</div>';
        echo '<a href="' . url('product/' . $p['id']) . '">' . product_img($p['image'], $p['name']) . '</a>';
        echo '</div>';
        echo '<div class="p-body">';
        echo '<div class="p-cat">' . h($p['category']) . '</div>';
        echo '<div class="p-name"><a href="' . url('product/' . $p['id']) . '">' . h($p['name']) . '</a></div>';
        echo '<div class="p-stars">' . stars($p['rating']) . ' <span>(' . number_format($p['reviews']) . ')</span></div>';
        echo '<div class="p-price-row"><span class="p-price">' . inr($p['price']) . '</span>';
        if ($p['old_price']) {
            echo '<span class="p-old">' . inr($p['old_price']) . '</span>';
            echo '<span class="p-discount">-' . $disc . '%</span>';
        }
        echo '</div>';
        echo '<form method="post" action="' . url('cart/add') . '">';
        echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
        echo '<input type="hidden" name="product_id" value="' . h($p['id']) . '">';
        echo '<input type="hidden" name="quantity" value="1">';
        echo '<button class="btn-add">Add to Cart 🛒</button>';
        echo '</form>';
        echo '</div></div></div>';
    }
    echo '</div>';

    layout_foot();
}

function page_product(int $id): void {
    $p = product($id);
    if (!$p) { page_404(); return; }
    $disc = $p['old_price'] ? round((1 - $p['price'] / $p['old_price']) * 100) : 0;
    $saving = $p['old_price'] ? $p['old_price'] - $p['price'] : 0;

    layout_head(h($p['name']));
    echo '<div class="bc"><a href="' . url() . '">Home</a><span class="bc-sep">›</span>';
    echo '<span>' . h($p['category']) . '</span><span class="bc-sep">›</span>';
    echo '<span>' . h($p['name']) . '</span></div>';

    echo '<div class="detail-grid">';
    echo '<div class="detail-gallery">' . product_img($p['image'], $p['name']) . '</div>';

    echo '<div>';
    echo '<div class="detail-cat">' . h($p['category']) . '</div>';
    echo '<h1 class="detail-name">' . h($p['name']) . '</h1>';
    echo '<div class="detail-stars">' . stars($p['rating']) . ' <span>' . number_format($p['reviews']) . ' ratings</span></div>';
    echo '<div class="detail-price-row">';
    echo '<span class="detail-price">' . inr($p['price']) . '</span>';
    if ($p['old_price']) {
        echo '<span class="detail-old">' . inr($p['old_price']) . '</span>';
        echo '<span class="detail-saving">You save ' . inr($saving) . ' (' . $disc . '% off)</span>';
    }
    echo '</div>';
    echo '<div class="detail-stock" style="margin-bottom:18px">✅ In Stock &nbsp;·&nbsp; 🚚 Free Delivery</div>';
    echo '<div class="detail-desc">' . h($p['description']) . '</div>';
    echo '<form class="detail-form" method="post" action="' . url('cart/add') . '">';
    echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
    echo '<input type="hidden" name="product_id" value="' . $p['id'] . '">';
    echo '<input type="number" name="quantity" class="detail-qty" value="1" min="1" max="10">';
    echo '<button class="btn btn-primary btn-lg" style="flex:1">🛒 Add to Cart</button>';
    echo '</form>';
    echo '</div></div>';

    layout_foot();
}

function page_cart(): void {
    $u = require_login();
    $lines = cart_lines((int)$u['id']);
    $total = cart_total($lines);

    layout_head('Cart');
    echo '<h1 class="pg-title">Shopping Cart</h1>';
    echo '<p class="pg-sub">' . count($lines) . ' item' . (count($lines) !== 1 ? 's' : '') . ' in your cart</p>';

    if (!$lines) {
        echo '<div class="empty"><div class="empty-icon">🛒</div>';
        echo '<h3>Your cart is empty</h3><p>Looks like you haven\'t added anything yet.</p>';
        echo '<a href="' . url() . '" class="btn btn-primary mt-3" style="display:inline-flex">Browse Controllers</a></div>';
        layout_foot(); return;
    }

    echo '<div class="cart-layout"><div>';
    echo '<div class="card" style="padding:0;overflow:hidden">';
    echo '<table><thead><tr>';
    echo '<th colspan="2">Product</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th><th></th>';
    echo '</tr></thead><tbody>';
    foreach ($lines as $l) {
        $neg = $l['subtotal'] < 0;
        echo '<tr>';
        echo '<td style="width:68px;padding-right:0"><div style="width:56px;height:56px;background:#f8f9fc;border-radius:8px;display:flex;align-items:center;justify-content:center;overflow:hidden">' . product_img($l['image'], $l['name'], '52px') . '</div></td>';
        echo '<td><a href="' . url('product/' . $l['product_id']) . '" style="font-weight:600;color:var(--c-text)">' . h($l['name']) . '</a></td>';
        echo '<td>' . inr($l['price']) . '</td>';
        echo '<td><form class="qty-row" method="post" action="' . url('cart/update') . '">';
        echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
        echo '<input type="hidden" name="product_id" value="' . $l['product_id'] . '">';
        echo '<input type="number" name="quantity" value="' . h($l['quantity']) . '" min="1" max="20">';
        echo '<button class="btn btn-ghost btn-sm">Update</button></form></td>';
        echo '<td class="fw-700' . ($neg ? ' text-red' : '') . '">' . inr($l['subtotal']) . '</td>';
        echo '<td><form method="post" action="' . url('cart/remove') . '">';
        echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
        echo '<input type="hidden" name="product_id" value="' . $l['product_id'] . '">';
        echo '<button class="btn btn-danger btn-sm">✕</button></form></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div></div>';

    // Order summary panel
    echo '<div class="order-panel">';
    echo '<div class="card"><div class="form-section-title">Order Summary</div>';
    foreach ($lines as $l) {
        $neg = $l['subtotal'] < 0;
        echo '<div class="summary-line"><span style="max-width:180px;line-height:1.3">' . h($l['name']) . '</span>';
        echo '<span class="' . ($neg ? 'text-red' : '') . ' fw-700">' . inr($l['subtotal']) . '</span></div>';
    }
    echo '<div class="summary-total"><span>Total</span><span>' . inr($total) . '</span></div>';
    echo '<div class="mt-3"><a href="' . url('checkout') . '" class="btn btn-primary btn-block btn-lg">Checkout →</a></div>';
    echo '<p style="font-size:.75rem;color:var(--c-muted);text-align:center;margin-top:10px">🔒 Secure checkout</p>';
    echo '</div></div>';
    echo '</div>';
    layout_foot();
}

function page_checkout(): void {
    $u = require_login();
    $lines = cart_lines((int)$u['id']);
    if (!$lines) { flash('error', 'Your cart is empty.'); redirect('cart'); }
    $total = cart_total($lines);

    layout_head('Checkout');
    echo '<h1 class="pg-title">Checkout</h1>';
    echo '<p class="pg-sub">Fill in your details to complete the order</p>';
    echo '<div class="checkout-layout">';

    echo '<form method="post" action="' . url('checkout') . '">';
    echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';

    echo '<div class="card"><div class="form-section-title">Contact Information</div>';
    echo '<div class="form-group"><label class="form-label">Full Name</label>';
    echo '<input class="form-input" name="customer_name" required value="' . h($u['name']) . '"></div>';
    echo '<div class="form-row">';
    echo '<div class="form-group"><label class="form-label">Email</label>';
    echo '<input class="form-input" name="email" type="email" required value="' . h($u['email']) . '"></div>';
    echo '<div class="form-group"><label class="form-label">Phone</label>';
    echo '<input class="form-input" name="phone" type="tel" required placeholder="+91 00000 00000"></div>';
    echo '</div></div>';

    echo '<div class="card"><div class="form-section-title">Shipping Address</div>';
    echo '<div class="form-group"><label class="form-label">Street Address</label>';
    echo '<input class="form-input" name="address" required placeholder="Flat / House No, Street, Area"></div>';
    echo '<div class="form-row">';
    echo '<div class="form-group"><label class="form-label">City</label><input class="form-input" name="city" required></div>';
    echo '<div class="form-group"><label class="form-label">State</label><input class="form-input" name="state" required></div>';
    echo '</div><div class="form-row">';
    echo '<div class="form-group"><label class="form-label">Postal Code</label><input class="form-input" name="postal_code" required></div>';
    echo '<div class="form-group"><label class="form-label">Country</label><input class="form-input" name="country" required value="India"></div>';
    echo '</div>';
    echo '<button class="btn btn-primary btn-block btn-lg mt-2">Place Order →</button>';
    echo '</div></form>';

    // Summary
    echo '<div class="checkout-panel"><div class="card">';
    echo '<div class="form-section-title">Your Order</div>';
    foreach ($lines as $l) {
        $neg = $l['subtotal'] < 0;
        echo '<div class="summary-line">';
        echo '<span style="display:flex;align-items:center;gap:8px;max-width:210px">';
        echo '<span style="flex-shrink:0;width:36px;height:36px;background:#f8f9fc;border-radius:6px;overflow:hidden;display:flex;align-items:center;justify-content:center">' . product_img($l['image'], $l['name'], '32px') . '</span>';
        echo '<span style="line-height:1.3;font-size:.83rem">' . h($l['name']) . '<br><span class="text-muted">Qty: ' . h($l['quantity']) . '</span></span>';
        echo '</span>';
        echo '<span class="' . ($neg ? 'text-red' : '') . ' fw-700">' . inr($l['subtotal']) . '</span>';
        echo '</div>';
    }
    echo '<div class="summary-total"><span>Total</span><span>' . inr($total) . '</span></div>';
    echo '</div></div>';
    echo '</div>';
    layout_foot();
}

function page_payment(int $oid): void {
    $u = require_login();
    $st = db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
    $st->execute([$oid, $u['id']]);
    $order = $st->fetch();
    if (!$order) { page_404(); return; }
    if ($order['status'] !== 'Pending Payment') {
        flash('info', 'This order has already been paid.'); redirect('order/' . $oid);
    }
    $si = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $si->execute([$oid]);
    $items = $si->fetchAll();

    layout_head('Payment');
    echo '<div class="payment-wrap">';
    echo '<h1 class="pg-title">Complete Payment</h1>';
    echo '<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:.82rem;color:#92400e;margin-bottom:24px">';
    echo '⚠️ <strong>Training environment</strong> — This is a simulated payment page. No real transaction occurs.';
    echo '</div>';

    echo '<div class="card mb-2">';
    echo '<div class="form-section-title">Order #' . $oid . ' Summary</div>';
    foreach ($items as $it) {
        $neg = $it['subtotal'] < 0;
        echo '<div class="summary-line"><span>' . h($it['product_name']) . ' &times; ' . h($it['quantity']) . '</span>';
        echo '<span class="' . ($neg ? 'text-red' : '') . ' fw-700">' . inr($it['subtotal']) . '</span></div>';
    }
    echo '<div class="summary-total"><span>Amount Due</span><span>' . inr($order['total']) . '</span></div>';
    echo '</div>';

    echo '<div class="card">';
    echo '<div class="form-section-title">Card Details</div>';
    echo '<div class="form-group"><label class="form-label">Card Number</label>';
    echo '<div class="fake-card-field">4242 4242 4242 4242 &nbsp;💳</div></div>';
    echo '<div class="form-row">';
    echo '<div class="form-group"><label class="form-label">Expiry</label><div class="fake-card-field">12 / 29</div></div>';
    echo '<div class="form-group"><label class="form-label">CVV</label><div class="fake-card-field">•••</div></div>';
    echo '</div>';
    echo '<form method="post" action="' . url('payment') . '">';
    echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
    echo '<input type="hidden" name="order_id" value="' . $oid . '">';
    echo '<button class="btn btn-primary btn-block btn-lg">🔒 Pay ' . inr($order['total']) . '</button>';
    echo '</form>';
    echo '<div class="pay-secure">🔒 Secured by SSL · Training Demo Only</div>';
    echo '</div></div>';
    layout_foot();
}

function page_order(int $id): void {
    $u = require_login();
    $st = db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
    $st->execute([$id, $u['id']]);
    $order = $st->fetch();
    if (!$order) { page_404(); return; }
    $si = db()->prepare('SELECT * FROM order_items WHERE order_id=?'); $si->execute([$id]);
    $items = $si->fetchAll();

    $slug = 's-' . strtolower(str_replace(' ', '-', $order['status']));

    layout_head('Order #' . $id);
    echo '<div class="bc"><a href="' . url('orders') . '">My Orders</a><span class="bc-sep">›</span>Order #' . $id . '</div>';
    echo '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px">';
    echo '<h1 class="pg-title" style="margin:0">Order #' . $id . '</h1>';
    echo '<span class="status-chip ' . $slug . '">' . h($order['status']) . '</span>';
    echo '</div>';

    echo '<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">';
    echo '<div class="card" style="padding:0;overflow:hidden">';
    echo '<table><thead><tr><th>Product</th><th class="text-right">Price</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr></thead><tbody>';
    foreach ($items as $it) {
        $neg = $it['subtotal'] < 0;
        echo '<tr><td class="fw-700">' . h($it['product_name']) . '</td>';
        echo '<td class="text-right">' . inr($it['price']) . '</td>';
        echo '<td class="text-right">' . h($it['quantity']) . '</td>';
        echo '<td class="text-right fw-700 ' . ($neg ? 'text-red' : '') . '">' . inr($it['subtotal']) . '</td></tr>';
    }
    echo '</tbody></table>';
    echo '<div style="text-align:right;font-size:1.05rem;font-weight:800;padding:16px 20px;border-top:2px solid var(--c-border)">';
    echo 'Total: ' . inr($order['total']) . '</div></div>';

    echo '<div>';
    echo '<div class="card mb-2"><div class="form-section-title">Shipping To</div>';
    echo '<p class="fw-700">' . h($order['customer_name']) . '</p>';
    echo '<p class="text-muted" style="margin-top:6px;line-height:1.7">';
    echo h($order['address']) . '<br>';
    echo h($order['city']) . ', ' . h($order['state']) . ' ' . h($order['postal_code']) . '<br>';
    echo h($order['country']) . '</p></div>';
    echo '<div class="card"><div class="form-section-title">Payment</div>';
    echo '<p class="fw-700 text-green">✓ Paid ' . inr($order['total']) . '</p>';
    if ($order['paid_at']) echo '<p class="text-muted" style="font-size:.8rem;margin-top:6px">' . $order['paid_at'] . '</p>';
    echo '</div></div>';
    echo '</div>';
    layout_foot();
}

function page_orders(): void {
    $u = require_login();
    $st = db()->prepare('SELECT * FROM orders WHERE user_id=? ORDER BY id DESC');
    $st->execute([$u['id']]);
    $orders = $st->fetchAll();

    layout_head('My Orders');
    echo '<h1 class="pg-title">My Orders</h1>';
    echo '<p class="pg-sub">' . count($orders) . ' order' . (count($orders) !== 1 ? 's' : '') . '</p>';

    if (!$orders) {
        echo '<div class="empty"><div class="empty-icon">📦</div>';
        echo '<h3>No orders yet</h3><p>When you place an order, it will appear here.</p>';
        echo '<a href="' . url() . '" class="btn btn-primary mt-3" style="display:inline-flex">Start Shopping</a></div>';
        layout_foot(); return;
    }

    echo '<div class="card" style="padding:0;overflow:hidden">';
    echo '<table><thead><tr><th>Order</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $slug = 's-' . strtolower(str_replace(' ', '-', $o['status']));
        $count_st = db()->prepare('SELECT COUNT(*) FROM order_items WHERE order_id=?'); $count_st->execute([$o['id']]);
        $item_count = $count_st->fetchColumn();
        echo '<tr>';
        echo '<td class="fw-700">#' . $o['id'] . '</td>';
        echo '<td class="text-muted">' . date('d M Y', strtotime($o['created_at'])) . '</td>';
        echo '<td class="text-muted">' . $item_count . ' item' . ($item_count !== 1 ? 's' : '') . '</td>';
        echo '<td class="fw-700">' . inr($o['total']) . '</td>';
        echo '<td><span class="status-chip ' . $slug . '">' . h($o['status']) . '</span></td>';
        echo '<td><a href="' . url('order/' . $o['id']) . '" class="btn btn-ghost btn-sm">View →</a></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
    layout_foot();
}

function page_register(): void {
    layout_head('Create Account');
    echo '<div style="max-width:460px;margin:40px auto">';
    echo '<h1 class="pg-title">Create Account</h1>';
    echo '<p class="pg-sub">Join ControlZone for faster checkout &amp; order tracking</p>';
    echo '<div class="card"><form method="post" action="' . url('register') . '">';
    echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
    echo '<div class="form-group"><label class="form-label">Full Name</label><input class="form-input" name="name" required autofocus placeholder="Your name"></div>';
    echo '<div class="form-group"><label class="form-label">Email Address</label><input class="form-input" name="email" type="email" required placeholder="you@example.com"></div>';
    echo '<div class="form-row">';
    echo '<div class="form-group"><label class="form-label">Password</label><input class="form-input" name="password" type="password" required minlength="6" placeholder="Min. 6 chars"></div>';
    echo '<div class="form-group"><label class="form-label">Confirm Password</label><input class="form-input" name="password2" type="password" required placeholder="Repeat password"></div>';
    echo '</div>';
    echo '<button class="btn btn-primary btn-block btn-lg">Create Account →</button>';
    echo '</form></div>';
    echo '<p class="text-muted mt-2" style="text-align:center;font-size:.88rem">Already have an account? <a href="' . url('login') . '">Login</a></p>';
    echo '</div>';
    layout_foot();
}

function page_login(): void {
    layout_head('Login');
    echo '<div style="max-width:420px;margin:40px auto">';
    echo '<h1 class="pg-title">Welcome Back</h1>';
    echo '<p class="pg-sub">Log in to your ControlZone account</p>';
    echo '<div class="card"><form method="post" action="' . url('login') . '">';
    echo '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
    echo '<div class="form-group"><label class="form-label">Email Address</label><input class="form-input" name="email" type="email" required autofocus placeholder="you@example.com"></div>';
    echo '<div class="form-group"><label class="form-label">Password</label><input class="form-input" name="password" type="password" required placeholder="Your password"></div>';
    echo '<button class="btn btn-primary btn-block btn-lg">Login →</button>';
    echo '</form></div>';
    echo '<p class="text-muted mt-2" style="text-align:center;font-size:.88rem">New here? <a href="' . url('register') . '">Create an account</a></p>';
    echo '</div>';
    layout_foot();
}

function page_profile(): void {
    $u = require_login();
    layout_head('My Profile');
    echo '<div style="max-width:520px">';
    echo '<h1 class="pg-title">My Profile</h1>';
    echo '<p class="pg-sub">Account details</p>';
    echo '<div class="card">';
    echo '<div class="form-group"><label class="form-label">Full Name</label>';
    echo '<div class="form-input" style="background:#f8f9fc;cursor:default">' . h($u['name']) . '</div></div>';
    echo '<div class="form-group"><label class="form-label">Email</label>';
    echo '<div class="form-input" style="background:#f8f9fc;cursor:default">' . h($u['email']) . '</div></div>';
    echo '<div class="form-group"><label class="form-label">Member Since</label>';
    echo '<div class="form-input" style="background:#f8f9fc;cursor:default">' . date('d M Y', strtotime($u['created_at'])) . '</div></div>';
    echo '<a href="' . url('orders') . '" class="btn btn-ghost">View My Orders</a>';
    echo '</div></div>';
    layout_foot();
}

function page_404(): void {
    http_response_code(404);
    layout_head('Not Found');
    echo '<div class="empty"><div class="empty-icon">🔍</div>';
    echo '<h3>Page Not Found</h3><p>The page you\'re looking for doesn\'t exist.</p>';
    echo '<a href="' . url() . '" class="btn btn-primary mt-3" style="display:inline-flex">Go Home</a></div>';
    layout_foot();
}

// ═══════════════════ ACTIONS ═══════════════════

function action_register(): void {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $pw2   = $_POST['password2'] ?? '';
    if (!$name || !$email || !$pw) { flash('error', 'All fields are required.'); redirect('register'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { flash('error', 'Invalid email address.'); redirect('register'); }
    if (strlen($pw) < 6) { flash('error', 'Password must be at least 6 characters.'); redirect('register'); }
    if ($pw !== $pw2)    { flash('error', 'Passwords do not match.'); redirect('register'); }
    $dup = db()->prepare('SELECT id FROM users WHERE email=?'); $dup->execute([$email]);
    if ($dup->fetch()) { flash('error', 'An account with that email already exists.'); redirect('register'); }
    $st = db()->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
    $st->execute([$name, $email, password_hash($pw, PASSWORD_DEFAULT)]);
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)db()->lastInsertId();
    flash('success', 'Welcome to ControlZone, ' . $name . '!');
    redirect('');
}

function action_login(): void {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $st = db()->prepare('SELECT id,name,password_hash FROM users WHERE email=?');
    $st->execute([$email]);
    $row = $st->fetch();
    if (!$row || !password_verify($pw, $row['password_hash'])) {
        flash('error', 'Invalid email or password.'); redirect('login');
    }
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$row['id'];
    flash('success', 'Welcome back, ' . $row['name'] . '!');
    redirect('');
}

function action_logout(): void {
    session_destroy();
    header('Location: ' . url('login')); exit;
}

function action_cart_add(): void {
    require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 1);
    $p = product($pid);
    if (!$p) { flash('error', 'Product not found.'); redirect(''); }
    if ($qty < 1 || $qty > 20) $qty = 1;
    $uid = (int)$_SESSION['uid'];
    db()->prepare('INSERT INTO cart_items (user_id,product_id,quantity) VALUES (?,?,?)
                   ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)')
        ->execute([$uid, $pid, $qty]);
    flash('success', h($p['name']) . ' added to cart.');
    $ref = $_SERVER['HTTP_REFERER'] ?? url('');
    header('Location: ' . $ref); exit;
}

function action_cart_update(): void {
    $u = require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    // Only validates that quantity is a valid integer — does NOT check that it is positive.
    $qty = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT);
    if ($qty === false) { flash('error', 'Quantity must be a number.'); redirect('cart'); }
    $qty = (int)$qty;
    $p = product($pid);
    if (!$p) { flash('error', 'Product not found.'); redirect('cart'); }
    db()->prepare('UPDATE cart_items SET quantity=? WHERE user_id=? AND product_id=?')
        ->execute([$qty, $u['id'], $pid]);
    redirect('cart');
}

function action_cart_remove(): void {
    $u = require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    db()->prepare('DELETE FROM cart_items WHERE user_id=? AND product_id=?')
        ->execute([$u['id'], $pid]);
    redirect('cart');
}

function action_checkout(): void {
    $u = require_login();
    verify_csrf();
    $lines = cart_lines((int)$u['id']);
    if (!$lines) { flash('error', 'Cart is empty.'); redirect('cart'); }

    $name    = trim($_POST['customer_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city    = trim($_POST['city'] ?? '');
    $state   = trim($_POST['state'] ?? '');
    $postal  = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if (!$name||!$email||!$phone||!$address||!$city||!$state||!$postal||!$country) {
        flash('error', 'Please fill in all fields.'); redirect('checkout');
    }

    $total = cart_total($lines);
    $d = db();
    $d->beginTransaction();
    $d->prepare("INSERT INTO orders (user_id,total,customer_name,email,phone,address,city,state,postal_code,country,status)
                 VALUES (?,?,?,?,?,?,?,?,?,?,'Pending Payment')")
      ->execute([$u['id'], $total, $name, $email, $phone, $address, $city, $state, $postal, $country]);
    $oid = (int)$d->lastInsertId();
    $si = $d->prepare('INSERT INTO order_items (order_id,product_id,product_name,price,quantity,subtotal) VALUES (?,?,?,?,?,?)');
    foreach ($lines as $l) $si->execute([$oid, $l['product_id'], $l['name'], $l['price'], $l['quantity'], $l['subtotal']]);
    $d->commit();
    redirect('payment-page/' . $oid);
}

function action_payment(): void {
    $u = require_login();
    verify_csrf();
    $oid = (int)($_POST['order_id'] ?? 0);
    $st = db()->prepare('SELECT * FROM orders WHERE id=? AND user_id=?');
    $st->execute([$oid, $u['id']]);
    $order = $st->fetch();
    if (!$order || $order['status'] !== 'Pending Payment') {
        flash('error', 'Order not found or already paid.'); redirect('orders');
    }
    db()->prepare("UPDATE orders SET status='Processing', paid_at=NOW() WHERE id=?")->execute([$oid]);
    db()->prepare('DELETE FROM cart_items WHERE user_id=?')->execute([$u['id']]);
    flash('success', '🎉 Payment successful! Your order #' . $oid . ' is confirmed.');
    redirect('order/' . $oid);
}
