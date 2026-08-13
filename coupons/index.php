<?php
// ============================================================
// SQL Injection Lab 118 — WooCommerce Coupon Usage Report SQLi
// Platform: WordPress Admin / WooCommerce v9.9.3 | HackerOne #3198980
// Endpoint: GET /wp-admin/admin.php?page=wc-reports&tab=orders
//                &report=coupon_usage&coupon_codes=PAYLOAD
// Vulnerability: coupon_codes raw inside SQL IN('...') clause
//
// Payloads (modify coupon_codes in URL/Burp):
//   coupon_codes=SUMMER20                                    → normal results
//   coupon_codes=SUMMER20') -- -                            → broken, no rows
//   coupon_codes=') UNION SELECT 1,2,3 -- -                → injected: 1 | 2 | 3
//   coupon_codes=') UNION SELECT secret_data,1337,99 FROM lab118_secret -- -
//                                                           → flag in Coupon Code col
// ============================================================

$server   = "localhost";
$username = "root";
$password = "";
$database = "VexiumCTF_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die("DB connection failed");

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab118Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab118_coupon_usage (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        coupon_code    VARCHAR(60)     NOT NULL,
        total_orders   INT             NOT NULL DEFAULT 0,
        total_discount DECIMAL(10,2)   NOT NULL DEFAULT 0.00
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab118_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");

    $checkSecret = mysqli_query($conn, "SELECT * FROM lab118_secret LIMIT 1");
    if (mysqli_num_rows($checkSecret) == 0) {
        mysqli_query($conn, "INSERT INTO lab118_secret (secret_data) VALUES ('flag{woocommerce_coupon_sqli_3198980}')");
    }

    $checkData = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab118_coupon_usage");
    $row = mysqli_fetch_assoc($checkData);
    if ((int)$row['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab118_coupon_usage (coupon_code, total_orders, total_discount) VALUES
            ('SUMMER20',   48,  1234.50),
            ('WELCOME10', 127,   892.30),
            ('FLASH50',    12,   456.00),
            ('VIP25',      34,   678.90),
            ('BUNDLE15',   67,   345.20)");
    }
}
initializeLab118Database($conn);

// ============================================================
// Report query — `coupon_codes` GET param injected raw into IN clause
// ⚠ Break out with: ') UNION SELECT ... -- -
// ============================================================
$couponCodes = $_GET['coupon_codes'] ?? '';
$queryError  = '';
$reportRows  = [];

if ($couponCodes !== '') {
    // ====================================================================
    // ⚠ VULNERABLE — coupon_codes interpolated raw into SQL IN() clause
    //
    //   Injection: coupon_codes=') UNION SELECT secret_data,1337,99 FROM lab118_secret -- -
    //   → WHERE coupon_code IN ('') UNION SELECT secret_data,1337,99 FROM lab118_secret -- -')
    //   → flag appears in the coupon_code column of the rendered table
    // ====================================================================
    $sql    = "SELECT coupon_code, total_orders, total_discount
               FROM lab118_coupon_usage
               WHERE coupon_code IN ('$couponCodes')";
    $result = mysqli_query($conn, $sql);
    if ($result === false) {
        $queryError = mysqli_error($conn);
    } else {
        while ($r = mysqli_fetch_assoc($result)) $reportRows[] = $r;
    }
} else {
    $result = mysqli_query($conn, "SELECT coupon_code, total_orders, total_discount
                                   FROM lab118_coupon_usage ORDER BY total_orders DESC");
    while ($r = mysqli_fetch_assoc($result)) $reportRows[] = $r;
}

$totalOrders   = array_sum(array_column($reportRows, 'total_orders'));
$totalDiscount = array_sum(array_column($reportRows, 'total_discount'));

$tab    = $_GET['tab']    ?? 'orders';
$report = $_GET['report'] ?? 'coupon_usage';

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports ‹ WooCommerce Store — WordPress</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f0f1;color:#3c434a;font-size:13px;min-height:100vh;}

/* ── WP Admin Bar ─────────────────────────────────────────────────────────── */
#wpadminbar{background:#23282d;height:32px;position:fixed;top:0;left:0;width:100%;z-index:9999;display:flex;align-items:center;font-size:13px;}
#wpadminbar a{color:#eee;text-decoration:none;}
.ab-item{display:flex;align-items:center;height:32px;padding:0 8px;color:rgba(240,245,250,.7);font-size:.75rem;cursor:pointer;white-space:nowrap;}
.ab-item:hover{background:#32373c;color:#fff;}
.ab-wp-logo{background:#23282d;padding:0 9px;display:flex;align-items:center;height:32px;}
.ab-wp-logo svg{fill:rgba(240,245,250,.6);width:20px;height:20px;}
.ab-wp-logo:hover svg{fill:#fff;}
#wp-admin-bar-site-name>.ab-item{font-weight:600;color:rgba(240,245,250,.9);}
.ab-right{margin-left:auto;display:flex;align-items:center;}

/* ── Layout ──────────────────────────────────────────────────────────────── */
#wpwrap{display:flex;margin-top:32px;min-height:calc(100vh - 32px);}
#adminmenuwrap{width:160px;background:#2c2c2c;min-height:calc(100vh - 32px);flex-shrink:0;}
#wpcontent{flex:1;padding:10px 20px 20px;min-height:calc(100vh - 32px);}

/* ── Admin Menu ──────────────────────────────────────────────────────────── */
#adminmenu{list-style:none;width:100%;}
#adminmenu li{border-bottom:1px solid rgba(255,255,255,.03);}
.menu-top{display:flex;align-items:center;gap:8px;padding:8px 12px;color:rgba(240,245,250,.7);font-size:.75rem;font-weight:400;cursor:pointer;white-space:nowrap;border-left:3px solid transparent;line-height:1.4;}
.menu-top:hover{background:#111;color:#fff;border-left-color:#999;}
.menu-top.wp-has-current-submenu{background:#111;color:#fff;border-left-color:#7f54b3;}
.menu-top svg{width:16px;height:16px;flex-shrink:0;fill:rgba(240,245,250,.5);}
.menu-top.wp-has-current-submenu svg{fill:#7f54b3;}
.wp-submenu{list-style:none;background:#111;display:none;padding:4px 0;}
.wp-has-current-submenu .wp-submenu{display:block;}
.wp-submenu a{display:block;padding:5px 12px 5px 30px;color:rgba(240,245,250,.5);font-size:.72rem;text-decoration:none;}
.wp-submenu a:hover,.wp-submenu a.current{color:#fff;}
.wp-submenu li.current a{color:#7f54b3;font-weight:700;}
.wp-menu-separator{height:1px;background:rgba(255,255,255,.05);margin:6px 0;}

/* ── Breadcrumb / Title ──────────────────────────────────────────────────── */
.wc-header{background:#fff;border-bottom:1px solid #c3c4c7;margin:-10px -20px 0;padding:12px 20px;display:flex;align-items:center;gap:10px;}
.wc-header-title{font-size:1.05rem;font-weight:600;color:#1d2327;}
.wc-env-badge{font-size:.65rem;font-weight:700;background:#f0f0f1;color:#646970;border:1px solid #c3c4c7;border-radius:3px;padding:2px 6px;margin-left:4px;}

/* ── Nav Tabs ────────────────────────────────────────────────────────────── */
.nav-tab-wrapper{border-bottom:1px solid #c3c4c7;margin:18px 0 0;padding:0;display:flex;flex-wrap:wrap;gap:0;}
.nav-tab{color:#555;border:1px solid transparent;border-bottom:none;background:transparent;padding:5px 12px;font-size:.8rem;font-weight:500;text-decoration:none;cursor:pointer;border-radius:3px 3px 0 0;margin-bottom:-1px;display:inline-flex;align-items:center;gap:4px;}
.nav-tab:hover{color:#1d2327;background:#f0f0f1;border-color:#c3c4c7 #c3c4c7 transparent;}
.nav-tab-active{color:#1d2327;background:#f0f0f1;border-color:#c3c4c7 #c3c4c7 transparent;border-bottom-color:#f0f0f1;}

/* ── Sub-nav ─────────────────────────────────────────────────────────────── */
.report-subnav{background:#fff;border:1px solid #c3c4c7;border-top:none;display:flex;flex-wrap:wrap;}
.report-subnav a{display:block;padding:8px 14px;font-size:.75rem;color:#555;text-decoration:none;border-right:1px solid #eee;}
.report-subnav a:hover{background:#f9f9f9;color:#1d2327;}
.report-subnav a.current{background:#f9f9f9;color:#7f54b3;font-weight:700;border-bottom:2px solid #7f54b3;}

/* ── Report Content ──────────────────────────────────────────────────────── */
.wc-report-wrap{background:#fff;border:1px solid #c3c4c7;border-top:none;padding:16px;}
.report-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px;padding:12px;background:#f9f9f9;border:1px solid #e5e5e5;border-radius:2px;}
.filter-label{font-size:.75rem;font-weight:600;color:#555;}
.filter-input{padding:5px 8px;font-size:.78rem;border:1px solid #8c8f94;border-radius:2px;outline:none;font-family:inherit;color:#2c2c2c;background:#fff;}
.filter-input:focus{border-color:#7f54b3;box-shadow:0 0 0 1px #7f54b3;}
.btn-update{background:#7f54b3;color:#fff;border:none;border-radius:2px;padding:6px 14px;font-size:.75rem;font-weight:700;cursor:pointer;font-family:inherit;transition:background .15s;}
.btn-update:hover{background:#6d3f9a;}

/* ── Stats strip ─────────────────────────────────────────────────────────── */
.stats-strip{display:flex;gap:0;border:1px solid #e5e5e5;border-radius:2px;margin-bottom:14px;overflow:hidden;}
.stat-box{flex:1;padding:12px 14px;border-right:1px solid #e5e5e5;}
.stat-box:last-child{border-right:none;}
.stat-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:3px;}
.stat-value{font-size:1.1rem;font-weight:700;color:#1d2327;}
.stat-note{font-size:.65rem;color:#aaa;margin-top:2px;}

/* ── Chart placeholder ───────────────────────────────────────────────────── */
.chart-placeholder{height:120px;background:#fafafa;border:1px solid #e5e5e5;border-radius:2px;margin-bottom:14px;display:flex;align-items:flex-end;justify-content:space-around;padding:10px 20px;gap:8px;}
.bar{background:#7f54b3;border-radius:2px 2px 0 0;flex:1;max-width:40px;opacity:.7;transition:opacity .2s;}
.bar:hover{opacity:1;}

/* ── Table ───────────────────────────────────────────────────────────────── */
.wc-table-wrap{overflow-x:auto;}
table.wc-report-table{width:100%;border-collapse:collapse;}
table.wc-report-table th{background:#f9f9f9;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#555;padding:9px 12px;border:1px solid #e1e1e1;text-align:left;}
table.wc-report-table td{padding:9px 12px;border:1px solid #eee;font-size:.8rem;color:#2c2c2c;vertical-align:middle;}
table.wc-report-table tr:hover td{background:#fafafa;}
table.wc-report-table tfoot td{background:#f9f9f9;font-weight:700;font-size:.78rem;border-top:2px solid #c3c4c7;}
.coupon-code{font-family:monospace;font-size:.82rem;font-weight:700;color:#1d2327;background:#f0f0f1;padding:2px 6px;border-radius:2px;}
.orders-cell{color:#555;}
.discount-cell{color:#7f54b3;font-weight:600;}
.no-results{text-align:center;padding:28px;color:#999;font-size:.82rem;}
.sql-error{background:#fce6e6;border:1px solid #f5b7b7;border-radius:2px;padding:10px 14px;font-family:monospace;font-size:.75rem;color:#a00;margin-bottom:12px;word-break:break-all;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
#wpfooter{padding:10px 20px;font-size:.7rem;color:#888;display:flex;justify-content:space-between;border-top:1px solid #e5e5e5;background:#f0f0f1;flex-wrap:wrap;gap:6px;}
#wpfooter a{color:#888;text-decoration:none;}.#wpfooter a:hover{color:#7f54b3;}
</style>
</head>
<body class="wp-admin woocommerce_page_wc-reports">

<!-- WP Admin Bar -->
<div id="wpadminbar">
  <div class="ab-wp-logo">
    <svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm-.91 16.315L5.733 5.5h1.724l2.24 7.79 2.17-7.79H13.4l2.1 7.79 2.24-7.79h1.724l-3.355 10.815h-1.75l-2.13-7.504-2.13 7.504H9.09z"/></svg>
  </div>
  <span class="ab-item" id="wp-admin-bar-site-name">My WooCommerce Store &nbsp;▾</span>
  <span class="ab-item">+ New</span>
  <span class="ab-item">WooCommerce</span>
  <div class="ab-right">
    <span class="ab-item">Howdy, <strong>admin</strong> &nbsp;▾</span>
  </div>
</div>

<div id="wpwrap">

  <!-- Sidebar -->
  <div id="adminmenuwrap">
    <ul id="adminmenu">
      <li>
        <div class="menu-top">
          <svg viewBox="0 0 20 20"><path d="M10.707 1.293a1 1 0 00-1.414 0L1 9.586V18a1 1 0 001 1h5v-6h6v6h5a1 1 0 001-1V9.586l-8.293-8.293z"/></svg>
          Dashboard
        </div>
      </li>
      <li>
        <div class="menu-top">
          <svg viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 3h6v2H7V8zm0 4h4v2H7v-2z" clip-rule="evenodd"/></svg>
          Posts
        </div>
      </li>
      <li>
        <div class="menu-top">
          <svg viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"/><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
          Media
        </div>
      </li>
      <div class="wp-menu-separator"></div>
      <li class="wp-has-current-submenu">
        <div class="menu-top wp-has-current-submenu">
          <svg viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C4.28 11.04 4 11.97 4 13a3 3 0 106 0h2a3 3 0 106 0c0-1.03-.28-1.96-.752-2.414l-.893-.892 1.358-5.43a1 1 0 00-.98-1.22H6.78l-.305-1.222A1 1 0 005.5 1H3z"/></svg>
          WooCommerce
        </div>
        <ul class="wp-submenu">
          <li><a href="#">Orders</a></li>
          <li><a href="#">Products</a></li>
          <li><a href="#">Customers</a></li>
          <li class="current"><a href="index.php?page=wc-reports&tab=orders&report=coupon_usage" class="current">Reports</a></li>
          <li><a href="#">Settings</a></li>
          <li><a href="#">Extensions</a></li>
        </ul>
      </li>
      <div class="wp-menu-separator"></div>
      <li>
        <div class="menu-top">
          <svg viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>
          Settings
        </div>
      </li>
    </ul>
  </div>

  <!-- Main Content -->
  <div id="wpcontent">

    <!-- WC Header -->
    <div class="wc-header">
      <div class="wc-header-title">Reports</div>
      <span class="wc-env-badge">WooCommerce 9.9.3</span>
    </div>

    <!-- Reports Tab Navigation -->
    <div class="nav-tab-wrapper">
      <a href="index.php?page=wc-reports&tab=orders&report=coupon_usage" class="nav-tab <?= $tab === 'orders' ? 'nav-tab-active' : '' ?>">Orders</a>
      <a href="#" class="nav-tab">Customers</a>
      <a href="#" class="nav-tab">Stock</a>
      <a href="#" class="nav-tab">Taxes</a>
    </div>

    <!-- Sub-navigation (Orders tab) -->
    <?php if ($tab === 'orders'): ?>
    <div class="report-subnav">
      <a href="#">Sales by Date</a>
      <a href="#">Sales by Product</a>
      <a href="#">Sales by Category</a>
      <a href="index.php?page=wc-reports&tab=orders&report=coupon_usage" class="current">Coupons by Date</a>
      <a href="#">Taxes by Date</a>
    </div>
    <?php endif; ?>

    <!-- Report Content -->
    <div class="wc-report-wrap">

      <!-- Filters -->
      <form method="get" action="index.php" style="margin:0;">
        <input type="hidden" name="page"   value="wc-reports">
        <input type="hidden" name="tab"    value="orders">
        <input type="hidden" name="report" value="coupon_usage">
        <div class="report-filters">
          <span class="filter-label">From:</span>
          <input class="filter-input" type="date" name="start_date" value="<?= esc($_GET['start_date'] ?? '2024-01-01') ?>" style="width:130px;">
          <span class="filter-label">To:</span>
          <input class="filter-input" type="date" name="end_date" value="<?= esc($_GET['end_date'] ?? date('Y-m-d')) ?>" style="width:130px;">
          <span class="filter-label" style="margin-left:8px;">Coupons:</span>
          <input class="filter-input" type="text" name="coupon_codes"
                 value="<?= esc($couponCodes) ?>"
                 placeholder="e.g. SUMMER20"
                 style="width:160px;">
          <button class="btn-update" type="submit">Update</button>
        </div>
      </form>

      <!-- Stats strip -->
      <div class="stats-strip">
        <div class="stat-box">
          <div class="stat-label">Total Coupons Used</div>
          <div class="stat-value"><?= count($reportRows) ?></div>
          <div class="stat-note">unique codes</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Total Orders</div>
          <div class="stat-value"><?= number_format($totalOrders) ?></div>
          <div class="stat-note">orders with coupons</div>
        </div>
        <div class="stat-box">
          <div class="stat-label">Total Discount</div>
          <div class="stat-value">$<?= number_format($totalDiscount, 2) ?></div>
          <div class="stat-note">discount applied</div>
        </div>
      </div>

      <!-- Bar chart placeholder (visual) -->
      <?php if (count($reportRows) > 0 && !$queryError): ?>
      <div class="chart-placeholder" title="Coupon usage chart">
        <?php foreach ($reportRows as $r):
            $pct = $totalOrders > 0 ? max(8, round(($r['total_orders'] / $totalOrders) * 100)) : 10;
        ?>
        <div class="bar" style="height:<?= $pct ?>%;" title="<?= esc($r['coupon_code']) ?>: <?= esc($r['total_orders']) ?> orders"></div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- SQL Error display -->
      <?php if ($queryError): ?>
      <div class="sql-error">MySQL Error: <?= esc($queryError) ?></div>
      <?php endif; ?>

      <!-- Results Table -->
      <div class="wc-table-wrap">
        <table class="wc-report-table">
          <thead>
            <tr>
              <th>Coupon Code</th>
              <th>Total Orders</th>
              <th>Total Discount</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($reportRows) === 0): ?>
            <tr><td colspan="3" class="no-results">No data found for the selected criteria.</td></tr>
            <?php else: ?>
            <?php foreach ($reportRows as $r): ?>
            <tr>
              <td><span class="coupon-code"><?= esc($r['coupon_code']) ?></span></td>
              <td class="orders-cell"><?= esc($r['total_orders']) ?></td>
              <td class="discount-cell">$<?= is_numeric($r['total_discount']) ? number_format((float)$r['total_discount'], 2) : esc($r['total_discount']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (count($reportRows) > 1): ?>
          <tfoot>
            <tr>
              <td>Total</td>
              <td><?= number_format($totalOrders) ?></td>
              <td>$<?= number_format($totalDiscount, 2) ?></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>

    </div><!-- .wc-report-wrap -->

    <!-- WP Footer -->
    <div id="wpfooter">
      <span>Thank you for creating with <a href="https://wordpress.org" target="_blank" rel="noopener">WordPress</a>. WooCommerce 9.9.3</span>
      <span><a href="https://hackerone.com/reports/3198980" target="_blank" rel="noopener">HackerOne #3198980</a></span>
    </div>

  </div><!-- #wpcontent -->
</div><!-- #wpwrap -->

</body>
</html>
