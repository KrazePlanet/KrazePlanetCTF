<?php
session_start();

$valid_otp = '51000';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

$submitted_otp = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $submitted_otp = trim($data['otp'] ?? $data['code'] ?? '');
        $phone = trim($data['phone'] ?? '08031234567');
    } else {
        $submitted_otp = trim($_POST['otp'] ?? $_POST['code'] ?? '');
        $phone = trim($_POST['phone'] ?? '08031234567');
    }

    // No rate limiting on 5-digit OTP brute forcing (HackerOne #1060541)
    if ($submitted_otp === $valid_otp) {
        $_SESSION['nin_verified'] = true;
        $_SESSION['phone'] = $phone;

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'NIN linked successfully to your MTN line.',
                'redirect' => 'dashboard.php'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: ../dashboard.php");
            exit;
        }
    } else {
        if ($is_json) {
            // 303 See Other / 401 Unauthorized matching HackerOne Burp Intruder 303 response!
            http_response_code(303);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 303,
                'success' => false,
                'error' => 'Invalid OTP code. Try again.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            // 303 See Other redirect back to index.php (exact status code reported by HackerOne tester!)
            http_response_code(303);
            header("Location: ../index.php?error=invalid_otp");
            exit;
        }
    }
} else {
    header("Location: ../index.php");
    exit;
}
