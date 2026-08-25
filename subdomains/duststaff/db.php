<?php
$db_file = __DIR__ . '/dust.db';
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
    workspace_id TEXT UNIQUE,
    email TEXT UNIQUE,
    password TEXT,
    plan TEXT DEFAULT 'Free Starter',
    folder_limit INTEGER DEFAULT 10,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS spaces (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    space_key TEXT UNIQUE,
    name TEXT,
    space_type TEXT DEFAULT 'restricted',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS folders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    space_id INTEGER,
    name TEXT,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

function seedInitialUserSpace($pdo, $user_id) {
    $space_key = 'vlt_' . substr(bin2hex(random_bytes(6)), 0, 12);
    $pdo->prepare("INSERT INTO spaces (user_id, space_key, name, space_type) VALUES (?, ?, 'Engineering Space', 'restricted')")
        ->execute([$user_id, $space_key]);
    $space_id = $pdo->lastInsertId();

    // Pre-seed 9 folders matching the researcher screenshot F4275950
    $sample_folders = [
        'TEAM-1',
        'TEAM-4',
        'TEAM-5',
        'TEAM-6',
        'TEAM-7',
        'TEAM-8',
        'sdfsdf',
        'zzz',
        'zzzd'
    ];

    $stmt_f = $pdo->prepare("INSERT INTO folders (user_id, space_id, name, description) VALUES (?, ?, ?, 'Internal knowledge repository')");
    foreach ($sample_folders as $fname) {
        $stmt_f->execute([$user_id, $space_id, $fname]);
    }
}
