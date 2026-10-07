<?php
// SwiftPay — Digital Wallet Training Lab
// Intentionally vulnerable: negative transfer amount accepted server-side.
const BASE     = '/subdomains/swiftpay';
const SEED_BAL = 50000; // ₹500.00 stored as paise (integer)
const LAB_FLAG = 'KP{negative_transfer_balance_exploit}';

session_set_cookie_params(['path' => BASE, 'httponly' => true, 'samesite' => 'Lax']);
session_name('SWIFTPAYSID');
session_start();

/* ── DATABASE ────────────────────────────────────────────── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = '127.0.0.1'; $user = 'root'; $pass = ''; $name = 'swiftpay_lab';
    $opt  = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false];
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, $opt);
    } catch (PDOException $e) {
        if ((int)$e->getCode() !== 1049) throw $e;
        $root = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, $opt);
        $root->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4");
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, $opt);
        install_schema($pdo);
    }
    return $pdo;
}

function install_schema(PDO $pdo, bool $reset = false): void {
    if ($reset) {
        foreach (['transactions','users'] as $t) $pdo->exec("DROP TABLE IF EXISTS $t");
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id        INT AUTO_INCREMENT PRIMARY KEY,
        name      VARCHAR(100) NOT NULL,
        phone     VARCHAR(20)  NOT NULL UNIQUE,
        email     VARCHAR(190) NOT NULL UNIQUE,
        upi_id    VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        balance   BIGINT NOT NULL DEFAULT 0,
        is_admin  TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Stores amount in paise (integer). Positive = credit, negative = debit.
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        from_user   INT NULL,
        to_user     INT NULL,
        amount      BIGINT NOT NULL,
        type        ENUM('transfer','topup','recharge','bill','reversal') NOT NULL DEFAULT 'transfer',
        description VARCHAR(255),
        ref         VARCHAR(64),
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (from_user) REFERENCES users(id),
        FOREIGN KEY (to_user)   REFERENCES users(id)
    )");

    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $seed = [
            ['Admin','9000000001','admin@swiftpay.app','admin@swiftpay',  'admin123', 1, 500000],
            ['Priya Sharma','9876543210','priya@example.com','priya.sharma@swift', 'priya123', 0, SEED_BAL],
            ['Ravi Kumar','9876543211','ravi@example.com','ravi.kumar@swift',   'ravi123',  0, SEED_BAL],
            ['Demo User','9999999999','demo@swiftpay.lab','demo.user@swift',    'demo123',  0, SEED_BAL],
        ];
        $st = $pdo->prepare("INSERT INTO users (name,phone,email,upi_id,password_hash,is_admin,balance) VALUES (?,?,?,?,?,?,?)");
        foreach ($seed as $s) {
            $st->execute([$s[0],$s[1],$s[2],$s[3],password_hash($s[4],PASSWORD_DEFAULT),$s[5],$s[6]]);
        }
        // Seed some realistic past transactions for admin account
        $admin_id = (int)$pdo->lastInsertId() - 3;
        $pdo->prepare("INSERT INTO transactions (from_user,to_user,amount,type,description,ref) VALUES (?,?,?,?,?,?)")
            ->execute([null, $admin_id, 500000, 'topup', 'Wallet top-up', 'TOPUP'.rand(100000,999999)]);
    }
}

/* ── HELPERS ──────────────────────────────────────────────── */
function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $p = ''): string { return BASE . ($p ? '/' . ltrim($p, '/') : ''); }

function inr(int $paise): string {
    $neg = $paise < 0;
    $abs = abs($paise);
    $rs  = (int)floor($abs / 100);
    $ps  = $abs % 100;
    $s   = (string)$rs;
    if (strlen($s) > 3) $s = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($s, 0, -3)) . ',' . substr($s, -3);
    return ($neg ? '−' : '') . '₹' . $s . '.' . str_pad($ps, 2, '0', STR_PAD_LEFT);
}

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function redirect(string $p): never { header('Location: ' . url($p)); exit; }

function user(): ?array {
    static $u = false;
    if ($u !== false) return $u;
    $u = null;
    if (!empty($_SESSION['uid'])) {
        $st = db()->prepare("SELECT id,name,phone,email,upi_id,balance,is_admin FROM users WHERE id=?");
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function require_login(): array {
    $u = user();
    if (!$u) { flash('error', 'Please sign in to continue.'); redirect('login'); }
    return $u;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? ''))
        { http_response_code(403); die('Invalid CSRF token.'); }
}

function gen_ref(): string {
    return strtoupper(substr(md5(uniqid('', true)), 0, 12));
}

// Reload fresh user data (after balance update)
function fresh_user(): ?array {
    if (empty($_SESSION['uid'])) return null;
    $st = db()->prepare("SELECT id,name,phone,email,upi_id,balance,is_admin FROM users WHERE id=?");
    $st->execute([$_SESSION['uid']]);
    return $st->fetch() ?: null;
}

db(); // auto-init
