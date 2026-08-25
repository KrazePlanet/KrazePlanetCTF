<?php
$db_file = __DIR__ . '/urban.db';
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS definitions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    word TEXT,
    meaning TEXT,
    example TEXT,
    author TEXT,
    thumbs_up INTEGER DEFAULT 0,
    thumbs_down INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    def_id INTEGER,
    vote_type TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Seed realistic Urban Dictionary slang words
$count = $pdo->query("SELECT COUNT(*) FROM definitions")->fetchColumn();
if ($count == 0) {
    $stmt = $pdo->prepare("INSERT INTO definitions (id, word, meaning, example, author, thumbs_up, thumbs_down) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        1,
        'Hangry',
        'When you are so hungry that you become angry, irritable, and lack patience with everyone around you.',
        'Don\'t talk to Sarah before lunch, she gets super hangry and might snap at you.',
        'snack_monster',
        1842,
        120
    ]);
    $stmt->execute([
        2,
        'Doomscrolling',
        'The act of endlessly scrolling through social media feeds and news websites reading negative and distressing news stories.',
        'I spent three hours doomscrolling on my phone last night instead of going to sleep.',
        'night_owl99',
        2430,
        85
    ]);
    $stmt->execute([
        3,
        'Ghosting',
        'The practice of suddenly and completely ending all communication and contact with someone without any explanation or warning.',
        'We went on four great dates and had planned a weekend trip, but then he totally ghosted me.',
        'broken_heart_kid',
        3510,
        142
    ]);
    $stmt->execute([
        4,
        'Adulting',
        'To do grown-up things and hold responsibilities such as having a job, paying your own bills, cooking dinner, and doing laundry.',
        'I paid all my utility bills and cleaned the entire apartment today. I am completely done adulting for the rest of the week.',
        'coffeelover_22',
        1290,
        98
    ]);
    $stmt->execute([
        5,
        'Flex',
        'To boast, show off, or flaunt one\'s wealth, physique, expensive possessions, or achievements in front of others.',
        'Posting photos of his brand new sports car and luxury watch on Instagram was a major flex.',
        'hypebeast_king',
        4100,
        310
    ]);
}
