<?php
// Prestige Realty Group — CMS Media Loader
$view   = $_GET['view']   ?? 'listings';
$prop   = $_GET['prop']   ?? null;
$media  = $_GET['media']  ?? null;   // Vulnerable LFI parameter
$agent  = $_GET['agent']  ?? null;

// Load media file from local storage (Vulnerable to LFI)
$media_content = null;
$media_name    = null;
if ($media) {
    $media_name    = basename($media);
    $media_content = @file_get_contents($media);
}

// Property catalog
$properties = [
    'P001' => [
        'id'       => 'PRG-001',
        'title'    => 'The Meridian — Penthouse 42A',
        'location' => '842 Lakeshore Blvd, Chicago, IL',
        'price'    => '$6,850,000',
        'beds'     => 4, 'baths' => 4.5, 'sqft' => '4,800',
        'type'     => 'Penthouse', 'status' => 'For Sale',
        'agent'    => 'Marcus Holloway',
        'tag'      => 'NEW',
        'gradient' => 'linear-gradient(135deg,#1a1a2e,#16213e)',
        'icon'     => '🏙️',
        'files' => [
            'brochure'  => ['📄 Property Brochure',  'media/properties/P001/brochure.txt'],
            'floorplan' => ['📐 Floor Plan',          'media/properties/P001/floorplan.txt'],
        ],
    ],
    'P002' => [
        'id'       => 'PRG-002',
        'title'    => 'Villa Aurelia — Newport Beach',
        'location' => '14 Seacliff Drive, Newport Beach, CA',
        'price'    => '$14,500,000',
        'beds'     => 6, 'baths' => 7, 'sqft' => '8,200',
        'type'     => 'Luxury Villa', 'status' => 'For Sale',
        'agent'    => 'Diana Castellano',
        'tag'      => 'EXCLUSIVE',
        'gradient' => 'linear-gradient(135deg,#0c3547,#1a5276)',
        'icon'     => '🌊',
        'files' => [
            'brochure'  => ['📄 Property Brochure',  'media/properties/P002/brochure.txt'],
            'floorplan' => ['📐 Floor Plan',          'media/properties/P002/floorplan.txt'],
        ],
    ],
];

