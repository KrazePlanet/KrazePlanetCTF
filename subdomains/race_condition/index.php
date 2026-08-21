<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    // If no session, redirect to signup
    header("Location: signup.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: signup.php");
    exit;
}

// Handle sending messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_msg') {
    $msg = trim($_POST['message'] ?? '');
    $channel = trim($_POST['channel'] ?? 'general');
    if (!empty($msg)) {
        $stmt_ins = $pdo->prepare("INSERT INTO messages (user_id, channel, sender_name, message) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$user_id, $channel, explode('@', $user['email'])[0], $msg]);
    }
    header("Location: index.php?channel=" . urlencode($channel));
    exit;
}

$current_channel = $_GET['channel'] ?? 'general';
$stmt_msgs = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? AND channel = ? ORDER BY id ASC");
$stmt_msgs->execute([$user_id, $current_channel]);
$messages = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['workspace_name']) ?> | Slack Workspace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --slack-aubergine: #3F0E40;
            --slack-aubergine-dark: #350d36;
            --slack-active-channel: #1164A3;
            --slack-text-muted: #bcabbc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #ffffff;
            color: #1d1c1d;
            height: 100vh;
            overflow: hidden;
            margin: 0;
        }

        .workspace-layout {
            display: flex;
            height: 100vh;
        }

        /* Sidebar */
        .slack-sidebar {
            width: 260px;
            background-color: var(--slack-aubergine);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            border-right: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .channel-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            color: var(--slack-text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 4px;
            margin: 2px 8px;
            transition: all 0.15s;
        }

        .channel-link:hover {
            background-color: var(--slack-aubergine-dark);
            color: #ffffff;
        }

        .channel-link.active {
            background-color: var(--slack-active-channel);
            color: #ffffff;
            font-weight: 700;
        }

        /* Main Chat Area */
        .chat-main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            background: #ffffff;
            height: 100vh;
        }

        .chat-header {
            padding: 14px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .messages-container {
            flex-grow: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .message-row {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .avatar {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #4a5568;
            font-size: 14px;
        }

        .chat-input-area {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
        }

        .survey-banner {
            background: #ecfdf5;
            border-bottom: 1px solid #a7f3d0;
            padding: 10px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #065f46;
        }
    </style>
</head>
<body>

    <div class="workspace-layout">
        
        <!-- Sidebar -->
        <div class="slack-sidebar">
            <div class="sidebar-header">
                <div>
                    <h6 class="fw-bold text-white mb-0"><?= htmlspecialchars($user['workspace_name']) ?></h6>
                    <span class="text-light opacity-75 small" style="font-size: 11px;"><?= htmlspecialchars($user['subdomain']) ?>.slack.com</span>
                </div>
                <a href="logout.php" class="text-white opacity-75" title="Sign Out"><i class="bi bi-box-arrow-right"></i></a>
            </div>

            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-uppercase fw-bold text-light opacity-75" style="font-size: 11px;">Channels</span>
                    <i class="bi bi-plus-lg text-white opacity-75 small"></i>
                </div>

                <a href="index.php?channel=general" class="channel-link <?= $current_channel === 'general' ? 'active' : '' ?>">
                    <i class="bi bi-hash"></i> general
                </a>
                <a href="index.php?channel=random" class="channel-link <?= $current_channel === 'random' ? 'active' : '' ?>">
                    <i class="bi bi-hash"></i> random
                </a>
                <a href="index.php?channel=dev-team" class="channel-link <?= $current_channel === 'dev-team' ? 'active' : '' ?>">
                    <i class="bi bi-hash"></i> dev-team
                </a>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                    <span class="text-uppercase fw-bold text-light opacity-75" style="font-size: 11px;">Direct Messages</span>
                </div>
                <a href="#" class="channel-link">
                    <span class="badge bg-success rounded-circle p-1 me-1"> </span> Slackbot
                </a>
            </div>

            <!-- Workspace Plan & Credits in Sidebar -->
            <div class="mt-auto p-3 border-top border-secondary border-opacity-25">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-light opacity-75 small">Plan: <strong><?= htmlspecialchars($user['plan']) ?></strong></span>
                    <span class="badge bg-success" style="background:#2bac76 !important;">$<?= number_format($user['credits']) ?> Credits</span>
                </div>
                <a href="billing.php" class="btn btn-sm btn-outline-light w-100 fw-bold" style="font-size: 12px;">
                    <i class="bi bi-credit-card me-1"></i> Manage Billing
                </a>
            </div>
        </div>

        <!-- Chat Main Area -->
        <div class="chat-main">
            
            <!-- Survey Prompt Banner if not completed yet -->
            <?php if ((int)$user['survey_completed'] === 0): ?>
                <div class="survey-banner">
                    <div>
                        <i class="bi bi-gift-fill me-1 text-success"></i>
                        <span><strong>Account Setup Offer:</strong> Complete the onboarding survey to claim <strong>$100 in free workspace credits</strong>.</span>
                    </div>
                    <a href="survey.php" class="btn btn-sm btn-success fw-bold py-1 px-3" style="background:#059669; border:none; font-size: 12px;">
                        Complete Survey &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <div class="chat-header">
                <div>
                    <h5 class="fw-bold mb-0"><i class="bi bi-hash text-muted"></i> <?= htmlspecialchars($current_channel) ?></h5>
                    <span class="text-secondary small">Company-wide discussions and updates</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-secondary small"><i class="bi bi-person-fill text-muted me-1"></i> 1 member</span>
                    <a href="billing.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear-fill me-1"></i> Workspace Settings</a>
                </div>
            </div>

            <div class="messages-container">
                <?php if (empty($messages)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-chat-square-text fs-1 mb-2"></i>
                        <p>This is the start of the <strong>#<?= htmlspecialchars($current_channel) ?></strong> channel.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="message-row">
                            <div class="avatar"><?= strtoupper(substr($m['sender_name'], 0, 2)) ?></div>
                            <div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <strong class="text-dark"><?= htmlspecialchars($m['sender_name']) ?></strong>
                                    <span class="text-muted" style="font-size: 11px;"><?= date('g:i A', strtotime($m['created_at'])) ?></span>
                                </div>
                                <div class="text-dark mt-1"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="chat-input-area">
                <form method="POST" action="index.php">
                    <input type="hidden" name="action" value="send_msg">
                    <input type="hidden" name="channel" value="<?= htmlspecialchars($current_channel) ?>">
                    <div class="input-group">
                        <input type="text" name="message" class="form-control py-2" placeholder="Send a message to #<?= htmlspecialchars($current_channel) ?>" required autocomplete="off">
                        <button type="submit" class="btn btn-success px-4" style="background:#007a5a; border:none;"><i class="bi bi-send-fill"></i></button>
                    </div>
                </form>
            </div>

        </div>

    </div>

</body>
</html>
