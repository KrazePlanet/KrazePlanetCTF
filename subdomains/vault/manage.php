<?php
require_once __DIR__ . '/includes/auth.php';

$auth = vt_handle_panel_auth('manage');
$logged_in = $auth['authenticated'];
$auth_err = $auth['error'];
$currentUser = htmlspecialchars((string) ($auth['user'] ?? 'vt_admin'));

$tab = $_GET['tab'] ?? 'overview';
if (!in_array($tab, ['overview', 'clients', 'settlements', 'team'], true)) {
    $tab = 'overview';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Operations Center &mdash; VaultTech</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { min-height: 100vh; background: #0c0a14; color: #e4e7eb; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; display: flex; flex-direction: column; }
a { color: inherit; text-decoration: none; }
.top-bar { background: #110f1c; border-bottom: 1px solid #2a2440; padding: 0 24px; height: 52px; display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.logo { font-weight: 800; font-size: .92rem; color: #f0f4f8; display: flex; align-items: center; gap: 8px; }
.tbadge { font-size: .62rem; text-transform: uppercase; letter-spacing: .8px; border: 1px solid #3b1f6e; padding: 2px 7px; border-radius: 3px; color: #a78bfa; background: #15102a; }
.spacer { flex: 1; }
.env { font-size: .72rem; color: #6b7280; }
.main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 24px; }
.login-card { background: #110f1c; border: 1px solid #2a2440; border-radius: 10px; width: 100%; max-width: 400px; overflow: hidden; box-shadow: 0 8px 32px rgba(0, 0, 0, .5); }
.accent { height: 3px; background: linear-gradient(90deg, #4c1d95, #7c3aed, #a78bfa); }
.card-body { padding: 32px 28px 28px; }
.card-body h1 { font-size: 1.15rem; font-weight: 800; color: #f0f4f8; margin-bottom: 4px; }
.sub { font-size: .78rem; color: #6b7280; margin-bottom: 22px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-size: .68rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 5px; }
.form-group input { width: 100%; padding: 10px 12px; border: 1px solid #2a2440; border-radius: 6px; background: #0c0a14; color: #f0f4f8; font-size: .88rem; outline: none; }
.form-group input:focus { border-color: #7c3aed; }
.btn-login { width: 100%; padding: 11px; background: #6d28d9; color: #fff; border: none; border-radius: 6px; font-size: .87rem; font-weight: 700; cursor: pointer; }
.btn-login:hover { background: #7c3aed; }
.error-alert { background: #1c0f0f; border: 1px solid #4a1c1c; border-left: 3px solid #ef4444; border-radius: 6px; padding: 10px 12px; margin-bottom: 16px; color: #fca5a5; font-size: .78rem; }
.dash-wrap { display: flex; flex: 1; min-height: 0; }
.sidebar { width: 210px; flex-shrink: 0; background: #0f0d1a; border-right: 1px solid #2a2440; padding: 12px 0; display: flex; flex-direction: column; }
.sidebar a { display: block; padding: 10px 18px; color: #9ca3af; font-size: .82rem; font-weight: 500; }
.sidebar a:hover, .sidebar a.active { background: #1a1630; color: #a78bfa; }
.content { flex: 1; padding: 24px 30px; overflow-y: auto; }
.page-header { margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #2a2440; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.ph-badge { background: #15102a; color: #c4b5fd; font-size: .68rem; font-weight: 700; padding: 5px 10px; border-radius: 4px; border: 1px solid #4c1d95; }
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px; margin-bottom: 20px; }
.stat-card { background: #110f1c; border: 1px solid #2a2440; border-radius: 8px; padding: 16px 18px; }
.sc-title { font-size: .66rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; margin-bottom: 4px; }
.sc-value { font-size: 1.45rem; font-weight: 800; color: #f0f4f8; }
.data-section { background: #110f1c; border: 1px solid #2a2440; border-radius: 8px; overflow: hidden; margin-bottom: 20px; }
.ds-title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #9ca3af; padding: 12px 16px; border-bottom: 1px solid #2a2440; background: #0f0d1a; }
table.dt { width: 100%; border-collapse: collapse; font-size: .8rem; }
table.dt th { padding: 10px 16px; text-align: left; font-size: .66rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; background: #0f0d1a; border-bottom: 1px solid #2a2440; }
table.dt td { padding: 9px 16px; border-bottom: 1px solid #1a1630; color: #d1d5db; }
.badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: .64rem; font-weight: 700; text-transform: uppercase; }
.badge-green { background: #064e3b; color: #6ee7b7; }
.badge-yellow { background: #3b2f0f; color: #fcd34d; }
.footer-bar { background: #110f1c; border-top: 1px solid #2a2440; padding: 12px 24px; text-align: center; font-size: .7rem; color: #6b7280; }
</style>
</head>
<body>

<div class="top-bar">
    <div class="logo">VaultTech</div>
    <span class="tbadge">Operations</span>
    <div class="spacer"></div>
    <span class="env">manage.vaulttech.io</span>
</div>

<?php if (!$logged_in): ?>
<div class="main">
    <div class="login-card">
        <div class="accent"></div>
        <div class="card-body">
            <h1>Operations Center</h1>
            <div class="sub">Access restricted to settlement &amp; treasury operations personnel.</div>
            <?php if ($auth_err): ?>
                <div class="error-alert"><?= htmlspecialchars($auth_err) ?></div>
            <?php endif; ?>
            <form method="POST" action="/manage/">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter operations username" autocomplete="off" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter operations password" required>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
        </div>
    </div>
</div>
<?php else: ?>
<div class="dash-wrap">
    <nav class="sidebar">
        <a href="/manage/?tab=overview" class="<?= $tab === 'overview' ? 'active' : '' ?>">Overview</a>
        <a href="/manage/?tab=clients" class="<?= $tab === 'clients' ? 'active' : '' ?>">Client Accounts</a>
        <a href="/manage/?tab=settlements" class="<?= $tab === 'settlements' ? 'active' : '' ?>">Settlements</a>
        <a href="/manage/?tab=team" class="<?= $tab === 'team' ? 'active' : '' ?>">Operations Team</a>
        <a href="/admin/" style="margin-top:16px;border-top:1px solid #2a2440;padding-top:14px;">Admin Console &rarr;</a>
        <a href="/manage/?logout=1" style="color:#fca5a5;">Sign Out</a>
    </nav>

    <div class="content">
        <div class="page-header">
            <div>
                <h2 style="font-size:1.15rem;">Operations Center</h2>
                <div style="font-size:.75rem;color:#9ca3af;">Authenticated operations session (Super Admin)</div>
            </div>
            <div class="ph-badge">Authenticated as <?= $currentUser ?></div>
        </div>

        <div class="stat-grid">
            <div class="stat-card"><div class="sc-title">Settlement Volume (MTD)</div><div class="sc-value">$48.2M</div></div>
            <div class="stat-card"><div class="sc-title">Active Merchants</div><div class="sc-value">1,847</div></div>
            <div class="stat-card"><div class="sc-title">Daily Authorizations</div><div class="sc-value">2.84M</div></div>
            <div class="stat-card"><div class="sc-title">Chargeback Ratio</div><div class="sc-value">0.08%</div></div>
        </div>

        <?php if ($tab === 'overview' || $tab === 'clients'): ?>
        <div class="data-section">
            <div class="ds-title">Client Accounts &mdash; Top Tier Settlement</div>
            <table class="dt">
                <thead><tr><th>Client</th><th>Sector</th><th>Volume (MTD)</th><th>Status</th><th>CSM Owner</th></tr></thead>
                <tbody>
                    <tr><td>Acme Corp</td><td>Retail</td><td>$8.4M</td><td><span class="badge badge-green">Active</span></td><td>ops_manager</td></tr>
                    <tr><td>TechVentures Ltd</td><td>SaaS</td><td>$6.1M</td><td><span class="badge badge-green">Active</span></td><td>analyst_01</td></tr>
                    <tr><td>GlobalPay Systems</td><td>Payments</td><td>$12.7M</td><td><span class="badge badge-green">Active</span></td><td>ops_manager</td></tr>
                    <tr><td>RetailPlus Group</td><td>E-commerce</td><td>$5.8M</td><td><span class="badge badge-yellow">Review</span></td><td>finance_lead</td></tr>
                    <tr><td>FinServe LLC</td><td>Financial</td><td>$9.3M</td><td><span class="badge badge-green">Active</span></td><td>ops_manager</td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'settlements'): ?>
        <div class="data-section">
            <div class="ds-title">Pending Treasury Disbursements</div>
            <table class="dt">
                <thead><tr><th>Batch ID</th><th>Rail</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    <tr><td>BAT-2026-1111-A</td><td>FedNow</td><td>$1,420,000.00</td><td><span class="badge badge-green">Settled</span></td></tr>
                    <tr><td>BAT-2026-1111-B</td><td>SEPA Instant</td><td>$890,500.00</td><td><span class="badge badge-yellow">Queued</span></td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($tab === 'team'): ?>
        <div class="data-section">
            <div class="ds-title">Operations On-Call Roster</div>
            <table class="dt">
                <thead><tr><th>Operator</th><th>Shift</th><th>Region</th></tr></thead>
                <tbody>
                    <tr><td>ops_manager</td><td>Primary</td><td>US-East</td></tr>
                    <tr><td>finance_lead</td><td>Treasury Approver</td><td>EU-Central</td></tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="footer-bar">&copy; 2026 VaultTech Inc. &mdash; Operations Center.</div>
</body>
</html>
