<?php
$db_file = __DIR__ . '/../gifts.db';
if (file_exists($db_file)) { @chmod($db_file, 0666); }
@chmod(__DIR__, 0777);
$pdo = new PDO('sqlite:' . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRFToken, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$author = trim($data['author'] ?? $data['username'] ?? 'AnonymousRedditor');
$comment_text = trim($data['comment'] ?? $data['body'] ?? $data['text'] ?? '');

if (empty($author)) {
    $author = 'Redditor_' . rand(1000, 9999);
}

if (!empty($comment_text)) {
    // No rate limit enforced (HackerOne #1202408)
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, author, comment) VALUES (1, ?, ?)");
    $stmt->execute([$author, $comment_text]);
    $new_id = $pdo->lastInsertId();

    http_response_code(200);
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
    http_response_code(400);
    echo json_encode([
        'status' => 400,
        'success' => false,
        'error' => 'Comment body cannot be empty.'
    ], JSON_PRETTY_PRINT);
    exit;
}
