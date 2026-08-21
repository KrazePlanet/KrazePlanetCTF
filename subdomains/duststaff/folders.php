<?php
session_start();
$user_id = $_SESSION['dust_user_id'] ?? null;
session_write_close(); // Release PHP session lock for true concurrency

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['status' => 401, 'error' => 'Authentication required. Please sign in.']);
    exit;
}

$user_id = (int)$user_id;

// Handle DELETE folder
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_GET['action']) && $_GET['action'] === 'delete') || (isset($_POST['_method']) && $_POST['_method'] === 'DELETE')) {
    $folder_id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($folder_id > 0) {
        $pdo->prepare("DELETE FROM folders WHERE id = ? AND user_id = ?")->execute([$folder_id, $user_id]);
        echo json_encode(['status' => 200, 'success' => true, 'message' => 'Folder deleted successfully.']);
        exit;
    }
}

// Handle POST create folder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_json = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'json') !== false);
    if ($is_json) {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: [];
        $name = trim($data['name'] ?? '');
        $desc = trim($data['description'] ?? '');
        $space_id = (int)($data['space_id'] ?? 0);
    } else {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $space_id = (int)($_POST['space_id'] ?? 0);
    }

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'error' => 'Folder name is required.']);
        exit;
    }

    // Get default space if not provided
    if ($space_id <= 0) {
        $stmt_s = $pdo->prepare("SELECT id FROM spaces WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt_s->execute([$user_id]);
        $space_id = (int)$stmt_s->fetchColumn();
    }

    // 1. TOCTOU Vulnerable Check: Check if user has reached the 10-folder limit
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM folders WHERE user_id = ? AND space_id = ?");
    $stmt_count->execute([$user_id, $space_id]);
    $current_count = (int)$stmt_count->fetchColumn();

    if ($current_count < 10) {
        // Race window delay: 120ms (simulating async vector indexing and permission allocation)
        usleep(120000);

        // 2. Perform write with cross-process mutex lock to guarantee concurrency without database crash
        $lock_file = sys_get_temp_dir() . '/dust_folder_mutex.lock';
        $lock_fp = fopen($lock_file, 'w+');
        flock($lock_fp, LOCK_EX);

        $db_conn = new PDO('sqlite:' . __DIR__ . '/../dust.db');
        $db_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt_ins = $db_conn->prepare("INSERT INTO folders (user_id, space_id, name, description) VALUES (?, ?, ?, ?)");
        $stmt_ins->execute([$user_id, $space_id, $name, $desc]);
        $folder_id = $db_conn->lastInsertId();

        $stmt_total = $db_conn->prepare("SELECT COUNT(*) FROM folders WHERE user_id = ? AND space_id = ?");
        $stmt_total->execute([$user_id, $space_id]);
        $new_total = (int)$stmt_total->fetchColumn();

        flock($lock_fp, LOCK_UN);
        fclose($lock_fp);

        echo json_encode([
            'status' => 200,
            'success' => true,
            'message' => 'Folder created successfully.',
            'folder' => [
                'id' => $folder_id,
                'name' => $name,
                'description' => $desc,
                'total_folders' => $new_total
            ]
        ], JSON_PRETTY_PRINT);
        exit;
    } else {
        // 1:1 Error matching HackerOne Screenshot F4275950
        http_response_code(403);
        echo json_encode([
            'status' => 403,
            'success' => false,
            'error' => 'Error creating Folder: Error: Your plan does not allow you to create more data sources.'
        ], JSON_PRETTY_PRINT);
        exit;
    }
}
