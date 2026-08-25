<?php
$db_file = __DIR__ . '/hacker101.db';
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
    email TEXT UNIQUE,
    password TEXT,
    points INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS challenges (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    difficulty TEXT,
    points_per_flag INTEGER,
    name TEXT,
    skills TEXT,
    total_flags INTEGER,
    slug TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    challenge_id INTEGER,
    flag_code TEXT UNIQUE,
    description TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    challenge_id INTEGER,
    flag_code TEXT,
    points_awarded INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Seed challenges
$count = $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO challenges (id, difficulty, points_per_flag, name, skills, total_flags, slug) VALUES 
    (1, 'Trivial (1 / flag)', 1, 'A little something to get you started', 'Web', 1, 'trivial-starter'),
    (2, 'Easy (2 / flag)', 2, 'Micro-CMS v1', 'Web', 4, 'micro-cms-v1'),
    (3, 'Moderate (3 / flag)', 3, 'Photo Gallery', 'Web', 3, 'photo-gallery'),
    (4, 'Hard (5 / flag)', 5, 'Encrypted Pastebin', 'Web, Crypto', 2, 'encrypted-pastebin')");

    // Seed flags
    $pdo->exec("INSERT INTO flags (challenge_id, flag_code, description) VALUES 
    (1, 'FLAG^a1b2c3d4e5f6g7h8^', 'Trivial starting flag hidden in HTML comment'),
    (2, 'FLAG^micro_cms_sql_bypass_9824^', 'Micro-CMS SQL Injection'),
    (2, 'FLAG^micro_cms_xss_reflected_112^', 'Micro-CMS Stored XSS'),
    (2, 'FLAG^micro_cms_idor_private_491^', 'Micro-CMS IDOR on unpublished page'),
    (2, 'FLAG^micro_cms_secret_admin_001^', 'Micro-CMS Admin Token Forge'),
    (3, 'FLAG^photo_gallery_traversal_841^', 'Photo Gallery Directory Traversal'),
    (3, 'FLAG^photo_gallery_blind_sql_912^', 'Photo Gallery Blind SQL Injection'),
    (3, 'FLAG^photo_gallery_rce_upload_552^', 'Photo Gallery Remote Code Execution'),
    (4, 'FLAG^pastebin_padding_oracle_771^', 'Pastebin CBC Padding Oracle'),
    (4, 'FLAG^pastebin_type_juggling_882^', 'Pastebin Cryptographic Key Recovery')");
}
