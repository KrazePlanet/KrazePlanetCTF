<?php
session_start();

$db_file = __DIR__ . '/gifts.db';
if (file_exists($db_file)) { @chmod($db_file, 0666); }
@chmod(__DIR__, 0777);
$pdo = new PDO('sqlite:' . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER DEFAULT 1,
    author TEXT,
    comment TEXT,
    upvotes INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Prepopulate sample comments if empty
$c_count = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
if ($c_count == 0) {
    $samples = [
        ['SnooEnthusiast', 'This is honestly the coolest Secret Santa gift I have ever seen! The keycaps look insane! 🎁'],
        ['KeyLover99', 'Did your match hand-lube those switches? That sound profile must be godly.'],
        ['SantaHelper_Official', 'Verified delivery! Thank you for participating in Redditgifts Secret Santa 2026. Happy Holidays! 🎄']
    ];
    $stmt = $pdo->prepare("INSERT INTO comments (author, comment, upvotes) VALUES (?, ?, ?)");
    foreach ($samples as $s) {
        $stmt->execute([$s[0], $s[1], rand(5, 42)]);
    }
}

// Handle action to clear all comments for clean testing
if (isset($_POST['action']) && $_POST['action'] === 'clear_comments') {
    $pdo->exec("DELETE FROM comments WHERE id > 3");
    header("Location: index.php");
    exit;
}

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $author = '';
    $comment_text = '';

    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $author = trim($data['author'] ?? $data['username'] ?? 'AnonymousRedditor');
        $comment_text = trim($data['comment'] ?? $data['body'] ?? $data['text'] ?? '');
    } else {
        $author = trim($_POST['author'] ?? 'AnonymousRedditor');
        $comment_text = trim($_POST['comment'] ?? '');
    }

    if (empty($author)) {
        $author = 'Redditor_' . rand(1000, 9999);
    }

    if (!empty($comment_text)) {
        // No rate limiting on adding comments (Vulnerability matching HackerOne #1202408)
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, author, comment) VALUES (1, ?, ?)");
        $stmt->execute([$author, $comment_text]);
        $new_id = $pdo->lastInsertId();

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Comment posted successfully.',
                'comment_id' => (int)$new_id,
                'author' => $author,
                'comment' => $comment_text,
                'created_at' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: index.php#comments-anchor");
            exit;
        }
    } else {
        if ($is_json) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Comment body cannot be empty.']);
            exit;
        }
    }
}

