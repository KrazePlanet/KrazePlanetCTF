<?php
$db_file = __DIR__ . '/hackerone.db';
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
    reputation INTEGER DEFAULT 120,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS program_teams (
    id TEXT PRIMARY KEY,
    name TEXT,
    handle TEXT,
    bounty_range TEXT,
    description TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS credential_pool (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    team_id TEXT,
    cred_gid TEXT UNIQUE,
    email TEXT,
    password TEXT,
    private_id TEXT,
    claimed_by_user_id INTEGER DEFAULT NULL,
    claimed_at DATETIME DEFAULT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS user_claims (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    team_id TEXT,
    cred_gid TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Seed sample program team
$pdo->exec("INSERT OR IGNORE INTO program_teams (id, name, handle, bounty_range, description) VALUES 
('dGVhbV8xMjk0', 'Shopify Private Staging Program', 'shopify-private', '$500 - $30,000', 'Private testing environment for Shopify merchant checkout, inventory management, and POS integrations.')");

// Seed 30 available test credentials in the pool
$count = $pdo->query("SELECT COUNT(*) FROM credential_pool WHERE team_id = 'dGVhbV8xMjk0'")->fetchColumn();
if ($count == 0) {
    for ($i = 101; $i <= 130; $i++) {
        $gid = "gid://hackerone/Credential/" . (3892000 + $i);
        $email = "h1_tester_{$i}@shopify-sandbox.io";
        $pass = "ShopifyStaging2026!#" . rand(100, 999);
        $priv = "priv_sec_" . substr(md5($i . "salt"), 0, 12);
        $pdo->prepare("INSERT INTO credential_pool (team_id, cred_gid, email, password, private_id) VALUES ('dGVhbV8xMjk0', ?, ?, ?, ?)")
            ->execute([$gid, $email, $pass, $priv]);
    }
}
