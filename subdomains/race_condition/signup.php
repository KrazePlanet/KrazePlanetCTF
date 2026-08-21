<?php
session_start();
require_once __DIR__ . '/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $workspace = trim($_POST['workspace_name'] ?? '');
    $subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', trim($_POST['subdomain'] ?? '')));
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($workspace) || empty($subdomain) || empty($email) || empty($password)) {
        $error = 'All fields are required to create your workspace.';
    } else {
        // Check if user or subdomain exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR subdomain = ?");
        $stmt->execute([$email, $subdomain]);
        if ($stmt->fetch()) {
            $error = 'A workspace with that domain or email address already exists.';
        } else {
            // New user created with 0 credits and survey_completed = 0
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (workspace_name, subdomain, email, password, credits, survey_completed, plan) VALUES (?, ?, ?, ?, 0, 0, 'Free')");
            $stmt->execute([$workspace, $subdomain, $email, $hashed]);
            $user_id = $pdo->lastInsertId();

            // Insert initial welcome message
            $stmt_msg = $pdo->prepare("INSERT INTO messages (user_id, channel, sender_name, message) VALUES (?, 'general', 'Slackbot', 'Welcome to your new Slack workspace! Complete your workspace setup to get started.')");
            $stmt_msg->execute([$user_id]);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['workspace_name'] = $workspace;
            $_SESSION['subdomain'] = $subdomain;
            $_SESSION['email'] = $email;

            // Direct to the onboarding survey to claim initial $100 credits
            header("Location: survey.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Slack Workspace | Slack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; color: #1d1c1d; margin: 0; padding: 0; }
        .slack-header { background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 18px 0; }
        .slack-brand { font-size: 26px; font-weight: 800; color: #1d1c1d; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .slack-logo-icon { width: 30px; height: 30px; background: linear-gradient(135deg, #ECB22E, #2EB67D, #E01E5A, #36C5F0); border-radius: 8px; }
        .auth-card { max-width: 520px; margin: 40px auto; padding: 40px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .btn-slack { background-color: #4A154B; color: #ffffff; font-weight: 700; font-size: 15px; padding: 12px 24px; border-radius: 6px; border: none; width: 100%; transition: background 0.15s; }
        .btn-slack:hover { background-color: #3F0E40; color: #ffffff; }
    </style>
</head>
<body>

    <header class="slack-header">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="signup.php" class="slack-brand">
                <div class="slack-logo-icon"></div>
                slack
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small">Already using Slack?</span>
                <a href="login.php" class="btn btn-outline-dark btn-sm fw-bold">Sign In</a>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <div class="auth-card">
            <h2 class="fw-bold text-center mb-2">Create your Slack workspace</h2>
            <p class="text-secondary text-center small mb-4">Start collaborating with your team today in one shared digital workspace.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small border-0 d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="signup.php">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Company or Team Name</label>
                    <input type="text" name="workspace_name" class="form-control" placeholder="e.g. Acme Corp" required autofocus value="<?= htmlspecialchars($_POST['workspace_name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Workspace URL</label>
                    <div class="input-group">
                        <input type="text" name="subdomain" class="form-control text-end" placeholder="acmecorp" required value="<?= htmlspecialchars($_POST['subdomain'] ?? '') ?>">
                        <span class="input-group-text bg-light text-secondary">.slack.com</span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-dark mb-1">Work Email</label>
                    <input type="email" name="email" class="form-control" placeholder="you@company.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-dark mb-1">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>

                <button type="submit" class="btn btn-slack">
                    Create Workspace &rarr;
                </button>
            </form>

            <div class="mt-4 text-center text-secondary small">
                By continuing, you agree to Slack's Customer Terms of Service and Privacy Policy.
            </div>
        </div>
    </div>

</body>
</html>
