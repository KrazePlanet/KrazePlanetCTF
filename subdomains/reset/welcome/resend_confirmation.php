<?php
session_start();
require_once __DIR__ . '/../mailer.php';

$is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false) ||
           (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) ||
           isset($_GET['api']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';

    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? $_SESSION['business_email'] ?? 'owner@artisanbakery.com');
    } else {
        $email = trim($_POST['email'] ?? $_SESSION['business_email'] ?? 'owner@artisanbakery.com');
    }

    if (!empty($email)) {
        // Send real confirmation email via SMTP without rate limiting (HackerOne #774050)
        sendYelpConfirmationEmail($email, $_SESSION['business_name'] ?? 'Artisan Bakery & Cafe');

        if ($is_json) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Confirmation email has been resent successfully.',
                'email' => $email
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: ../index.php?sent=1");
            exit;
        }
    } else {
        if ($is_json) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Email is required.']);
            exit;
        } else {
            header("Location: ../index.php");
            exit;
        }
    }
} else {
    header("Location: ../index.php");
    exit;
}
