<?php
session_start();
$owner_id = $_SESSION['omise_user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mailer.php';

$is_json_request = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false;
$wants_json_resp = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) || isset($_GET['api']);

if (empty($owner_id)) {
    if ($wants_json_resp || $is_json_request) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'error' => 'Authentication required. Please sign in.']);
        exit;
    } else {
        header("Location: ../login.php");
        exit;
    }
}

$owner_id = (int)$owner_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = '';
    $is_admin = 0;
    $is_technical = 0;

    if ($is_json_request) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $email = trim($data['email'] ?? '');
        $is_admin = !empty($data['admin']) || (!empty($data['membership']['admin']) && $data['membership']['admin'] == '1') ? 1 : 0;
        $is_technical = !empty($data['technical']) || (!empty($data['membership']['technical']) && $data['membership']['technical'] == '1') ? 1 : 0;
    } else {
        $email = trim($_POST['email'] ?? '');
        $is_admin = isset($_POST['membership']['admin']) && $_POST['membership']['admin'] == '1' ? 1 : 0;
        $is_technical = isset($_POST['membership']['technical']) && $_POST['membership']['technical'] == '1' ? 1 : 0;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        if ($wants_json_resp || $is_json_request) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 400, 'error' => 'Valid email address is required.']);
            exit;
        } else {
            header("Location: ../index.php?error=invalid_email");
            exit;
        }
    }

    // 1. Vulnerable check before race delay window
    $stmt_check = $pdo->prepare("SELECT id FROM memberships WHERE owner_id = ? AND email = ?");
    $stmt_check->execute([$owner_id, $email]);
    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        // Race window delay: 120ms (simulating async background permission checks & token generation)
        usleep(120000);

        // 2. Perform write with cross-process mutex lock to guarantee concurrency without database crash
        $lock_file = sys_get_temp_dir() . '/omise_team_mutex.lock';
        $lock_fp = fopen($lock_file, 'w+');
        flock($lock_fp, LOCK_EX);

        $db_conn = new PDO('sqlite:' . __DIR__ . '/../omise.db');
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $token = bin2hex(random_bytes(16));
        $stmt_ins = $db_conn->prepare("INSERT INTO memberships (owner_id, email, is_admin, is_technical, status, token) VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt_ins->execute([$owner_id, $email, $is_admin, $is_technical, $token]);
        $member_id = $db_conn->lastInsertId();

        flock($lock_fp, LOCK_UN);
        fclose($lock_fp);

        // Send real SMTP invitation email
        $roles = [];
        if ($is_admin) $roles[] = 'Admin';
        if ($is_technical) $roles[] = 'Technical';
        if (empty($roles)) $roles[] = 'Member';
        sendOmiseInvitationEmail($email, $roles);

        if ($wants_json_resp || $is_json_request) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => "Invitation sent to {$email}.",
                'membership_id' => $member_id
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: ../index.php?invited=1");
            exit;
        }
    } else {
        if ($wants_json_resp || $is_json_request) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 422,
                'success' => false,
                'error' => 'User has already been invited to this team.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: ../index.php?error=already_invited");
            exit;
        }
    }
} else {
    header("Location: ../index.php");
    exit;
}
