<?php
// Lab 68 — DOM XSS via window.location.hash in jQuery Fancybox Selector
// Platform: ForeScout Technologies (www.forescout.com) | HackerOne Report #704266
// Vulnerable code: jQuery('a.fancybox-inline[href="' + window.location.hash + '"]:first').each(...)
// In IE/Edge: hash is NOT percent-encoded — raw <img src=x onerror=alert('XSS')> reaches jQuery
// Lab adaptation: decodeURIComponent makes it cross-browser exploitable
// Exploit: index.php#<img src=x onerror=alert(document.domain)>
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ForeScout Technologies — See Every Device. Stop Every Threat.</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Helvetica Neue',Arial,sans-serif;color:#1a1a2e;background:#fff;min-height:100vh;display:flex;flex-direction:column;}

/* ── Header ───────────────────────────────────────────────────────────────── */
.header{background:#0d1b3e;height:60px;display:flex;align-items:center;padding:0 32px;position:sticky;top:0;z-index:100;box-shadow:0 2px 12px rgba(0,0,0,.4);}
.header-logo{display:flex;align-items:center;gap:10px;text-decoration:none;margin-right:32px;}
.header-icon{width:34px;height:34px;background:linear-gradient(135deg,#f47b20,#e05a00);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:900;color:#fff;letter-spacing:-.03em;flex-shrink:0;}
.header-wordmark{color:#fff;font-size:.95rem;font-weight:800;letter-spacing:.02em;}
.header-wordmark span{color:#f47b20;}
.header-nav{display:flex;gap:2px;flex:1;}
.header-nav a{color:rgba(255,255,255,.75);font-size:.75rem;font-weight:500;text-decoration:none;padding:8px 10px;border-radius:4px;transition:background .15s,color .15s;}
.header-nav a:hover{background:rgba(255,255,255,.08);color:#fff;}
.header-right{display:flex;gap:8px;align-items:center;}
.hdr-btn{background:transparent;border:1.5px solid rgba(255,255,255,.35);border-radius:4px;color:rgba(255,255,255,.8);font-size:.72rem;font-weight:600;padding:6px 14px;cursor:pointer;font-family:inherit;transition:all .15s;}
.hdr-btn:hover{border-color:#f47b20;color:#f47b20;}
.hdr-cta{background:#f47b20;border:none;border-radius:4px;color:#fff;font-size:.72rem;font-weight:700;padding:7px 16px;cursor:pointer;font-family:inherit;transition:opacity .15s;}
.hdr-cta:hover{opacity:.88;}

/* ── Hero ─────────────────────────────────────────────────────────────────── */
.hero{background:linear-gradient(135deg,#0d1b3e 0%,#12285a 60%,#0f2244 100%);padding:80px 48px;text-align:center;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Ccircle cx='30' cy='30' r='2'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");}
.hero-tag{display:inline-flex;align-items:center;gap:6px;background:rgba(244,123,32,.15);border:1px solid rgba(244,123,32,.35);border-radius:20px;padding:5px 14px;font-size:.7rem;font-weight:700;color:#f47b20;text-transform:uppercase;letter-spacing:.06em;margin-bottom:20px;}
.hero h1{font-size:2.8rem;font-weight:900;color:#fff;line-height:1.1;margin-bottom:16px;letter-spacing:-.02em;position:relative;}
.hero h1 span{color:#f47b20;}
.hero-sub{font-size:1rem;color:rgba(255,255,255,.65);max-width:560px;margin:0 auto 32px;line-height:1.6;position:relative;}
.hero-btns{display:flex;gap:12px;justify-content:center;position:relative;}
.btn-primary{background:#f47b20;color:#fff;border:none;border-radius:5px;padding:13px 28px;font-size:.88rem;font-weight:700;cursor:pointer;font-family:inherit;transition:opacity .15s;}
.btn-primary:hover{opacity:.88;}
.btn-secondary{background:transparent;border:2px solid rgba(255,255,255,.4);color:#fff;border-radius:5px;padding:11px 24px;font-size:.88rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;}
.btn-secondary:hover{border-color:#f47b20;color:#f47b20;}

/* ── Stats bar ────────────────────────────────────────────────────────────── */
.stats-bar{background:#f47b20;padding:16px 48px;display:flex;justify-content:center;gap:48px;}
.stat-item{text-align:center;}
.stat-num{font-size:1.4rem;font-weight:900;color:#fff;line-height:1;}
.stat-label{font-size:.65rem;font-weight:600;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:.04em;margin-top:2px;}

/* ── Section ──────────────────────────────────────────────────────────────── */
.section{padding:64px 48px;max-width:1200px;margin:0 auto;width:100%;}
.section-tag{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#f47b20;margin-bottom:8px;}
.section-title{font-size:1.6rem;font-weight:800;color:#0d1b3e;margin-bottom:10px;letter-spacing:-.01em;}
.section-sub{font-size:.88rem;color:#5a6a8a;max-width:520px;line-height:1.65;margin-bottom:36px;}

/* ── Feature cards ────────────────────────────────────────────────────────── */
.feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;}
.feature-card{background:#fff;border:1px solid #dde3ee;border-radius:10px;padding:24px;transition:box-shadow .2s,transform .2s;}
.feature-card:hover{box-shadow:0 8px 28px rgba(13,27,62,.12);transform:translateY(-2px);}
.feature-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:14px;}
.feature-icon.blue{background:rgba(13,27,62,.08);}
.feature-icon.orange{background:rgba(244,123,32,.1);}
.feature-icon.teal{background:rgba(0,168,150,.1);}
.feature-card h3{font-size:.9rem;font-weight:800;color:#0d1b3e;margin-bottom:6px;}
.feature-card p{font-size:.76rem;color:#5a6a8a;line-height:1.6;margin-bottom:14px;}
.feature-link{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;font-weight:700;color:#f47b20;text-decoration:none;cursor:pointer;}
.feature-link:hover{text-decoration:underline;}
.feature-link svg{width:12px;height:12px;}

/* ── Platform section ─────────────────────────────────────────────────────── */
.platform-section{background:#f5f7fc;padding:64px 48px;}
.platform-inner{max-width:1200px;margin:0 auto;}
.platform-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center;margin-top:32px;}
.platform-visual{background:#0d1b3e;border-radius:12px;padding:28px;min-height:240px;display:flex;flex-direction:column;justify-content:center;}
.platform-visual-title{font-size:.7rem;font-weight:700;text-transform:uppercase;color:rgba(255,255,255,.5);letter-spacing:.06em;margin-bottom:16px;}
.device-list{display:flex;flex-direction:column;gap:8px;}
.device-row{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.06);border-radius:6px;padding:8px 12px;}
.device-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;}
.device-dot.green{background:#22c55e;}
.device-dot.orange{background:#f47b20;}
.device-dot.red{background:#ef4444;}
.device-name{font-size:.72rem;color:rgba(255,255,255,.8);flex:1;}
.device-type{font-size:.65rem;color:rgba(255,255,255,.4);font-family:monospace;}
.platform-points{list-style:none;}
.platform-points li{display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #dde3ee;font-size:.8rem;color:#3a4a6a;line-height:1.5;}
.platform-points li:last-child{border-bottom:none;}
.platform-points li::before{content:'✓';color:#f47b20;font-weight:700;flex-shrink:0;margin-top:1px;}

/* ── Inline preview area — vulnerable sink ────────────────────────────────── */
.inline-preview-bar{background:#fff8f2;border:1px solid rgba(244,123,32,.2);border-radius:8px;padding:14px 20px;margin:0 48px;font-size:.78rem;color:#5a6a8a;min-height:40px;}
.inline-preview-bar:empty{display:none;}

/* ── Fancybox lightbox ────────────────────────────────────────────────────── */
.fb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:1000;align-items:center;justify-content:center;}
.fb-overlay.open{display:flex;}
.fb-box{background:#fff;border-radius:12px;width:560px;max-width:90vw;overflow:hidden;position:relative;box-shadow:0 24px 64px rgba(0,0,0,.4);}
.fb-hdr{background:#0d1b3e;padding:20px 24px;display:flex;align-items:center;justify-content:space-between;}
.fb-title{font-size:.9rem;font-weight:700;color:#fff;}
.fb-close{background:none;border:none;color:rgba(255,255,255,.6);font-size:1.1rem;cursor:pointer;padding:4px;line-height:1;}
.fb-close:hover{color:#fff;}
.fb-body{padding:28px 24px;}
.fb-body p{font-size:.82rem;color:#3a4a6a;line-height:1.7;margin-bottom:14px;}
.fb-body .fb-cta{display:inline-block;background:#f47b20;color:#fff;border-radius:5px;padding:10px 22px;font-size:.8rem;font-weight:700;text-decoration:none;margin-top:6px;}

/* Inline content (hidden, used by fancybox normal mode) */
.fb-inline-content{display:none;}

/* ── CTA strip ────────────────────────────────────────────────────────────── */
.cta-strip{background:linear-gradient(90deg,#0d1b3e,#1a3060);padding:48px;text-align:center;}
.cta-strip h2{font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:8px;}
.cta-strip p{font-size:.84rem;color:rgba(255,255,255,.65);margin-bottom:24px;}

/* ── Footer ───────────────────────────────────────────────────────────────── */
.footer{background:#080f20;padding:32px 48px;margin-top:auto;}
.footer-inner{max-width:1200px;margin:0 auto;display:flex;flex-wrap:wrap;gap:24px;justify-content:space-between;align-items:center;}
.footer-logo{display:flex;align-items:center;gap:8px;}
.footer-icon{width:26px;height:26px;background:#f47b20;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.65rem;font-weight:900;color:#fff;}
.footer-name{color:#fff;font-size:.82rem;font-weight:700;}
.footer-links{display:flex;gap:16px;flex-wrap:wrap;}
.footer-links a{font-size:.7rem;color:rgba(255,255,255,.4);text-decoration:none;}
.footer-links a:hover{color:rgba(255,255,255,.8);}
.footer-copy{font-size:.68rem;color:rgba(255,255,255,.2);}

@media(max-width:768px){
    .feature-grid,.platform-grid{grid-template-columns:1fr;}
    .hero h1{font-size:1.8rem;}
    .stats-bar{gap:20px;}
    .section,.platform-section,.cta-strip{padding:40px 20px;}
    .inline-preview-bar{margin:0 20px;}
}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <a href="#" class="header-logo">
    <div class="header-icon">FS</div>
    <span class="header-wordmark">Fore<span>Scout</span></span>
  </a>
  <nav class="header-nav">
    <a href="#">Platform</a>
    <a href="#">Solutions</a>
    <a href="#">Industries</a>
    <a href="#">Partners</a>
    <a href="#">Resources</a>
    <a href="#">About</a>
  </nav>
  <div class="header-right">
    <button class="hdr-btn">Sign In</button>
    <button class="hdr-cta">Request Demo</button>
  </div>
</header>

<!-- Hero -->
<section class="hero">
  <div class="hero-tag">
    <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><circle cx="5" cy="5" r="5"/></svg>
    Enterprise Cybersecurity
  </div>
  <h1>See Every Device.<br><span>Stop Every Threat.</span></h1>
  <p class="hero-sub">ForeScout delivers continuous visibility and control of all devices across your enterprise — IT, OT, IoT and cloud. No agents required.</p>
  <div class="hero-btns">
    <button class="btn-primary" onclick="openFancybox('demo')">Request a Demo</button>
    <a class="fancybox-inline btn-secondary" href="#feature-platform" style="padding:11px 24px;font-weight:600;font-size:.88rem;text-decoration:none;display:inline-flex;align-items:center;">
      Watch Overview ▶
    </a>
  </div>
</section>

<!-- Stats bar -->
<div class="stats-bar">
  <div class="stat-item"><div class="stat-num">8M+</div><div class="stat-label">Devices Managed</div></div>
  <div class="stat-item"><div class="stat-num">3,200+</div><div class="stat-label">Global Customers</div></div>
  <div class="stat-item"><div class="stat-num">76</div><div class="stat-label">Fortune 100 Clients</div></div>
  <div class="stat-item"><div class="stat-num">80+</div><div class="stat-label">Countries</div></div>
</div>

<!-- ⚠ VULNERABLE SINK — populated by the prettyPhoto/fancybox hash handler below -->
<!-- When hash contains <img src=x onerror=alert(1)>, .html() injects it here -->
<div class="inline-preview-bar" id="inline-preview"></div>

<!-- Features section -->
<div style="max-width:1200px;margin:0 auto;width:100%;padding:0 48px;">
<section class="section" style="padding-left:0;padding-right:0;">
  <div class="section-tag">Capabilities</div>
  <div class="section-title">Complete Enterprise Visibility</div>
  <div class="section-sub">From unmanaged IoT sensors to cloud workloads, ForeScout automatically discovers and classifies every connected device — the moment it touches your network.</div>

  <div class="feature-grid">
    <div class="feature-card">
      <div class="feature-icon blue">👁</div>
      <h3>Asset Visibility</h3>
      <p>Achieve 100% device visibility across IT, OT, IoT and cloud without requiring endpoint agents. Real-time device intelligence from day one.</p>
      <!-- Normal fancybox-inline anchor: href="#feature-visibility" matches the inline div below -->
      <a class="fancybox-inline feature-link" href="#feature-visibility">
        Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="feature-card">
      <div class="feature-icon orange">🛡</div>
      <h3>Threat Detection</h3>
      <p>Continuous monitoring and behavioural analytics detect threats at the earliest stage — before attackers can pivot or exfiltrate data across your environment.</p>
      <a class="fancybox-inline feature-link" href="#feature-threat">
        Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>

    <div class="feature-card">
      <div class="feature-icon teal">⚡</div>
      <h3>Automated Response</h3>
      <p>Orchestrate automated policy enforcement and incident response across your entire security ecosystem with bi-directional integrations into 70+ security tools.</p>
      <a class="fancybox-inline feature-link" href="#feature-response">
        Learn More <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>
</section>
</div>

<!-- Platform section -->
<div class="platform-section">
  <div class="platform-inner">
    <div class="section-tag">Platform</div>
    <div class="section-title" style="color:#0d1b3e;">The ForeScout Platform</div>
    <div class="section-sub">A unified, agentless foundation for enterprise device security — deployed across your network perimeter and cloud in days, not months.</div>
    <div class="platform-grid">
      <div class="platform-visual">
        <div class="platform-visual-title">Live Device Intelligence</div>
        <div class="device-list">
          <div class="device-row"><div class="device-dot green"></div><span class="device-name">corp-laptop-0x48a2</span><span class="device-type">Win10 — Managed</span></div>
          <div class="device-row"><div class="device-dot orange"></div><span class="device-name">HVAC-Controller-B2</span><span class="device-type">OT — Unmanaged</span></div>
          <div class="device-row"><div class="device-dot green"></div><span class="device-name">iPhone-FScout-CEO</span><span class="device-type">iOS — MDM</span></div>
          <div class="device-row"><div class="device-dot red"></div><span class="device-name">192.168.4.77 (Unknown)</span><span class="device-type">IoT — Rogue</span></div>
          <div class="device-row"><div class="device-dot green"></div><span class="device-name">aws-prod-workload-12</span><span class="device-type">Cloud — EC2</span></div>
        </div>
      </div>
      <ul class="platform-points">
        <li>Agentless discovery across wired, wireless and virtual environments</li>
        <li>Classification of IT, OT, IoT, mobile and cloud assets in real-time</li>
        <li>Policy-based access control without network downtime</li>
        <li>70+ out-of-the-box integrations with leading security vendors</li>
        <li>Deployable on-premises, in the cloud or as SaaS</li>
      </ul>
    </div>
  </div>
</div>

<!-- CTA strip -->
<div class="cta-strip">
  <h2>Ready to See What's Really on Your Network?</h2>
  <p>Schedule a personalised demo with a ForeScout security specialist today.</p>
  <button class="btn-primary" onclick="openFancybox('demo')">Schedule a Demo →</button>
</div>

<!-- Footer -->
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-logo">
      <div class="footer-icon">FS</div>
      <span class="footer-name">ForeScout Technologies</span>
    </div>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Use</a>
      <a href="#">Security</a>
      <a href="#">Sitemap</a>
      <a href="https://hackerone.com/reports/704266" target="_blank">Report #704266</a>
    </div>
    <span class="footer-copy">© 2019 ForeScout Technologies, Inc. All rights reserved.</span>
  </div>
</footer>

<!-- ── Inline content divs (normal fancybox targets) ──────────────────────── -->
<div id="feature-visibility" class="fb-inline-content">
  <strong>Asset Visibility</strong><br>ForeScout discovers every IP-connected device — no agents, no credentials required. Get a complete, real-time device inventory across your entire enterprise.
</div>
<div id="feature-threat" class="fb-inline-content">
  <strong>Threat Detection</strong><br>ForeScout CounterACT correlates device context with network behaviour and 3rd-party threat intelligence to surface high-fidelity alerts and prioritise response.
</div>
<div id="feature-response" class="fb-inline-content">
  <strong>Automated Response</strong><br>ForeScout orchestrates multi-vendor response actions — quarantine, re-authenticate, notify — automatically, across your entire security stack.
</div>
<div id="feature-platform" class="fb-inline-content">
  <strong>Platform Overview</strong><br>Watch how ForeScout provides agentless, continuous visibility and control across all device types — in under 3 minutes.
</div>

<!-- Fancybox lightbox (simplified) -->
<div class="fb-overlay" id="fb-overlay">
  <div class="fb-box">
    <div class="fb-hdr">
      <span class="fb-title" id="fb-title">Feature Details</span>
      <button class="fb-close" onclick="closeFancybox()">✕</button>
    </div>
    <div class="fb-body" id="fb-body">
      <p>Loading...</p>
    </div>
  </div>
</div>

<!-- jQuery (as used on the real ForeScout page) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// ============================================================
//  ForeScout homepage — fancybox-inline hash trigger
//  Mirrors the vulnerable jQuery code reported in #704266:
//
//    jQuery(window).load(function() {
//        jQuery('a.fancybox-inline[href="' + window.location.hash + '"]:first')
//            .each(function() {
//                jQuery(this).delay(700).trigger('click');
//            });
//    });
//
//  In IE/Edge: window.location.hash returns the raw, unencoded fragment.
//  e.g. hash = "#<img src=x onerror=alert('XSS')>" — raw HTML passed to jQuery.
//  Chrome/Firefox percent-encode the hash, preventing exploitation.
//
//  ⚠ LAB ADAPTATION: decodeURIComponent normalises the percent-encoded hash
//  so the lab is exploitable cross-browser — functionally identical to
//  IE/Edge's native behaviour with the original unencoded hash.
// ============================================================

// ── Normal fancybox click handler ─────────────────────────────────────────
$('a.fancybox-inline').on('click', function(e) {
    e.preventDefault();
    var targetId = $(this).attr('href'); // e.g. "#feature-visibility"
    var $content = $(targetId);
    if ($content.length) {
        var title = $(this).closest('.feature-card').find('h3').text()
                    || 'Feature Details';
        $('#fb-title').text(title);
        $('#fb-body').html('<p>' + $content.html() + '</p>');
        $('#fb-overlay').addClass('open');
    }
});

// ── Vulnerable hash-based auto-trigger (mirrors original ForeScout code) ──
$(window).on('load', function() {
    var hash = window.location.hash; // ⚠ source — no sanitization

    if (hash.length > 1) {
        // Exact original selector from ForeScout source code (Report #704266):
        //   jQuery('a.fancybox-inline[href="' + window.location.hash + '"]:first')
        //       .each(function() { jQuery(this).delay(700).trigger('click'); });
        //
        // ⚠ Cross-browser decode (replicates IE/Edge's unencoded hash behaviour):
        var decoded = decodeURIComponent(hash.substring(1));

        // Try to auto-click the matching fancybox anchor (normal behaviour)
        var $anchor = $('a.fancybox-inline[href="' + hash + '"]:first');

        if ($anchor.length) {
            // Normal path: hash matches a known anchor → open lightbox
            $anchor.delay(700).trigger('click');
        } else {
            // ⚠ VULNERABLE SINK:
            // No matching anchor found — decoded hash content injected into
            // the inline preview strip via .html() without sanitization.
            // Mirrors how IE/Edge's jQuery execution evaluated the raw hash HTML.
            //
            // Payload: index.php#<img src=x onerror=alert(document.domain)>
            //   decoded = '<img src=x onerror=alert(document.domain)>'
            //   .html(decoded) → img created in DOM → onerror fires → XSS ✓
            $('#inline-preview').html(decoded);
        }
    }
});

// ── Demo request lightbox helper ──────────────────────────────────────────
function openFancybox(type) {
    $('#fb-title').text('Request a Demo');
    $('#fb-body').html(
        '<p>Fill in the form below and a ForeScout security specialist will be in touch within one business day.</p>' +
        '<div style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">' +
        '<input type="text" placeholder="Full Name" style="padding:8px 12px;border:1px solid #dde3ee;border-radius:4px;font-size:.82rem;">' +
        '<input type="email" placeholder="Business Email" style="padding:8px 12px;border:1px solid #dde3ee;border-radius:4px;font-size:.82rem;">' +
        '<input type="text" placeholder="Company" style="padding:8px 12px;border:1px solid #dde3ee;border-radius:4px;font-size:.82rem;">' +
        '</div>' +
        '<a href="#" class="fb-cta" style="margin-top:16px;display:inline-block;" onclick="closeFancybox()">Submit Request →</a>'
    );
    $('#fb-overlay').addClass('open');
}

function closeFancybox() {
    $('#fb-overlay').removeClass('open');
}

// Close on overlay click
$('#fb-overlay').on('click', function(e) {
    if (e.target === this) closeFancybox();
});
</script>

</body>
</html>
