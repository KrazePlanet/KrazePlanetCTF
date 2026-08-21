<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['urban_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['urban_user_id'];
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$current_user = $stmt_u->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit;
}

// Fetch all definitions
$definitions = $pdo->query("SELECT * FROM definitions ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch user votes
$stmt_votes = $pdo->prepare("SELECT def_id, vote_type FROM votes WHERE user_id = ?");
$stmt_votes->execute([$user_id]);
$user_votes = [];
while ($row = $stmt_votes->fetch(PDO::FETCH_ASSOC)) {
    $user_votes[$row['def_id']] = $row['vote_type'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urban Dictionary: Define Your World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@400;600;700;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Source Sans Pro', sans-serif;
            background-color: #12151a;
            color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* 1:1 Urban Dictionary Header */
        .urban-navbar {
            background-color: #1c222d;
            border-bottom: 2px solid #2d3748;
            padding: 14px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .urban-brand {
            font-size: 24px;
            font-weight: 900;
            color: #efff00;
            text-decoration: none;
            letter-spacing: -0.5px;
        }

        .urban-brand span {
            color: #ffffff;
        }

        .search-bar {
            background-color: #12151a;
            border: 1px solid #3b4252;
            color: #ffffff;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 15px;
            width: 360px;
        }

        .search-bar:focus {
            background-color: #12151a;
            border-color: #efff00;
            color: #ffffff;
            outline: none;
        }

        .alphabet-bar {
            background-color: #161b22;
            border-bottom: 1px solid #2d3748;
            padding: 8px 0;
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
        }

        .alphabet-bar a {
            color: #94a3b8;
            text-decoration: none;
            margin: 0 5px;
            transition: color 0.15s;
        }

        .alphabet-bar a:hover {
            color: #efff00;
        }

        .def-card {
            background-color: #1c222d;
            border-radius: 10px;
            border: 1px solid #2d3748;
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }

        .def-word {
            font-size: 32px;
            font-weight: 900;
            color: #1b85f2;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 14px;
            letter-spacing: -0.3px;
        }

        .def-meaning {
            font-size: 18px;
            line-height: 1.6;
            color: #f1f5f9;
            margin-bottom: 16px;
            white-space: pre-line;
        }

        .def-example {
            font-size: 16px;
            font-style: italic;
            color: #94a3b8;
            margin-bottom: 20px;
            border-left: 3px solid #efff00;
            padding-left: 14px;
            line-height: 1.5;
        }

        .def-meta {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 22px;
        }

        .def-meta strong {
            color: #cbd5e1;
        }

        /* Modern & Ultra-Clear Voting Buttons */
        .vote-button-group {
            display: inline-flex;
            align-items: center;
            gap: 12px;
        }

        /* Upvote Button */
        .vote-btn-up {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #242c3d;
            color: #94a3b8;
            border: 1.5px solid #3b4559;
            border-radius: 25px;
            padding: 8px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }

        .vote-btn-up:hover {
            background-color: #2d374b;
            color: #22c55e;
            border-color: #22c55e;
            transform: translateY(-1px);
        }

        .vote-btn-up.active {
            background-color: #14532d;
            color: #4ade80;
            border-color: #22c55e;
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.35);
        }

        .vote-btn-up.active i {
            transform: scale(1.15);
        }

        /* Downvote Button */
        .vote-btn-down {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #242c3d;
            color: #94a3b8;
            border: 1.5px solid #3b4559;
            border-radius: 25px;
            padding: 8px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            user-select: none;
        }

        .vote-btn-down:hover {
            background-color: #2d374b;
            color: #f43f5e;
            border-color: #f43f5e;
            transform: translateY(-1px);
        }

        .vote-btn-down.active {
            background-color: #4c0519;
            color: #fb7185;
            border-color: #f43f5e;
            box-shadow: 0 0 12px rgba(244, 63, 94, 0.35);
        }

        .vote-btn-down.active i {
            transform: scale(1.15);
        }

        .vote-btn-down.negative-val {
            color: #f43f5e;
            border-color: #f43f5e;
            font-weight: 900;
        }

        .vote-status-indicator {
            font-size: 12px;
            font-weight: 600;
            margin-left: 4px;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <header class="urban-navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <a href="index.php" class="urban-brand">urban <span>dictionary</span></a>
                <input type="text" class="search-bar d-none d-md-block" placeholder="Search words or phrases..." value="Hangry">
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary small">
                    Signed in as <strong class="text-white"><?= htmlspecialchars($current_user['username']) ?></strong>
                </span>
                <a href="logout.php" class="btn btn-outline-secondary btn-sm" style="font-size:12px; color:#cbd5e1; border-color:#3b4559;">Log Out</a>
            </div>
        </div>
    </header>

    <!-- Alphabet Bar -->
    <div class="alphabet-bar text-center d-none d-md-block">
        <div class="container">
            <?php foreach (range('A', 'Z') as $char): ?>
                <a href="#"><?= $char ?></a>
            <?php endforeach; ?>
            <a href="#">#</a>
        </div>
    </div>

    <!-- Main Definitions Feed -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <?php foreach ($definitions as $def): ?>
                    <?php 
                        $user_v = $user_votes[$def['id']] ?? null; 
                        $is_up_active = ($user_v === 'up');
                        $is_down_active = ($user_v === 'down');
                        $is_down_negative = ((int)$def['thumbs_down'] < 0);
                    ?>
                    <div class="def-card" id="def-card-<?= $def['id'] ?>">
                        <a href="#" class="def-word"><?= htmlspecialchars($def['word']) ?></a>
                        
                        <div class="def-meaning">
                            <?= htmlspecialchars($def['meaning']) ?>
                        </div>

                        <div class="def-example">
                            <?= htmlspecialchars($def['example']) ?>
                        </div>

                        <div class="def-meta">
                            by <strong><?= htmlspecialchars($def['author']) ?></strong> &bull; <?= date('F j, Y', strtotime($def['created_at'])) ?>
                        </div>

                        <!-- Clear, Interactive & Highly Visual Voting Button Group -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div class="vote-button-group">
                                <!-- Upvote Button -->
                                <button type="button" 
                                        id="up-btn-<?= $def['id'] ?>" 
                                        class="vote-btn-up <?= $is_up_active ? 'active' : '' ?>" 
                                        onclick="castVote(<?= $def['id'] ?>, 'up')" 
                                        title="Upvote this definition">
                                    <i class="bi <?= $is_up_active ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up' ?> fs-5"></i>
                                    <span id="up-count-<?= $def['id'] ?>"><?= (int)$def['thumbs_up'] ?></span>
                                </button>

                                <!-- Downvote Button -->
                                <button type="button" 
                                        id="down-btn-<?= $def['id'] ?>" 
                                        class="vote-btn-down <?= $is_down_active ? 'active' : '' ?> <?= $is_down_negative ? 'negative-val' : '' ?>" 
                                        onclick="castVote(<?= $def['id'] ?>, 'down')" 
                                        title="Downvote this definition">
                                    <i class="bi <?= $is_down_active ? 'bi-hand-thumbs-down-fill' : 'bi-hand-thumbs-down' ?> fs-5"></i>
                                    <span id="down-count-<?= $def['id'] ?>"><?= (int)$def['thumbs_down'] ?></span>
                                </button>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-secondary text-secondary border-0" title="Share definition"><i class="bi bi-share"></i></button>
                                <button class="btn btn-sm btn-outline-secondary text-secondary border-0" title="Flag definition"><i class="bi bi-flag"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <script>
    function castVote(defId, direction) {
        const formData = new FormData();
        formData.append('defid', defId);
        formData.append('direction', direction);

        const upBtn = document.getElementById('up-btn-' + defId);
        const downBtn = document.getElementById('down-btn-' + defId);
        const upCount = document.getElementById('up-count-' + defId);
        const downCount = document.getElementById('down-count-' + defId);

        fetch('vote.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (upCount) upCount.innerText = data.up;
                if (downCount) {
                    downCount.innerText = data.down;
                    if (data.down < 0) {
                        downBtn.classList.add('negative-val');
                    } else {
                        downBtn.classList.remove('negative-val');
                    }
                }

                // Update visual active state
                if (data.user_vote === 'up') {
                    upBtn.classList.add('active');
                    upBtn.querySelector('i').className = 'bi bi-hand-thumbs-up-fill fs-5';
                    downBtn.classList.remove('active');
                    downBtn.querySelector('i').className = 'bi bi-hand-thumbs-down fs-5';
                } else if (data.user_vote === 'down') {
                    downBtn.classList.add('active');
                    downBtn.querySelector('i').className = 'bi bi-hand-thumbs-down-fill fs-5';
                    upBtn.classList.remove('active');
                    upBtn.querySelector('i').className = 'bi bi-hand-thumbs-up fs-5';
                } else {
                    // Toggled off
                    upBtn.classList.remove('active');
                    upBtn.querySelector('i').className = 'bi bi-hand-thumbs-up fs-5';
                    downBtn.classList.remove('active');
                    downBtn.querySelector('i').className = 'bi bi-hand-thumbs-down fs-5';
                }
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(err => console.error('Vote error:', err));
    }
    </script>
</body>
</html>
