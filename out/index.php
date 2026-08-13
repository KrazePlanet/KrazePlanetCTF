<?php
// Lab 303 — Open Redirect via ?url= Parameter (Semrush)
// Based on HackerOne Report #311330 (Semrush — Severity: Low, Resolved Feb 22, 2018)
// Vulnerability: /redirect endpoint passes ?url= directly to header("Location:") — zero validation

// ── Absolute base URL for attack panel links ───────────────────────────────
$scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$labBase = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/index.php';

// ── Instant open redirect — fires before any HTML output ──────────────────
// ⚠ VULNERABLE: $url is passed directly to header("Location:") with no validation
$url = $_GET['url'] ?? '';
if (!empty($url)) {
    header('Location: ' . $url);
    exit;
}

// ── Hardcoded keyword/backlink data for the dashboard ─────────────────────
$keywords = [
    ['kw'=>'seo tools','pos'=>3,'vol'=>'246,000','diff'=>82,'url'=>'https://www.semrush.com/features/','cpc'=>'$8.20'],
    ['kw'=>'keyword research tool','pos'=>1,'vol'=>'135,000','diff'=>78,'url'=>'https://www.semrush.com/analytics/keywordmagic/','cpc'=>'$11.50'],
    ['kw'=>'backlink checker','pos'=>2,'vol'=>'90,500','diff'=>75,'url'=>'https://www.semrush.com/analytics/backlinks/','cpc'=>'$6.80'],
    ['kw'=>'site audit tool','pos'=>4,'vol'=>'60,500','diff'=>71,'url'=>'https://www.semrush.com/siteaudit/','cpc'=>'$9.10'],
    ['kw'=>'competitor analysis','pos'=>5,'vol'=>'49,500','diff'=>68,'url'=>'https://www.semrush.com/competitive-research/','cpc'=>'$7.40'],
    ['kw'=>'rank tracker','pos'=>7,'vol'=>'40,500','diff'=>62,'url'=>'https://www.semrush.com/analytics/overview/','cpc'=>'$5.90'],
    ['kw'=>'on-page seo checker','pos'=>6,'vol'=>'33,100','diff'=>59,'url'=>'https://www.semrush.com/on-page-seo-checker/','cpc'=>'$4.20'],
    ['kw'=>'google analytics alternative','pos'=>12,'vol'=>'22,200','diff'=>55,'url'=>'https://www.semrush.com/traffic-analytics/','cpc'=>'$6.60'],
];

$backlinks = [
    ['domain'=>'moz.com','authority'=>91,'links'=>1240,'anchor'=>'SEO tools comparison','url'=>'https://moz.com/blog/seo-tools'],
    ['domain'=>'ahrefs.com','authority'=>89,'links'=>870,'anchor'=>'semrush review','url'=>'https://ahrefs.com/blog/semrush-review'],
    ['domain'=>'searchengineland.com','authority'=>88,'links'=>654,'anchor'=>'best seo platforms','url'=>'https://searchengineland.com'],
    ['domain'=>'neilpatel.com','authority'=>85,'links'=>430,'anchor'=>'keyword research','url'=>'https://neilpatel.com/blog'],
    ['domain'=>'backlinko.com','authority'=>82,'links'=>310,'anchor'=>'seo toolkit','url'=>'https://backlinko.com'],
];

$activeTab = $_GET['tab'] ?? 'keywords';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Semrush — Domain Overview · semrush.com</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:13px;background:#f5f5f8;color:#1b1b37;}
a{text-decoration:none;color:inherit;}

