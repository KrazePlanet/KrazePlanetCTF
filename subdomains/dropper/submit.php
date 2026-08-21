<?php
session_start();
$user_id = $_SESSION['h101_user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/db.php';

$is_json_request = isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false;
$wants_json_resp = (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'json') !== false) || isset($_GET['api']);

if (empty($user_id)) {
    if ($wants_json_resp || $is_json_request) {
        http_response_code(401);
        echo json_encode(['status' => 401, 'error' => 'Please sign in to submit flags.']);
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
}

$user_id = (int)$user_id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flag_input = '';
    if ($is_json_request) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $flag_input = trim($data['flag'] ?? $data['flag_code'] ?? '');
    } else {
        $flag_input = trim($_POST['flag'] ?? $_POST['flag_code'] ?? '');
    }

    if (empty($flag_input)) {
        if ($wants_json_resp || $is_json_request) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Flag code is required.']);
            exit;
        } else {
            header("Location: index.php?error=empty_flag");
            exit;
        }
    }

    // Check if flag is valid
    $stmt_flag = $pdo->prepare("SELECT f.*, c.points_per_flag, c.id as c_id, c.name as c_name FROM flags f JOIN challenges c ON f.challenge_id = c.id WHERE f.flag_code = ?");
    $stmt_flag->execute([$flag_input]);
    $flag_row = $stmt_flag->fetch(PDO::FETCH_ASSOC);

    if (!$flag_row) {
        if ($wants_json_resp || $is_json_request) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'error' => 'Invalid flag! Please try again.']);
            exit;
        } else {
            header("Location: index.php?error=invalid_flag");
            exit;
        }
    }

    // 1. Vulnerable check before race delay window
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ? AND flag_code = ?");
    $stmt_check->execute([$user_id, $flag_input]);
    $already_submitted = ((int)$stmt_check->fetchColumn() > 0);

    if (!$already_submitted) {
        // Race window delay: 120ms (simulating async scoring and verification engine)
        usleep(120000);

        // 2. Perform write with cross-process mutex lock to guarantee zero SQLite crash
        $lock_file = sys_get_temp_dir() . '/h101_flag_mutex.lock';
        $lock_fp = fopen($lock_file, 'w+');
        flock($lock_fp, LOCK_EX);

        $db_conn = new PDO('sqlite:' . __DIR__ . '/hacker101.db');
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pts = (int)$flag_row['points_per_flag'];
        $c_id = (int)$flag_row['c_id'];

        $stmt_ins = $db_conn->prepare("INSERT INTO submissions (user_id, challenge_id, flag_code, points_awarded) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$user_id, $c_id, $flag_input, $pts]);
        $sub_id = $db_conn->lastInsertId();

        $db_conn->exec("UPDATE users SET points = points + {$pts} WHERE id = {$user_id}");

        // Fetch fresh totals
        $stmt_u = $db_conn->prepare("SELECT points FROM users WHERE id = ?");
        $stmt_u->execute([$user_id]);
        $fresh_points = (int)$stmt_u->fetchColumn();

        $stmt_cc = $db_conn->prepare("SELECT COUNT(*) FROM submissions WHERE user_id = ? AND challenge_id = ?");
        $stmt_cc->execute([$user_id, $c_id]);
        $challenge_completions = (int)$stmt_cc->fetchColumn();

        flock($lock_fp, LOCK_UN);
        fclose($lock_fp);

        $invitations = floor($fresh_points / 26);
        $next_pts = $fresh_points % 26;

        if ($wants_json_resp || $is_json_request) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => 'Congratulations, you found a flag!',
                'submission_id' => $sub_id,
                'challenge_completions' => $challenge_completions,
                'total_points' => $fresh_points,
                'invitations' => $invitations
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: index.php?congrats=many");
            exit;
        }
    } else {
        if ($wants_json_resp || $is_json_request) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => 400,
                'success' => false,
                'error' => 'You have already submitted this flag.'
            ], JSON_PRETTY_PRINT);
            exit;
        } else {
            header("Location: index.php?error=already_submitted");
            exit;
        }
    }
} else {
    header("Location: index.php");
    exit;
}
