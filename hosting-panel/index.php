<?php
// CloudHost Panel — Log & File Viewer Controller
$section = $_GET['section'] ?? 'home';
$log     = $_GET['log']     ?? null;    // Vulnerable LFI parameter

// Load log/file content from filesystem (Vulnerable to LFI)
$log_content = null;
$log_name    = null;
if ($log) {
    $log_name    = basename($log);
    $log_content = @file_get_contents($log);
}

// Log catalog
$log_catalog = [
    'apache-access' => ['Apache Access Log',  'logs/apache/access.log',        'access',  '#22c55e'],
    'apache-error'  => ['Apache Error Log',   'logs/apache/error.log',         'error',   '#ef4444'],
    'php-errors'    => ['PHP Error Log',       'logs/php/php_errors.log',       'php',     '#f59e0b'],
    'nginx-error'   => ['Nginx Error Log',     'logs/nginx/nginx_error.log',    'nginx',   '#38bdf8'],
    'backup'        => ['Backup Manifest',     'logs/backups/backup_manifest.txt','backup','#a78bfa'],
];

// Disk usage data (fake)
$disk = ['used'=>8.4,'total'=>20,'pct'=>42];
// Bandwidth
$bw   = ['used'=>142,'total'=>500,'pct'=>28];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CloudHost Panel — Server Administration</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="icon" href="/favicon.ico" />

  <style>
    :root {
      --bg:     #0a0f0a;
      --panel:  #0e140e;
      --card:   #111811;
      --card2:  #162016;
      --border: rgba(34,197,94,0.18);
      --green:  #22c55e;
      --green2: #4ade80;
      --green3: rgba(34,197,94,0.08);
      --dim:    #15803d;
      --red:    #ef4444;
      --amber:  #f59e0b;
      --blue:   #38bdf8;
      --purple: #a78bfa;
      --text:   #e2f5e2;
      --muted:  #4b7a4b;
      --mono:   'JetBrains Mono', monospace;
    }
    * { box-sizing: border-box; }
    html, body { background: var(--bg); color: var(--text); min-height: 100vh; display: flex; flex-direction: column; }
    body { font-family: 'IBM Plex Sans', system-ui, sans-serif; }
    code, .mono { font-family: var(--mono); }

    /* ── Top Bar ── */
    .panel-topbar {
      background: var(--panel);
      border-bottom: 1px solid var(--border);
      padding: 0 1.5rem;
      display: flex; align-items: center;
      justify-content: space-between;
      min-height: 52px;
    }
    .panel-brand {
      display: flex; align-items: center; gap: 10px;
      font-family: var(--mono); font-weight: 700; font-size: 1rem;
      color: var(--green); text-decoration: none;
    }
    .brand-icon {
      width: 30px; height: 30px; border-radius: 6px;
      background: var(--green3); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: .9rem; color: var(--green);
    }
    .topbar-meta { display: flex; align-items: center; gap: 1.5rem; font-family: var(--mono); font-size: .75rem; color: var(--muted); }
    .meta-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); display: inline-block; margin-right: 5px; box-shadow: 0 0 6px var(--green); animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .user-tag {
      background: var(--green3); border: 1px solid var(--border);
      border-radius: 4px; padding: 4px 12px;
      font-family: var(--mono); font-size: .75rem; color: var(--green);
    }

    /* ── Secondary Nav ── */
    .panel-nav {
      background: var(--card);
      border-bottom: 1px solid var(--border);
      padding: 0 1.5rem;
      display: flex; gap: 0;
      overflow-x: auto;
    }
    .pnav-link {
      display: flex; align-items: center; gap: 7px;
      padding: .7rem 1.1rem; font-size: .82rem; font-weight: 600;
      color: var(--muted); text-decoration: none; white-space: nowrap;
      border-bottom: 2px solid transparent; transition: all .2s;
      font-family: 'IBM Plex Sans', sans-serif;
    }
    .pnav-link:hover { color: var(--green2); }
    .pnav-link.active { color: var(--green); border-bottom-color: var(--green); background: rgba(34,197,94,.04); }

    /* ── Layout ── */
    .panel-body { flex: 1; display: flex; }
    .panel-sidebar {
      width: 220px; flex-shrink: 0;
      background: var(--panel);
      border-right: 1px solid var(--border);
      padding: 1rem;
    }
    .sb-section { font-family: var(--mono); font-size: .65rem; color: var(--dim); letter-spacing: .12em; text-transform: uppercase; padding: .5rem .5rem .3rem; margin-top: .75rem; }
    .sb-link {
      display: flex; align-items: center; gap: 8px;
      padding: .48rem .75rem; border-radius: 6px;
      font-size: .83rem; font-weight: 500; color: var(--muted);
      text-decoration: none; transition: all .2s; margin-bottom: 1px;
      font-family: 'IBM Plex Sans', sans-serif;
    }
    .sb-link:hover { background: var(--green3); color: var(--green2); }
    .sb-link.active { background: var(--green3); color: var(--green); border-left: 2px solid var(--green); }
    .sb-link i { width: 16px; text-align: center; font-size: .9rem; }

    /* ── Main ── */
    .panel-main { flex: 1; padding: 1.5rem; overflow: auto; }

    /* ── Breadcrumb ── */
    .panel-crumb { font-family: var(--mono); font-size: .75rem; color: var(--muted); margin-bottom: 1.25rem; }
    .panel-crumb a { color: var(--green); text-decoration: none; }
    .panel-crumb a:hover { text-decoration: underline; }

    /* ── Stat Row ── */
    .stat-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr)); gap: .85rem; margin-bottom: 1.5rem; }
    .stat-box {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 8px; padding: 1rem;
    }
    .stat-box-label { font-family: var(--mono); font-size: .65rem; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin-bottom: .4rem; }
    .stat-box-val { font-family: var(--mono); font-size: 1.5rem; font-weight: 700; color: var(--green2); line-height: 1; }
    .stat-box-sub { font-size: .75rem; color: var(--muted); margin-top: 3px; }
    .progress-slim { height: 4px; background: rgba(34,197,94,.15); border-radius: 2px; margin-top: 8px; }
    .progress-slim-fill { height: 4px; border-radius: 2px; background: var(--green); }

    /* ── Icon Grid (cPanel style) ── */
    .icon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px,1fr)); gap: .85rem; margin-bottom: 1.5rem; }
    .icon-tile {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 8px; padding: 1.2rem .75rem;
      display: flex; flex-direction: column; align-items: center;
      text-align: center; gap: .6rem; text-decoration: none;
      transition: all .2s; cursor: pointer;
    }
    .icon-tile:hover { background: var(--card2); border-color: var(--green); box-shadow: 0 0 14px rgba(34,197,94,.12); }
    .icon-tile i { font-size: 1.6rem; }
    .icon-tile-label { font-size: .76rem; font-weight: 600; color: var(--text); line-height: 1.3; }
    .icon-tile-sub { font-size: .66rem; color: var(--muted); }

    /* ── Log Panel ── */
    .log-panel { background: var(--card); border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-bottom: 1rem; }
    .log-header {
      background: var(--card2); border-bottom: 1px solid var(--border);
      padding: .7rem 1rem; display: flex; align-items: center; justify-content: space-between;
    }
    .log-title { font-family: var(--mono); font-size: .85rem; font-weight: 700; }
    .log-path { font-family: var(--mono); font-size: .7rem; color: var(--muted); margin-top: 2px; }
    .log-badge { font-family: var(--mono); font-size: .65rem; font-weight: 700; padding: 2px 8px; border-radius: 3px; text-transform: uppercase; letter-spacing: .07em; }
    .log-content {
      font-family: var(--mono); font-size: .78rem; color: #a3d9a3;
      background: #050905; padding: 1.2rem;
      white-space: pre-wrap; word-break: break-all;
      max-height: 520px; overflow-y: auto; line-height: 1.7;
    }
    .log-toolbar { background: var(--card2); border-top: 1px solid var(--border); padding: .6rem 1rem; display: flex; gap: .5rem; }

    /* ── Log List Rows ── */
    .log-row {
      background: var(--card); border: 1px solid var(--border);
      border-left: 3px solid var(--green);
      padding: .85rem 1rem;
      display: flex; align-items: center; gap: .85rem;
      margin-bottom: .5rem; transition: all .2s;
    }
    .log-row:hover { background: var(--card2); border-left-color: var(--green2); box-shadow: 0 0 10px rgba(34,197,94,.08); }
    .log-row-icon { font-size: 1.15rem; flex-shrink: 0; width: 24px; text-align: center; }
    .log-row-name { font-family: var(--mono); font-weight: 700; font-size: .85rem; }
    .log-row-path { font-family: var(--mono); font-size: .7rem; color: var(--muted); margin-top: 1px; }
    .log-row-actions { margin-left: auto; display: flex; gap: .4rem; }

    /* ── Buttons ── */
    .btn-green {
      background: rgba(34,197,94,.15); border: 1px solid rgba(34,197,94,.3);
      color: var(--green); font-family: var(--mono); font-size: .75rem; font-weight: 700;
      padding: 5px 13px; text-decoration: none; transition: all .2s; border-radius: 4px;
    }
    .btn-green:hover { background: var(--green); color: #0a0f0a; }
    .btn-dim {
      border: 1px solid rgba(255,255,255,.1); color: var(--muted);
      font-family: var(--mono); font-size: .75rem; font-weight: 600;
      padding: 5px 11px; text-decoration: none; transition: all .2s; border-radius: 4px;
    }
    .btn-dim:hover { border-color: var(--muted); color: var(--text); }

    /* ── Log File Path Input ── */
    .log-loader {
      background: var(--card); border: 1px solid var(--border);
      border-top: 2px solid var(--green); border-radius: 8px;
      padding: .9rem 1rem; margin-bottom: 1.25rem;
    }
    .log-loader-label { font-family: var(--mono); font-size: .65rem; color: var(--muted); text-transform: uppercase; letter-spacing: .12em; margin-bottom: .5rem; }

    /* ── Terminal prompt decoration ── */
    .term-prompt { color: var(--green); font-family: var(--mono); font-size: .75rem; }

    footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border); background: var(--panel); font-family: var(--mono); font-size: .72rem; color: var(--muted); display: flex; justify-content: space-between; }
  </style>