$current = ($prop && isset($properties[$prop])) ? $properties[$prop] : null;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Prestige Realty Group — Luxury Property CMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg:     #0d0d0d;
      --dark1:  #111111;
      --dark2:  #1a1a1a;
      --dark3:  #222222;
      --gold:   #c9a84c;
      --gold2:  #e8c97a;
      --gold3:  rgba(201,168,76,0.12);
      --border: rgba(201,168,76,0.2);
      --text:   #f5f0e8;
      --muted:  #8a8070;
      --white:  #ffffff;
    }
    * { box-sizing: border-box; }
    html, body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }
    body { font-family: 'Jost', system-ui, sans-serif; }
    h1,h2,h3,h4 { font-family: 'Cormorant Garamond', Georgia, serif; }

    /* ── Navbar ── */
    .prg-nav {
      background: rgba(13,13,13,0.97);
      border-bottom: 1px solid var(--border);
      padding: 0 3rem;
      display: flex; align-items: center;
      justify-content: space-between;
      position: sticky; top: 0; z-index: 200;
      backdrop-filter: blur(16px);
      min-height: 70px;
    }
    .prg-logo {
      font-family: 'Cormorant Garamond', Georgia, serif;
      font-size: 1.4rem; font-weight: 700;
      color: var(--gold); text-decoration: none; letter-spacing: .04em;
    }
    .prg-logo span { color: var(--text); font-weight: 400; }
    .nav-links { display: flex; gap: 2rem; }
    .nav-link-item {
      color: var(--muted); font-size: .88rem; font-weight: 500;
      text-decoration: none; letter-spacing: .06em; text-transform: uppercase;
      transition: color .2s; padding: .5rem 0;
      border-bottom: 2px solid transparent;
    }
    .nav-link-item:hover, .nav-link-item.active { color: var(--gold); border-bottom-color: var(--gold); }
    .nav-right { display: flex; align-items: center; gap: 1rem; }
    .agent-chip {
      display: flex; align-items: center; gap: 8px;
      border: 1px solid var(--border); border-radius: 4px;
      padding: 6px 14px; font-size: .8rem; color: var(--muted);
    }
    .agent-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--gold); }

    /* ── Hero Banner ── */
    .hero-band {
      background: linear-gradient(180deg, #0d0d0d 0%, #141008 100%);
      border-bottom: 1px solid var(--border);
      padding: 3rem 3rem 2rem;
      position: relative; overflow: hidden;
    }
    .hero-band::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0; bottom: 0;
      background: radial-gradient(ellipse at 70% 50%, rgba(201,168,76,.07) 0%, transparent 65%);
      pointer-events: none;
    }
    .hero-eyebrow {
      font-size: .72rem; font-weight: 600; letter-spacing: .2em;
      text-transform: uppercase; color: var(--gold); margin-bottom: .5rem;
    }
    .hero-title {
      font-size: 3rem; font-weight: 700; line-height: 1.1;
      color: var(--text); margin-bottom: .5rem;
    }
    .hero-sub { color: var(--muted); font-size: 1rem; font-weight: 300; }
    .hero-stats { display: flex; gap: 3rem; margin-top: 2rem; }
    .hstat-val { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; font-weight: 700; color: var(--gold2); line-height: 1; }
    .hstat-lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .1em; color: var(--muted); margin-top: 4px; }

    /* ── Main Layout ── */
    .prg-main { flex: 1; padding: 2.5rem 3rem; }

    /* ── Section Labels ── */
    .section-eyebrow {
      font-size: .68rem; font-weight: 700; letter-spacing: .18em;
      text-transform: uppercase; color: var(--gold);
      border-left: 3px solid var(--gold); padding-left: .75rem;
      margin-bottom: .5rem;
    }
    .section-title-lg {
      font-size: 2rem; font-weight: 700; color: var(--text);
      margin-bottom: 1.5rem; line-height: 1.2;
    }

    /* ── Property Cards ── */
    .property-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px,1fr)); gap: 1.5rem; margin-bottom: 3rem; }
    .prop-card {
      background: var(--dark2);
      border: 1px solid rgba(255,255,255,.07);
      border-radius: 4px;
      overflow: hidden;
      transition: all .3s;
      text-decoration: none; color: inherit;
    }
    .prop-card:hover { border-color: var(--gold); transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.5), 0 0 30px rgba(201,168,76,.08); }
    .prop-visual {
      height: 200px; position: relative;
      display: flex; align-items: center; justify-content: center;
      font-size: 5rem;
    }
    .prop-tag {
      position: absolute; top: 14px; left: 14px;
      background: var(--gold); color: #0d0d0d;
      font-size: .65rem; font-weight: 700; letter-spacing: .14em;
      text-transform: uppercase; padding: 3px 10px;
    }
    .prop-status {
      position: absolute; top: 14px; right: 14px;
      background: rgba(0,0,0,.6); border: 1px solid var(--border);
      color: var(--gold2); font-size: .68rem; font-weight: 600;
      letter-spacing: .08em; padding: 3px 10px;
    }
    .prop-body { padding: 1.4rem; }
    .prop-id { font-size: .68rem; color: var(--muted); letter-spacing: .12em; text-transform: uppercase; margin-bottom: .3rem; }
    .prop-title { font-family: 'Cormorant Garamond', serif; font-size: 1.35rem; font-weight: 700; color: var(--text); margin-bottom: .25rem; line-height: 1.3; }
    .prop-location { font-size: .83rem; color: var(--muted); margin-bottom: 1rem; }
    .prop-price { font-family: 'Cormorant Garamond', serif; font-size: 1.65rem; font-weight: 700; color: var(--gold2); margin-bottom: .85rem; }
    .prop-specs { display: flex; gap: 1.2rem; font-size: .8rem; color: var(--muted); border-top: 1px solid rgba(255,255,255,.06); padding-top: .85rem; }
    .prop-spec { display: flex; align-items: center; gap: 5px; }
    .prop-footer { padding: .9rem 1.4rem; border-top: 1px solid rgba(255,255,255,.06); display: flex; align-items: center; justify-content: space-between; }
    .agent-mini { font-size: .78rem; color: var(--muted); }
    .btn-view-prop {
      background: var(--gold3); border: 1px solid var(--border);
      color: var(--gold); font-size: .78rem; font-weight: 600;
      letter-spacing: .06em; text-transform: uppercase;
      padding: 7px 16px; transition: all .2s; text-decoration: none;
    }
    .btn-view-prop:hover { background: var(--gold); color: #0d0d0d; }

    /* ── Property Detail / File Browser ── */
    .detail-header {
      background: var(--dark2); border: 1px solid var(--border);
      border-radius: 4px; padding: 2rem;
      display: flex; align-items: flex-start; gap: 2rem;
      margin-bottom: 1.5rem;
    }
    .detail-visual {
      width: 160px; height: 120px; border-radius: 4px;
      display: flex; align-items: center; justify-content: center;
      font-size: 4rem; flex-shrink: 0;
    }
    .detail-title { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 700; margin-bottom: .25rem; }
    .detail-loc { color: var(--muted); margin-bottom: .5rem; }
    .detail-price { font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--gold2); }
    .detail-badges { display: flex; gap: .5rem; margin-top: .75rem; flex-wrap: wrap; }
    .detail-badge {
      background: var(--gold3); border: 1px solid var(--border);
      color: var(--gold2); font-size: .72rem; font-weight: 600;
      padding: 3px 10px; letter-spacing: .06em; text-transform: uppercase;
    }

    /* ── Media File List ── */
    .media-section-label {
      font-size: .68rem; font-weight: 700; letter-spacing: .14em;
      text-transform: uppercase; color: var(--muted);
      margin: 1.5rem 0 .75rem;
    }
    .media-file-row {
      background: var(--dark2); border: 1px solid rgba(255,255,255,.06);
      border-left: 3px solid var(--gold);
      padding: 1rem 1.4rem;
      display: flex; align-items: center; gap: 1.2rem;
      margin-bottom: .6rem; transition: all .2s;
    }
    .media-file-row:hover { border-left-color: var(--gold2); background: var(--dark3); }
    .mfr-icon { font-size: 1.6rem; flex-shrink: 0; }
    .mfr-name { font-weight: 600; color: var(--text); font-size: .92rem; }
    .mfr-path { font-family: 'Courier New', monospace; font-size: .72rem; color: var(--muted); margin-top: 2px; }
    .mfr-actions { margin-left: auto; display: flex; gap: .5rem; }
    .btn-gold-sm {
      background: var(--gold3); border: 1px solid var(--border);
      color: var(--gold); font-size: .75rem; font-weight: 600;
      letter-spacing: .05em; padding: 6px 14px; text-decoration: none;
      transition: all .2s; text-transform: uppercase;
    }
    .btn-gold-sm:hover { background: var(--gold); color: #0d0d0d; }
    .btn-ghost-sm {
      border: 1px solid rgba(255,255,255,.1);
      color: var(--muted); font-size: .75rem; font-weight: 600;
      padding: 6px 12px; text-decoration: none; transition: all .2s;
    }
    .btn-ghost-sm:hover { border-color: var(--muted); color: var(--text); }

    /* ── Agent Dashboard ── */
    .agent-card {
      background: var(--dark2); border: 1px solid rgba(255,255,255,.07);
      padding: 1.4rem; display: flex; align-items: center; gap: 1rem;
      margin-bottom: .85rem;
    }
    .agent-av {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--gold3); border: 2px solid var(--gold);
      display: flex; align-items: center; justify-content: center;
      font-family: 'Cormorant Garamond', serif;
      font-size: 1.2rem; font-weight: 700; color: var(--gold2);
      flex-shrink: 0;
    }
    .agent-name { font-weight: 600; font-size: 1rem; color: var(--text); }
    .agent-role { font-size: .78rem; color: var(--muted); }
    .agent-license { font-size: .72rem; font-family: monospace; color: var(--gold); margin-top: 3px; }

    /* ── Media Input ── */
    .media-input-bar {
      background: var(--dark2);
      border: 1px solid var(--border);
      border-top: 3px solid var(--gold);
      padding: 1rem 1.4rem;
      margin-bottom: 1.5rem;
    }
    .media-input-label { font-size: .7rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted); margin-bottom: .6rem; }

    /* ── Media Viewer ── */
    .media-viewer {
      background: var(--dark1);
      border: 1px solid var(--border);
    }
    .mv-header {
      background: linear-gradient(90deg, #1a1408, #0d0d0d);
      border-bottom: 1px solid var(--border);
      padding: 1rem 1.4rem;
      display: flex; align-items: center; justify-content: space-between;
    }
    .mv-title { font-family: 'Cormorant Garamond', serif; font-size: 1.1rem; font-weight: 700; color: var(--gold2); }
    .mv-path { font-family: 'Courier New', monospace; font-size: .72rem; color: var(--muted); margin-top: 2px; }
    .mv-content {
      font-family: 'Courier New', monospace; font-size: .83rem;
      color: #c9b99a; background: #080704;
      padding: 1.4rem 1.6rem;
      white-space: pre-wrap; word-break: break-all;
      max-height: 520px; overflow-y: auto; line-height: 1.8;
    }
    .mv-footer {
      border-top: 1px solid var(--border); padding: .75rem 1.4rem;
      background: var(--dark2); display: flex; gap: .5rem;
    }

    /* ── Back link ── */
    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      color: var(--muted); font-size: .82rem; text-decoration: none;
      margin-bottom: 1.5rem; transition: color .2s;
    }
    .back-link:hover { color: var(--gold); }

    /* ── CMS notice ── */
    .cms-notice {
      background: rgba(201,168,76,.06);
      border: 1px solid var(--border);
      border-left: 3px solid var(--gold);
      padding: .85rem 1.2rem; font-size: .83rem; color: var(--muted);
      margin-bottom: 1.5rem;
    }

    footer {
      padding: 1.5rem 3rem;
      border-top: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      font-size: .78rem; color: var(--muted); background: var(--dark1);
    }
    .footer-brand { font-family: 'Cormorant Garamond', serif; color: var(--gold); font-size: .95rem; }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="prg-nav">
  <a class="prg-logo" href="?view=listings">PRESTIGE <span>REALTY</span></a>
  <div class="nav-links">
    <a class="nav-link-item <?php echo $view==='listings'?'active':''; ?>" href="?view=listings">Listings</a>
    <a class="nav-link-item <?php echo $view==='gallery'?'active':''; ?>" href="?view=gallery">Gallery</a>
    <a class="nav-link-item <?php echo $view==='agents'?'active':''; ?>" href="?view=agents">Agents</a>
    <a class="nav-link-item <?php echo $view==='media'?'active':''; ?>" href="?view=media">Media Library</a>
  </div>
  <div class="nav-right">
    <div class="agent-chip"><div class="agent-dot"></div>Agent Portal Active</div>
  </div>