/* ── Topbar ───────────────────────────────────────────────────────────────── */
.sm-top{background:#1b1b37;height:52px;display:flex;align-items:center;padding:0 20px;gap:0;position:sticky;top:0;z-index:900;box-shadow:0 2px 8px rgba(0,0,0,.25);}
.sm-logo{display:flex;align-items:center;gap:6px;margin-right:28px;flex-shrink:0;}
.sm-logo-mark{width:28px;height:28px;background:#ff642d;border-radius:4px;display:flex;align-items:center;justify-content:center;}
.sm-logo-mark svg{width:16px;height:16px;fill:#fff;}
.sm-logo-text{font-size:1.15rem;font-weight:800;color:#fff;letter-spacing:-.3px;}
.sm-nav{display:flex;align-items:center;gap:0;}
.sm-nav-item{padding:8px 14px;font-size:.78rem;font-weight:600;color:rgba(255,255,255,.65);border-radius:4px;cursor:pointer;transition:color .15s;}
.sm-nav-item:hover,.sm-nav-item.active{color:#fff;}
.sm-top-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.sm-btn{padding:7px 16px;border-radius:5px;font-size:.75rem;font-weight:700;cursor:pointer;border:none;font-family:inherit;}
.sm-btn-outline{background:transparent;border:1.5px solid rgba(255,255,255,.4);color:#fff;}
.sm-btn-outline:hover{border-color:#ff642d;color:#ff642d;}
.sm-btn-solid{background:#ff642d;color:#fff;}
.sm-btn-solid:hover{background:#e55525;}
.sm-avatar{width:30px;height:30px;border-radius:50%;background:#ff642d;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;cursor:pointer;}

/* ── Domain Search Bar ───────────────────────────────────────────────────── */
.sm-search-bar{background:#fff;border-bottom:1px solid #e0e0e8;padding:12px 24px;}
.sm-search-inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;gap:10px;}
.sm-domain-pill{display:flex;align-items:center;gap:8px;background:#f5f5f8;border:1px solid #d0d0dc;border-radius:6px;padding:7px 14px;flex:1;max-width:420px;}
.sm-domain-pill input{border:none;background:transparent;outline:none;font-size:.85rem;font-weight:600;color:#1b1b37;width:100%;}
.sm-domain-pill-flag{font-size:1rem;}
.sm-search-cta{background:#ff642d;color:#fff;border:none;border-radius:5px;padding:8px 18px;font-size:.8rem;font-weight:700;cursor:pointer;}

/* ── Layout ───────────────────────────────────────────────────────────────── */
.sm-layout{max-width:1200px;margin:0 auto;padding:20px 24px;display:grid;grid-template-columns:1fr 280px;gap:20px;align-items:start;}
@media(max-width:900px){.sm-layout{grid-template-columns:1fr;}}

/* ── Metric Cards ────────────────────────────────────────────────────────── */
.sm-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
@media(max-width:700px){.sm-metrics{grid-template-columns:repeat(2,1fr);}}
.sm-metric{background:#fff;border:1px solid #e0e0e8;border-radius:8px;padding:16px;}
.sm-metric-label{font-size:.68rem;color:#888;text-transform:uppercase;letter-spacing:.6px;margin-bottom:6px;}
.sm-metric-value{font-size:1.5rem;font-weight:800;color:#1b1b37;line-height:1;}
.sm-metric-sub{font-size:.72rem;color:#888;margin-top:4px;}
.sm-metric-trend{font-size:.7rem;font-weight:600;margin-top:6px;}
.sm-metric-trend.up{color:#00a854;}
.sm-metric-trend.down{color:#d32f2f;}

/* ── Tabs ─────────────────────────────────────────────────────────────────── */
.sm-tabs{display:flex;gap:0;border-bottom:2px solid #e0e0e8;margin-bottom:16px;}
.sm-tab{padding:10px 18px;font-size:.8rem;font-weight:600;color:#666;border-bottom:2px solid transparent;margin-bottom:-2px;cursor:pointer;}
.sm-tab:hover{color:#ff642d;}
.sm-tab.active{color:#ff642d;border-bottom-color:#ff642d;}

/* ── Table ───────────────────────────────────────────────────────────────── */
.sm-card{background:#fff;border:1px solid #e0e0e8;border-radius:8px;overflow:hidden;}
.sm-card-header{padding:14px 18px;border-bottom:1px solid #e0e0e8;display:flex;align-items:center;justify-content:space-between;}
.sm-card-title{font-size:.85rem;font-weight:700;color:#1b1b37;}
.sm-card-count{font-size:.72rem;color:#888;background:#f5f5f8;padding:3px 8px;border-radius:3px;}
.sm-table{width:100%;border-collapse:collapse;}
.sm-table th{padding:9px 12px;text-align:left;font-size:.68rem;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.5px;background:#fafafa;border-bottom:1px solid #e8e8f0;}
.sm-table td{padding:10px 12px;font-size:.78rem;border-bottom:1px solid #f0f0f5;vertical-align:middle;}
.sm-table tr:last-child td{border-bottom:none;}
.sm-table tr:hover td{background:#fafafe;}
.sm-pos{font-weight:800;color:#1b1b37;font-size:.9rem;}
.sm-pos.top3{color:#ff642d;}
.sm-kw{font-weight:600;color:#1b1b37;}
.sm-vol{color:#555;}
.sm-diff-bar{display:flex;align-items:center;gap:8px;}
.sm-diff-fill{height:5px;border-radius:3px;background:#ff642d;}
.sm-diff-bg{flex:1;height:5px;background:#f0f0f5;border-radius:3px;overflow:hidden;}
.sm-cpc{font-weight:600;color:#00a854;}
.sm-ext-btn{display:inline-flex;align-items:center;gap:4px;background:#fff5f2;border:1px solid #ffb89a;color:#ff642d;border-radius:4px;padding:4px 9px;font-size:.7rem;font-weight:700;cursor:pointer;white-space:nowrap;}
.sm-ext-btn:hover{background:#ff642d;color:#fff;}
.sm-ext-btn svg{width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;}
.sm-auth{font-weight:700;color:#1b1b37;}
.sm-auth.high{color:#00a854;}
.sm-domain{color:#1b1b37;font-weight:600;}

/* ── Sidebar ─────────────────────────────────────────────────────────────── */
.sm-sidebar{}
.sm-sidebar-widget{background:#fff;border:1px solid #e0e0e8;border-radius:8px;padding:16px;margin-bottom:14px;}
.sm-sidebar-title{font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#888;margin-bottom:12px;}
.sm-score-ring{text-align:center;padding:10px 0;}
.sm-score-num{font-size:3rem;font-weight:900;color:#ff642d;line-height:1;}
.sm-score-label{font-size:.72rem;color:#888;margin-top:4px;}
.sm-stat-row{display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #f5f5f8;}
.sm-stat-row:last-child{border-bottom:none;}
.sm-stat-name{font-size:.75rem;color:#555;}
.sm-stat-val{font-size:.75rem;font-weight:700;color:#1b1b37;}

/* ── Attack Panel ────────────────────────────────────────────────────────── */
.sm-attack{background:#fff8f3;border:1px solid #fb923c;border-radius:8px;padding:16px;}
.sm-attack-title{font-size:.78rem;font-weight:700;color:#7c2d12;margin-bottom:8px;display:flex;align-items:center;gap:6px;}
.sm-attack-desc{font-size:.72rem;color:#7c2d12;line-height:1.5;margin-bottom:10px;}
.sm-attack-code{font-family:monospace;font-size:.68rem;background:#fff;border:1px solid #fed7aa;border-radius:4px;padding:5px 8px;color:#c2410c;word-break:break-all;display:block;margin:4px 0;}
.sm-attack-code:hover{background:#fff7ed;}
.sm-attack-code a{color:inherit;display:block;}
.sm-attack-label{font-size:.65rem;color:#9a3412;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-top:8px;}
.sm-attack-note{font-size:.68rem;color:#9a3412;margin-top:8px;line-height:1.5;}
.sm-attack-ref{margin-top:10px;padding-top:10px;border-top:1px solid #fed7aa;font-size:.67rem;color:#9a3412;}
.sm-attack-ref a{color:#c2410c;text-decoration:underline;}
</style>
</head>
<body>

<!-- ── Topbar ──────────────────────────────────────────────────────────────── -->
<div class="sm-top">
  <div class="sm-logo">
    <div class="sm-logo-mark">
      <svg viewBox="0 0 24 24"><path d="M3 17l4-8 4 4 4-6 4 10"/></svg>
    </div>
    <span class="sm-logo-text">Semrush</span>
  </div>
  <nav class="sm-nav">
    <span class="sm-nav-item active">Projects</span>
    <span class="sm-nav-item">Keyword Research</span>
    <span class="sm-nav-item">Link Building</span>
    <span class="sm-nav-item">SEO</span>
    <span class="sm-nav-item">Advertising</span>
    <span class="sm-nav-item">Reports</span>
  </nav>
  <div class="sm-top-right">
    <button class="sm-btn sm-btn-outline">Try Free</button>
    <button class="sm-btn sm-btn-solid">Subscribe</button>
    <div class="sm-avatar">AS</div>
  </div>
</div>

<!-- ── Domain Search Bar ───────────────────────────────────────────────────── -->
<div class="sm-search-bar">
  <div class="sm-search-inner">
    <div class="sm-domain-pill">
      <span class="sm-domain-pill-flag">🌐</span>
      <input type="text" value="semrush.com" readonly>
    </div>
    <select style="border:1px solid #d0d0dc;border-radius:5px;padding:8px 10px;font-size:.78rem;background:#fff;color:#1b1b37;outline:none;">
      <option>🇺🇸 US — Google</option>
      <option>🇬🇧 UK — Google</option>
      <option>🇮🇳 IN — Google</option>
    </select>
    <button class="sm-search-cta">Analyze</button>
    <span style="margin-left:8px;font-size:.72rem;color:#888;">Domain Overview · Last updated: Feb 22, 2018</span>
  </div>
</div>

<!-- ── Main Layout ─────────────────────────────────────────────────────────── -->
<div class="sm-layout">

  <!-- Left: Main Content -->
  <div>

    <!-- Metric cards -->
    <div class="sm-metrics">
      <div class="sm-metric">
        <div class="sm-metric-label">Authority Score</div>
        <div class="sm-metric-value" style="color:#ff642d;">74</div>
        <div class="sm-metric-sub">out of 100</div>
        <div class="sm-metric-trend up">↑ +2 vs last month</div>
      </div>
      <div class="sm-metric">
        <div class="sm-metric-label">Organic Traffic</div>
        <div class="sm-metric-value">3.2M</div>
        <div class="sm-metric-sub">visits / month</div>
        <div class="sm-metric-trend up">↑ +8.4%</div>
      </div>
      <div class="sm-metric">
        <div class="sm-metric-label">Organic Keywords</div>
        <div class="sm-metric-value">148K</div>
        <div class="sm-metric-sub">ranking keywords</div>
        <div class="sm-metric-trend up">↑ +1,240</div>
      </div>
      <div class="sm-metric">
        <div class="sm-metric-label">Backlinks</div>
        <div class="sm-metric-value">2.1M</div>
        <div class="sm-metric-sub">referring domains: 41,800</div>
        <div class="sm-metric-trend down">↓ -0.3%</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="sm-tabs">
      <a href="index.php?tab=keywords" class="sm-tab<?php echo $activeTab==='keywords'?' active':''; ?>">Organic Keywords</a>
      <a href="index.php?tab=backlinks" class="sm-tab<?php echo $activeTab==='backlinks'?' active':''; ?>">Backlinks</a>
      <a href="index.php?tab=competitors" class="sm-tab">Competitors</a>
      <a href="index.php?tab=traffic" class="sm-tab">Traffic</a>
    </div>

    <?php if($activeTab === 'keywords'): ?>
    <!-- Keyword Rankings Table -->
    <div class="sm-card">
      <div class="sm-card-header">
        <span class="sm-card-title">Organic Keyword Rankings</span>
        <span class="sm-card-count">148,320 total</span>
      </div>
      <table class="sm-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Keyword</th>
            <th>Volume</th>
            <th>Difficulty</th>
            <th>CPC</th>
            <th>Source Page</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($keywords as $k): ?>
          <tr>
            <td><span class="sm-pos<?php echo $k['pos']<=3?' top3':''; ?>"><?php echo $k['pos']; ?></span></td>
            <td><span class="sm-kw"><?php echo htmlspecialchars($k['kw']); ?></span></td>
            <td><span class="sm-vol"><?php echo $k['vol']; ?></span></td>
            <td>
              <div class="sm-diff-bar">
                <div class="sm-diff-bg"><div class="sm-diff-fill" style="width:<?php echo $k['diff']; ?>%"></div></div>
                <span style="font-size:.72rem;color:#555;width:26px;text-align:right;"><?php echo $k['diff']; ?></span>
              </div>
            </td>
            <td><span class="sm-cpc"><?php echo $k['cpc']; ?></span></td>
            <td>
              <a href="<?php echo $labBase; ?>?url=<?php echo urlencode($k['url']); ?>" class="sm-ext-btn">
                <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Visit Page
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php else: ?>
    <!-- Backlinks Table -->
    <div class="sm-card">
      <div class="sm-card-header">
        <span class="sm-card-title">Referring Domains</span>
        <span class="sm-card-count">41,800 total</span>
      </div>
      <table class="sm-table">
        <thead>
          <tr>
            <th>Domain</th>
            <th>Authority</th>
            <th>Backlinks</th>
            <th>Anchor Text</th>
            <th>Source</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($backlinks as $b): ?>
          <tr>
            <td><span class="sm-domain"><?php echo htmlspecialchars($b['domain']); ?></span></td>
            <td><span class="sm-auth high"><?php echo $b['authority']; ?></span></td>
            <td><?php echo number_format($b['links']); ?></td>
            <td style="color:#555;max-width:200px;"><?php echo htmlspecialchars($b['anchor']); ?></td>
            <td>
              <a href="<?php echo $labBase; ?>?url=<?php echo urlencode($b['url']); ?>" class="sm-ext-btn">
                <svg viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Visit Source
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>

  </div>

  <!-- Right: Sidebar -->
  <aside class="sm-sidebar">

    <!-- Domain Score -->
    <div class="sm-sidebar-widget">
      <div class="sm-sidebar-title">Authority Score</div>
      <div class="sm-score-ring">
        <div class="sm-score-num">74</div>
        <div class="sm-score-label">semrush.com · United States</div>
      </div>
      <div class="sm-stat-row">
        <span class="sm-stat-name">Organic Traffic</span>
        <span class="sm-stat-val">3.2M / mo</span>
      </div>
      <div class="sm-stat-row">
        <span class="sm-stat-name">Paid Traffic</span>
        <span class="sm-stat-val">124K / mo</span>
      </div>
      <div class="sm-stat-row">
        <span class="sm-stat-name">Backlinks</span>
        <span class="sm-stat-val">2.1M</span>
      </div>
      <div class="sm-stat-row">
        <span class="sm-stat-name">Referring Domains</span>
        <span class="sm-stat-val">41,800</span>
      </div>
      <div class="sm-stat-row">
        <span class="sm-stat-name">Display Ads</span>
        <span class="sm-stat-val">340 / mo</span>
      </div>
    </div>

  </aside>
</div>

<!-- ── Footer ──────────────────────────────────────────────────────────────── -->
<div style="background:#1b1b37;color:rgba(255,255,255,.45);padding:24px;margin-top:32px;text-align:center;font-size:.72rem;">
  © 2018 Semrush Inc. — Security Lab for Educational Purposes
</div>

</body>
</html>
