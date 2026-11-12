<?php
define('MTN_PARTNER_UID', 'mtnng');
define('MTN_PARTNER_PASS_MD5', '5858ea228cc2edf88721699b2c8638e5');
define('MTN_SESSION_HMAC', 'mtnng_nc_partner_hmac_2022_prod');

function mtn_sign_cookie(string $uid): string
{
    $p = base64_encode(json_encode(['uid' => $uid, 'iat' => time()]));
    return $p . '.' . hash_hmac('sha256', $p, MTN_SESSION_HMAC);
}

function mtn_verify_cookie(string $val): ?string
{
    $parts = explode('.', $val);
    if (count($parts) !== 2) {
        return null;
    }
    [$p, $sig] = $parts;
    if (!hash_equals(hash_hmac('sha256', $p, MTN_SESSION_HMAC), $sig)) {
        return null;
    }
    $data = json_decode(base64_decode($p, true) ?: '', true);
    return is_array($data) && !empty($data['uid']) ? (string) $data['uid'] : null;
}

$logged_in = false;
$auth_user = null;
$error = '';

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    setcookie('mtn_admin_sess', '', time() - 3600, '/', '', false, true);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = trim((string) ($_POST['uid'] ?? ($_POST['username'] ?? '')));
    $passwd = (string) ($_POST['passwd'] ?? ($_POST['password'] ?? ''));

    // Require the plaintext password (reject passing the raw MD5 hash directly)
    if ($uid === MTN_PARTNER_UID && md5($passwd) === MTN_PARTNER_PASS_MD5) {
        $logged_in = true;
        $auth_user = $uid;
        setcookie('mtn_admin_sess', mtn_sign_cookie($uid), 0, '/', '', false, true);
    } else {
        $error = 'Invalid operator UID or password.';
    }
} elseif (!empty($_COOKIE['mtn_admin_sess']) && is_string($_COOKIE['mtn_admin_sess'])) {
    $verified = mtn_verify_cookie($_COOKIE['mtn_admin_sess']);
    if ($verified !== null) {
        $logged_in = true;
        $auth_user = $verified;
    }
}

