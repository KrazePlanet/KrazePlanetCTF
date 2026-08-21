<?php
session_start();
$pending_id = $_SESSION['pending_user_id'] ?? $_SESSION['auth_user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($pending_id)) {
    http_response_code(401);
    echo json_encode(['status' => 401, 'error' => 'No active verification session. Please sign in or register.']);
    exit;
}

$user_id = (int)$pending_id;

$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$user = $stmt_u->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 404, 'error' => 'User not found.']);
    exit;
}

// 1. Vulnerable check before race delay window
$current_resends = (int)$user['resend_count'];

if ($current_resends < 3) {
    // Race window delay: 150ms (simulating SMTP handshake & verification dispatcher)
    usleep(150000);

    // 2. Perform write with cross-process mutex lock to guarantee zero SQLite crash
    $lock_file = sys_get_temp_dir() . '/codeshack_otp_mutex.lock';
    $lock_fp = fopen($lock_file, 'w+');
    flock($lock_fp, LOCK_EX);

    $db_conn = new PDO('sqlite:' . __DIR__ . '/codeshack.db');
    $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $new_otp = (string)mt_rand(100000, 999999);

    $stmt_upd = $db_conn->prepare("UPDATE users SET otp_code = ?, resend_count = resend_count + 1, last_resend_time = datetime('now') WHERE id = ?");
    $stmt_upd->execute([$new_otp, $user_id]);

    $stmt_fresh = $db_conn->prepare("SELECT resend_count, email, username FROM users WHERE id = ?");
    $stmt_fresh->execute([$user_id]);
    $fresh_user = $stmt_fresh->fetch(PDO::FETCH_ASSOC);
    $total_resends = (int)$fresh_user['resend_count'];

    flock($lock_fp, LOCK_UN);
    fclose($lock_fp);

    // Send the live email
    sendVerificationOTP($fresh_user['email'], $fresh_user['username'], $new_otp);

    $remaining = max(0, 3 - $total_resends);

    echo json_encode([
        'status' => 200,
        'success' => true,
        'message' => 'A new 6-digit verification code has been sent to your email.',
        'resend_count' => $total_resends,
        'remaining_resends' => $remaining,
        'cooldown_seconds' => 30
    ], JSON_PRETTY_PRINT);
    exit;
} else {
    http_response_code(429);
    echo json_encode([
        'status' => 429,
        'success' => false,
        'error' => 'Maximum OTP resend limit reached (3 of 3). You cannot request more OTPs.'
    ], JSON_PRETTY_PRINT);
    exit;
}