</nav>

<!-- Hero Band -->
<div class="hero-band">
  <div class="hero-eyebrow">Prestige Realty Group — Property CMS</div>
  <h1 class="hero-title">Luxury Property<br>Management Portal</h1>
  <p class="hero-sub">Manage listings, media assets, brochures and floor plans across all properties.</p>
  <div class="hero-stats">
    <div><div class="hstat-val">2</div><div class="hstat-lbl">Active Listings</div></div>
    <div><div class="hstat-val">$21.3M</div><div class="hstat-lbl">Portfolio Value</div></div>
    <div><div class="hstat-val">4</div><div class="hstat-lbl">Media Files</div></div>
    <div><div class="hstat-val">2</div><div class="hstat-lbl">Agents</div></div>
  </div>
</div>

<!-- Main -->
<div class="prg-main">

  <!-- CMS Media Input Bar (always visible — exposes the vulnerable parameter) -->
  <div class="media-input-bar">
    <div class="media-input-label"><i class="bi bi-folder2-open me-2"></i>CMS Media Loader — Direct File Access</div>
    <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
      <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
      <?php if ($prop): ?><input type="hidden" name="prop" value="<?php echo htmlspecialchars($prop); ?>"><?php endif; ?>
      <input type="text" name="media"
        class="form-control form-control-sm font-monospace"
        style="max-width:440px;background:#111;border-color:rgba(201,168,76,.3);color:#c9b99a;"
        placeholder="media/properties/P001/brochure.txt"
        value="<?php echo htmlspecialchars($media ?? '', ENT_QUOTES); ?>">
      <button type="submit" style="background:var(--gold);color:#0d0d0d;border:none;padding:6px 18px;font-weight:700;font-size:.82rem;letter-spacing:.06em;text-transform:uppercase;">
        LOAD FILE
      </button>
    </form>
  </div>

  <?php if ($media && $media_content !== null && $media_content !== false): ?>
  <!-- ── MEDIA VIEWER ── -->
  <a class="back-link" href="<?php echo $prop?"?view={$view}&prop={$prop}":"?view={$view}"; ?>">← Back</a>
  <div class="media-viewer">
    <div class="mv-header">
      <div>
        <div class="mv-title">📄 <?php echo htmlspecialchars($media_name); ?></div>
        <div class="mv-path"><?php echo htmlspecialchars($media); ?></div>
      </div>
      <span style="background:var(--gold3);border:1px solid var(--border);color:var(--gold);font-size:.7rem;font-weight:700;letter-spacing:.1em;padding:3px 10px;text-transform:uppercase;">CMS DOCUMENT</span>
    </div>
    <div class="mv-content"><?php echo htmlspecialchars($media_content); ?></div>
    <div class="mv-footer">
      <a href="<?php echo htmlspecialchars($media); ?>" download class="btn-gold-sm"><i class="bi bi-download me-1"></i>Download</a>
      <a href="<?php echo $prop?"?view={$view}&prop={$prop}":"?view={$view}"; ?>" class="btn-ghost-sm">← Back</a>
    </div>
  </div>

  <?php elseif ($media): ?>
  <div class="cms-notice"><i class="bi bi-exclamation-triangle me-2" style="color:var(--gold)"></i>Media file not found: <code><?php echo htmlspecialchars($media); ?></code></div>

  <?php elseif ($view === 'agents'): ?>
  <!-- ── AGENT DASHBOARD ── -->
  <div class="section-eyebrow">Agent Portal</div>
  <h2 class="section-title-lg">Agent Directory</h2>
  <div class="cms-notice">
    <i class="bi bi-info-circle me-2" style="color:var(--gold)"></i>
    Agent performance data and credentials are stored in <code>media/agents/agent_database.txt</code>.
    Use the CMS Media Loader above to access agent records.
  </div>
  <?php
    $agents = [
      ['MH','Marcus Holloway','Senior Listing Agent — Chicago','IL-2084712','2 Active Listings'],
      ['DC','Diana Castellano','Senior Listing Agent — Orange County','CA-01943812','1 Active Listing'],
      ['RO','Rebecca Osei','Broker / CMS Administrator','DRE-BROKER-2026','Full Access'],
    ];
    foreach ($agents as [$init,$name,$role,$lic,$listings]):
  ?>
  <div class="agent-card">
    <div class="agent-av"><?php echo $init; ?></div>
    <div>
      <div class="agent-name"><?php echo $name; ?></div>
      <div class="agent-role"><?php echo $role; ?></div>
      <div class="agent-license"><?php echo $lic; ?> &nbsp;·&nbsp; <?php echo $listings; ?></div>
    </div>
    <div style="margin-left:auto">
      <a href="?view=agents&media=media/agents/agent_database.txt" class="btn-gold-sm">View Record</a>
    </div>
  </div>
  <?php endforeach; ?>

  <?php elseif ($view === 'media'): ?>
  <!-- ── MEDIA LIBRARY ── -->
  <div class="section-eyebrow">CMS Media Library</div>
  <h2 class="section-title-lg">All Property Files</h2>
  <div class="cms-notice"><i class="bi bi-info-circle me-2" style="color:var(--gold)"></i>Media files are loaded from the <code>media/</code> directory. Use the loader above to preview any file path.</div>
  <?php foreach ($properties as $slug => $p): ?>
  <div class="media-section-label"><?php echo $p['id']; ?> — <?php echo htmlspecialchars($p['title']); ?></div>
  <?php foreach ($p['files'] as [$label,$path]): ?>
  <div class="media-file-row">
    <div class="mfr-icon"><?php echo substr($label,0,2); ?></div>
    <div>
      <div class="mfr-name"><?php echo htmlspecialchars($label); ?></div>
      <div class="mfr-path"><?php echo htmlspecialchars($path); ?></div>
    </div>
    <div class="mfr-actions">
      <a href="?view=media&media=<?php echo urlencode($path); ?>" class="btn-gold-sm">Preview</a>
      <a href="<?php echo htmlspecialchars($path); ?>" download class="btn-ghost-sm"><i class="bi bi-download"></i></a>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endforeach; ?>

  <?php elseif ($current): ?>
  <!-- ── PROPERTY DETAIL ── -->
  <a class="back-link" href="?view=listings">← All Listings</a>
  <div class="detail-header">
    <div class="detail-visual" style="background:<?php echo $current['gradient']; ?>"><?php echo $current['icon']; ?></div>
    <div>
      <div style="font-size:.68rem;color:var(--muted);letter-spacing:.1em;text-transform:uppercase;margin-bottom:.3rem;"><?php echo $current['id']; ?></div>
      <div class="detail-title"><?php echo htmlspecialchars($current['title']); ?></div>
      <div class="detail-loc"><i class="bi bi-geo-alt me-1" style="color:var(--gold)"></i><?php echo htmlspecialchars($current['location']); ?></div>
      <div class="detail-price"><?php echo $current['price']; ?></div>
      <div class="detail-badges">
        <span class="detail-badge">🛏 <?php echo $current['beds']; ?> Beds</span>
        <span class="detail-badge">🚿 <?php echo $current['baths']; ?> Baths</span>
        <span class="detail-badge">📐 <?php echo $current['sqft']; ?> sq ft</span>
        <span class="detail-badge"><?php echo $current['type']; ?></span>
        <span class="detail-badge" style="background:rgba(201,168,76,.25);color:var(--gold2);"><?php echo $current['status']; ?></span>
      </div>
    </div>
  </div>
  <div class="media-section-label">Property Media Files</div>
  <?php foreach ($current['files'] as $key => [$label,$path]): ?>
  <div class="media-file-row">
    <div class="mfr-icon"><?php echo substr($label,0,2); ?></div>
    <div>
      <div class="mfr-name"><?php echo htmlspecialchars($label); ?></div>
      <div class="mfr-path"><?php echo htmlspecialchars($path); ?></div>
    </div>
    <div class="mfr-actions">
      <a href="?view=listings&prop=<?php echo urlencode($prop); ?>&media=<?php echo urlencode($path); ?>" class="btn-gold-sm">Preview</a>
      <a href="<?php echo htmlspecialchars($path); ?>" download class="btn-ghost-sm"><i class="bi bi-download"></i></a>
    </div>
  </div>
  <?php endforeach; ?>

  <?php else: ?>
  <!-- ── LISTINGS ── -->
  <div class="section-eyebrow">Exclusive Listings</div>
  <h2 class="section-title-lg">Featured Properties</h2>
  <div class="property-grid">
    <?php foreach ($properties as $slug => $p): ?>
    <a class="prop-card" href="?view=listings&prop=<?php echo urlencode($slug); ?>">
      <div class="prop-visual" style="background:<?php echo $p['gradient']; ?>">
        <?php echo $p['icon']; ?>
        <div class="prop-tag"><?php echo $p['tag']; ?></div>
        <div class="prop-status"><?php echo $p['status']; ?></div>
      </div>
      <div class="prop-body">
        <div class="prop-id"><?php echo $p['id']; ?></div>
        <div class="prop-title"><?php echo htmlspecialchars($p['title']); ?></div>
        <div class="prop-location"><i class="bi bi-geo-alt me-1" style="color:var(--gold)"></i><?php echo htmlspecialchars($p['location']); ?></div>
        <div class="prop-price"><?php echo $p['price']; ?></div>
        <div class="prop-specs">
          <span class="prop-spec"><i class="bi bi-door-open"></i> <?php echo $p['beds']; ?> Beds</span>
          <span class="prop-spec"><i class="bi bi-droplet"></i> <?php echo $p['baths']; ?> Baths</span>
          <span class="prop-spec"><i class="bi bi-arrows-angle-expand"></i> <?php echo $p['sqft']; ?> sq ft</span>
        </div>
      </div>
      <div class="prop-footer">
        <span class="agent-mini"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($p['agent']); ?></span>
        <span class="btn-view-prop">View Listing</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>

<footer>
  <div class="footer-brand">PRESTIGE REALTY GROUP</div>
  <div>&copy; 2026 Prestige Realty Group &nbsp;|&nbsp; Licensed Real Estate Broker &nbsp;|&nbsp; CMS v4.2.1</div>
  <div>All information subject to change without notice.</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
</body>
</html>
