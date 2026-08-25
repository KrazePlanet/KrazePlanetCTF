<?php
$db_file = __DIR__ . '/codeshack.db';
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
    username TEXT UNIQUE,
    fullname TEXT,
    email TEXT UNIQUE,
    password TEXT,
    otp_code TEXT,
    is_verified INTEGER DEFAULT 0,
    resend_count INTEGER DEFAULT 0,
    last_resend_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
