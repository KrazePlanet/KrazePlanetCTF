<?php
$db_file = __DIR__ . '/slack_app.db';
if (file_exists($db_file)) { @chmod($db_file, 0666); }
@chmod(__DIR__, 0777);
$pdo = new PDO('sqlite:' . $db_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
$pdo->exec("PRAGMA journal_mode=WAL;");
$pdo->exec("PRAGMA busy_timeout=30000;");
$pdo->exec("PRAGMA synchronous=NORMAL;");

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    workspace_name TEXT,
    subdomain TEXT UNIQUE,
    email TEXT UNIQUE,
    password TEXT,
    credits INTEGER DEFAULT 0,
    survey_completed INTEGER DEFAULT 0,
    plan TEXT DEFAULT 'Free',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS billing_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    date_str TEXT,
    item TEXT,
    amount INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    channel TEXT,
    sender_name TEXT,
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

function executeWithRetry($pdo, $callback, $max_attempts = 25) {
    for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
        try {
            return $callback($pdo);
        } catch (PDOException $e) {
            if ($attempt === $max_attempts) {
                throw $e;
            }
            usleep(rand(20000, 50000)); // 20-50ms random backoff
        }
    }
}
