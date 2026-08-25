<?php
$db_file = __DIR__ . '/omise.db';
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
    company_name TEXT,
    full_name TEXT,
    email TEXT UNIQUE,
    password TEXT,
    account_id TEXT UNIQUE,
    currency TEXT DEFAULT 'USD',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS memberships (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER,
    email TEXT,
    is_admin INTEGER DEFAULT 0,
    is_technical INTEGER DEFAULT 1,
    status TEXT DEFAULT 'pending',
    token TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
