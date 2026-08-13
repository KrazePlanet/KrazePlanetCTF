<?php
// portal.php - Authentication & Lab Progress Sync API
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/includes/db.php';

if (!$pdo) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . ($db_error ?? 'Unknown error')
    ]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Helper to get current user state
function getUserState($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return [
            'loggedIn' => false,
            'username' => null,
            'solvedLabs' => [],
            'bookmarkedLabs' => []
        ];
    }

    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? '';

    // Fetch solved labs
    $stmt = $pdo->prepare("SELECT lab_id FROM user_solved_labs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $solved = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch bookmarked labs
    $stmt2 = $pdo->prepare("SELECT lab_id FROM user_bookmarks WHERE user_id = ?");
    $stmt2->execute([$userId]);
    $bookmarked = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    return [
        'loggedIn' => true,
        'username' => $username,
        'solvedLabs' => $solved,
        'bookmarkedLabs' => $bookmarked
    ];
}

switch ($action) {

    case 'get_state':
        echo json_encode([
            'success' => true,
            'data' => getUserState($pdo)
        ]);
        exit;

    case 'signup':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters.']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit;
        }

        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit;
        }

        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or Email is already taken.']);
            exit;
        }

        // Hash password and insert
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        
        try {
            $stmt->execute([$username, $email, $hashed]);
            $userId = $pdo->lastInsertId();

            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;

            echo json_encode([
                'success' => true,
                'message' => 'Account created successfully!',
                'data' => getUserState($pdo)
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to create user account.']);
        }
        exit;

    case 'login':
        $login_input = trim($_POST['login_input'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login_input) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your username/email and password.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid username/email or password.']);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        echo json_encode([
            'success' => true,
            'message' => 'Logged in successfully!',
            'data' => getUserState($pdo)
        ]);
        exit;

    case 'logout':
        session_unset();
        session_destroy();
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
        exit;

    case 'toggle_solved':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'requireLogin' => true, 'message' => 'Please login to track lab progress.']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $labId = trim($_POST['lab_id'] ?? '');

        if (empty($labId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid lab ID.']);
            exit;
        }

        // Check if already solved
        $stmt = $pdo->prepare("SELECT id FROM user_solved_labs WHERE user_id = ? AND lab_id = ?");
        $stmt->execute([$userId, $labId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Delete / mark unsolved
            $del = $pdo->prepare("DELETE FROM user_solved_labs WHERE user_id = ? AND lab_id = ?");
            $del->execute([$userId, $labId]);
            $isSolved = false;
        } else {
            // Insert / mark solved
            $ins = $pdo->prepare("INSERT INTO user_solved_labs (user_id, lab_id) VALUES (?, ?)");
            $ins->execute([$userId, $labId]);
            $isSolved = true;
        }

        echo json_encode([
            'success' => true,
            'isSolved' => $isSolved,
            'data' => getUserState($pdo)
        ]);
        exit;

    case 'toggle_bookmark':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'requireLogin' => true, 'message' => 'Please login to bookmark labs.']);
            exit;
        }

        $userId = $_SESSION['user_id'];
        $labId = trim($_POST['lab_id'] ?? '');

        if (empty($labId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid lab ID.']);
            exit;
        }

        // Check if already bookmarked
        $stmt = $pdo->prepare("SELECT id FROM user_bookmarks WHERE user_id = ? AND lab_id = ?");
        $stmt->execute([$userId, $labId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Remove bookmark
            $del = $pdo->prepare("DELETE FROM user_bookmarks WHERE user_id = ? AND lab_id = ?");
            $del->execute([$userId, $labId]);
            $isBookmarked = false;
        } else {
            // Add bookmark
            $ins = $pdo->prepare("INSERT INTO user_bookmarks (user_id, lab_id) VALUES (?, ?)");
            $ins->execute([$userId, $labId]);
            $isBookmarked = true;
        }

        echo json_encode([
            'success' => true,
            'isBookmarked' => $isBookmarked,
            'data' => getUserState($pdo)
        ]);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);
        exit;
}
