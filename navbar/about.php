<?php
// about.php - About KrazePlanet Labs. Short, one column, beginner friendly. Public page.
// Numbers come from data/labs.json, so they never go out of date.
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';
startKrazeSession();

$catalog = krazeLabCatalog();
$labCount = count($catalog['byId']);
$typeCount = count($catalog['byCategory']);
$easyCount = 0;
foreach ($catalog['byId'] as $lab) if ($lab['difficulty'] === 'easy') $easyCount++;

$loggedIn = krazeLoggedIn();
$signupUrl = krazeLoginUrl('/index.php', 'signup');
$labsHub = 'https://labs.krazeplanet.com/';
$e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - KrazePlanet Labs</title>
  <meta name="description" content="KrazePlanet Labs: <?= $labCount ?> free practice websites with hidden security bugs. Find the bug, write a short report, earn points.">
  <link rel="icon" type="image/png" href="https://krazeplanet.com/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    :root {
      --bg: #070b14;
      --card: #0b1324;
      --line: rgba(255, 255, 255, 0.09);
      --text: #e2e8f0;
      --body: #b4c0d3;
      --muted: #8796ad;
      --accent: #38bdf8;
    }
    body { background: var(--bg); color: var(--body); font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
    .ab { max-width: 960px; padding-top: 4rem; padding-bottom: 5rem; }
    .ab a { color: var(--accent); text-decoration: none; }
    .ab a:hover { text-decoration: underline; text-underline-offset: 3px; }
    .ab strong { color: var(--text); font-weight: 600; }
    /* Section headings: same as the main page's category titles */
    .ab h2 { font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: #f1f5f9; letter-spacing: -0.2px; line-height: 1.2; margin: 0 0 1rem; }
    .ab-section { margin-top: 3rem; }

    /* Header: same sizes as the main page's hero */
    .ab-head { padding: 1rem 0 0.5rem; }
    .ab-head h1 { font-family: 'Outfit', sans-serif; font-size: 2.35rem; font-weight: 800; color: #fff; letter-spacing: -0.8px; line-height: 1.12; margin: 0 0 18px; }
    .ab-head p { font-size: 1.08rem; color: #c3cddb; line-height: 1.6; margin: 0; max-width: 560px; }
    .ab-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 28px; }
    .ab-btn { display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; font-weight: 600; border-radius: 10px; padding: 10px 18px; border: 1px solid transparent; text-decoration: none !important; transition: background .15s ease, border-color .15s ease, color .15s ease; }
    .ab-btn-primary { background: #0284c7; color: #fff !important; }
    .ab-btn-primary:hover { background: #0ea5e9; }
    .ab-btn-ghost { color: #e2e8f0 !important; border-color: rgba(255, 255, 255, 0.18); background: rgba(255, 255, 255, 0.03); }
    .ab-btn-ghost:hover { color: #fff !important; border-color: rgba(255, 255, 255, 0.35); }

    /* Facts: quieter than the title, so they support it instead of competing */
    .ab-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin: 3.25rem 0 0; }
    .ab-fact { padding: 1.25rem 1.5rem 1.25rem 0; }
    .ab-fact + .ab-fact { padding-left: 1.5rem; border-left: 1px solid var(--line); }
    .ab-fact dt { font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: #f1f5f9; line-height: 1.2; }
    .ab-fact dd { margin: 2px 0 0; font-size: 0.86rem; color: var(--muted); }

    /* Steps: four short ones side by side */
    .ab-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; list-style: none; margin: 0; padding: 0; counter-reset: step; }
    .ab-steps li { counter-increment: step; background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 18px; }
    .ab-steps li::before { content: counter(step); display: block; font-size: 0.8rem; font-weight: 600; color: var(--muted); margin-bottom: 8px; }
    .ab-steps h3 { font-family: 'Inter', sans-serif; font-size: 15.5px; font-weight: 600; color: #e2e8f0; margin: 0 0 6px; }
    .ab-steps p { font-size: 0.9rem; line-height: 1.55; margin: 0; color: var(--muted); }
    .ab-steps p strong { color: #cbd5e1; font-weight: 500; }

    /* Points: one quiet row */
    .ab-points { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .ab-point { border: 1px solid var(--line); border-radius: 12px; padding: 12px 16px; display: flex; align-items: baseline; justify-content: space-between; gap: 8px; }
    .ab-point span { display: inline-flex; align-items: center; gap: 8px; color: #cbd5e1; font-size: 0.95rem; font-weight: 500; }
    .ab-point span::before { content: ''; width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .ab-point b { font-size: 0.95rem; color: #e2e8f0; font-weight: 600; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .ab-point b small { font-size: 0.8rem; color: var(--muted); font-weight: 500; margin-left: 3px; }
    .ab-point span.c-easy::before { background: #34d399; } .ab-point span.c-medium::before { background: #fbbf24; }
    .ab-point span.c-hard::before { background: #f87171; } .ab-point span.c-secure::before { background: #7dd3fc; }

    /* Domains */
    .ab-domains { background: var(--card); border: 1px solid var(--line); border-radius: 14px; padding: 22px 24px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
    .ab-domains h2 { margin-bottom: 6px; }
    .ab-domains p { margin: 0; font-size: 0.95rem; line-height: 1.6; max-width: 600px; color: var(--muted); }
    .ab-domains p strong { color: #cbd5e1; font-weight: 500; }
    .ab-domains .ab-btn { flex-shrink: 0; }

    .ab-foot { margin-top: 3rem; padding-top: 1.5rem; border-top: 1px solid var(--line); font-size: 0.9rem; color: var(--muted); }

    @media (max-width: 860px) {
      .ab { padding-top: 2.5rem; padding-bottom: 3rem; }
      .ab-head { padding-top: 0.5rem; }
      .ab-head h1 { font-size: 1.7rem; margin-bottom: 14px; }
      .ab-head p { font-size: 0.96rem; }
      .ab-actions { margin-top: 22px; }
      .ab-actions .ab-btn { flex: 1 1 auto; justify-content: center; }
      .ab-facts, .ab-steps, .ab-points { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .ab-fact, .ab-fact + .ab-fact { padding: 1.2rem 1rem 1.2rem 0; border-left: 0; }
      .ab-fact:nth-child(even) { padding-left: 1rem; border-left: 1px solid var(--line); }
      .ab-fact:nth-child(n+3) { border-top: 1px solid var(--line); }
      .ab-fact dt { font-size: 1.6rem; }
      .ab-section { margin-top: 2.5rem; }
      .ab-domains { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 520px) {
      .ab-steps { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<?php @include_once __DIR__ . '/navbar.php'; ?>

<main class="container ab flex-grow-1">

  <header class="ab-head">
    <h1>About KrazePlanet Labs</h1>
    <p>Free practice websites with a security bug hidden in each one. Find the bug, write a short report, and earn points. No experience needed.</p>
    <div class="ab-actions">
      <a href="/index.php" class="ab-btn ab-btn-primary"><i class="bi bi-play-fill"></i> Start a lab</a>
      <?php if (!$loggedIn): ?><a href="<?= $e($signupUrl) ?>" class="ab-btn ab-btn-ghost">Create free account</a><?php endif; ?>
    </div>
  </header>

  <dl class="ab-facts">
    <div class="ab-fact"><dt><?= $labCount ?></dt><dd>Labs</dd></div>
    <div class="ab-fact"><dt><?= $easyCount ?></dt><dd>Easy labs for beginners</dd></div>
    <div class="ab-fact"><dt><?= $typeCount ?></dt><dd>Bug types</dd></div>
    <div class="ab-fact"><dt>Free</dt><dd>Always</dd></div>
  </dl>

  <section class="ab-section">
    <h2>How it works</h2>
    <ol class="ab-steps">
      <li><h3>Open a lab</h3><p>Start with an <strong>Easy</strong> one. Nothing to install.</p></li>
      <li><h3>Find the bug</h3><p>Stuck? Open the lab's <strong>⋯</strong> menu and click <strong>Solution</strong>.</p></li>
      <li><h3>Write a report</h3><p>Click <strong>Write Report</strong> in the <strong>⋯</strong> menu. First, <a href="/navbar/profile.php?tab=settings#github">add your GitHub username</a>.</p></li>
      <li><h3>Earn points</h3><p>Mark the lab <strong>Solved</strong> and climb the <a href="/navbar/scoreboard.php">scoreboard</a>.</p></li>
    </ol>
  </section>

  <section class="ab-section">
    <h2>Points</h2>
    <div class="ab-points">
      <div class="ab-point"><span class="c-easy">Easy</span><b>20<small>pts</small></b></div>
      <div class="ab-point"><span class="c-medium">Medium</span><b>50<small>pts</small></b></div>
      <div class="ab-point"><span class="c-hard">Hard</span><b>100<small>pts</small></b></div>
      <div class="ab-point"><span class="c-secure">Secure</span><b>150<small>pts</small></b></div>
    </div>
  </section>

  <section class="ab-section">
    <div class="ab-domains">
      <div>
        <h2>Many websites, one account</h2>
        <p>Our labs run on several free domains. If one is down, use another. Your progress is <strong>synced on all of them</strong>.</p>
      </div>
      <a href="<?= $e($labsHub) ?>" class="ab-btn ab-btn-ghost" target="_blank" rel="noopener">See all domains <i class="bi bi-arrow-up-right"></i></a>
    </div>
  </section>

  <p class="ab-foot">Only test our labs, never real websites without permission. Questions? <a href="/navbar/contact.php">Contact us</a>.</p>

</main>

<?php @include_once __DIR__ . '/../footer/footer.php'; ?>
</body>
</html>
