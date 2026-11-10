<?php

/*
 * RIVERVIEW INSTITUTE OF TECHNOLOGY
 * Admissions Management System (AMS Enterprise v4.2)
 * Applicant Dossier & Academic Verification
 */

$base_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
$parent_dir = rtrim(dirname($base_dir), '/\\') . '/';
if ($parent_dir === '//') $parent_dir = '/';

$id = $_GET['id'] ?? 'RV-2026-1049';

$dossiers = [
    'RV-2026-1049' => [
        'id' => 'RV-2026-1049',
        'name' => 'Johnathan Vance',
        'dob' => '2005-04-12',
        'email' => 'j.vance@inbox.test',
        'phone' => '+1 (555) 234-8901',
        'address' => '742 Evergreen Terrace, Springfield, OR 97477',
        'ssn' => '219-45-8812',
        'parent_name' => 'Robert & Clara Vance',
        'household_income' => '$62,400 / yr',
        'program' => 'B.Sc. Computer Science & AI',
        'gpa' => '3.85',
        'sat' => '1490 (Math: 780, EBRW: 710)',
        'status' => 'Under Review',
        'financial_award' => '$18,500 Need-Based Institutional Grant',
        'reviewer_notes' => 'Strong mathematics foundation. Letters confirm regional placement in state robotics tournament. Committee endorsed for final matriculation.',
        'uploaded_files' => [
            'transcripts_johnathan_vance_2026.pdf',
            'financial_declaration_confidential.pdf',
            'recommendation_letter_dean_jenkins.pdf'
        ]
    ],
    'RV-2026-1052' => [
        'id' => 'RV-2026-1052',
        'name' => 'Elena Rostova',
        'dob' => '2002-11-28',
        'email' => 'elena.rostova@mailservice.test',
        'phone' => '+371 28 910 234',
        'address' => 'Brivibas iela 45, Riga, LV-1010, Latvia',
        'ssn' => 'Passport: LV-948210385',
        'parent_name' => 'Viktor Rostov',
        'household_income' => '€28,000 / yr',
        'program' => 'M.Sc. Cybersecurity & Trust',
        'gpa' => '3.92',
        'sat' => 'GRE: Quantitative 168, Verbal 160',
        'status' => 'Approved',
        'financial_award' => 'Full Tuition & Living Stipend Waiver (€42,000)',
        'reviewer_notes' => 'International cybersecurity background. Co-author in academic systems workshop. Student visa F-1 documentation in progress.',
        'uploaded_files' => [
            'passport_scan_elena_rostova.jpg'
        ]
    ]
];

$dossier = $dossiers[$id] ?? $dossiers['RV-2026-1049'];

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dossier Review: <?= htmlspecialchars($dossier['id']) ?> | AMS Enterprise</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #1e3a8a;
    --border: #e2e8f0;
    --bg-page: #f8fafc;
    --bg-surface: #ffffff;
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
.app-header {
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.brand-title {
    font-size: 15px;
    font-weight: 700;
}
.nav-back {
    color: var(--primary);
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}
.app-container {
    max-width: 1040px;
    margin: 32px auto;
    padding: 0 24px;
}
.card {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 32px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.dossier-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border);
    padding-bottom: 20px;
    margin-bottom: 24px;
}
.dossier-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
}
.dossier-id {
    font-family: monospace;
    font-size: 14px;
    color: var(--text-muted);
}
.grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}
.field-box {
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 14px 18px;
}
.field-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-muted);
    font-weight: 600;
    margin-bottom: 4px;
}
.field-value {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-main);
}
.pii-value {
    color: #dc2626;
    font-family: monospace;
}
.notes-box {
    background: #fffbeb;
    border: 1px solid #fef3c7;
    border-radius: 6px;
    padding: 18px;
    margin-bottom: 24px;
}
.notes-box h4 {
    margin: 0 0 8px 0;
    color: #92400e;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.notes-box p {
    margin: 0;
    font-size: 13px;
    color: #78350f;
    line-height: 1.6;
}
.files-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.files-list li {
    padding: 12px 18px;
    background: #f8fafc;
    border: 1px solid var(--border);
    border-radius: 6px;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}
.files-list a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}
.files-list a:hover { text-decoration: underline; }
</style>
</head>
<body>

<header class="app-header">
    <div class="brand-title">Riverview Admissions Management System · Candidate Dossier Evaluation</div>
    <a href="<?= htmlspecialchars($base_dir) ?>" class="nav-back">← Back to Queue</a>
</header>

<main class="app-container">

    <div class="card">
        <div class="dossier-header">
            <div>
                <h2><?= htmlspecialchars($dossier['name']) ?></h2>
                <div class="dossier-id"><?= htmlspecialchars($dossier['id']) ?> · <?= htmlspecialchars($dossier['program']) ?></div>
            </div>
            <div>
                <span style="background:#ecfdf5;color:#047857;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;">Status: <?= htmlspecialchars($dossier['status']) ?></span>
            </div>
        </div>

        <div class="grid-2">
            <div class="field-box">
                <div class="field-label">Date of Birth</div>
                <div class="field-value"><?= htmlspecialchars($dossier['dob']) ?></div>
            </div>
            <div class="field-box">
                <div class="field-label">Social Security / National ID</div>
                <div class="field-value pii-value"><?= htmlspecialchars($dossier['ssn']) ?></div>
            </div>
            <div class="field-box">
                <div class="field-label">Email Address</div>
                <div class="field-value"><?= htmlspecialchars($dossier['email']) ?></div>
            </div>
            <div class="field-box">
                <div class="field-label">Telephone</div>
                <div class="field-value"><?= htmlspecialchars($dossier['phone']) ?></div>
            </div>
            <div class="field-box">
                <div class="field-label">Permanent Home Address</div>
                <div class="field-value"><?= htmlspecialchars($dossier['address']) ?></div>
            </div>
            <div class="field-box">
                <div class="field-label">Household Adjusted Gross Income (IRS Form 1040)</div>
                <div class="field-value pii-value"><?= htmlspecialchars($dossier['household_income']) ?></div>
            </div>
            <div class="field-box">
                <div class="field-label">Cumulative GPA</div>
                <div class="field-value"><?= htmlspecialchars($dossier['gpa']) ?> / 4.00</div>
            </div>
            <div class="field-box">
                <div class="field-label">Standardized Scores</div>
                <div class="field-value"><?= htmlspecialchars($dossier['sat']) ?></div>
            </div>
        </div>

        <div class="notes-box">
            <h4>Admissions Committee Reviewer Notes</h4>
            <p><?= htmlspecialchars($dossier['reviewer_notes']) ?></p>
        </div>

        <div>
            <h4 style="font-size: 13px; text-transform: uppercase; color: var(--text-muted); margin: 0 0 12px 0;">Attached Student Verification Records</h4>
            <ul class="files-list">
                <?php foreach ($dossier['uploaded_files'] as $fname): ?>
                <li>
                    <span>📄 <?= htmlspecialchars($fname) ?></span>
                    <a href="<?= htmlspecialchars($parent_dir) ?>uploads/<?= urlencode($fname) ?>" target="_blank">Download Document →</a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

</main>

</body>
</html>
