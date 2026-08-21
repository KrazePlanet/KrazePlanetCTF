<?php
// Lab 302 — Open Redirect via URL Path Routing (Omise)
// Based on HackerOne Report #504751 (Omise — Severity: Low 3.3, $100 bounty)
// Vulnerability: PHP router reads REQUEST_URI, strips /index.php, passes remainder to header("Location:")
// Attack: index.php////bing.com/?www.omise.co/?category=interview&page=2
//         ↑ "////bing.com/" is in the PATH — browser normalises → //bing.com/ → external redirect

// ── Absolute base URL for attack links (prevents browser treating index.php as hostname) ──────
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$labBase  = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/index.php';

// ── URL Path Routing Logic (the vulnerable part) ──────────────────────────────
$scriptName  = $_SERVER['SCRIPT_NAME'];   // /index.php
$requestUri  = $_SERVER['REQUEST_URI'];   // /index.php////bing.com/?www.omise.co/...
$queryString = $_SERVER['QUERY_STRING'];  // www.omise.co/?category=interview&page=2

// Extract everything after /index.php in the URL path
$afterScript = '';
if (strpos($requestUri, $scriptName) === 0) {
    $remainder = substr($requestUri, strlen($scriptName));
    // Treat as path redirect only if it has content beyond a single slash
    // ////bing.com/ ← this is the attack payload embedded in the path
    if (strlen($remainder) > 1 && substr($remainder, 0, 1) === '/') {
        $afterScript = $remainder;
    }
}

$hasRedirect = !empty($afterScript);

// ── Blog data (no database — fully stateless) ─────────────────────────────────
$articles = [
    ['id'=>1,'title'=>'Building a Seamless Checkout Experience with the Omise API',
     'excerpt'=>'Learn how to integrate Omise\'s payment gateway into your web application in under 30 minutes. We cover tokenization, 3-D Secure, and error handling.',
     'category'=>'developers','author'=>'Natthawut K.','date'=>'Feb 28, 2019','read'=>'8 min read',
     'tags'=>['API','Integration','PHP'],'color'=>'#00b777'],
    ['id'=>2,'title'=>'Interview: How Pomelo Fashion Scaled Payments Across Southeast Asia',
     'excerpt'=>'We sat down with Pomelo\'s CTO to discuss how they handled multi-currency transactions, fraud prevention, and peak load during flash sales.',
     'category'=>'interview','author'=>'Salinee T.','date'=>'Mar 1, 2019','read'=>'12 min read',
     'tags'=>['Interview','Case Study','E-commerce'],'color'=>'#2874f0'],
    ['id'=>3,'title'=>'Omise.js 3.0 — What\'s New for Developers',
     'excerpt'=>'Omise.js 3.0 ships with a redesigned token form, better mobile UX, TypeScript definitions, and a new customizable UI library for card input fields.',
     'category'=>'developers','author'=>'Frederico A.','date'=>'Feb 20, 2019','read'=>'6 min read',
     'tags'=>['JavaScript','Release','SDK'],'color'=>'#7c3aed'],
    ['id'=>4,'title'=>'Q1 2019 Platform Updates: Faster Payouts and New Dashboard',
     'excerpt'=>'This quarter we shipped same-day payouts for Thailand merchants, a redesigned analytics dashboard, and webhooks v2 with retry logic and delivery logs.',
     'category'=>'updates','author'=>'Omise Team','date'=>'Mar 3, 2019','read'=>'4 min read',
     'tags'=>['Updates','Dashboard','Payouts'],'color'=>'#db2777'],
    ['id'=>5,'title'=>'How Grab Integrated Omise for In-App Payments in Thailand',
     'excerpt'=>'A technical deep-dive into how Grab\'s engineering team integrated the Omise API for in-app ride payment — handling concurrency, retries, and refunds at scale.',
     'category'=>'interview','author'=>'Dhawal N.','date'=>'Feb 15, 2019','read'=>'15 min read',
     'tags'=>['Interview','Mobile','Scale'],'color'=>'#059669'],
    ['id'=>6,'title'=>'Understanding PCI DSS Compliance with Omise Vault',
     'excerpt'=>'A step-by-step guide to achieving PCI DSS SAQ A compliance using Omise Vault for card data tokenization — no cardholder data ever touches your servers.',
     'category'=>'developers','author'=>'Security Team','date'=>'Feb 10, 2019','read'=>'10 min read',
     'tags'=>['Security','PCI DSS','Compliance'],'color'=>'#dc2626'],
];

