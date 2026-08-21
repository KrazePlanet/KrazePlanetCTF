<?php
session_start();
require_once dirname(__DIR__) . '/includes/config.php';

if (!isset($_SESSION['foodie_admin'])) {
    header("Location: index.php");
    exit();
}

$active_tab = $_GET['tab'] ?? 'orders';
$msg = '';
$err = '';

// Handle Order Status Update
if (isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
    $msg = "Order #{$order_id} status updated to {$new_status}!";
}

// Handle Reservation Status Update
if (isset($_POST['update_res_status'])) {
    $res_id = intval($_POST['res_id']);
    $new_status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $res_id]);
    $msg = "Reservation #{$res_id} marked as {$new_status}!";
}

// Handle Add Menu Item
if (isset($_POST['add_menu_item'])) {
    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $discount_price = !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null;
    $rating = floatval($_POST['rating'] ?? 5.0);
    $badge = trim($_POST['badge'] ?? '');
    $image = trim($_POST['image'] ?? './assets/images/food-menu-1.png');
    $description = trim($_POST['description'] ?? '');

    if (!empty($title) && $price > 0) {
        $stmt = $pdo->prepare("INSERT INTO food_items (title, category, price, discount_price, rating, badge, image, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $category, $price, $discount_price, $rating, $badge, $image, $description]);
        $msg = "Added new food item: {$title}!";
    } else {
        $err = "Please enter valid title and price.";
    }
}

// Handle Delete Menu Item
if (isset($_GET['delete_item'])) {
    $del_id = intval($_GET['delete_item']);
    $pdo->prepare("DELETE FROM food_items WHERE id = ?")->execute([$del_id]);
    header("Location: dashboard.php?tab=menu&msg=Item+deleted+successfully");
    exit();
}

// Counts for KPI stats
$count_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$count_reservations = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$count_items = $pdo->query("SELECT COUNT(*) FROM food_items")->fetchColumn();
$count_subscribers = $pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();

// Fetch Data for Tables
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 50")->fetchAll();
$reservations = $pdo->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 50")->fetchAll();
$menu_items = $pdo->query("SELECT * FROM food_items ORDER BY id DESC")->fetchAll();
$subscribers = $pdo->query("SELECT * FROM subscribers ORDER BY id DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie Management Command Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Rubik:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #f84525;
            --primary-dark: #d63314;
            --bg-body: #0b1120;
            --sidebar-bg: #0f172a;
            --card-bg: #1e293b;
            --card-border: rgba(255,255,255,0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: var(--bg-body);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--card-border);
            padding: 24px;
            display: flex;
            flex-direction: column;
        }
        .brand {
            font-size: 26px;
            font-weight: 800;
            color: #fff;
            text-decoration: none;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .brand span { color: var(--primary); }
        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(248,69,37,0.12);
            color: #fff;
        }
        .nav-link.active {
            color: var(--primary);
            border-left: 3px solid var(--primary);
        }
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .kpi-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .kpi-num { font-size: 24px; font-weight: 800; }
        .kpi-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; }
        .content-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--card-border);
        }
        .table-responsive { overflow-x: auto; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            text-align: left;
        }
        th {
            background: rgba(15,23,42,0.6);
            padding: 12px 14px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        td {
            padding: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .badge-status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-Pending { background: rgba(245,158,11,0.2); color: #f59e0b; }
        .badge-Cooking { background: rgba(59,130,246,0.2); color: #3b82f6; }
        .badge-Out-for-Delivery { background: rgba(168,85,247,0.2); color: #a855f7; }
        .badge-Delivered, .badge-Confirmed { background: rgba(16,185,129,0.2); color: #10b981; }
        .badge-Cancelled { background: rgba(239,68,68,0.2); color: #ef4444; }
        .btn-action {
            background: rgba(255,255,255,0.08);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-action:hover { background: var(--primary); }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            background: rgba(15,23,42,0.6);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            outline: none;
        }
        .form-control:focus { border-color: var(--primary); }
        .alert-success {
            background: rgba(16,185,129,0.15);
            border: 1px solid rgba(16,185,129,0.3);
            color: #34d399;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <a href="../index.php" class="brand">
        <i class="fa-solid fa-utensils" style="color:var(--primary);"></i> Foodie<span>.</span>
    </a>

    <ul class="nav-links">
        <li>
            <a href="dashboard.php?tab=orders" class="nav-link <?= $active_tab === 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Orders
            </a>
        </li>
        <li>
            <a href="dashboard.php?tab=reservations" class="nav-link <?= $active_tab === 'reservations' ? 'active' : '' ?>">
                <i class="fa-solid fa-calendar-check"></i> Reservations
            </a>
        </li>
        <li>
            <a href="dashboard.php?tab=menu" class="nav-link <?= $active_tab === 'menu' ? 'active' : '' ?>">
                <i class="fa-solid fa-burger"></i> Menu Items
            </a>
        </li>
        <li>
            <a href="dashboard.php?tab=subscribers" class="nav-link <?= $active_tab === 'subscribers' ? 'active' : '' ?>">
                <i class="fa-solid fa-envelope-open-text"></i> Subscribers
            </a>
        </li>
        <li style="margin-top: auto;">
            <a href="../index.php" class="nav-link" target="_blank">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Visit Website
            </a>
        </li>
        <li>
            <a href="logout.php" class="nav-link" style="color:#f87171;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </li>
    </ul>
</aside>

<main class="main-content">
    <div class="header-bar">
        <div>
            <h2>Dashboard Overview</h2>
            <p style="color:var(--text-muted);font-size:14px;">Welcome back, <strong><?= htmlspecialchars($_SESSION['foodie_admin']) ?></strong></p>
        </div>
        <a href="logout.php" class="btn-action" style="background:var(--primary);">Logout</a>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert-success"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- KPI Statistics -->
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(248,69,37,0.15);color:#f84525;">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <div>
                <div class="kpi-num"><?= $count_orders ?></div>
                <div class="kpi-label">Total Orders</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6;">
                <i class="fa-solid fa-champagne-glasses"></i>
            </div>
            <div>
                <div class="kpi-num"><?= $count_reservations ?></div>
                <div class="kpi-label">Reservations</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(16,185,129,0.15);color:#10b981;">
                <i class="fa-solid fa-pizza-slice"></i>
            </div>
            <div>
                <div class="kpi-num"><?= $count_items ?></div>
                <div class="kpi-label">Dishes on Menu</div>
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-icon" style="background:rgba(168,85,247,0.15);color:#a855f7;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <div class="kpi-num"><?= $count_subscribers ?></div>
                <div class="kpi-label">VIP Subscribers</div>
            </div>
        </div>
    </div>

    <?php if($active_tab === 'orders'): ?>
        <!-- ── ORDERS MANAGER ──────────────────────────────────────────────── -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-receipt" style="color:var(--primary);margin-right:8px;"></i> Customer Delivery Orders</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Customer</th>
                            <th>Dish Ordered</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Delivery Address</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $ord): ?>
                            <tr>
                                <td><strong style="color:var(--primary);"><?= htmlspecialchars($ord['order_code']) ?></strong></td>
                                <td>
                                    <strong><?= htmlspecialchars($ord['customer_name']) ?></strong><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($ord['customer_phone']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($ord['item_name']) ?></td>
                                <td>x<?= $ord['quantity'] ?></td>
                                <td><strong>$<?= number_format($ord['total_price'], 2) ?></strong></td>
                                <td style="max-width:200px;font-size:12px;"><?= htmlspecialchars($ord['delivery_address']) ?></td>
                                <td>
                                    <span class="badge-status badge-<?= str_replace(' ', '-', $ord['status']) ?>">
                                        <?= htmlspecialchars($ord['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display:flex;gap:6px;">
                                        <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                        <select name="status" class="form-control" style="padding:4px 8px;font-size:12px;width:auto;">
                                            <option value="Pending" <?= $ord['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Cooking" <?= $ord['status'] == 'Cooking' ? 'selected' : '' ?>>Cooking</option>
                                            <option value="Out for Delivery" <?= $ord['status'] == 'Out for Delivery' ? 'selected' : '' ?>>Out for Delivery</option>
                                            <option value="Delivered" <?= $ord['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="Cancelled" <?= $ord['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_order_status" class="btn-action">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($active_tab === 'reservations'): ?>
        <!-- ── RESERVATIONS MANAGER ────────────────────────────────────────── -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-calendar-check" style="color:#3b82f6;margin-right:8px;"></i> Table Bookings</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Guest Name</th>
                            <th>Contact</th>
                            <th>Party Size</th>
                            <th>Date & Time</th>
                            <th>Special Request</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($reservations as $res): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($res['name']) ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($res['phone']) ?><br>
                                    <small style="color:var(--text-muted);"><?= htmlspecialchars($res['email']) ?></small>
                                </td>
                                <td><span style="font-weight:700;color:var(--primary);"><?= $res['num_guests'] ?> Guests</span></td>
                                <td><?= htmlspecialchars($res['reservation_date']) ?> at <strong><?= htmlspecialchars($res['reservation_time']) ?></strong></td>
                                <td style="max-width:200px;font-size:12px;color:var(--text-muted);"><?= htmlspecialchars($res['special_request'] ?: 'None') ?></td>
                                <td>
                                    <span class="badge-status badge-<?= htmlspecialchars($res['status']) ?>">
                                        <?= htmlspecialchars($res['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display:flex;gap:6px;">
                                        <input type="hidden" name="res_id" value="<?= $res['id'] ?>">
                                        <select name="status" class="form-control" style="padding:4px 8px;font-size:12px;width:auto;">
                                            <option value="Confirmed" <?= $res['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                            <option value="Pending" <?= $res['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Cancelled" <?= $res['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_res_status" class="btn-action">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($active_tab === 'menu'): ?>
        <!-- ── MENU ITEMS CRUD ─────────────────────────────────────────────── -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-plus-circle" style="color:#10b981;margin-right:8px;"></i> Add New Dish to Menu</h3>
            </div>
            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Dish Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Truffle Mushroom Pizza" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" class="form-control">
                            <option value="Pizza">Pizza</option>
                            <option value="Burger">Burger</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Sandwich">Sandwich</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Selling Price ($)</label>
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="39.00" required>
                    </div>
                    <div class="form-group">
                        <label>Regular Price ($)</label>
                        <input type="number" step="0.01" name="discount_price" class="form-control" placeholder="49.00">
                    </div>
                    <div class="form-group">
                        <label>Badge Tag</label>
                        <input type="text" name="badge" class="form-control" placeholder="e.g. -20% or Popular">
                    </div>
                    <div class="form-group">
                        <label>Image URL / Path</label>
                        <input type="text" name="image" class="form-control" value="./assets/images/food-menu-1.png">
                    </div>
                </div>
                <div class="form-group" style="margin-bottom:16px;">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Ingredients and flavor profile..."></textarea>
                </div>
                <button type="submit" name="add_menu_item" class="btn-action" style="background:var(--primary);padding:10px 24px;font-size:14px;font-weight:700;">
                    <i class="fa-solid fa-plus"></i> Add Item to Menu
                </button>
            </form>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-burger" style="color:var(--primary);margin-right:8px;"></i> Existing Dishes (<?= count($menu_items) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Dish Title</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Badge</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($menu_items as $item): ?>
                            <tr>
                                <td><img src="<?= htmlspecialchars($item['image']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px;"></td>
                                <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                                <td><span style="color:var(--text-muted);"><?= htmlspecialchars($item['category']) ?></span></td>
                                <td><strong>$<?= number_format($item['price'], 2) ?></strong></td>
                                <td><?= htmlspecialchars($item['badge'] ?: '-') ?></td>
                                <td>
                                    <a href="dashboard.php?tab=menu&delete_item=<?= $item['id'] ?>" class="btn-action" style="background:#ef4444;text-decoration:none;" onclick="return confirm('Delete this menu item?');">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif($active_tab === 'subscribers'): ?>
        <!-- ── SUBSCRIBERS ─────────────────────────────────────────────────── -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fa-solid fa-envelope-open-text" style="color:#a855f7;margin-right:8px;"></i> VIP Newsletter Subscribers</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subscriber Email</th>
                            <th>Date Subscribed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($subscribers as $sub): ?>
                            <tr>
                                <td>#<?= $sub['id'] ?></td>
                                <td><strong><?= htmlspecialchars($sub['email']) ?></strong></td>
                                <td><?= htmlspecialchars($sub['subscribed_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</main>

</body>
</html>
