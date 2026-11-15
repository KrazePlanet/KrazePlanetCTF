<?php
// auth_api.php - Account actions for the lab pages' JavaScript.
// Sign-in, sign-up and password reset happen on the central login server
// (see config/sso.php); everything here forwards to its API so a student's
// data is the same on every KrazePlanet site.
require_once __DIR__ . '/../config/sso.php';
startKrazeSession();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function reply($res) {
    if (!empty($res['success'])) {
        echo json_encode(['status' => 200] + $res);
    } else {
        echo json_encode(['status' => 400, 'success' => false, 'error' => $res['error'] ?? 'Request failed.']);
    }
    exit;
}

function requireLogin() {
    if (!krazeLoggedIn()) {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Unauthorized.']);
        exit;
    }
}

switch ($action) {
    case 'login':
    case 'signup_send_otp':
    case 'signup_create_account':
    case 'signup_verify_otp':
    case 'forgot_send_otp':
    case 'forgot_reset_password':
        echo json_encode(['status' => 410, 'success' => false, 'error' => 'Sign in at ' . krazeSsoUrl(), 'login_url' => krazeLoginUrl('/')]);
        exit;

    case 'logout':
        if (krazeLoggedIn()) krazeSsoCall('logout');
        krazeLocalLogout();
        // Plain browser navigation also ends the central session.
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Location: /sso/logout.php');
            exit;
        }
        echo json_encode(['status' => 200, 'success' => true, 'message' => 'Logged out successfully.', 'logout_url' => '/sso/logout.php']);
        exit;

    case 'update_avatar':
        requireLogin();
        $res = krazeSsoCall('update_profile', ['avatar' => trim($_POST['avatar_url'] ?? '')]);
        if (!empty($res['success'])) {
            krazeSetSessionUser($res['user']);
            reply(['success' => true, 'avatar_url' => $res['user']['avatar'], 'message' => 'Avatar updated successfully!']);
        }
        reply($res);

    // Profile tab: the GitHub repository is the only thing a student can change there.
    case 'update_github_repo':
        requireLogin();
        $res = krazeSsoCall('update_profile', ['github_repo' => trim((string)($_POST['github_repo'] ?? ''))]);
        if (!empty($res['success'])) {
            krazeSetSessionUser($res['user']);
            reply(['success' => true, 'github_repo' => $res['user']['github_repo'] ?? '']);
        }
        reply($res);

    default:
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid action.']);
        exit;
}