</head>
<body>

<!-- Top Bar -->
<div class="panel-topbar">
  <a class="panel-brand" href="?section=home">
    <div class="brand-icon"><i class="bi bi-terminal"></i></div>
    CloudHost Panel
  </a>
  <div class="topbar-meta">
    <span><span class="meta-dot"></span>Server Online</span>
    <span>srv-42.cloudhost.internal</span>
    <span>PHP 8.2.10 · Apache 2.4.57</span>
  </div>
  <div class="user-tag">hostadmin@example.com</div>
</div>

<!-- Secondary Nav -->
<div class="panel-nav">
  <a class="pnav-link <?php echo $section==='home'?'active':''; ?>" href="?section=home"><i class="bi bi-house"></i> Home</a>
  <a class="pnav-link <?php echo $section==='files'?'active':''; ?>" href="?section=files"><i class="bi bi-folder2"></i> File Manager</a>
  <a class="pnav-link <?php echo $section==='logs'?'active':''; ?>" href="?section=logs"><i class="bi bi-journal-text"></i> Log Viewer</a>
  <a class="pnav-link <?php echo $section==='backups'?'active':''; ?>" href="?section=backups"><i class="bi bi-archive"></i> Backups</a>
  <a class="pnav-link" href="?section=home"><i class="bi bi-database"></i> Databases</a>
  <a class="pnav-link" href="?section=home"><i class="bi bi-envelope"></i> Email</a>
  <a class="pnav-link" href="?section=home"><i class="bi bi-shield-check"></i> Security</a>
