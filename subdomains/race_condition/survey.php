<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/db.php';

if (empty($user_id)) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$user_id;

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

$error_msg = '';
$is_already_completed = ($user && (int)$user['survey_completed'] === 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Vulnerable check before race delay window
    $stmt_check = $pdo->prepare("SELECT survey_completed, credits FROM users WHERE id = ?");
    $stmt_check->execute([$user_id]);
    $u_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($u_data && (int)$u_data['survey_completed'] === 0) {
        // Race window delay: 120ms
        usleep(120000);

        // 2. Perform write with cross-process mutex lock
        $lock_file = sys_get_temp_dir() . '/slack_race_mutex.lock';
        $lock_fp = fopen($lock_file, 'w+');
        flock($lock_fp, LOCK_EX);

        $db_conn = new PDO('sqlite:' . __DIR__ . '/slack_app.db');
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db_conn->exec("UPDATE users SET credits = credits + 100, survey_completed = 1 WHERE id = {$user_id}");
        $stmt_ins = $db_conn->prepare("INSERT INTO billing_history (user_id, date_str, item, amount) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$user_id, date('Y-m-d'), 'Survey completed', 100]);

        $stmt_fresh = $db_conn->prepare("SELECT credits FROM users WHERE id = ?");
        $stmt_fresh->execute([$user_id]);
        $fresh_data = $stmt_fresh->fetch(PDO::FETCH_ASSOC);
        $new_credits = (int)($fresh_data['credits'] ?? 100);

        flock($lock_fp, LOCK_UN);
        fclose($lock_fp);

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Survey completed successfully. $100 credited to your workspace.',
                'credits' => $new_credits
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: billing.php");
            exit;
        }
    } else {
        if ($is_json) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 400,
                'success' => false,
                'error' => 'Survey has already been completed for this team.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            // Stay on the survey page and show the error message!
            $error_msg = 'This survey has already been completed for your workspace. Your $100 credit is already applied.';
            $is_already_completed = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Setup Survey — Slack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8f8f8;
            color: #1d1c1d;
            margin: 0;
            padding: 0;
        }

        .slack-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 0;
        }

        .slack-brand {
            font-size: 24px;
            font-weight: 800;
            color: #1d1c1d;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .slack-logo-icon {
            width: 28px;
            height: 28px;
            background: linear-gradient(135deg, #ECB22E, #2EB67D, #E01E5A, #36C5F0);
            border-radius: 6px;
        }

        .survey-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            margin-top: 30px;
        }

        .reward-pill {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
            font-weight: 700;
            font-size: 13px;
            padding: 6px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 16px;
        }

        .form-check-label {
            font-size: 14px;
            color: #4a5568;
        }

        .btn-slack {
            background-color: #007a5a;
            color: #ffffff;
            font-weight: 700;
            font-size: 15px;
            padding: 12px 28px;
            border-radius: 6px;
            border: none;
            transition: background 0.15s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-slack:hover {
            background-color: #148567;
            color: #ffffff;
        }
    </style>
</head>
<body>

    <header class="slack-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="index.php" class="slack-brand">
                <div class="slack-logo-icon"></div>
                slack
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small fw-bold">Workspace: <strong><?= htmlspecialchars($user['workspace_name'] ?? 'Workspace') ?></strong></span>
                <a href="billing.php" class="btn btn-outline-secondary btn-sm fw-bold"><i class="bi bi-credit-card me-1"></i> View Billing Ledger</a>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <div class="survey-card">

                    <?php if ($is_already_completed || !empty($error_msg)): ?>
                        <div class="alert alert-warning py-3 px-4 rounded d-flex align-items-center gap-3 mb-4" style="background:#fff8e1; border: 1px solid #ffe082; color: #855b00;">
                            <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Survey Already Completed</h6>
                                <p class="small mb-0">
                                    This setup survey has already been submitted for your workspace (<strong><?= htmlspecialchars($user['workspace_name'] ?? '') ?></strong>). Your <strong>$100 credit</strong> is already active in your billing account.
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <span class="reward-pill"><i class="bi bi-gift-fill me-1"></i> Earn $100 in Slack Workspace Credits</span>
                    <h2 class="fw-bold mb-2">Help us tailor your team's experience</h2>
                    <p class="text-secondary mb-4">Complete this quick 1-minute survey to receive $100 in free credit towards your Slack subscription.</p>

                    <!-- Survey Form matching HackerOne POST /survey/6-23387113491-bed6344a95 -->
                    <form method="POST" action="survey.php" id="surveyForm">
                        <input type="hidden" name="done2" value="1">
                        <input type="hidden" name="crumb" value="s-1473199596-f5b4521138-☃">

                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-2">How did you hear about Slack?</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="referral_options[]" value="Airport" checked id="ref_airport">
                                        <label class="form-check-label" for="ref_airport">Airport / Outdoor Ad</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="referral_options[]" value="Radio" checked id="ref_radio">
                                        <label class="form-check-label" for="ref_radio">Radio / Podcast Sponsorship</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="referral_options[]" value="Colleague" id="ref_friend">
                                        <label class="form-check-label" for="ref_friend">Friend or Colleague</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-2">What is your primary goal with Slack?</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest_options[]" value="Works seamlessly across all devices" checked id="int_devices">
                                        <label class="form-check-label" for="int_devices">Works seamlessly across all devices</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="interest_options[]" value="Improve productivity" checked id="int_prod">
                                        <label class="form-check-label" for="int_prod">Improve productivity &amp; team alignment</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold text-dark mb-2">What communication tools did your team previously use?</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="software_options[]" value="Google Hangouts/Chat" checked id="soft_hangouts">
                                        <label class="form-check-label" for="soft_hangouts">Google Hangouts / Chat</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="software_options[]" value="Yammer" checked id="soft_yammer">
                                        <label class="form-check-label" for="soft_yammer">Microsoft Yammer</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="fw-bold text-dark mb-1">Company Size</label>
                                <select name="company_size" class="form-select">
                                    <option value="1-9" selected>1-9 employees</option>
                                    <option value="10-49">10-49 employees</option>
                                    <option value="50-249">50-249 employees</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-bold text-dark mb-1">Company Industry</label>
                                <select name="company_industry" class="form-select">
                                    <option value="Consumer Goods" selected>Consumer Goods</option>
                                    <option value="Technology &amp; Software">Technology &amp; Software</option>
                                    <option value="Financial Services">Financial Services</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="index.php" class="text-secondary text-decoration-none small">Skip survey</a>
                            
                            <?php if ($is_already_completed): ?>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Submit anyway to test race condition">
                                        Submit Again
                                    </button>
                                    <a href="billing.php" class="btn-slack">
                                        View Credits in Billing &rarr;
                                    </a>
                                </div>
                            <?php else: ?>
                                <button type="submit" class="btn-slack">
                                    Submit Survey &amp; Claim $100 Credit &rarr;
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
