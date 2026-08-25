<?php
// Lab 1201 — HTML Injection in LinkedIn Premium Support Chat
// Based on HackerOne Report #3079966 (LinkedIn — Severity: Low 3.1, Disclosed May 7 2025)
// Vulnerability: User messages stored raw, rendered via PHP echo without htmlspecialchars in agent view
// Attack: Inject <a href="evil.com">CLICK</a> → support agent sees real clickable phishing link

session_start();

$db_name = 'KrazePlanet';
$pdo = null;

$db_candidates = [
    ['host' => 'krazeplanet', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => 'localhost', 'port' => 3306],
    ['socket' => '/var/run/mysqld/mysqld.sock']
];

foreach ($db_candidates as $cand) {
    try {
        if (isset($cand['socket']) && file_exists($cand['socket'])) {
            $dsn = "mysql:unix_socket={$cand['socket']};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$cand['host']};port={$cand['port']};charset=utf8mb4";
        }
        $pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 2
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo->exec("USE `{$db_name}`;");
        break;
    } catch (Exception $e) {
        $pdo = null;
    }
}

if (!$pdo) {
    die('DB connection failed');
}

// ── Table ─────────────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS lab1201_messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) DEFAULT 'Anonymous',
    message     TEXT         DEFAULT '',
    ip          VARCHAR(64)  DEFAULT '',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// Seed demo safe messages
$sc = $pdo->query("SELECT COUNT(*) FROM lab1201_messages")->fetchColumn();
if ($sc == 0) {
    $ins = $pdo->prepare("INSERT INTO lab1201_messages (username, message, ip) VALUES (?,?,?)");
    $ins->execute(['LinkedIn Member', 'Hi, I need help with my Premium subscription. It was charged but I cannot access the Premium features.', '192.168.1.10']);
    $ins->execute(['Priya S.', 'Hello! I upgraded to Premium yesterday but the Career Insights feature is not showing. Can you help?', '10.0.0.42']);
}