</div>

<!-- Body -->
<div class="panel-body">

  <!-- Sidebar -->
  <nav class="panel-sidebar">
    <div class="sb-section">Server</div>
    <a class="sb-link <?php echo $section==='home'?'active':''; ?>" href="?section=home"><i class="bi bi-speedometer"></i>Overview</a>
    <a class="sb-link" href="?section=home"><i class="bi bi-cpu"></i>Resource Usage</a>
    <a class="sb-link" href="?section=home"><i class="bi bi-globe"></i>Domains</a>

    <div class="sb-section">Files</div>
    <a class="sb-link <?php echo $section==='files'?'active':''; ?>" href="?section=files"><i class="bi bi-folder2-open"></i>File Manager</a>
    <a class="sb-link <?php echo $section==='backups'?'active':''; ?>" href="?section=backups"><i class="bi bi-archive"></i>Backups</a>
    <a class="sb-link" href="?section=home"><i class="bi bi-upload"></i>FTP Accounts</a>

    <div class="sb-section">Logs</div>
    <a class="sb-link <?php echo $section==='logs'?'active':''; ?>" href="?section=logs"><i class="bi bi-journal-text"></i>Log Viewer</a>
    <a class="sb-link" href="?section=logs&log=logs/apache/error.log"><i class="bi bi-bug"></i>Error Logs</a>
    <a class="sb-link" href="?section=logs&log=logs/apache/access.log"><i class="bi bi-activity"></i>Access Logs</a>
    <a class="sb-link" href="?section=logs&log=logs/php/php_errors.log"><i class="bi bi-filetype-php"></i>PHP Logs</a>
    <a class="sb-link" href="?section=logs&log=logs/nginx/nginx_error.log"><i class="bi bi-hdd-network"></i>Nginx Logs</a>
  </nav>

  <!-- Main Content -->
  <main class="panel-main">

    <!-- Log/File Loader (always visible) -->
    <div class="log-loader">
      <div class="log-loader-label"><span class="term-prompt">$</span> log_viewer --file</div>
      <form method="get" class="d-flex gap-2 flex-wrap align-items-center">
        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">
        <span class="term-prompt">&gt;</span>
        <input type="text" name="log"
          class="form-control form-control-sm mono"
          style="max-width:440px;background:#050905;border-color:rgba(34,197,94,.3);color:#a3d9a3;font-size:.8rem;"
          placeholder="logs/apache/access.log"
          value="<?php echo htmlspecialchars($log ?? '', ENT_QUOTES); ?>">
        <button type="submit" class="btn-green">VIEW FILE</button>
      </form>
    </div>

    <!-- Breadcrumb -->
    <div class="panel-crumb">
      <a href="?section=home">~</a> /
      <?php if ($log): ?>
        <a href="?section=<?php echo htmlspecialchars($section); ?>"><?php echo htmlspecialchars($section); ?></a> /
        <?php echo htmlspecialchars($log_name); ?>
      <?php else: ?>
        <?php echo htmlspecialchars($section); ?>
      <?php endif; ?>
    </div>

    <?php if ($log && $log_content !== null && $log_content !== false): ?>
    <!-- LOG VIEWER -->
    <div class="log-panel">
      <div class="log-header">
        <div>
          <div class="log-title"><i class="bi bi-file-text me-2" style="color:var(--green)"></i><?php echo htmlspecialchars($log_name); ?></div>
          <div class="log-path"><?php echo htmlspecialchars($log); ?></div>
        </div>
        <span class="log-badge" style="background:rgba(34,197,94,.15);color:var(--green);border:1px solid rgba(34,197,94,.3);">LOG FILE</span>
      </div>
      <div class="log-content"><?php echo htmlspecialchars($log_content); ?></div>
      <div class="log-toolbar">
        <a href="<?php echo htmlspecialchars($log); ?>" download class="btn-green"><i class="bi bi-download me-1"></i>Download</a>
        <a href="?section=<?php echo htmlspecialchars($section); ?>" class="btn-dim">← Back</a>
      </div>
    </div>

    <?php elseif ($log): ?>
    <div style="background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.3);border-left:3px solid #ef4444;padding:.85rem 1rem;border-radius:6px;font-family:var(--mono);font-size:.8rem;color:#ef4444;margin-bottom:1rem;">
      <i class="bi bi-x-circle me-2"></i>Permission denied or file not found: <strong><?php echo htmlspecialchars($log); ?></strong>
    </div>

    <?php elseif ($section === 'logs'): ?>
    <!-- LOG LIST -->
    <div style="font-family:var(--mono);font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;">Available Log Files</div>
    <?php foreach ($log_catalog as $key => [$name, $path, $type, $col]): ?>
    <div class="log-row">
      <div class="log-row-icon" style="color:<?php echo $col; ?>"><i class="bi bi-file-text"></i></div>
      <div>
        <div class="log-row-name" style="color:<?php echo $col; ?>"><?php echo htmlspecialchars($name); ?></div>
        <div class="log-row-path"><?php echo htmlspecialchars($path); ?></div>
      </div>
      <div class="log-row-actions">
        <a href="?section=logs&log=<?php echo urlencode($path); ?>" class="btn-green">VIEW</a>
        <a href="<?php echo htmlspecialchars($path); ?>" download class="btn-dim"><i class="bi bi-download"></i></a>
      </div>
    </div>
    <?php endforeach; ?>

    <?php elseif ($section === 'files'): ?>
    <!-- FILE MANAGER -->
    <div style="font-family:var(--mono);font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;">File Manager — /var/www/html</div>
    <?php
    $dirs = ['apache/ (access.log, error.log)'=>'logs/apache','php/ (php_errors.log)'=>'logs/php','nginx/ (nginx_error.log)'=>'logs/nginx','backups/ (backup_manifest.txt)'=>'logs/backups'];
    foreach ($dirs as $label=>$path):
    ?>
    <div class="log-row">
      <div class="log-row-icon" style="color:var(--amber)"><i class="bi bi-folder2"></i></div>
      <div>
        <div class="log-row-name"><?php echo htmlspecialchars($label); ?></div>
        <div class="log-row-path"><?php echo htmlspecialchars($path); ?>/</div>
      </div>
      <div class="log-row-actions">
        <a href="?section=logs" class="btn-green">Browse</a>
      </div>
    </div>
    <?php endforeach; ?>

    <?php elseif ($section === 'backups'): ?>
    <!-- BACKUPS -->
    <div style="font-family:var(--mono);font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;">Backup Management</div>
    <div class="log-row">
      <div class="log-row-icon" style="color:var(--purple)"><i class="bi bi-archive"></i></div>
      <div>
        <div class="log-row-name" style="color:var(--purple)">backup_example_com_20260804_030001.tar.gz</div>
        <div class="log-row-path">2026-08-04 03:00:01 UTC — 2.31 GB — Manifest: logs/backups/backup_manifest.txt</div>
      </div>
      <div class="log-row-actions">
        <a href="?section=backups&log=logs/backups/backup_manifest.txt" class="btn-green">Manifest</a>
        <a href="#" class="btn-dim">Download</a>
      </div>
    </div>

    <?php else: ?>
    <!-- HOME -->
    <div class="stat-row">
      <div class="stat-box">
        <div class="stat-box-label">Disk Usage</div>
        <div class="stat-box-val"><?php echo $disk['pct']; ?>%</div>
        <div class="stat-box-sub"><?php echo $disk['used']; ?> GB / <?php echo $disk['total']; ?> GB</div>
        <div class="progress-slim"><div class="progress-slim-fill" style="width:<?php echo $disk['pct']; ?>%"></div></div>
      </div>
      <div class="stat-box">
        <div class="stat-box-label">Bandwidth</div>
        <div class="stat-box-val"><?php echo $bw['pct']; ?>%</div>
        <div class="stat-box-sub"><?php echo $bw['used']; ?> GB / <?php echo $bw['total']; ?> GB</div>
        <div class="progress-slim"><div class="progress-slim-fill" style="width:<?php echo $bw['pct']; ?>%;background:var(--blue)"></div></div>
      </div>
      <div class="stat-box">
        <div class="stat-box-label">Uptime</div>
        <div class="stat-box-val">99.9%</div>
        <div class="stat-box-sub">42 days, 6 hrs</div>
      </div>
      <div class="stat-box">
        <div class="stat-box-label">Active Processes</div>
        <div class="stat-box-val">14</div>
        <div class="stat-box-sub">Load avg: 0.42</div>
      </div>
      <div class="stat-box">
        <div class="stat-box-label">MySQL</div>
        <div class="stat-box-val" style="color:var(--blue)">UP</div>
        <div class="stat-box-sub">3 databases</div>
      </div>
      <div class="stat-box">
        <div class="stat-box-label">Last Backup</div>
        <div class="stat-box-val" style="color:var(--purple)">OK</div>
        <div class="stat-box-sub">Today 03:00 UTC</div>
      </div>
    </div>

    <div style="font-family:var(--mono);font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.12em;margin-bottom:.75rem;">Quick Access</div>
    <div class="icon-grid">
      <a class="icon-tile" href="?section=files"><i class="bi bi-folder2-open" style="color:var(--amber)"></i><span class="icon-tile-label">File Manager</span><span class="icon-tile-sub">Browse files</span></a>
      <a class="icon-tile" href="?section=backups"><i class="bi bi-archive" style="color:var(--purple)"></i><span class="icon-tile-label">Backups</span><span class="icon-tile-sub">Download & restore</span></a>
      <a class="icon-tile" href="?section=logs&log=logs/apache/access.log"><i class="bi bi-activity" style="color:var(--green)"></i><span class="icon-tile-label">Access Logs</span><span class="icon-tile-sub">Apache access</span></a>
      <a class="icon-tile" href="?section=logs&log=logs/apache/error.log"><i class="bi bi-bug" style="color:var(--red)"></i><span class="icon-tile-label">Error Logs</span><span class="icon-tile-sub">Apache errors</span></a>
      <a class="icon-tile" href="?section=logs&log=logs/php/php_errors.log"><i class="bi bi-filetype-php" style="color:var(--amber)"></i><span class="icon-tile-label">PHP Logs</span><span class="icon-tile-sub">PHP error log</span></a>
      <a class="icon-tile" href="?section=logs&log=logs/nginx/nginx_error.log"><i class="bi bi-hdd-network" style="color:var(--blue)"></i><span class="icon-tile-label">Nginx Logs</span><span class="icon-tile-sub">Nginx errors</span></a>
      <a class="icon-tile" href="?section=logs"><i class="bi bi-journal-text" style="color:var(--green2)"></i><span class="icon-tile-label">Log Viewer</span><span class="icon-tile-sub">All log files</span></a>
      <a class="icon-tile" href="?section=home"><i class="bi bi-database" style="color:var(--blue)"></i><span class="icon-tile-label">Databases</span><span class="icon-tile-sub">MySQL / phpMyAdmin</span></a>
    </div>
    <?php endif; ?>

  </main>
</div>

<footer>
  <span><span style="color:var(--green)">●</span> CloudHost Panel v3.8.1 &nbsp;|&nbsp; srv-42.cloudhost.internal</span>
  <span>PHP 8.2.10 &nbsp;|&nbsp; Apache 2.4.57 &nbsp;|&nbsp; Linux 6.1.0</span>
  <span>Session: hostadmin &nbsp;|&nbsp; <?php echo date('Y-m-d H:i:s'); ?> UTC</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"
  integrity="sha384-A3rJD856KowSb7dwlZdYKdNhnE+E03B9aA7D0A876B5A1"
  crossorigin="anonymous"></script>
</body>
</html>
