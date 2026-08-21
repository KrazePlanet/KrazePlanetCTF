<?php
session_start();
$user_id = $_SESSION['urban_user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['status' => 401, 'error' => 'Please sign in to vote on definitions.']);
    exit;
}

$user_id = (int)$user_id;

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false);
if ($is_json) {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: [];
    $def_id = (int)($data['defid'] ?? $data['def_id'] ?? 0);
    $direction = strtolower(trim($data['direction'] ?? ''));
} else {
    $def_id = (int)($_POST['defid'] ?? $_POST['def_id'] ?? $_GET['defid'] ?? 0);
    $direction = strtolower(trim($_POST['direction'] ?? $_GET['direction'] ?? ''));
}

if ($def_id <= 0 || !in_array($direction, ['up', 'down'])) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'error' => 'Invalid parameters. Specify defid and direction (up/down).']);
    exit;
}

// 1. Vulnerable check before race delay window
$stmt_vote = $pdo->prepare("SELECT vote_type FROM votes WHERE user_id = ? AND def_id = ? ORDER BY id DESC LIMIT 1");
$stmt_vote->execute([$user_id, $def_id]);
$current_vote = $stmt_vote->fetchColumn(); // 'up', 'down', or false

// Race window delay: 120ms
usleep(120000);

// 2. Perform write with cross-process mutex lock to guarantee zero SQLite crash
$lock_file = sys_get_temp_dir() . '/urban_vote_mutex.lock';
$lock_fp = fopen($lock_file, 'w+');
flock($lock_fp, LOCK_EX);

$db_conn = new PDO('sqlite:' . __DIR__ . '/urban.db');
$db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user_active_vote = null;

if ($direction === 'up') {
    if ($current_vote === 'down') {
        // Flipping from down to up: Decrement downvotes, Increment upvotes
        $db_conn->exec("UPDATE definitions SET thumbs_down = thumbs_down - 1, thumbs_up = thumbs_up + 1 WHERE id = {$def_id}");
        $db_conn->prepare("INSERT INTO votes (user_id, def_id, vote_type) VALUES (?, ?, 'up')")->execute([$user_id, $def_id]);
        $user_active_vote = 'up';
    } elseif ($current_vote === 'up') {
        // Already voted up: toggle off
        $db_conn->exec("UPDATE definitions SET thumbs_up = thumbs_up - 1 WHERE id = {$def_id}");
        $db_conn->prepare("DELETE FROM votes WHERE user_id = ? AND def_id = ?")->execute([$user_id, $def_id]);
        $user_active_vote = null;
    } else {
        // First vote: increment upvotes
        $db_conn->exec("UPDATE definitions SET thumbs_up = thumbs_up + 1 WHERE id = {$def_id}");
        $db_conn->prepare("INSERT INTO votes (user_id, def_id, vote_type) VALUES (?, ?, 'up')")->execute([$user_id, $def_id]);
        $user_active_vote = 'up';
    }
} else { // direction === 'down'
    if ($current_vote === 'up') {
        // Flipping from up to down: Decrement upvotes, Increment downvotes
        $db_conn->exec("UPDATE definitions SET thumbs_up = thumbs_up - 1, thumbs_down = thumbs_down + 1 WHERE id = {$def_id}");
        $db_conn->prepare("INSERT INTO votes (user_id, def_id, vote_type) VALUES (?, ?, 'down')")->execute([$user_id, $def_id]);
        $user_active_vote = 'down';
    } elseif ($current_vote === 'down') {
        // Already voted down: toggle off
        $db_conn->exec("UPDATE definitions SET thumbs_down = thumbs_down - 1 WHERE id = {$def_id}");
        $db_conn->prepare("DELETE FROM votes WHERE user_id = ? AND def_id = ?")->execute([$user_id, $def_id]);
        $user_active_vote = null;
    } else {
        // First vote: increment downvotes
        $db_conn->exec("UPDATE definitions SET thumbs_down = thumbs_down + 1 WHERE id = {$def_id}");
        $db_conn->prepare("INSERT INTO votes (user_id, def_id, vote_type) VALUES (?, ?, 'down')")->execute([$user_id, $def_id]);
        $user_active_vote = 'down';
    }
}

// Fetch fresh counts
$stmt_fresh = $db_conn->prepare("SELECT thumbs_up, thumbs_down FROM definitions WHERE id = ?");
$stmt_fresh->execute([$def_id]);
$counts = $stmt_fresh->fetch(PDO::FETCH_ASSOC);

flock($lock_fp, LOCK_UN);
fclose($lock_fp);

echo json_encode([
    'status' => 200,
    'success' => true,
    'defid' => $def_id,
    'direction' => $direction,
    'user_vote' => $user_active_vote,
    'up' => (int)$counts['thumbs_up'],
    'down' => (int)$counts['thumbs_down']
], JSON_PRETTY_PRINT);
exit;
