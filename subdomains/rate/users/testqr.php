<?php
session_start();

$valid_2fa_code = '5800';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

$submitted_code = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $submitted_code = trim($data['users']['gauth_token'] ?? $data['gauth_token'] ?? $data['code'] ?? '');
    } else {
        $submitted_code = trim($_POST['users']['gauth_token'] ?? $_POST['gauth_token'] ?? $_POST['code'] ?? '');
    }

    // No rate limiting on 2FA code verification (HackerOne #128777)
    if ($submitted_code === $valid_2fa_code) {
        $_SESSION['authenticated'] = true;
        $_SESSION['user'] = 'developer@enterprise.io';
        $_SESSION['2fa_verified'] = true;

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => '2FA verification successful.',
                'redirect' => 'dashboard.php'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            // 302 Found redirect to dashboard (matching HackerOne Burp Intruder 302 status!)
            header("Location: ../dashboard.php");
            exit;
        }
    } else {
        if ($is_json) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 401,
                'success' => false,
                'error' => 'Invalid 2FA verification code.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            // Redirect back with error (302 Found)
            header("Location: ../index.php?error=invalid_code");
            exit;
        }
    }
} else {
    header("Location: ../index.php");
    exit;
}