// Fetch all comments
$total_comments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$stmt = $pdo->query("SELECT * FROM comments ORDER BY id DESC LIMIT 200");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secret Santa 2026: Custom Artisan Keyboard! — Redditgifts Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --reddit-orange: #ff4500;
            --reddit-orange-hover: #e03d00;
            --reddit-bg: #0e1113;
            --reddit-card: #1a1a1b;
            --reddit-border: #343536;
            --reddit-text: #d7dadc;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--reddit-bg);
            color: var(--reddit-text);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        /* Top Bar */
        .reddit-nav {
            background: #1a1a1b;
            border-bottom: 1px solid var(--reddit-border);
            padding: 12px 0;
        }

        .reddit-brand {
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .snoo-badge {
            width: 32px;
            height: 32px;
            background: var(--reddit-orange);
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .gifts-tag {
            color: #818384;
            font-size: 14px;
            font-weight: 700;
            padding-left: 6px;
            border-left: 1px solid #343536;
        }

        .nav-link-reddit {
            font-size: 13px;
            font-weight: 600;
            color: #d7dadc;
            text-decoration: none;
            padding: 6px 14px;
            border-radius: 20px;
            transition: background 0.15s;
        }

        .nav-link-reddit:hover {
            background: #272729;
            color: #ffffff;
        }

        /* Main Post Layout */
        .post-card {
            background: var(--reddit-card);
            border: 1px solid var(--reddit-border);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .post-header {
            padding: 14px 20px;
            border-bottom: 1px solid #272729;
            font-size: 12px;
            color: #818384;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .post-body {
            padding: 24px;
        }

        .post-title {
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 14px;
        }

        .exchange-badge {
            background: rgba(255, 69, 0, 0.15);
            color: #ff4500;
            border: 1px solid rgba(255, 69, 0, 0.3);
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 12px;
        }

        /* Comment Area */
        .comment-input-box {
            background: #272729;
            border: 1px solid #343536;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .form-control-reddit {
            background: #1a1a1b;
            border: 1px solid #343536;
            color: #ffffff;
            font-size: 14px;
            border-radius: 6px;
        }

        .form-control-reddit:focus {
            background: #1a1a1b;
            border-color: var(--reddit-orange);
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(255, 69, 0, 0.2);
        }

        .btn-reddit {
            background: var(--reddit-orange);
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            padding: 8px 20px;
            border-radius: 20px;
            border: none;
            transition: background 0.15s;
        }

        .btn-reddit:hover {
            background: var(--reddit-orange-hover);
            color: #ffffff;
        }

        .comment-item {
            background: #151516;
            border-left: 2px solid #343536;
            padding: 14px 18px;
            margin-bottom: 12px;
            border-radius: 0 8px 8px 0;
        }

        .comment-author {
            font-weight: 700;
            font-size: 13px;
            color: #ff4500;
        }

        .comment-time {
            font-size: 11px;
            color: #818384;
            margin-left: 8px;
        }

        .comment-text {
            font-size: 14px;
            color: #d7dadc;
            margin-top: 6px;
            word-break: break-word;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="reddit-nav sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <a href="index.php" class="reddit-brand">
                    <div class="snoo-badge"><i class="bi bi-gift-fill"></i></div>
                    reddit<span style="font-weight: 400;">gifts</span>
                </a>
                <span class="gifts-tag d-none d-md-inline">Secret Santa &bull; Gift Exchange</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="gallery.php" class="nav-link-reddit">Gift Gallery</a>
                <a href="exchanges.php" class="nav-link-reddit">Exchanges</a>
                <a href="index.php#comment-form" class="btn-reddit">Add Comment</a>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Main Gift Showcase Post -->
                <div class="post-card">
                    <div class="post-header">
                        <i class="bi bi-award-fill text-warning fs-6"></i>
                        <span>Posted by <strong>u/CyberKeeb_Dev</strong> 3 days ago</span>
                        <span>&bull;</span>
                        <span class="badge bg-secondary">Verified Match Gift</span>
                    </div>

                    <div class="post-body">
                        <span class="exchange-badge"><i class="bi bi-snow me-1"></i> Secret Santa 2026 Exchange</span>
                        <h1 class="post-title">My match built and hand-lubed a custom artisan mechanical keyboard! ⌨️🎉</h1>
                        
                        <p class="text-light opacity-90">
                            I opened the package today and literally gasped! My Secret Santa saw that I love programming and custom mechanical keyboards, so they machined a custom aluminum case with brass weight, soldered Gateron Oil King switches, and hand-cast a resin Snoo artisan keycap!
                        </p>

                        <div class="text-center my-4">
                            <img src="https://images.unsplash.com/photo-1595225476474-87563907a212?w=800&auto=format&fit=crop&q=80" alt="Mechanical Keyboard" class="img-fluid rounded-3 border border-secondary" style="max-height: 380px; width: 100%; object-fit: cover;">
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-3 border-top border-dark text-secondary small">
                            <div class="d-flex align-items-center gap-3">
                                <span><i class="bi bi-arrow-up-circle-fill text-danger me-1"></i> <strong>1,482 Upvotes</strong></span>
                                <span><i class="bi bi-chat-left-text-fill me-1"></i> <strong><?= number_format($total_comments) ?> Comments</strong></span>
                            </div>
                            <div>
                                <span><i class="bi bi-share-fill me-1"></i> Share</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Unrestricted Comment Submission Form (Target of HackerOne #1202408) -->
                <div class="comment-input-box" id="comment-form">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small fw-bold text-white mb-0">Leave a comment on this gift</label>
                        <span class="text-secondary small" style="font-size: 11px;">API: <code>POST /load/index.php</code> (or <code>/api/comments.php</code>)</span>
                    </div>
                    
                    <form method="POST" action="index.php#comments-anchor">
                        <div class="mb-2">
                            <input type="text" name="author" class="form-control form-control-reddit py-2 mb-2" placeholder="Your Reddit Username (e.g. u/KeyboardFanatic)" value="<?= htmlspecialchars($_POST['author'] ?? '') ?>">
                            <textarea name="comment" class="form-control form-control-reddit" rows="3" placeholder="What are your thoughts on this Secret Santa gift?" required><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-secondary small" style="font-size: 11px;"><i class="bi bi-markdown me-1"></i> Markdown styling supported</span>
                            <button type="submit" class="btn-reddit">
                                <i class="bi bi-send-fill me-1"></i> Post Comment
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Comment Feed -->
                <div id="comments-anchor">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold text-white mb-0">Community Comments (<?= number_format($total_comments) ?>)</h5>
                        <?php if ($total_comments > 10): ?>
                            <form method="POST" onsubmit="return confirm('Reset and clear flood comments?');">
                                <input type="hidden" name="action" value="clear_comments">
                                <button type="submit" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size: 11px;">Reset Flood</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php foreach ($comments as $c): ?>
                        <div class="comment-item">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle text-secondary me-2"></i>
                                <span class="comment-author">u/<?= htmlspecialchars($c['author']) ?></span>
                                <span class="comment-time">&bull; <?= htmlspecialchars($c['created_at']) ?></span>
                            </div>
                            <div class="comment-text">
                                <?= nl2br(htmlspecialchars($c['comment'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-4 border-top border-dark text-center text-secondary small">
        <div class="container">
            &copy; 2026 Reddit Inc. &bull; Redditgifts Secret Santa Community &bull; <a href="gallery.php" class="text-secondary text-decoration-none">Gift Gallery</a>
        </div>
    </footer>

</body>
</html>