$categories = ['all'=>'All Posts','developers'=>'Developers','interview'=>'Interviews','updates'=>'Updates'];
$currentCat = $_GET['category'] ?? 'all';
$currentPage = max(1, (int)($_GET['page'] ?? 1));

$filtered = $currentCat === 'all'
    ? $articles
    : array_values(array_filter($articles, fn($a) => $a['category'] === $currentCat));

// Pages of 3
$perPage    = 3;
$totalPages = max(1, ceil(count($filtered) / $perPage));
$currentPage= min($currentPage, $totalPages);
$pageItems  = array_slice($filtered, ($currentPage-1)*$perPage, $perPage);

// ── Instant open redirect — no interstitial, no warning ──────────────────────────────
// ⚠ VULNERABLE: ////bing.com/ passed raw to Location header
// Browser normalises → //bing.com/ (protocol-relative) → external redirect
if ($hasRedirect) {
    header('Location: ' . $afterScript);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Omise Blog — Payments for Southeast Asia</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;background:#f7f9fb;color:#1a1a1a;}
a{text-decoration:none;color:inherit;}

/* ── Omise Navbar ─────────────────────────────────────────────────────────── */
.om-nav{background:#fff;border-bottom:1px solid #e8ecef;position:sticky;top:0;z-index:900;box-shadow:0 1px 4px rgba(0,0,0,.06);}
.om-nav-inner{max-width:1160px;margin:0 auto;padding:0 24px;display:flex;align-items:center;height:64px;gap:0;}
.om-logo{font-size:1.4rem;font-weight:800;color:#00b777;letter-spacing:-1px;margin-right:40px;flex-shrink:0;}
.om-logo span{color:#1a1a1a;font-weight:300;}
.om-nav-links{display:flex;align-items:center;gap:0;}
.om-nav-link{padding:8px 16px;font-size:.85rem;font-weight:500;color:#444;border-radius:4px;transition:color .15s;}
.om-nav-link:hover{color:#00b777;}
.om-nav-link.active{color:#00b777;font-weight:700;}
.om-nav-right{margin-left:auto;display:flex;align-items:center;gap:12px;}
.om-btn{padding:8px 18px;border-radius:6px;font-size:.82rem;font-weight:600;cursor:pointer;border:none;font-family:inherit;}
.om-btn-outline{background:transparent;border:2px solid #00b777;color:#00b777;}
.om-btn-outline:hover{background:#f0faf5;}
.om-btn-solid{background:#00b777;color:#fff;}
.om-btn-solid:hover{background:#009d66;}

/* ── Hero ─────────────────────────────────────────────────────────────────── */
.om-hero{background:linear-gradient(135deg,#003d26 0%,#00b777 60%,#00d68f 100%);padding:60px 24px;text-align:center;color:#fff;}
.om-hero h1{font-size:2.2rem;font-weight:800;margin-bottom:10px;letter-spacing:-.5px;}
.om-hero p{font-size:1rem;opacity:.88;max-width:540px;margin:0 auto 24px;line-height:1.6;}
.om-hero-search{display:flex;gap:0;max-width:460px;margin:0 auto;border-radius:6px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,.15);}
.om-hero-search input{flex:1;border:none;padding:12px 16px;font-size:.88rem;outline:none;}
.om-hero-search button{background:#003d26;color:#fff;border:none;padding:12px 20px;font-size:.82rem;font-weight:700;cursor:pointer;}

/* ── Category Tabs ────────────────────────────────────────────────────────── */
.om-tabs{background:#fff;border-bottom:1px solid #e8ecef;}
.om-tabs-inner{max-width:1160px;margin:0 auto;padding:0 24px;display:flex;gap:0;}
.om-tab{padding:14px 20px;font-size:.85rem;font-weight:600;color:#666;border-bottom:2px solid transparent;cursor:pointer;transition:color .15s;}
.om-tab:hover{color:#00b777;}
.om-tab.active{color:#00b777;border-bottom-color:#00b777;}

/* ── Blog Layout ──────────────────────────────────────────────────────────── */
.om-blog{max-width:1160px;margin:32px auto;padding:0 24px;display:grid;grid-template-columns:1fr 300px;gap:32px;align-items:start;}
@media(max-width:900px){.om-blog{grid-template-columns:1fr;}}

/* Article card */
.om-article-list{display:flex;flex-direction:column;gap:20px;}
.om-article{background:#fff;border:1px solid #e8ecef;border-radius:8px;overflow:hidden;transition:box-shadow .2s;display:flex;gap:0;}
.om-article:hover{box-shadow:0 4px 20px rgba(0,183,119,.12);}
.om-article-color{width:6px;flex-shrink:0;}
.om-article-body{padding:20px 24px;flex:1;}
.om-article-cat{display:inline-block;background:#e8faf3;color:#00b777;font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:3px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;}
.om-article-title{font-size:1rem;font-weight:700;color:#1a1a1a;margin-bottom:8px;line-height:1.4;}
.om-article-title:hover{color:#00b777;}
.om-article-excerpt{font-size:.82rem;color:#555;line-height:1.6;margin-bottom:12px;}
.om-article-meta{display:flex;align-items:center;gap:10px;font-size:.75rem;color:#888;}
.om-article-avatar{width:28px;height:28px;border-radius:50%;background:#00b777;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;color:#fff;flex-shrink:0;}
.om-article-tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px;}
.om-article-tag{background:#f5f5f5;color:#555;font-size:.68rem;padding:2px 7px;border-radius:3px;}
.om-read-more{display:inline-flex;align-items:center;gap:4px;color:#00b777;font-size:.8rem;font-weight:600;margin-top:12px;}
.om-read-more:hover{text-decoration:underline;}

/* Pagination */
.om-pagination{display:flex;gap:6px;margin-top:20px;align-items:center;}
.om-page-btn{padding:7px 13px;border:1px solid #e0e0e0;border-radius:4px;font-size:.78rem;color:#444;cursor:pointer;background:#fff;}
.om-page-btn:hover{border-color:#00b777;color:#00b777;}
.om-page-btn.active{background:#00b777;color:#fff;border-color:#00b777;}
.om-page-btn.disabled{opacity:.4;cursor:default;pointer-events:none;}

/* Sidebar */
.om-sidebar{}
.om-sidebar-card{background:#fff;border:1px solid #e8ecef;border-radius:8px;padding:20px;margin-bottom:16px;}
.om-sidebar-title{font-size:.82rem;font-weight:700;color:#1a1a1a;margin-bottom:14px;text-transform:uppercase;letter-spacing:.5px;}
.om-sidebar-item{display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5;cursor:pointer;}
.om-sidebar-item:last-child{border-bottom:none;}
.om-sidebar-dot{width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;}
.om-sidebar-item-title{font-size:.8rem;color:#333;line-height:1.4;}
.om-sidebar-item-title:hover{color:#00b777;}

/* Lab attack panel */
.om-attack-panel{background:#fff8f3;border:1px solid #fb923c;border-radius:8px;padding:20px;}
.om-attack-title{font-size:.82rem;font-weight:700;color:#7c2d12;margin-bottom:10px;display:flex;align-items:center;gap:6px;}
.om-attack-row{display:flex;flex-direction:column;gap:6px;margin-top:8px;}
.om-attack-link{font-family:monospace;font-size:.72rem;background:#fff;border:1px solid #fed7aa;border-radius:4px;padding:5px 8px;color:#c2410c;word-break:break-all;cursor:pointer;display:block;}
.om-attack-link:hover{background:#fff7ed;}
.om-attack-label{font-size:.68rem;color:#9a3412;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-top:6px;}
.om-attack-blocked{background:#f0fdf4;border-color:#bbf7d0;color:#166534;}
.om-attack-bypasses{background:#fff7ed;border-color:#fed7aa;color:#9a3412;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
.om-footer{background:#0d2b1c;color:#8aab98;margin-top:40px;padding:40px 24px 20px;}
.om-footer-inner{max-width:1160px;margin:0 auto;display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:32px;}
@media(max-width:768px){.om-footer-inner{grid-template-columns:1fr 1fr;}}
.om-footer-brand{font-size:1.4rem;font-weight:800;color:#00b777;margin-bottom:10px;}
.om-footer-tagline{font-size:.78rem;line-height:1.6;max-width:220px;}
.om-footer h4{color:#fff;font-size:.78rem;font-weight:700;margin-bottom:12px;text-transform:uppercase;letter-spacing:.5px;}
.om-footer ul{list-style:none;display:flex;flex-direction:column;gap:7px;}
.om-footer li{font-size:.78rem;cursor:pointer;}
.om-footer li:hover{color:#fff;}
.om-footer-bottom{max-width:1160px;margin:24px auto 0;border-top:1px solid rgba(255,255,255,.08);padding-top:16px;font-size:.72rem;display:flex;gap:16px;flex-wrap:wrap;}
.om-footer-bottom a{color:#8aab98;}
.om-footer-bottom a:hover{color:#00b777;}
</style>
</head>
<body>

<!-- ════════════════════════════════════════════════════════════════════════
     OMISE BLOG
════════════════════════════════════════════════════════════════════════ -->

<!-- Navbar -->
<nav class="om-nav">
  <div class="om-nav-inner">
    <div class="om-logo">Omise<span>.co</span></div>
    <div class="om-nav-links">
      <a href="index.php" class="om-nav-link">Products</a>
      <a href="index.php" class="om-nav-link">Developers</a>
      <a href="index.php" class="om-nav-link">Pricing</a>
      <a href="index.php?category=all" class="om-nav-link active">Blog</a>
      <a href="index.php" class="om-nav-link">Contact</a>
    </div>
    <div class="om-nav-right">
      <button class="om-btn om-btn-outline">Log In</button>
      <button class="om-btn om-btn-solid">Get API Keys</button>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="om-hero">
  <h1>Omise Developer Blog</h1>
  <p>Insights on payments, APIs, security, and fintech from the team building Southeast Asia's most trusted payment gateway.</p>
  <div class="om-hero-search">
    <input type="text" placeholder="Search articles…">
    <button>Search</button>
  </div>
</div>

<!-- Category Tabs -->
<div class="om-tabs">
  <div class="om-tabs-inner">
    <?php foreach($categories as $slug => $label): ?>
    <a href="index.php?category=<?php echo $slug; ?>&page=1"
       class="om-tab<?php echo $currentCat===$slug?' active':''; ?>">
      <?php echo htmlspecialchars($label); ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- Blog + Sidebar -->
<div class="om-blog">

  <!-- Article List -->
  <div>
    <?php if(empty($pageItems)): ?>
    <div style="background:#fff;border:1px solid #e8ecef;border-radius:8px;padding:40px;text-align:center;color:#888;">
      No articles in this category.
    </div>
    <?php else: ?>
    <div class="om-article-list">
      <?php foreach($pageItems as $a): ?>
      <div class="om-article">
        <div class="om-article-color" style="background:<?php echo $a['color']; ?>;"></div>
        <div class="om-article-body">
          <div class="om-article-cat"><?php echo htmlspecialchars($categories[$a['category']] ?? $a['category']); ?></div>
          <div class="om-article-title"><?php echo htmlspecialchars($a['title']); ?></div>
          <div class="om-article-excerpt"><?php echo htmlspecialchars($a['excerpt']); ?></div>
          <div class="om-article-meta">
            <div class="om-article-avatar"><?php echo strtoupper(substr($a['author'],0,1)); ?></div>
            <span><?php echo htmlspecialchars($a['author']); ?></span>
            <span>·</span>
            <span><?php echo htmlspecialchars($a['date']); ?></span>
            <span>·</span>
            <span><?php echo htmlspecialchars($a['read']); ?></span>
          </div>
          <div class="om-article-tags">
            <?php foreach($a['tags'] as $t): ?><span class="om-article-tag"><?php echo htmlspecialchars($t); ?></span><?php endforeach; ?>
          </div>
          <a href="index.php?category=<?php echo $a['category']; ?>&page=<?php echo $currentPage; ?>" class="om-read-more">
            Read More →
          </a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="om-pagination">
      <a href="index.php?category=<?php echo $currentCat; ?>&page=<?php echo $currentPage-1; ?>"
         class="om-page-btn<?php echo $currentPage<=1?' disabled':''; ?>">← Prev</a>
      <?php for($p=1;$p<=$totalPages;$p++): ?>
      <a href="index.php?category=<?php echo $currentCat; ?>&page=<?php echo $p; ?>"
         class="om-page-btn<?php echo $p===$currentPage?' active':''; ?>"><?php echo $p; ?></a>
      <?php endfor; ?>
      <a href="index.php?category=<?php echo $currentCat; ?>&page=<?php echo $currentPage+1; ?>"
         class="om-page-btn<?php echo $currentPage>=$totalPages?' disabled':''; ?>">Next →</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Sidebar -->
  <aside class="om-sidebar">

    <!-- Recent posts -->
    <div class="om-sidebar-card">
      <div class="om-sidebar-title">Recent Posts</div>
      <?php foreach(array_slice($articles,0,4) as $a): ?>
      <div class="om-sidebar-item">
        <div class="om-sidebar-dot" style="background:<?php echo $a['color']; ?>;"></div>
        <div class="om-sidebar-item-title"><?php echo htmlspecialchars($a['title']); ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Lab attack panel -->
    <div class="om-attack-panel">
      <div class="om-attack-title">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#7c2d12" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        Lab 302 — Attack Payloads
      </div>
      <div style="font-size:.75rem;color:#7c2d12;line-height:1.5;margin-bottom:10px;">
        The server router reads <code style="background:#fee2e2;padding:0 3px;border-radius:2px;">REQUEST_URI</code>, strips <code style="background:#fee2e2;padding:0 3px;border-radius:2px;">/index.php</code>, and passes the rest to <code style="background:#fee2e2;padding:0 3px;border-radius:2px;">header("Location:")</code>.<br><br>
        <strong>Normal URL (safe):</strong>
      </div>
      <code class="om-attack-link om-attack-blocked">index.php?category=interview&amp;page=2</code>
      <div class="om-attack-label" style="margin-top:12px;">⚠ Attack — external domain in PATH:</div>
      <div class="om-attack-row">
        <a href="<?php echo $labBase; ?>////bing.com/?www.omise.co/?category=interview&page=2" class="om-attack-link om-attack-bypasses"><?php echo htmlspecialchars($labBase); ?>////bing.com/?www.omise.co/?category=interview&amp;page=2</a>
        <a href="<?php echo $labBase; ?>////evil.com/?www.omise.co" class="om-attack-link om-attack-bypasses"><?php echo htmlspecialchars($labBase); ?>////evil.com/?www.omise.co</a>
        <a href="<?php echo $labBase; ?>/%2f%2f%2fbing.com%2f?www.omise.co" class="om-attack-link om-attack-bypasses"><?php echo htmlspecialchars($labBase); ?>/%2f%2f%2fbing.com%2f?www.omise.co (URL-encoded)</a>
      </div>
      <div style="font-size:.7rem;color:#9a3412;margin-top:10px;line-height:1.5;">
        <strong>How:</strong> <code style="background:#fee2e2;padding:0 3px;border-radius:2px;">////bing.com/</code> in Location header → browser normalises → <code style="background:#fee2e2;padding:0 3px;border-radius:2px;">//bing.com/</code> (protocol-relative) → external redirect
      </div>
      <div style="margin-top:10px;padding-top:10px;border-top:1px solid #fed7aa;font-size:.7rem;color:#9a3412;">
        📄 <a href="https://hackerone.com/reports/504751" target="_blank" style="color:#c2410c;text-decoration:underline;">HackerOne #504751</a> · Omise · $100 · Low 3.3
      </div>
    </div>

  </aside>
</div>

<!-- Footer -->
<div class="om-footer">
  <div class="om-footer-inner">
    <div>
      <div class="om-footer-brand">Omise<span style="font-weight:300;color:#8aab98;">.co</span></div>
      <div class="om-footer-tagline">The payment gateway for Southeast Asia. Fast, reliable, and developer-friendly.</div>
    </div>
    <div>
      <h4>Products</h4>
      <ul><li>Payments</li><li>Vault</li><li>Omise.js</li><li>Webhooks</li></ul>
    </div>
    <div>
      <h4>Developers</h4>
      <ul><li>Documentation</li><li>API Reference</li><li>SDKs</li><li>Status</li></ul>
    </div>
    <div>
      <h4>Company</h4>
      <ul><li>About</li><li>Blog</li><li>Careers</li><li>Security</li></ul>
    </div>
  </div>
  <div class="om-footer-bottom">
    <span>© 2019 Omise Co., Ltd. — Security Lab for Educational Purposes</span>
    <a href="index.php">Blog</a>
    <a href="https://hackerone.com/reports/504751" target="_blank">HackerOne #504751</a>
    <a href="index.php">← Back to Labs</a>
  </div>
</div>

</body>
</html>
