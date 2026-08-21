<?php
// ============================================================
// SQL Injection Lab 116 — Boolean-Blind SQLi in PUT API Path Segment
// Platform: Hyperpure (by Zomato) | HackerOne Report #1044716
// Endpoint: PUT /api/consumer/onboarding/saleslead/{salesLeadId}
// Vulnerability: salesLeadId path segment injected raw into SQL string context
//
// Real endpoint: https://api.hyperpure.com/consumer/onboarding/saleslead/{id}
//
// Payloads (intercept PUT in Burp, modify path segment):
//   {uuid}                            → normal → {"response":{"salesLeadId":"..."}}
//   {uuid}" AND 1="1 --+-            → TRUE  → salesLeadId echoed
//   {uuid}" AND 1="0 --+-            → FALSE → {"response":{}}
//   {uuid}" OR 1="1 --+-             → always TRUE (any record)
//   {uuid}" AND (length(database()))="11 --+-
//                                    → TRUE if DB name length = 11 → salesLeadId echoed
// ============================================================

$server   = "localhost";
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) die(json_encode(["error" => "Connection failed"]));

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

function initializeLab116Database($conn) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab116_salesleads (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        sales_lead_id VARCHAR(120)  NOT NULL,
        outlet_name  VARCHAR(100)  NOT NULL,
        city         VARCHAR(60)   NOT NULL,
        state        VARCHAR(60)   NOT NULL DEFAULT 'Maharashtra',
        zip_code     VARCHAR(10)   NOT NULL,
        email        VARCHAR(120)  NOT NULL,
        phone        VARCHAR(20)   NOT NULL,
        status       VARCHAR(20)   NOT NULL DEFAULT 'pending',
        created_at   DATETIME      NOT NULL
    )");

    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab116_secret (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        secret_data  VARCHAR(100)  NOT NULL
    )");

    $check = mysqli_query($conn, "SELECT * FROM lab116_secret LIMIT 1");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO lab116_secret (secret_data) VALUES ('flag{hyperpure_zomato_sqli_1044716}')");
    }

    $checkLeads = mysqli_query($conn, "SELECT COUNT(*) AS c FROM lab116_salesleads");
    $row = mysqli_fetch_assoc($checkLeads);
    if ((int)$row['c'] === 0) {
        mysqli_query($conn, "INSERT INTO lab116_salesleads
            (sales_lead_id, outlet_name, city, state, zip_code, email, phone, status, created_at) VALUES
            ('6b6a8a5a-4a74-46db-b2fe-32a46f927ecc', 'Spice Garden Restaurant', 'Mumbai',    'Maharashtra', '400001', 'owner@spicegarden.in',    '+91-9876543210', 'pending',  '2020-11-25 10:30:00'),
            ('31cf8eb0-f81e-4c99-acad-35eae89ed659', 'Biryani House',          'Hyderabad', 'Telangana',   '500001', 'info@biryanihouse.in',    '+91-9812345678', 'active',   '2020-11-24 14:20:00'),
            ('a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'The Coastal Kitchen',    'Bangalore', 'Karnataka',   '560001', 'coastal@kitchen.in',      '+91-9823456789', 'pending',  '2020-11-23 09:15:00'),
            ('b2c3d4e5-f6a7-8901-bcde-f01234567891', 'Delhi Dhabha',           'New Delhi', 'Delhi',       '110001', 'delhi@dhaba.in',          '+91-9834567890', 'active',   '2020-11-22 16:45:00'),
            ('c3d4e5f6-a7b8-9012-cdef-012345678902', 'South Spice Corner',     'Chennai',   'Tamil Nadu',  '600001', 'contact@southspice.in',   '+91-9845678901', 'pending',  '2020-11-21 11:00:00')");
    }
}
initializeLab116Database($conn);

// ============================================================
// API ENDPOINT — PUT /index.php?api/consumer/onboarding/saleslead/{salesLeadId}
// ⚠ salesLeadId path segment injected raw into SQL double-quoted string
// Accepts PUT (and GET for Burp Repeater convenience)
// ============================================================
$qs     = $_SERVER['QUERY_STRING'] ?? '';
$prefix = 'api/consumer/onboarding/saleslead/';

if (strpos($qs, $prefix) === 0) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: https://www.hyperpure.com');
    header('Access-Control-Allow-Credentials: true');
    header('x-envoy-upstream-service-time: 142');
    header('Server: envoy');

    // Extract salesLeadId from URL path — ⚠ NOT sanitised
    $salesLeadId = urldecode(substr($qs, strlen($prefix)));

    // ====================================================================
    // ⚠ VULNERABLE QUERY — salesLeadId interpolated raw into SQL
    //   inside a double-quoted string context (mirrors MySQL with ANSI mode off)
    //
    // Injection point: the path segment after /saleslead/ in the URL:
    //   PUT /index.php?api/consumer/onboarding/saleslead/[HERE]
    //
    // Payload: {uuid}" AND 1="1 --+-
    //   → WHERE sales_lead_id = "{uuid}" AND 1="1 --+-"
    //   → condition TRUE  → row found → salesLeadId echoed in response
    //
    // Payload: {uuid}" AND 1="0 --+-
    //   → condition FALSE → no row → {"response":{}}
    //
    // Payload: {uuid}" AND (length(database()))="11 --+-
    //   → TRUE if DB name length = 11 → salesLeadId echoed
    // ====================================================================
    $sql    = 'SELECT * FROM lab116_salesleads WHERE sales_lead_id = "' . $salesLeadId . '"';
    $result = mysqli_query($conn, $sql);

    if ($result === false) {
        echo json_encode(["response" => ["error" => mysqli_error($conn)]]);
        exit;
    }

    $row = mysqli_fetch_assoc($result);
    if ($row) {
        echo json_encode([
            "response" => [
                "salesLeadId" => $salesLeadId,
                "status"      => $row['status']
            ]
        ]);
    } else {
        echo json_encode(["response" => (object)[]]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hyperpure — Fresh Ingredients for Your Restaurant</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#F7F7F7;color:#1C1C1E;font-size:14px;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ──────────────────────────────────────────────────────────────── */
.header{background:#fff;border-bottom:1px solid #EDEDED;height:60px;display:flex;align-items:center;}
.header-inner{max-width:1200px;margin:0 auto;padding:0 24px;width:100%;display:flex;align-items:center;gap:0;}
.logo{display:flex;align-items:baseline;gap:5px;text-decoration:none;flex-shrink:0;}
.logo-main{font-size:1.3rem;font-weight:800;color:#00A34C;letter-spacing:-.04em;}
.logo-by{font-size:.65rem;font-weight:600;color:#E84393;letter-spacing:.02em;margin-left:3px;}
.header-nav{display:flex;gap:0;margin-left:32px;}
.header-nav a{color:#555;font-size:.8rem;font-weight:500;text-decoration:none;padding:0 14px;line-height:60px;transition:color .15s;}
.header-nav a:hover{color:#00A34C;}
.header-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.btn-login{color:#00A34C;font-size:.8rem;font-weight:700;text-decoration:none;padding:7px 18px;border:1.5px solid #00A34C;border-radius:4px;transition:all .15s;}
.btn-login:hover{background:#00A34C;color:#fff;}

/* ── Page layout ─────────────────────────────────────────────────────────── */
.page-body{flex:1;display:grid;grid-template-columns:1fr 460px;max-width:1200px;margin:0 auto;padding:40px 24px;gap:48px;width:100%;}
@media(max-width:900px){.page-body{grid-template-columns:1fr;}}

/* ── Hero (left) ─────────────────────────────────────────────────────────── */
.hero{padding:16px 0;}
.hero-eyebrow{font-size:.72rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#00A34C;margin-bottom:16px;}
.hero-title{font-size:2.2rem;font-weight:800;line-height:1.15;letter-spacing:-.04em;color:#1C1C1E;margin-bottom:16px;}
.hero-title span{color:#00A34C;}
.hero-sub{font-size:.92rem;color:#666;line-height:1.65;max-width:420px;margin-bottom:28px;}
.hero-perks{display:flex;flex-direction:column;gap:12px;margin-bottom:32px;}
.perk{display:flex;align-items:flex-start;gap:10px;}
.perk-icon{width:20px;height:20px;background:#E8F5EE;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.perk-icon svg{width:11px;height:11px;stroke:#00A34C;stroke-width:2.5;fill:none;}
.perk-text{font-size:.82rem;color:#555;line-height:1.5;}
.perk-text strong{color:#1C1C1E;}
.trust-strip{display:flex;align-items:center;gap:8px;margin-top:24px;}
.trust-label{font-size:.7rem;color:#aaa;font-weight:500;}
.trust-num{font-size:.82rem;font-weight:700;color:#333;}

/* ── Registration card (right) ───────────────────────────────────────────── */
.reg-card{background:#fff;border:1px solid #EDEDED;border-radius:10px;padding:28px 26px;box-shadow:0 2px 12px rgba(0,0,0,.06);}
.reg-title{font-size:1.05rem;font-weight:700;color:#1C1C1E;margin-bottom:4px;}
.reg-sub{font-size:.75rem;color:#888;margin-bottom:22px;}
.form-group{margin-bottom:14px;}
.form-label{display:block;font-size:.72rem;font-weight:700;color:#555;margin-bottom:5px;letter-spacing:.01em;}
.form-input{width:100%;padding:9px 12px;border:1.5px solid #DEDEDE;border-radius:6px;font-size:.82rem;color:#1C1C1E;font-family:inherit;background:#FAFAFA;outline:none;transition:border-color .15s;}
.form-input:focus{border-color:#00A34C;background:#fff;box-shadow:0 0 0 3px rgba(0,163,76,.08);}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.form-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23999'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
.btn-submit{width:100%;background:#00A34C;color:#fff;border:none;border-radius:6px;padding:12px;font-size:.88rem;font-weight:700;cursor:pointer;font-family:inherit;margin-top:8px;transition:background .15s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-submit:hover{background:#009040;}
.btn-submit:disabled{opacity:.65;cursor:not-allowed;}
.btn-submit svg{width:16px;height:16px;flex-shrink:0;}
.form-note{font-size:.68rem;color:#aaa;text-align:center;margin-top:10px;line-height:1.5;}

/* ── Response area ───────────────────────────────────────────────────────── */
#regResponse{margin-top:14px;display:none;}
.resp-success{background:#E8F5EE;border:1px solid #A8D9BC;border-radius:6px;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;}
.resp-success-icon{width:18px;height:18px;background:#00A34C;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.resp-success-icon svg{width:10px;height:10px;stroke:#fff;stroke-width:2.5;fill:none;}
.resp-success-text{font-size:.78rem;color:#1a6636;}
.resp-success-text strong{display:block;font-size:.82rem;margin-bottom:2px;}
.resp-notfound{background:#FFF4F4;border:1px solid #FFBCBC;border-radius:6px;padding:12px 14px;font-size:.78rem;color:#c0392b;}
.resp-error{background:#FFF8E8;border:1px solid #FFD59E;border-radius:6px;padding:12px 14px;font-size:.78rem;color:#7d5a00;font-family:monospace;}

/* ── How it works strip ──────────────────────────────────────────────────── */
.how-strip{background:#fff;border-top:1px solid #EDEDED;border-bottom:1px solid #EDEDED;padding:28px 24px;margin-top:auto;}
.how-inner{max-width:1100px;margin:0 auto;display:flex;align-items:flex-start;justify-content:space-between;gap:24px;flex-wrap:wrap;}
.how-step{flex:1;min-width:160px;text-align:center;}
.how-num{width:32px;height:32px;background:#E8F5EE;border-radius:50%;font-size:.8rem;font-weight:800;color:#00A34C;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;}
.how-step-title{font-size:.8rem;font-weight:700;color:#1C1C1E;margin-bottom:4px;}
.how-step-desc{font-size:.72rem;color:#888;line-height:1.5;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.footer{background:#1C1C1E;color:rgba(255,255,255,.45);padding:18px 24px;font-size:.7rem;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.footer a{color:rgba(255,255,255,.45);text-decoration:none;}.footer a:hover{color:#00A34C;}
.footer-links{display:flex;gap:16px;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="header-inner">
    <a href="index.php" class="logo">
      <span class="logo-main">hyperpure</span>
      <span class="logo-by">by Zomato</span>
    </a>
    <nav class="header-nav">
      <a href="#">For Restaurants</a>
      <a href="#">Our Products</a>
      <a href="#">About Us</a>
      <a href="#">Blog</a>
    </nav>
    <div class="header-right">
      <a href="#" class="btn-login">Login</a>
    </div>
  </div>
</header>

<!-- Page Body -->
<div class="page-body">

  <!-- Left: Hero -->
  <div class="hero">
    <div class="hero-eyebrow">Restaurant Partner Program</div>
    <h1 class="hero-title">Fresh ingredients,<br>delivered to your<br><span>kitchen door</span></h1>
    <p class="hero-sub">Join thousands of restaurants across India who trust Hyperpure for quality produce, dairy, meat, seafood and staples — sourced directly from farms.</p>
    <div class="hero-perks">
      <div class="perk">
        <div class="perk-icon"><svg viewBox="0 0 12 12"><polyline points="1 6 4 9 11 2"/></svg></div>
        <div class="perk-text"><strong>Farm-to-fork traceability</strong> — know exactly where your ingredients come from</div>
      </div>
      <div class="perk">
        <div class="perk-icon"><svg viewBox="0 0 12 12"><polyline points="1 6 4 9 11 2"/></svg></div>
        <div class="perk-text"><strong>Next-day delivery</strong> — order by 12 AM, get delivery by 7 AM</div>
      </div>
      <div class="perk">
        <div class="perk-icon"><svg viewBox="0 0 12 12"><polyline points="1 6 4 9 11 2"/></svg></div>
        <div class="perk-text"><strong>Consistent quality</strong> — graded, sorted and packed under strict hygiene standards</div>
      </div>
      <div class="perk">
        <div class="perk-icon"><svg viewBox="0 0 12 12"><polyline points="1 6 4 9 11 2"/></svg></div>
        <div class="perk-text"><strong>No minimum order</strong> — order as little or as much as you need</div>
      </div>
    </div>
    <div class="trust-strip">
      <span class="trust-label">Trusted by</span>
      <span class="trust-num">50,000+ restaurants</span>
      <span class="trust-label">&nbsp;across</span>
      <span class="trust-num">20+ cities</span>
    </div>
  </div>

  <!-- Right: Registration Form -->
  <div>
    <div class="reg-card">
      <div class="reg-title">Register Your Outlet</div>
      <div class="reg-sub">Fill in your details and our team will get in touch within 24 hours.</div>

      <div class="form-group">
        <label class="form-label" for="outletName">Outlet / Restaurant Name</label>
        <input class="form-input" type="text" id="outletName" placeholder="e.g. Spice Garden Restaurant" autocomplete="off">
      </div>

      <div class="form-group">
        <label class="form-label" for="addressLine">Address Line</label>
        <input class="form-input" type="text" id="addressLine" placeholder="e.g. 12, MG Road, Andheri West" autocomplete="off">
      </div>

      <div class="form-row">
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="city">City</label>
          <select class="form-input form-select" id="city">
            <option value="Mumbai" data-id="34">Mumbai</option>
            <option value="Delhi">New Delhi</option>
            <option value="Bangalore">Bangalore</option>
            <option value="Hyderabad">Hyderabad</option>
            <option value="Chennai">Chennai</option>
            <option value="Pune">Pune</option>
            <option value="Kolkata">Kolkata</option>
            <option value="Ahmedabad">Ahmedabad</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label class="form-label" for="zipCode">ZIP / PIN Code</label>
          <input class="form-input" type="text" id="zipCode" placeholder="e.g. 400001" maxlength="6">
        </div>
      </div>

      <div class="form-group" style="margin-top:14px;">
        <label class="form-label" for="phoneNumber">Phone Number</label>
        <input class="form-input" type="tel" id="phoneNumber" placeholder="+91-XXXXXXXXXX">
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <input class="form-input" type="email" id="email" placeholder="owner@restaurant.in">
      </div>

      <button class="btn-submit" id="btnSubmit" onclick="submitRegistration()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        Register Outlet
      </button>
      <div class="form-note">By registering you agree to our Terms of Service and Privacy Policy.</div>

      <div id="regResponse"></div>
    </div>
  </div>

</div>

<!-- How it works -->
<div class="how-strip">
  <div class="how-inner">
    <div class="how-step">
      <div class="how-num">1</div>
      <div class="how-step-title">Register</div>
      <div class="how-step-desc">Submit your outlet details via our onboarding form.</div>
    </div>
    <div class="how-step">
      <div class="how-num">2</div>
      <div class="how-step-title">Verification</div>
      <div class="how-step-desc">Our team verifies your FSSAI and business details.</div>
    </div>
    <div class="how-step">
      <div class="how-num">3</div>
      <div class="how-step-title">Onboarding</div>
      <div class="how-step-desc">Get access to our full catalogue and place your first order.</div>
    </div>
    <div class="how-step">
      <div class="how-num">4</div>
      <div class="how-step-title">First Delivery</div>
      <div class="how-step-desc">Fresh produce arrives at your kitchen the next morning.</div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <span>&copy; <?= date('Y') ?> Hyperpure by Zomato &nbsp;&middot;&nbsp; api.hyperpure.com &nbsp;&middot;&nbsp; PUT /consumer/onboarding/saleslead/{id}</span>
    <div class="footer-links">
      <a href="https://hackerone.com/reports/1044716" target="_blank" rel="noopener">HackerOne #1044716</a>
      <a href="#">Privacy</a>
      <a href="#">Terms</a>
    </div>
  </div>
</footer>

<script>
// Generates a random UUID v4
function uuid4() {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
    var r = Math.random() * 16 | 0;
    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
  });
}

function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// PUT request — students intercept this in Burp Suite
// Injection point: the salesLeadId UUID in the URL path
//   PUT /index.php?api/consumer/onboarding/saleslead/{salesLeadId}
function submitRegistration() {
  var outletName  = document.getElementById('outletName').value.trim()  || 'Test Outlet';
  var addressLine = document.getElementById('addressLine').value.trim() || 'Test Address';
  var cityEl      = document.getElementById('city');
  var city        = cityEl.value;
  var cityId      = parseInt(cityEl.options[cityEl.selectedIndex].dataset.id || '34', 10);
  var zipCode     = document.getElementById('zipCode').value.trim()     || '400001';
  var phone       = document.getElementById('phoneNumber').value.trim() || '+91-9000000000';
  var email       = document.getElementById('email').value.trim()       || 'user@example.in';

  var salesLeadId = uuid4();
  var trackingId  = uuid4();

  var btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;animation:spin 1s linear infinite"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> Submitting…';

  fetch('index.php?api/consumer/onboarding/saleslead/' + salesLeadId, {
    method: 'PUT',
    headers: {
      'Content-Type':  'application/json;charset=utf-8',
      'X-Client':      'consumer',
      'X-TrackingId':  trackingId,
      'HeaderRoute':   'v2',
      'APIVersion':    '4.2',
      'AppType':       'web'
    },
    body: JSON.stringify({
      address: {
        addressLine: addressLine,
        cityId:      cityId,
        state:       { name: city === 'Mumbai' || city === 'Pune' ? 'Maharashtra' : city },
        zipCode:     zipCode
      },
      deliveryTime: 0,
      email:        email,
      outletName:   outletName,
      phoneNumber:  phone,
      salesLeadId:  salesLeadId
    })
  })
  .then(function(r){ return r.json(); })
  .then(function(data){ renderResponse(data, outletName); })
  .catch(function(e){ renderResponse(null, outletName); console.error(e); })
  .finally(function(){
    btn.disabled = false;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M5 12h14M12 5l7 7-7 7"/></svg> Register Outlet';
  });
}

function renderResponse(data, outletName) {
  var el = document.getElementById('regResponse');
  el.style.display = 'block';
  if (!data) {
    el.innerHTML = '<div class="resp-error">Network error — please try again.</div>';
    return;
  }
  if (data.response && data.response.salesLeadId) {
    el.innerHTML = '<div class="resp-success">'
      + '<div class="resp-success-icon"><svg viewBox="0 0 12 12"><polyline points="1 6 4 9 11 2"/></svg></div>'
      + '<div class="resp-success-text"><strong>Registration submitted!</strong>'
      + escHtml(outletName) + ' has been added to our onboarding queue. Our team will contact you within 24 hours.</div>'
      + '</div>';
  } else if (data.response && data.response.error) {
    el.innerHTML = '<div class="resp-error">DB Error: ' + escHtml(data.response.error) + '</div>';
  } else {
    el.innerHTML = '<div class="resp-notfound">Sales lead not found. Please check your details and try again.</div>';
  }
}
</script>
<style>
@keyframes spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}
</style>

</body>
</html>