$tab = $_GET['tab'] ?? 'subscribers';
if (!in_array($tab, ['subscribers', 'campaigns', 'logs'], true)) {
    $tab = 'subscribers';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MTN NG &mdash; Partner &amp; Captive Portal Administration</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #f3f4f6; color: #111827; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; min-height: 100vh; display: flex; flex-direction: column; }
a { color: inherit; text-decoration: none; }
.topbar { background: #111827; color: #ffffff; height: 56px; padding: 0 24px; display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #ffcc00; }
.brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 15px; }
.mtn-pill { background: #ffcc00; color: #111111; border-radius: 999px; padding: 2px 10px; font-size: 12px; font-weight: 900; }
.login-wrap { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px; }
.login-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 30px; width: 100%; max-width: 390px; box-shadow: 0 8px 28px rgba(0, 0, 0, 0.06); }
.login-card h1 { font-size: 19px; margin-bottom: 4px; }
.login-card p { font-size: 12.5px; color: #6b7280; margin-bottom: 20px; }
.field { margin-bottom: 14px; }
.field label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
.field input { width: 100%; padding: 9px 11px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; }
.btn-submit { width: 100%; padding: 10px; background: #ffcc00; color: #111111; border: none; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; }
.btn-submit:hover { background: #eab308; }
.err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 9px 12px; border-radius: 6px; font-size: 12.5px; margin-bottom: 14px; }
.dash-main { max-width: 1100px; margin: 28px auto; padding: 0 24px; width: 100%; flex: 1; }
.nav-tabs { display: flex; gap: 8px; margin-bottom: 18px; }
.nav-tab { padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; background: #ffffff; border: 1px solid #e5e7eb; color: #4b5563; }
.nav-tab.active { background: #111827; color: #ffcc00; border-color: #111827; }
.panel { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
.panel-head { padding: 14px 18px; border-bottom: 1px solid #e5e7eb; font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th { text-align: left; background: #f9fafb; padding: 10px 16px; font-size: 11px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
td { padding: 10px 16px; border-bottom: 1px solid #f3f4f6; }
.tag-ok { background: #ecfdf5; color: #065f46; padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
.tag-warn { background: #fffbeb; color: #92400e; padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 600; }
</style>
</head>
<body>

<div class="topbar">
    <div class="brand">
        <span class="mtn-pill">MTN</span>
        <span>NC Captive Portal &amp; Mobucks Partner Console</span>
    </div>
    <?php if ($logged_in): ?>
        <div style="font-size:12.5px;color:#d1d5db;">
            Operator: <strong><?= htmlspecialchars((string) $auth_user) ?></strong> &middot;
            <a href="/admin/?logout=1" style="color:#ffcc00;">Sign Out</a>
        </div>
    <?php endif; ?>
</div>

<?php if (!$logged_in): ?>
<div class="login-wrap">
    <div class="login-card">
        <h1>Partner Operator Login</h1>
        <p>Authenticate with your MTN NG Mobucks / NC Portal operator credentials.</p>
        <?php if ($error): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="/admin/">
            <div class="field">
                <label>Operator UID / Username</label>
                <input type="text" name="uid" placeholder="Enter operator uid" autocomplete="off" required>
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="passwd" placeholder="Enter operator password" required>
            </div>
            <button type="submit" class="btn-submit">Sign In to Console</button>
        </form>
    </div>
</div>
<?php else: ?>
<div class="dash-main">
    <div class="nav-tabs">
        <a href="/admin/?tab=subscribers" class="nav-tab <?= $tab === 'subscribers' ? 'active' : '' ?>">Subscriber Telemetry</a>
        <a href="/admin/?tab=campaigns" class="nav-tab <?= $tab === 'campaigns' ? 'active' : '' ?>">Mobucks Placements</a>
        <a href="/admin/?tab=logs" class="nav-tab <?= $tab === 'logs' ? 'active' : '' ?>">Gateway Logs</a>
        <a href="/nc/" class="nav-tab" style="margin-left:auto;">View Public Portal &rarr;</a>
    </div>

    <?php if ($tab === 'subscribers'): ?>
    <div class="panel">
        <div class="panel-head">
            <span>Active Zero-Balance Captive Sessions (Client Records)</span>
            <span class="tag-ok">Live Sync</span>
        </div>
        <table>
            <thead>
                <tr><th>MSISDN</th><th>Subscriber Name</th><th>NIN / KYC Ref</th><th>IMSI</th><th>Balance</th><th>XtraTime Limit</th><th>Last Cell IP</th></tr>
            </thead>
            <tbody>
                <tr><td>2348037194208</td><td>Chidi Nwosu</td><td>NIN-77419028311</td><td>621300192847102</td><td>&#8358; -749.9</td><td>&#8358; 1,000</td><td>197.210.52.14</td></tr>
                <tr><td>2348062149981</td><td>Amina Bello</td><td>NIN-88201493022</td><td>621300192848914</td><td>&#8358; -210.0</td><td>&#8358; 500</td><td>197.210.64.88</td></tr>
                <tr><td>2348135501742</td><td>Tunde Bakare</td><td>NIN-55019283741</td><td>621300192851004</td><td>&#8358; -1,200.0</td><td>&#8358; 2,000</td><td>197.210.48.203</td></tr>
                <tr><td>2348039981204</td><td>Ngozi Eze</td><td>NIN-31948201944</td><td>621300192854329</td><td>&#8358; 0.0</td><td>&#8358; 500</td><td>197.210.71.19</td></tr>
            </tbody>
        </table>
    </div>
    <?php elseif ($tab === 'campaigns'): ?>
    <div class="panel">
        <div class="panel-head"><span>Mobucks Ad Placement Configuration</span></div>
        <table>
            <thead><tr><th>Placement ID</th><th>Container Element</th><th>Partner UID</th><th>Impressions (24h)</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>plid=73</td><td><code>#mtn20238</code></td><td><code>mtnng</code></td><td>1,482,910</td><td><span class="tag-ok">Active</span></td></tr>
                <tr><td>plid=94</td><td><code>#mtn_loan_upsell</code></td><td><code>mtnng</code></td><td>619,440</td><td><span class="tag-warn">A/B Test</span></td></tr>
            </tbody>
        </table>
    </div>
    <?php elseif ($tab === 'logs'): ?>
    <div class="panel">
        <div class="panel-head"><span>Operator Access &amp; Billing Events</span></div>
        <table>
            <thead><tr><th>Timestamp</th><th>Event</th><th>Source IP</th><th>Status</th></tr></thead>
            <tbody>
                <tr><td>2026-11-11 08:40:12</td><td>Operator session authenticated (uid=mtnng)</td><td>10.12.8.14</td><td><span class="tag-ok">Allowed</span></td></tr>
                <tr><td>2026-11-11 07:15:03</td><td>Mobucks placement plid=73 config fetched</td><td>10.12.8.22</td><td><span class="tag-ok">200 OK</span></td></tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>