// ── JSON API ──────────────────────────────────────────────────────────────────
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'send_message') {
        $b        = json_decode(file_get_contents('php://input'), true);
        $username = $b['username'] ?? 'Anonymous'; // stored raw
        $message  = $b['message']  ?? '';          // ⚠ NOT sanitised
        if (trim($message) === '') {
            echo json_encode(['ok' => false, 'message' => 'Message cannot be empty.']);
            exit;
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $pdo->prepare("INSERT INTO lab1201_messages (username, message, ip) VALUES (?,?,?)")
            ->execute([$username, $message, $ip]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;
    }

    if ($action === 'get_messages') {
        $rows = $pdo->query("SELECT id, username, message, created_at FROM lab1201_messages ORDER BY id ASC LIMIT 50")->fetchAll();
        echo json_encode(['ok' => true, 'messages' => $rows]);
        exit;
    }

    if ($action === 'clear_messages') {
        $pdo->exec("DELETE FROM lab1201_messages");
        echo json_encode(['ok' => true]);
        exit;
    }

    echo json_encode(['ok' => false, 'message' => 'Unknown action']);
    exit;
}

// ── View ──────────────────────────────────────────────────────────────────────
$view = $_GET['view'] ?? 'chat';

// Fetch messages for agent view (PHP-rendered — vulnerable)
$agentMessages = [];
if ($view === 'agent') {
    $agentMessages = $pdo->query("SELECT * FROM lab1201_messages ORDER BY id ASC")->fetchAll();
}

function s($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
function ago($d) {
    $sec = time() - strtotime($d);
    if ($sec < 60)   return $sec . 's ago';
    if ($sec < 3600) return floor($sec/60) . 'm ago';
    return floor($sec/3600) . 'h ago';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $view === 'agent' ? 'Support Agent Inbox — LinkedIn' : 'LinkedIn — Feed'; ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;font-size:14px;background:#f3f2ef;color:#191919;}
a{text-decoration:none;color:inherit;}

/* ── LinkedIn Navbar ─────────────────────────────────────────────────────── */
.li-nav{background:#fff;border-bottom:1px solid #e0e0e0;position:sticky;z-index:900;box-shadow:0 1px 3px rgba(0,0,0,.08);}
.li-nav-inner{max-width:1128px;margin:0 auto;padding:0 16px;display:flex;align-items:center;height:52px;gap:0;}
.li-logo{display:flex;align-items:center;margin-right:8px;flex-shrink:0;}
.li-logo svg{width:34px;height:34px;fill:#0077b5;}
.li-search{display:flex;align-items:center;background:#eef3f8;border-radius:4px;padding:0 8px;height:34px;gap:6px;width:220px;flex-shrink:0;}
.li-search svg{width:16px;height:16px;fill:#0077b5;flex-shrink:0;}
.li-search input{background:transparent;border:none;outline:none;font-size:.82rem;color:#191919;width:100%;}
.li-search input::placeholder{color:#666;}
.li-nav-items{display:flex;align-items:center;margin-left:auto;gap:0;}
.li-nav-item{display:flex;flex-direction:column;align-items:center;padding:0 12px;height:52px;justify-content:center;color:#666;font-size:.65rem;cursor:pointer;border-bottom:2px solid transparent;transition:color .15s;white-space:nowrap;}
.li-nav-item:hover{color:#191919;}
.li-nav-item.active{color:#191919;border-bottom-color:#191919;}
.li-nav-item svg{width:22px;height:22px;margin-bottom:2px;}
.li-nav-divider{width:1px;height:32px;background:#e0e0e0;margin:0 4px;}
.li-nav-btn{border:1px solid #0077b5;color:#0077b5;border-radius:16px;padding:5px 14px;font-size:.82rem;font-weight:600;cursor:pointer;background:transparent;transition:background .15s;white-space:nowrap;}
.li-nav-btn:hover{background:#eef3f8;}
.li-nav-btn.solid{background:#0077b5;color:#fff;margin-left:8px;}
.li-nav-btn.solid:hover{background:#006097;}

/* ── Feed Layout ─────────────────────────────────────────────────────────── */
.li-feed-wrap{max-width:1128px;margin:20px auto;padding:0 16px;display:grid;grid-template-columns:225px 1fr 300px;gap:20px;align-items:start;}
@media(max-width:900px){.li-feed-wrap{grid-template-columns:1fr;}.li-sidebar-right,.li-sidebar-left{display:none;}}

/* ── Left Sidebar ────────────────────────────────────────────────────────── */
.li-card{background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;margin-bottom:8px;}
.li-profile-banner{height:56px;background:linear-gradient(135deg,#0077b5 0%,#00a0dc 100%);}
.li-profile-body{padding:0 12px 12px;text-align:center;}
.li-profile-avatar{width:56px;height:56px;border-radius:50%;background:#0077b5;border:2px solid #fff;margin:-28px auto 8px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:700;color:#fff;}
.li-profile-name{font-weight:700;font-size:.9rem;color:#191919;}
.li-profile-sub{font-size:.75rem;color:#666;margin-top:2px;}
.li-profile-stats{border-top:1px solid #e0e0e0;margin-top:10px;padding-top:10px;}
.li-profile-stat{display:flex;justify-content:space-between;padding:3px 0;font-size:.75rem;}
.li-profile-stat span:first-child{color:#666;}
.li-profile-stat span:last-child{color:#0077b5;font-weight:700;}
.li-premium-card{padding:14px;border-top:1px solid #e0e0e0;}
.li-premium-title{font-size:.78rem;font-weight:600;color:#191919;margin-bottom:4px;}
.li-premium-sub{font-size:.72rem;color:#666;margin-bottom:10px;line-height:1.4;}
.li-premium-btn{display:block;text-align:center;border:1px solid #b88a3c;color:#b88a3c;border-radius:16px;padding:6px 14px;font-size:.78rem;font-weight:600;cursor:pointer;transition:background .15s;}
.li-premium-btn:hover{background:#fdf7ec;}
.li-premium-try{font-size:.72rem;color:#b88a3c;font-weight:600;text-align:center;margin-top:6px;}

/* ── Feed Center ─────────────────────────────────────────────────────────── */
.li-create-post{padding:12px;display:flex;align-items:center;gap:10px;}
.li-create-avatar{width:48px;height:48px;border-radius:50%;background:#0077b5;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;flex-shrink:0;}
.li-create-input{flex:1;border:1px solid #ccc;border-radius:24px;padding:10px 16px;font-size:.85rem;color:#666;cursor:pointer;background:#fff;}
.li-create-input:hover{background:#f0f0f0;}
.li-create-actions{display:flex;gap:0;border-top:1px solid #e0e0e0;padding:4px 12px;}
.li-create-action{display:flex;align-items:center;gap:5px;padding:8px 12px;border-radius:4px;cursor:pointer;color:#666;font-size:.78rem;font-weight:600;}
.li-create-action:hover{background:#f0f0f0;}
.li-create-action svg{width:20px;height:20px;}
.li-post{border-top:1px solid #e0e0e0;}
.li-post-header{display:flex;align-items:flex-start;gap:10px;padding:12px 12px 0;}
.li-post-avatar{width:48px;height:48px;border-radius:50%;background:#e0e0e0;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;flex-shrink:0;}
.li-post-meta{flex:1;}
.li-post-name{font-weight:700;font-size:.88rem;color:#191919;}
.li-post-title{font-size:.75rem;color:#666;}
.li-post-time{font-size:.72rem;color:#999;display:flex;align-items:center;gap:3px;}
.li-post-body{padding:10px 12px;font-size:.85rem;color:#191919;line-height:1.5;}
.li-post-img{width:100%;max-height:280px;object-fit:cover;background:#e0e0e0;display:flex;align-items:center;justify-content:center;color:#999;font-size:.85rem;}
.li-post-actions{display:flex;padding:4px 12px;border-top:1px solid #e0e0e0;}
.li-post-action{display:flex;align-items:center;gap:5px;padding:8px 12px;border-radius:4px;cursor:pointer;color:#666;font-size:.78rem;font-weight:600;flex:1;justify-content:center;}
.li-post-action:hover{background:#f0f0f0;}
.li-post-action svg{width:18px;height:18px;}

/* ── Right Sidebar ───────────────────────────────────────────────────────── */
.li-news-card{padding:14px;}
.li-news-title{font-size:.82rem;font-weight:700;color:#191919;margin-bottom:10px;}
.li-news-item{display:flex;gap:8px;padding:5px 0;cursor:pointer;}
.li-news-item:hover .li-news-item-title{text-decoration:underline;}
.li-news-dot{width:6px;height:6px;border-radius:50%;background:#191919;margin-top:5px;flex-shrink:0;}
.li-news-item-title{font-size:.78rem;font-weight:600;color:#191919;line-height:1.3;}
.li-news-item-meta{font-size:.7rem;color:#666;margin-top:1px;}

/* ── Floating Support Chat Widget ────────────────────────────────────────── */
.chat-fab{position:fixed;bottom:24px;right:24px;z-index:1000;display:flex;flex-direction:column;align-items:flex-end;gap:12px;}
.chat-fab-btn{width:56px;height:56px;border-radius:50%;background:#0077b5;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,119,181,.4);transition:background .2s,transform .2s;}
.chat-fab-btn:hover{background:#006097;transform:scale(1.05);}
.chat-fab-btn svg{width:28px;height:28px;fill:#fff;}
.chat-window{background:#fff;border-radius:8px;box-shadow:0 4px 24px rgba(0,0,0,.15);width:340px;display:none;flex-direction:column;overflow:hidden;border:1px solid #e0e0e0;max-height:480px;}
.chat-window.open{display:flex;}
.chat-win-header{background:#0077b5;padding:14px 16px;display:flex;align-items:center;gap:10px;}
.chat-win-logo{width:32px;height:32px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;}
.chat-win-logo svg{width:20px;height:20px;fill:#0077b5;}
.chat-win-title{flex:1;color:#fff;}
.chat-win-title strong{font-size:.88rem;display:block;}
.chat-win-title span{font-size:.72rem;opacity:.85;}
.chat-win-close{background:transparent;border:none;cursor:pointer;color:#fff;font-size:1.1rem;padding:0 4px;line-height:1;}
.chat-messages{flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:10px;min-height:200px;max-height:280px;}
.chat-msg{display:flex;gap:8px;align-items:flex-start;}
.chat-msg.mine{flex-direction:row-reverse;}
.chat-msg-avatar{width:32px;height:32px;border-radius:50%;background:#0077b5;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0;}
.chat-msg.mine .chat-msg-avatar{background:#777;}
.chat-msg-bubble{max-width:220px;border-radius:8px;padding:8px 10px;font-size:.82rem;line-height:1.4;}
.chat-msg .chat-msg-bubble{background:#f3f2ef;color:#191919;}
.chat-msg.mine .chat-msg-bubble{background:#0077b5;color:#fff;}
.chat-msg-time{font-size:.68rem;color:#999;margin-top:2px;}
.chat-msg.mine .chat-msg-time{text-align:right;}
.chat-typing{font-size:.75rem;color:#666;padding:0 4px;font-style:italic;}
.chat-input-area{padding:10px 12px;border-top:1px solid #e0e0e0;display:flex;flex-direction:column;gap:8px;}
.chat-input-row{display:flex;gap:8px;align-items:flex-end;}
.chat-input{flex:1;border:1px solid #ccc;border-radius:20px;padding:8px 14px;font-size:.82rem;outline:none;resize:none;font-family:inherit;max-height:80px;min-height:36px;}
.chat-input:focus{border-color:#0077b5;}
.chat-send-btn{background:#0077b5;border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;transition:background .2s;}
.chat-send-btn:hover{background:#006097;}
.chat-send-btn svg{width:18px;height:18px;fill:#fff;}
.chat-name-row{display:flex;gap:6px;align-items:center;}
.chat-name-label{font-size:.72rem;color:#666;flex-shrink:0;}
.chat-name-input{border:1px solid #e0e0e0;border-radius:4px;padding:4px 8px;font-size:.78rem;font-family:inherit;outline:none;flex:1;}
.chat-name-input:focus{border-color:#0077b5;}
.chat-payload-hint{background:#fef3cd;border:1px solid #f59e0b;border-radius:4px;padding:8px 10px;font-size:.72rem;color:#92400e;}
.chat-payload-hint strong{display:block;margin-bottom:4px;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;}
.chat-payload-code{background:#fff;border:1px solid #f0d080;border-radius:3px;padding:3px 6px;font-family:monospace;font-size:.7rem;cursor:pointer;display:block;margin-top:4px;word-break:break-all;}
.chat-payload-code:hover{background:#fef9e7;}
.chat-msg-err{font-size:.75rem;color:#dc2626;text-align:center;}

/* ── Agent View ──────────────────────────────────────────────────────────── */
body.agent-page{background:#f8fafc;}
.ag-wrap{max-width:900px;margin:0 auto;padding:24px 16px;}
.ag-header{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px 24px;margin-bottom:20px;display:flex;align-items:center;gap:16px;}
.ag-header-icon{width:48px;height:48px;border-radius:8px;background:#0077b5;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.ag-header-icon svg{width:28px;height:28px;fill:#fff;}
.ag-header-info h2{font-size:1.1rem;font-weight:700;color:#191919;}
.ag-header-info p{font-size:.8rem;color:#666;margin-top:3px;}
.ag-header-count{margin-left:auto;background:#0077b5;color:#fff;border-radius:16px;padding:4px 14px;font-size:.78rem;font-weight:700;}
.ag-vuln-box{background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;padding:12px 16px;margin-bottom:20px;font-size:.82rem;color:#991b1b;line-height:1.5;}
.ag-vuln-box strong{display:block;margin-bottom:4px;}
.ag-vuln-box code{background:#fee2e2;border-radius:3px;padding:1px 4px;font-family:monospace;}
.ag-msg-list{display:flex;flex-direction:column;gap:12px;}
.ag-msg{background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;}
.ag-msg-header{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid #f0f0f0;background:#fafafa;}
.ag-msg-avatar{width:36px;height:36px;border-radius:50%;background:#0077b5;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;flex-shrink:0;}
.ag-msg-meta{flex:1;}
.ag-msg-name{font-weight:700;font-size:.85rem;color:#191919;}
.ag-msg-time{font-size:.72rem;color:#999;}
.ag-msg-ip{margin-left:auto;font-size:.72rem;color:#999;}
.ag-msg-body{padding:14px 16px;font-size:.88rem;line-height:1.6;color:#191919;}
.ag-msg-vuln-tag{font-size:.65rem;background:#fef2f2;color:#dc2626;border:1px solid #fca5a5;border-radius:3px;padding:1px 5px;margin-left:6px;font-weight:700;letter-spacing:.4px;}
.ag-empty{text-align:center;padding:48px;color:#999;font-size:.88rem;}
.ag-actions{display:flex;gap:10px;margin-bottom:16px;justify-content:flex-end;}
.ag-btn{border-radius:6px;padding:8px 16px;font-size:.82rem;font-weight:600;cursor:pointer;font-family:inherit;border:none;transition:background .2s;}
.ag-btn-danger{background:#dc2626;color:#fff;}
.ag-btn-danger:hover{background:#b91c1c;}
.ag-toast{position:fixed;bottom:24px;right:24px;background:#191919;color:#fff;padding:10px 18px;border-radius:6px;font-size:.82rem;opacity:0;transition:opacity .3s;z-index:9999;}
.ag-toast.show{opacity:1;}
</style>
</head>
<body<?php echo $view==='agent'?' class="agent-page"':''; ?>>

<?php if ($view === 'chat'): ?>
<!-- ════════════════════════════════════════════════════════════════════════
     LINKEDIN FEED REPLICA — User / Attacker view
     User types in the support chat widget → message stored raw in DB
     Agent side renders it without escaping → HTML injection
════════════════════════════════════════════════════════════════════════ -->

<!-- LinkedIn Navbar -->
<nav class="li-nav">
  <div class="li-nav-inner">
    <!-- Logo -->
    <div class="li-logo">
      <svg viewBox="0 0 34 34" xmlns="http://www.w3.org/2000/svg">
        <path d="M34 2.5v29A2.5 2.5 0 0131.5 34h-29A2.5 2.5 0 010 31.5v-29A2.5 2.5 0 012.5 0h29A2.5 2.5 0 0134 2.5zM10 13H5v16h5V13zm.45-4.5a2.95 2.95 0 10-5.9 0 2.95 2.95 0 005.9 0zM29 19.28c0-4.81-3.06-6.68-6.1-6.68A5.7 5.7 0 0018.1 15h-.1v-2H14v16h5v-8.51a3.32 3.32 0 013-3.58c1.86 0 2.57 1.42 2.57 3.51V29h5v-9.72z"/>
      </svg>
    </div>
    <!-- Search -->
    <div class="li-search">
      <svg viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" stroke="#0077b5" stroke-width="2" fill="none" stroke-linecap="round"/></svg>
      <input type="text" placeholder="Search">
    </div>
    <!-- Nav items -->
    <div class="li-nav-items">
      <div class="li-nav-item active">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
        Home
      </div>
      <div class="li-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
        Network
      </div>
      <div class="li-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
        Jobs
      </div>
      <div class="li-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Messaging
      </div>
      <div class="li-nav-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
        Notifications
      </div>
      <div class="li-nav-divider"></div>
      <button class="li-nav-btn">Log In</button>
      <button class="li-nav-btn solid">Join now</button>
    </div>
  </div>
</nav>

<!-- Feed -->
<div class="li-feed-wrap">

  <!-- Left Sidebar -->
  <aside class="li-sidebar-left">
    <div class="li-card">
      <div class="li-profile-banner"></div>
      <div class="li-profile-body">
        <div class="li-profile-avatar">Y</div>
        <div class="li-profile-name">You</div>
        <div class="li-profile-sub">Security Researcher</div>
        <div class="li-profile-stats">
          <div class="li-profile-stat"><span>Profile views</span><span>142</span></div>
          <div class="li-profile-stat"><span>Post impressions</span><span>1,893</span></div>
        </div>
      </div>
      <div class="li-premium-card">
        <div class="li-premium-title">🌟 Job search smarter with Premium</div>
        <div class="li-premium-sub">Get AI-powered insights and be a top applicant</div>
        <a href="#" class="li-premium-btn" onclick="openChat();return false;">Try for ₹0</a>
        <div class="li-premium-try">1 month free, then ₹2,799/month</div>
      </div>
    </div>
  </aside>

  <!-- Center Feed -->
  <main>
    <!-- Create post box -->
    <div class="li-card" style="margin-bottom:8px;">
      <div class="li-create-post">
        <div class="li-create-avatar">Y</div>
        <div class="li-create-input">Start a post, try a photo</div>
      </div>
      <div class="li-create-actions">
        <div class="li-create-action" style="color:#378fe9;">
          <svg viewBox="0 0 24 24" fill="currentColor"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          Photo
        </div>
        <div class="li-create-action" style="color:#5f9b41;">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21 7.5V6a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-1.5"/><path d="M8 12h8M8 16h5"/><rect x="16" y="2" width="6" height="6" rx="1"/></svg>
          Write article
        </div>
        <div class="li-create-action" style="color:#e06847;">
          <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
          Video
        </div>
      </div>
    </div>

    <!-- Post 1 -->
    <div class="li-card" style="margin-bottom:8px;">
      <div class="li-post-header">
        <div class="li-post-avatar" style="background:#4f46e5;">S</div>
        <div class="li-post-meta">
          <div class="li-post-name">Sarah Mitchell <span style="font-size:.72rem;color:#0077b5;font-weight:400;">• 1st</span></div>
          <div class="li-post-title">Senior Security Engineer at Google</div>
          <div class="li-post-time">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            3h • <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
          </div>
        </div>
      </div>
      <div class="li-post-body">
        🔐 Just submitted my 50th HackerOne report! HTML Injection in support chat systems is still wildly underreported. The risk of phishing support staff via injected <code>&lt;a href&gt;</code> links is real — especially in internal tooling. Always sanitize before rendering!<br><br>
        #BugBounty #WebSecurity #HTMLInjection #HackerOne
      </div>
      <div style="padding:0 12px 8px;font-size:.75rem;color:#666;">👍 342 reactions · 28 comments</div>
      <div class="li-post-actions">
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3H14z"/><path d="M7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>Like</div>
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>Comment</div>
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>Share</div>
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send</div>
      </div>
    </div>

    <!-- Post 2 -->
    <div class="li-card" style="margin-bottom:8px;">
      <div class="li-post-header">
        <div class="li-post-avatar" style="background:#0077b5;">A</div>
        <div class="li-post-meta">
          <div class="li-post-name">Aryan Gupta <span style="font-size:.72rem;color:#0077b5;font-weight:400;">• 2nd</span></div>
          <div class="li-post-title">Penetration Tester · OSCP · Bug Bounty Hunter</div>
          <div class="li-post-time">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            6h
          </div>
        </div>
      </div>
      <div class="li-post-body">
        💰 Got my first bounty from LinkedIn! Found HTML injection in their Premium support chat — the message field renders raw HTML for the support agent. Simple <code>&lt;a href="evil.com"&gt;CLICK&lt;/a&gt;</code> appears as a real link in the agent's inbox. Reported via HackerOne, triaged in under 24h. 🙌<br><br>
        Low severity but it's a start! Next goal: escalate to XSS.<br><br>
        #BugBounty #LinkedIn #HackerOne
      </div>
      <div style="padding:0 12px 8px;font-size:.75rem;color:#666;">👍 127 reactions · 15 comments</div>
      <div class="li-post-actions">
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 00-3-3l-4 9v11h11.28a2 2 0 002-1.7l1.38-9a2 2 0 00-2-2.3H14z"/><path d="M7 22H4a2 2 0 01-2-2v-7a2 2 0 012-2h3"/></svg>Like</div>
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>Comment</div>
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>Share</div>
        <div class="li-post-action"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>Send</div>
      </div>
    </div>

    <!-- Lab education box -->
    <div class="li-card" style="margin-bottom:8px;border-left:4px solid #0077b5;">
      <div style="padding:16px;">
        <div style="font-weight:700;color:#0077b5;margin-bottom:8px;font-size:.92rem;">🎯 Lab Objective — HTML Injection</div>
        <div style="font-size:.82rem;color:#444;line-height:1.6;">
          The <strong>support chat widget</strong> (bottom-right) stores your message raw in the database.<br>
          The <strong>support agent</strong> views messages in their inbox where PHP renders them with <code>echo $msg</code> — no <code>htmlspecialchars()</code>.<br><br>
          <strong>Your goal:</strong> inject an HTML payload that renders as real clickable content for the agent.
        </div>
        <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
          <a href="index.php?view=agent" style="background:#0077b5;color:#fff;padding:6px 14px;border-radius:16px;font-size:.78rem;font-weight:600;">View Agent Inbox</a>
          <a href="https://hackerone.com/reports/3079966" target="_blank" style="border:1px solid #0077b5;color:#0077b5;padding:6px 14px;border-radius:16px;font-size:.78rem;font-weight:600;">HackerOne #3079966</a>
        </div>
      </div>
    </div>
  </main>

  <!-- Right Sidebar -->
  <aside class="li-sidebar-right">
    <div class="li-card">
      <div class="li-news-card">
        <div class="li-news-title">LinkedIn News</div>
        <div class="li-news-item"><div class="li-news-dot"></div><div><div class="li-news-item-title">Bug bounty payouts hit record high in 2025</div><div class="li-news-item-meta">4h ago · 1,204 readers</div></div></div>
        <div class="li-news-item"><div class="li-news-dot"></div><div><div class="li-news-item-title">HTML injection: the overlooked web vuln</div><div class="li-news-item-meta">8h ago · 842 readers</div></div></div>
        <div class="li-news-item"><div class="li-news-dot"></div><div><div class="li-news-item-title">LinkedIn patches support chat flaw</div><div class="li-news-item-meta">1d ago · 3,120 readers</div></div></div>
        <div class="li-news-item"><div class="li-news-dot"></div><div><div class="li-news-item-title">OWASP Top 10 update includes HTMLI</div><div class="li-news-item-meta">2d ago · 5,891 readers</div></div></div>
        <div class="li-news-item"><div class="li-news-dot"></div><div><div class="li-news-item-title">DOMPurify v3 released with CSP support</div><div class="li-news-item-meta">3d ago · 2,230 readers</div></div></div>
      </div>
    </div>
    <div class="li-card" style="margin-top:8px;">
      <div style="padding:14px;">
        <div style="font-size:.78rem;font-weight:700;color:#191919;margin-bottom:8px;">Suggested for you</div>
        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
          <div style="width:36px;height:36px;border-radius:50%;background:#e9b046;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:.85rem;">R</div>
          <div style="flex:1;font-size:.78rem;"><strong>Riya K.</strong><div style="color:#666;font-size:.72rem;">Security Analyst · 2nd</div></div>
          <button style="border:1px solid #0077b5;color:#0077b5;border-radius:16px;padding:4px 12px;font-size:.72rem;font-weight:600;background:transparent;cursor:pointer;">+ Follow</button>
        </div>
        <div style="display:flex;align-items:center;gap:8px;padding:6px 0;">
          <div style="width:36px;height:36px;border-radius:50%;background:#16a34a;display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:.85rem;">M</div>
          <div style="flex:1;font-size:.78rem;"><strong>Mohamed A.</strong><div style="color:#666;font-size:.72rem;">OSCP · Pentester · 3rd</div></div>
          <button style="border:1px solid #0077b5;color:#0077b5;border-radius:16px;padding:4px 12px;font-size:.72rem;font-weight:600;background:transparent;cursor:pointer;">+ Follow</button>
        </div>
      </div>
    </div>
  </aside>
</div>

<!-- ── Floating Support Chat Widget ──────────────────────────────────────── -->
<div class="chat-fab" id="chatFab">
  <!-- Chat window -->
  <div class="chat-window" id="chatWindow">
    <div class="chat-win-header">
      <div class="chat-win-logo">
        <svg viewBox="0 0 34 34"><path d="M34 2.5v29A2.5 2.5 0 0131.5 34h-29A2.5 2.5 0 010 31.5v-29A2.5 2.5 0 012.5 0h29A2.5 2.5 0 0134 2.5zM10 13H5v16h5V13zm.45-4.5a2.95 2.95 0 10-5.9 0 2.95 2.95 0 005.9 0zM29 19.28c0-4.81-3.06-6.68-6.1-6.68A5.7 5.7 0 0018.1 15h-.1v-2H14v16h5v-8.51a3.32 3.32 0 013-3.58c1.86 0 2.57 1.42 2.57 3.51V29h5v-9.72z"/></svg>
      </div>
      <div class="chat-win-title">
        <strong>LinkedIn Premium Support</strong>
        <span>🟢 Typically replies in 14 min</span>
      </div>
      <button class="chat-win-close" onclick="closeChat()">✕</button>
    </div>

    <div class="chat-messages" id="chatMessages">
      <div class="chat-typing" id="agentTyping" style="display:none;">Support agent is typing…</div>
    </div>

    <div class="chat-input-area">
      <!-- Name row -->
      <div class="chat-name-row">
        <span class="chat-name-label">Your name:</span>
        <input type="text" class="chat-name-input" id="chatName" value="LinkedIn Member" maxlength="60">
      </div>
      <!-- Message row -->
      <div class="chat-input-row">
        <textarea class="chat-input" id="chatInput" placeholder="Type a message…" rows="1"></textarea>
        <button class="chat-send-btn" onclick="sendMessage()">
          <svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
      </div>
      <div id="chatErr" class="chat-msg-err" style="display:none;"></div>
    </div>
  </div>

  <!-- FAB button -->
  <button class="chat-fab-btn" id="chatFabBtn" onclick="toggleChat()">
    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
  </button>
</div>

<?php elseif ($view === 'agent'): ?>
<!-- ════════════════════════════════════════════════════════════════════════
     SUPPORT AGENT INBOX — Vulnerable rendering
     Messages rendered via PHP echo without htmlspecialchars → HTML injection
════════════════════════════════════════════════════════════════════════ -->
<div class="ag-wrap">

  <!-- Header -->
  <div class="ag-header">
    <div class="ag-header-icon">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
    </div>
    <div class="ag-header-info">
      <h2>LinkedIn Premium — Support Inbox</h2>
      <p>Messages from users rendered here · Agent dashboard</p>
    </div>
    <div class="ag-header-count"><?php echo count($agentMessages); ?> message<?php echo count($agentMessages) !== 1 ? 's' : ''; ?></div>
  </div>

  <!-- Vulnerability warning -->
  <div class="ag-vuln-box">
    <strong>⚠ Vulnerable Rendering — HTML Injection (HackerOne #3079966)</strong>
    Each message below is rendered via <code>&lt;?php echo $row['message']; ?&gt;</code> — no <code>htmlspecialchars()</code> applied.<br>
    Any HTML tags in user messages render as real HTML here. Send a payload from the <a href="index.php" style="color:#991b1b;">chat widget</a>, then refresh this page to see it render.
  </div>

  <div class="ag-actions">
    <button class="ag-btn ag-btn-danger" onclick="clearMessages()">Clear All Messages</button>
  </div>

  <!-- Message list -->
  <div class="ag-msg-list">
    <?php if (empty($agentMessages)): ?>
      <div class="ag-empty">No messages yet. <a href="index.php" style="color:#0077b5;">Send one from the chat widget →</a></div>
    <?php else: ?>
      <?php foreach ($agentMessages as $row): ?>
      <div class="ag-msg">
        <div class="ag-msg-header">
          <div class="ag-msg-avatar"><?php echo strtoupper(substr($row['username'] ?? 'A', 0, 1)); ?></div>
          <div class="ag-msg-meta">
            <div class="ag-msg-name">
              <?php echo s($row['username']); ?>
              <?php if (strpos($row['message'], '<') !== false): ?>
              <span class="ag-msg-vuln-tag">⚠ HTML DETECTED</span>
              <?php endif; ?>
            </div>
            <div class="ag-msg-time"><?php echo s(ago($row['created_at'])); ?> · #<?php echo (int)$row['id']; ?></div>
          </div>
          <div class="ag-msg-ip">IP: <?php echo s($row['ip']); ?></div>
        </div>
        <!-- ⚠ VULNERABLE: raw echo without htmlspecialchars — HTML injection fires here -->
        <div class="ag-msg-body"><?php echo $row['message']; ?></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>
<div class="ag-toast" id="agToast"></div>

<?php endif; ?>

<script>
// ── Chat widget logic ─────────────────────────────────────────────────────────
<?php if ($view === 'chat'): ?>

let chatOpen = false;
const AGENT_REPLIES = [
    "Hello! Thank you for reaching out to LinkedIn Premium Support. How can I help you today?",
    "I can see your message. Let me look into this for you right away.",
    "Could you please provide your account email so I can verify your subscription?",
    "Thank you! I've escalated this to our billing team. You'll receive an update within 24 hours.",
    "Is there anything else I can help you with today?"
];
let replyIndex = 0;

function toggleChat(){
    chatOpen = !chatOpen;
    document.getElementById('chatWindow').classList.toggle('open', chatOpen);
    if(chatOpen && document.getElementById('chatMessages').children.length <= 1){
        loadMessages();
    }
}
function openChat(){ chatOpen = false; toggleChat(); }
function closeChat(){ chatOpen = false; document.getElementById('chatWindow').classList.remove('open'); }

function safe(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function appendMessage(username, text, mine){
    const container = document.getElementById('chatMessages');
    const typing = document.getElementById('agentTyping');
    const div = document.createElement('div');
    div.className = 'chat-msg' + (mine?' mine':'');
    const initial = (username||'?')[0].toUpperCase();
    div.innerHTML = `
        <div class="chat-msg-avatar">${safe(initial)}</div>
        <div>
            <div class="chat-msg-bubble">${text}</div>
            <div class="chat-msg-time">${safe(username)} · just now</div>
        </div>`;
    container.insertBefore(div, typing);
    container.scrollTop = container.scrollHeight;
}

function appendAgentMessage(text){
    const container = document.getElementById('chatMessages');
    const typing = document.getElementById('agentTyping');
    document.getElementById('agentTyping').style.display = 'block';
    container.scrollTop = container.scrollHeight;
    setTimeout(()=>{
        document.getElementById('agentTyping').style.display = 'none';
        const div = document.createElement('div');
        div.className = 'chat-msg';
        div.innerHTML = `
            <div class="chat-msg-avatar" style="background:#0077b5;">L</div>
            <div>
                <div class="chat-msg-bubble">${safe(text)}</div>
                <div class="chat-msg-time">LinkedIn Support · just now</div>
            </div>`;
        container.insertBefore(div, typing);
        container.scrollTop = container.scrollHeight;
    }, 1400);
}

async function loadMessages(){
    try{
        const d = await (await fetch('?action=get_messages',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'})).json();
        if(d.ok && d.messages.length){
            const container = document.getElementById('chatMessages');
            // Show first agent greeting
            appendAgentMessage("Hello! Welcome to LinkedIn Premium Support. How can I assist you today?");
        }
    } catch(e){}
}

async function sendMessage(){
    const input = document.getElementById('chatInput');
    const nameInput = document.getElementById('chatName');
    const errEl = document.getElementById('chatErr');
    const msg = input.value.trim();
    const name = nameInput.value.trim() || 'LinkedIn Member';
    errEl.style.display = 'none';
    if(!msg){ errEl.textContent = 'Please type a message.'; errEl.style.display = 'block'; return; }

    // Show message on user side — HTML renders here too (mirrors real LinkedIn chat behaviour)
    appendMessage(name, msg, true);
    input.value = '';
    input.style.height = '';

    try{
        const d = await (await fetch('?action=send_message',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:name, message:msg})})).json();
        if(d.ok){
            // Simulated agent auto-reply
            if(replyIndex < AGENT_REPLIES.length){
                appendAgentMessage(AGENT_REPLIES[replyIndex++]);
            }
            // Hint after first message
            if(replyIndex === 2){
                setTimeout(()=>{
                    const hint = document.createElement('div');
                    hint.style.cssText = 'text-align:center;font-size:.72rem;color:#0077b5;padding:4px 8px;';
                    hint.innerHTML = '✓ Message saved. <a href="index.php?view=agent" target="_blank" style="color:#0077b5;text-decoration:underline;">Check Agent Inbox →</a>';
                    document.getElementById('chatMessages').appendChild(hint);
                }, 2000);
            }
        } else {
            errEl.textContent = d.message || 'Send failed.';
            errEl.style.display = 'block';
        }
    } catch(e){
        errEl.textContent = 'Network error.';
        errEl.style.display = 'block';
    }
}

function copyToInput(el){
    document.getElementById('chatInput').value = el.textContent;
    document.getElementById('chatInput').focus();
}

// Enter to send (Shift+Enter for newline)
document.getElementById('chatInput').addEventListener('keydown', e => {
    if(e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }
});
// Auto-resize textarea
document.getElementById('chatInput').addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 80) + 'px';
});

<?php endif; ?>

<?php if ($view === 'agent'): ?>
async function clearMessages(){
    if(!confirm('Clear all messages?')) return;
    const d = await (await fetch('?action=clear_messages',{method:'POST',headers:{'Content-Type':'application/json'},body:'{}'})).json();
    if(d.ok){
        const t = document.getElementById('agToast');
        t.textContent = 'Messages cleared.';
        t.classList.add('show');
        setTimeout(()=>{ t.classList.remove('show'); location.reload(); }, 1500);
    }
}
<?php endif; ?>
</script>
</body>
</html>
