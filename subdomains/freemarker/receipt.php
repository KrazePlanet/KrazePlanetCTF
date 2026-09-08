<?php
session_start();

$default_header = 'Customer Experience Review & Verification â€” ${user.company}';
$default_comments = '<div class="receipt-quote mb-3">
    <p class="mb-2">"Our engineering teams across <strong>${user.company}</strong> have completed pilot deployment for <strong>${survey.product}</strong>. System reliability and real-time telemetry have exceeded our enterprise criteria."</p>
    <div class="small text-warning">&starf;&starf;&starf;&starf;&starf; <strong>${survey.score}</strong></div>
</div>
<div class="p-3 rounded-2 mb-3" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25);">
    <div class="small text-muted mb-1">Loyalty Account Credited:</div>
    <div class="fw-bold text-white">${user.loyaltyTier} &bull; ${user.points} Priority Points</div>
</div>';

$header_input = $_GET['header'] ?? $default_header;
$comments_input = $_GET['comments'] ?? $default_comments;

$b64_h = base64_encode($header_input);
$cmd_h = sprintf('env -u LD_LIBRARY_PATH java -cp %s:%s FreeMarkerEvaluator --base64 %s 2>&1',
    escapeshellarg(__DIR__),
    escapeshellarg(__DIR__ . '/freemarker.jar'),
    escapeshellarg($b64_h)
);
$rendered_header = shell_exec($cmd_h);

$b64_c = base64_encode($comments_input);
$cmd_c = sprintf('env -u LD_LIBRARY_PATH java -cp %s:%s FreeMarkerEvaluator --base64 %s 2>&1',
    escapeshellarg(__DIR__),
    escapeshellarg(__DIR__ . '/freemarker.jar'),
    escapeshellarg($b64_c)
);
$rendered_comments = shell_exec($cmd_c);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Receipt â€” PulseFeedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #05070a; color: #f1f5f9; min-height: 100vh; }
        .receipt-nav { background: #090b10; border-bottom: 1px solid #1f2533; padding: 16px 32px; }
        .receipt-container { max-width: 820px; margin: 40px auto; background: #0d121c; border: 1px solid #1f2738; border-radius: 14px; padding: 32px; }
        .receipt-quote { background: #090c13; border-left: 4px solid #f59e0b; padding: 16px 20px; border-radius: 0 8px 8px 0; font-style: italic; }
    </style>
</head>
<body>
    <header class="receipt-nav d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-star-fill text-warning fs-4"></i>
            <span class="fw-bold fs-4 text-white">PulseFeedback</span>
            <span class="text-secondary small ms-2">/ Official Customer Receipt</span>
        </div>
        <div>
            <a href="index.php" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-pencil-square me-1"></i> Edit in Survey Studio
            </a>
        </div>
    </header>

    <div class="container">
        <div class="receipt-container">
            <h3 class="fw-bold text-white mb-2"><?= $rendered_header ?></h3>
            <div class="d-flex align-items-center gap-3 text-secondary small pb-3 mb-4 border-bottom border-secondary border-opacity-25">
                <div><i class="bi bi-person-fill text-warning me-1"></i> Alex Morgan</div>
                <div><i class="bi bi-calendar3 me-1"></i> September 2026</div>
                <div><span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-25">Verified Submission</span></div>
            </div>
            <div>
                <?= $rendered_comments ?>
            </div>
        </div>
    </div>
</body>
</html>