<?php

/*
 * RIVERVIEW INSTITUTE OF TECHNOLOGY
 * Admissions Management System (AMS Enterprise v4.2)
 * Staff Review & Docket Evaluation Console
 */

$base_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$parent_dir = rtrim(dirname($base_dir), '/\\') . '/';
if ($parent_dir === '//') $parent_dir = '/';

$tab = $_GET['tab'] ?? 'queue';

$applicants = [
    [
        'id' => 'RV-2026-1049',
        'name' => 'Johnathan Vance',
        'email' => 'j.vance@inbox.test',
        'phone' => '+1 (555) 234-8901',
        'program' => 'B.Sc. Computer Science & AI',
        'gpa' => '3.85',
        'sat' => '1490',
        'ssn' => '219-45-8812',
        'financial_aid' => '$18,500 / yr',
        'status' => 'Under Review',
        'submitted' => 'Nov 02, 2026',
        'notes' => 'High school transcript verified. Committee review pending.'
    ],
    [
        'id' => 'RV-2026-1052',
        'name' => 'Elena Rostova',
        'email' => 'elena.rostova@mailservice.test',
        'phone' => '+371 28 910 234',
        'program' => 'M.Sc. Cybersecurity & Trust',
        'gpa' => '3.92',
        'sat' => 'GRE 328',
        'ssn' => 'Passport: LV-948210385',
        'financial_aid' => 'Full Tuition Waiver ($42,000)',
        'status' => 'Approved',
        'submitted' => 'Oct 28, 2026',
        'notes' => 'International visa F-1 expedited. Embassy clearance verified.'
    ],
    [
        'id' => 'RV-2026-1088',
        'name' => 'Marcus Sterling',
        'email' => 'm.sterling@campusnet.test',
        'phone' => '+1 (555) 492-1084',
        'program' => 'B.Eng. Mechanical Engineering',
        'gpa' => '3.98',
        'sat' => '1560',
        'ssn' => '184-90-3419',
        'financial_aid' => 'Presidential Fellowship ($50,000)',
        'status' => 'Approved',
        'submitted' => 'Oct 15, 2026',
        'notes' => 'Top 1% STEM cohort. Direct matriculation offered.'
    ],
    [
        'id' => 'RV-2026-1104',
        'name' => 'Sophia Morales',
        'email' => 'sophia.m@outlook.test',
        'phone' => '+1 (555) 782-9912',
        'program' => 'B.A. Digital Media & Design',
        'gpa' => '3.65',
        'sat' => '1380',
        'ssn' => '392-18-5041',
        'financial_aid' => '$12,000 / yr',
        'status' => 'Pending Documents',
        'submitted' => 'Nov 04, 2026',
        'notes' => 'Awaiting official tax return documents from parents.'
    ],
    [
        'id' => 'RV-2026-1120',
        'name' => 'David Kim',
        'email' => 'david.kim99@webmail.test',
        'phone' => '+1 (555) 601-3829',
        'program' => 'B.Sc. Data Science & Analytics',
        'gpa' => '3.78',
        'sat' => '1440',
        'ssn' => '409-77-6218',
        'financial_aid' => 'Tuition Grant ($8,500)',
        'status' => 'Under Review',
        'submitted' => 'Nov 01, 2026',
        'notes' => 'Mathematics prerequisites completed with honors.'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admissions Management System | Riverview Institute</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script>
if (!window.location.pathname.endsWith('/')) {
    window.history.replaceState(null, '', window.location.pathname + '/' + window.location.search);
}
</script>
<style>
:root {
    --primary: #1e3a8a;
    --primary-dark: #172554;
    --accent: #dc2626;
    --bg-page: #f8fafc;
    --bg-surface: #ffffff;
    --border: #e2e8f0;
    --text-main: #0f172a;
    --text-muted: #64748b;
}

* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    background: var(--bg-page);
    color: var(--text-main);
    line-height: 1.5;
}

/* TOP APP BAR */
.app-header {
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.brand-group {
    display: flex;
    align-items: center;
    gap: 16px;
}
.brand-badge {
    width: 36px;
    height: 36px;
    background: var(--primary);
    color: white;
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}
.brand-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-main);
}
.brand-version {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 500;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 14px;
    font-size: 13px;
}
.user-avatar {
    width: 34px;
    height: 34px;
    background: #e2e8f0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #334155;
}
.user-info {
    text-align: right;
    line-height: 1.2;
}
.user-name {
    font-weight: 600;
    color: var(--text-main);
}
.user-role {
    font-size: 11px;
    color: var(--text-muted);
}
.btn-logout {
    color: #ef4444;
    text-decoration: none;
    font-weight: 500;
    font-size: 12px;
    padding: 6px 12px;
    border: 1px solid #fecaca;
    border-radius: 4px;
    background: #fff;
    transition: background 0.2s;
}
.btn-logout:hover { background: #fef2f2; }

/* NAVIGATION TABS */
.nav-tabs-bar {
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    display: flex;
    gap: 28px;
}
.tab-link {
    padding: 14px 4px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-bottom: 2px solid transparent;
    transition: all 0.2s;
}
.tab-link:hover { color: var(--text-main); }
.tab-link.active {
    color: var(--primary);
    font-weight: 600;
    border-bottom-color: var(--primary);
}

/* MAIN CONTAINER */
.app-container {
    max-width: 1360px;
    margin: 28px auto;
    padding: 0 32px;
}

/* KPI METRICS */
.kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}
.kpi-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 20px 24px;
}
.kpi-title {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.kpi-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-main);
    line-height: 1;
}
.kpi-sub {
    font-size: 12px;
    color: #10b981;
    font-weight: 500;
    margin-top: 6px;
}

