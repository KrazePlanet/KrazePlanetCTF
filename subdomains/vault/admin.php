<?php
require_once __DIR__ . '/includes/auth.php';

$auth = vt_handle_panel_auth('admin');
$logged_in = $auth['authenticated'];
$auth_err = $auth['error'];
$currentUser = htmlspecialchars((string) ($auth['user'] ?? 'vt_admin'));

$tab = $_GET['tab'] ?? 'dashboard';
if (!in_array($tab, ['dashboard', 'accounts', 'keys', 'logs'], true)) {
    $tab = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administration Console &mdash; VaultTech</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { min-height: 100vh; background: #0a0c10; color: #e4e7eb; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; display: flex; flex-direction: column; }
a { color: inherit; text-decoration: none; }
.top-bar { background: #111318; border-bottom: 1px solid #272d3b; padding: 0 24px; height: 52px; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.logo { font-weight: 800; font-size: .92rem; color: #f0f4f8; display: flex; align-items: center; gap: 8px; }
.tbadge { font-size: .62rem; text-transform: uppercase; letter-spacing: .8px; border: 1px solid #78350f; padding: 2px 7px; border-radius: 3px; color: #f59e0b; background: #1c1007; }
.spacer { flex: 1; }
.env { font-size: .72rem; color: #6b7280; }
.main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px; }
.login-card { background: #111318; border: 1px solid #272d3b; border-radius: 10px; width: 100%; max-width: 400px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, .5); }
.accent { height: 3px; background: linear-gradient(90deg, #92400e, #f59e0b, #fcd34d); }
.card-body { padding: 32px 28px 28px; }
.card-body h1 { font-size: 1.15rem; font-weight: 800; color: #f0f4f8; margin-bottom: 4px; }
.sub { font-size: .78rem; color: #6b7280; margin-bottom: 22px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-size: .68rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 5px; }
.form-group input { width: 100%; padding: 10px 12px; border: 1px solid #272d3b; border-radius: 6px; background: #0a0c10; color: #f0f4f8; font-size: .88rem; outline: none; }
.form-group input:focus { border-color: #f59e0b; }
.btn-login { width: 100%; padding: 11px; background: #b45309; color: #fff; border: none; border-radius: 6px; font-size: .87rem; font-weight: 700; cursor: pointer; }
.btn-login:hover { background: #d97706; }
.error-alert { background: #1c0f0f; border: 1px solid #4a1c1c; border-left: 3px solid #ef4444; border-radius: 6px; padding: 10px 12px; margin-bottom: 16px; color: #fca5a5; font-size: .78rem; }
.dash-wrap { display: flex; flex: 1; min-height: 0; }
.sidebar { width: 210px; flex-shrink: 0; background: #0e1016; border-right: 1px solid #272d3b; padding: 12px 0; display: flex; flex-direction: column; }
.sidebar a { display: block; padding: 10px 18px; color: #9ca3af; font-size: .82rem; font-weight: 500; }
.sidebar a:hover, .sidebar a.active { background: #1a1e28; color: #f59e0b; }
.content { flex: 1; padding: 24px 30px; overflow-y: auto; }
.page-header { margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #272d3b; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.ph-badge { background: #1c2a0f; color: #86efac; font-size: .68rem; font-weight: 700; padding: 5px 10px; border-radius: 4px; border: 1px solid #166534; }
.data-section { background: #111318; border: 1px solid #272d3b; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
.ds-title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; padding: 12px 16px; border-bottom: 1px solid #272d3b; background: #0e1016; }
table.dt { width: 100%; border-collapse: collapse; font-size: .8rem; }
table.dt th { padding: 10px 16px; text-align: left; font-size: .66rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; background: #0e1016; border-bottom: 1px solid #272d3b; }
table.dt td { padding: 9px 16px; border-bottom: 1px solid #1a1e28; color: #d1d5db; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: .64rem; font-weight: 700; text-transform: uppercase; }
.badge-green { background: #064e3b; color: #6ee7b7; }
.badge-yellow { background: #3b2f0f; color: #fcd34d; }
.badge-red { background: #3b1c1c; color: #fca5a5; }
.badge-blue { background: #1e3a5f; color: #93c5fd; }
.footer-bar { background: #111318; border-top: 1px solid #272d3b; padding: 12px 24px; text-align: center; font-size: .7rem; color: #6b7280; }
</style>
</head>
<body>

<div class="top-bar">
    <div class="logo">VaultTech</div>
    <span class="tbadge">Administration</span>
    <div class="spacer"></div>
    <span class="env">admin.vaulttech.io</span>
</div>

<?php if (!$logged_in): ?>
<div class="main">
    <div class="login-card">
        <div class="accent"></div>
        <div class="card-body">
            <h1>Administration Console</h1>
            <div class="sub">Authorized personnel only. All login activity is logged and audited.</div>
            <?php if ($auth_err): ?>
                <div class="error-alert"><?= htmlspecialchars($auth_err) ?></div>
            <?php endif; ?>
            <form method="POST" action="/admin/">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter operator username" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter operator password" required>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="dash-wrap">
    <nav class="sidebar">
        <a href="/admin/?tab=dashboard" class="<?= $tab === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="/admin/?tab=accounts" class="<?= $tab === 'accounts' ? 'active' : '' ?>">User Accounts</a>
        <a href="/admin/?tab=keys" class="<?= $tab === 'keys' ? 'active' : '' ?>">API Keys</a>
        <a href="/admin/?tab=logs" class="<?= $tab === 'logs' ? 'active' : '' ?>">Audit Logs</a>
        <a href="/manage/" style="margin-top:16px;border-top:1px solid #272d3b;padding-top:14px;">Operations Center &rarr;</a>
        <a href="/admin/?logout=1" style="color:#fca5a5;">Sign Out</a>
    </nav>

    <div class="content">
        <div class="page-header">
            <div>
                <h2 style="font-size:1.15rem;">Administration Console</h2>
                <div style="font-size:.75rem;color:#9ca3af;">Authenticated operator session (Super Admin)</div>
            </div>
            <div class="ph-badge">Authenticated as <?= $currentUser ?></div>
        </div>

        <?php if ($tab === 'dashboard' || $tab === 'accounts'): ?>
        <div class="data-section">
            <div class="ds-title">Privileged Operator Accounts</div>
            <table class="dt">
                <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>MFA</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>vt_admin</td><td>admin@vaulttech.io</td><td><span class="badge badge-yellow">Super Admin</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-red">Disabled</span></td></tr>
                    <tr><td>2</td><td>ops_manager</td><td>ops@vaulttech.io</td><td><span class="badge badge-blue">Operator</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-green">Enabled</span></td></tr>
                    <tr><td>3</td><td>analyst_01</td><td>analyst@vaulttech.io</td><td><span class="badge badge-blue">Analyst</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-green">Enabled</span></td></tr>
                    <tr><td>4</td><td>support_head</td><td>support@vaulttech.io</td><td><span class="badge badge-blue">Support</span></td><td><span class="badge badge-yellow">Suspended</span></td><td><span class="badge badge-red">Disabled</span></td></tr>
                    <tr><td>5</td><td>finance_lead</td><td>finance@vaulttech.io</td><td><span class="badge badge-blue">Finance</span></td><td><span class="badge badge-green">Active</span></td><td><span class="badge badge-green">Enabled</span></td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard' || $tab === 'keys'): ?>
        <div class="data-section">
            <div class="ds-title">Signing &amp; Gateway Service Keys</div>
            <table class="dt">
                <thead><tr><th>Key Name</th><th>Key Prefix</th><th>Scope</th><th>Status</th><th>Rotated</th></tr></thead>
                <tbody>
                    <tr><td>Production Settlement Key</td><td><code>vt_live_84c1...</code></td><td><code>charges:write, payouts:write</code></td><td><span class="badge badge-green">Active</span></td><td>2026-09-14</td></tr>
                    <tr><td>Internal Telemetry Key</td><td><code>vtk_diag_910a...</code></td><td><code>telemetry:read</code></td><td><span class="badge badge-yellow">Legacy</span></td><td>2025-04-12</td></tr>
                    <tr><td>Webhook HMAC Dispatcher</td><td><code>whsec_vt_918c...</code></td><td><code>events:sign</code></td><td><span class="badge badge-green">Active</span></td><td>2026-10-02</td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'dashboard' || $tab === 'logs'): ?>
        <div class="data-section">
            <div class="ds-title">Recent Control Plane Events</div>
            <table class="dt">
                <thead><tr><th>Timestamp</th><th>Event</th><th>Source IP</th><th>Result</th></tr></thead>
                <tbody>
                    <tr><td>2026-11-11 08:01:14</td><td>Operator login succeeded (vt_admin)</td><td>10.0.4.18</td><td><span class="badge badge-green">Success</span></td></tr>
                    <tr><td>2026-11-10 23:44:07</td><td>Merchant gateway key rotated</td><td>10.0.4.8</td><td><span class="badge badge-green">Success</span></td></tr>
                    <tr><td>2026-11-10 22:12:55</td><td>Account suspended: support_head</td><td>10.0.4.1</td><td><span class="badge badge-yellow">Warning</span></td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="footer-bar">&copy; 2026 VaultTech Inc. &mdash; Administration Console.</div>
</body>
</html>