/* TABLE CARD */
.data-card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    overflow: hidden;
}
.card-toolbar {
    padding: 18px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
}
.card-toolbar h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-main);
}
.toolbar-actions {
    display: flex;
    gap: 12px;
}
.btn-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s;
}
.btn-export {
    background: #0284c7;
    color: white;
    border: none;
}
.btn-export:hover { background: #0369a1; }
.btn-secondary {
    background: #f1f5f9;
    color: #334155;
    border: 1px solid var(--border);
}
.btn-secondary:hover { background: #e2e8f0; }

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
th {
    background: #f8fafc;
    color: var(--text-muted);
    font-weight: 600;
    text-align: left;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
td {
    padding: 16px 18px;
    border-bottom: 1px solid var(--border);
    color: #334155;
    vertical-align: middle;
}
tr:hover td {
    background: #f8fafc;
}
.applicant-name {
    font-weight: 600;
    color: var(--text-main);
    display: block;
}
.applicant-id {
    font-family: monospace;
    font-size: 12px;
    color: var(--text-muted);
}
.badge-status {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}
.status-approved { background: #ecfdf5; color: #047857; }
.status-review { background: #fefce8; color: #a16207; }
.status-pending { background: #f1f5f9; color: #475569; }

.btn-view-dossier {
    background: var(--primary);
    color: white;
    text-decoration: none;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    transition: background 0.2s;
}
.btn-view-dossier:hover { background: var(--primary-dark); }

/* SETTINGS VIEW */
.settings-box {
    padding: 32px;
}
.settings-section {
    max-width: 800px;
    margin-bottom: 36px;
}
.settings-section h3 {
    font-size: 16px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: var(--text-main);
}
.settings-section p {
    font-size: 13px;
    color: var(--text-muted);
    margin: 0 0 20px 0;
}
.form-field {
    margin-bottom: 16px;
}
.form-field label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
}
.form-field input {
    width: 100%;
    padding: 9px 12px;
    border: 1px solid var(--border);
    border-radius: 4px;
    font-family: monospace;
    font-size: 13px;
    background: #f8fafc;
    color: #0f172a;
}
</style>
</head>
<body>

<header class="app-header">
    <div class="brand-group">
        <div class="brand-badge">RV</div>
        <div>
            <div class="brand-title">Riverview Admissions Management System</div>
            <div class="brand-version">Institutional Enrollment Platform · v4.2.14</div>
        </div>
    </div>

    <div class="user-profile">
        <div class="user-info">
            <div class="user-name">Dr. Sarah Jenkins</div>
            <div class="user-role">Admissions Committee Chair</div>
        </div>
        <div class="user-avatar">SJ</div>
        <a href="<?= htmlspecialchars($parent_dir) ?>" class="btn-logout">Exit to Public Site</a>
    </div>
</header>

<div class="nav-tabs-bar">
    <a href="?tab=queue" class="tab-link <?= $tab === 'queue' ? 'active' : '' ?>">Applicant Evaluation Queue</a>
    <a href="?tab=decisions" class="tab-link <?= $tab === 'decisions' ? 'active' : '' ?>">Decisions & Matriculation</a>
    <a href="?tab=settings" class="tab-link <?= $tab === 'settings' ? 'active' : '' ?>">System & Storage Configuration</a>
</div>

<main class="app-container">

<?php if ($tab === 'queue' || $tab === 'decisions'): ?>

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-title">Total Applications Received</div>
            <div class="kpi-value">1,248</div>
            <div class="kpi-sub">↑ 8.4% vs last cycle</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Dossiers in Committee Review</div>
            <div class="kpi-value">42</div>
            <div class="kpi-sub" style="color:#d97706;">Action required</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Admissions Offers Released</div>
            <div class="kpi-value">275</div>
            <div class="kpi-sub">22.0% acceptance rate</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-title">Institutional Aid Committed</div>
            <div class="kpi-value">$4.2M</div>
            <div class="kpi-sub">88% of FY2026 allocation</div>
        </div>
    </div>

    <div class="data-card">
        <div class="card-toolbar">
            <h2>Applicant Dossiers — Fall 2026 Regular Intake</h2>
            <div class="toolbar-actions">
                <a href="<?= htmlspecialchars($base_dir) ?>export.php" class="btn-action btn-export">
                    <span>Export Application Dataset (.CSV)</span>
                </a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Docket / Name</th>
                    <th>Intended Major</th>
                    <th>Contact Details</th>
                    <th>Tax Identification (SSN)</th>
                    <th>GPA / SAT</th>
                    <th>Financial Aid Status</th>
                    <th>Review Stage</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applicants as $app): ?>
                <tr>
                    <td>
                        <span class="applicant-name"><?= htmlspecialchars($app['name']) ?></span>
                        <span class="applicant-id"><?= htmlspecialchars($app['id']) ?> · <?= htmlspecialchars($app['submitted']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($app['program']) ?></td>
                    <td>
                        <?= htmlspecialchars($app['email']) ?><br>
                        <small style="color:#64748b;"><?= htmlspecialchars($app['phone']) ?></small>
                    </td>
                    <td>
                        <code style="color:#dc2626;background:#fef2f2;padding:2px 6px;border-radius:3px;"><?= htmlspecialchars($app['ssn']) ?></code>
                    </td>
                    <td>
                        <strong><?= htmlspecialchars($app['gpa']) ?></strong> / <?= htmlspecialchars($app['sat']) ?>
                    </td>
                    <td><?= htmlspecialchars($app['financial_aid']) ?></td>
                    <td>
                        <?php
                            $badge = 'status-pending';
                            if ($app['status'] === 'Approved') $badge = 'status-approved';
                            if ($app['status'] === 'Under Review') $badge = 'status-review';
                        ?>
                        <span class="badge-status <?= $badge ?>"><?= htmlspecialchars($app['status']) ?></span>
                    </td>
                    <td>
                        <a href="<?= htmlspecialchars($base_dir) ?>review.php?id=<?= urlencode($app['id']) ?>" class="btn-view-dossier">Review File</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php elseif ($tab === 'settings'): ?>

    <div class="data-card settings-box">
        <div class="settings-section">
            <h3>Infrastructure & Data Storage Configuration</h3>
            <p>Underlying relational storage connections, object stores, and student intake paths for AMS Enterprise.</p>

            <div class="form-field">
                <label>Primary Database Endpoint</label>
                <input type="text" readonly value="db01.internal.riverview.test:3306">
            </div>

            <div class="form-field">
                <label>Database Service Username</label>
                <input type="text" readonly value="admissions_app">
            </div>

            <div class="form-field">
                <label>Local Document Storage Mount Path</label>
                <input type="text" readonly value="/var/www/riverview/admissions/storage">
            </div>

            <div class="form-field">
                <label>Applicant Document Intake Directory</label>
                <input type="text" readonly value="/var/www/riverview/admissions/uploads">
            </div>

            <div class="form-field">
                <label>AWS S3 Cold Archive URI</label>
                <input type="text" readonly value="s3://riverview-admissions-cold-storage-vault/">
            </div>

            <div class="form-field">
                <label>REST API Webhook Secret Token</label>
                <input type="text" readonly value="rv_live_sec_9f83a2c841b50e32d917">
            </div>
        </div>
    </div>

<?php endif; ?>

</main>

</body>
</html>
