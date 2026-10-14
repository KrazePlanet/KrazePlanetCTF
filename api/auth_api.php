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

    case 'update_settings':
    case 'update_profile':
        requireLogin();
        if (trim($_POST['new_password'] ?? '') !== '' || (trim($_POST['email'] ?? '') !== '' && strcasecmp(trim($_POST['email']), $_SESSION['user_email'] ?? '') !== 0)) {
            reply(['success' => false, 'error' => 'Email and password are changed on your KrazePlanet account page: ' . krazeSsoAccountUrl()]);
        }
        $res = krazeSsoCall('update_profile', ['fullname' => trim($_POST['fullname'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')]);
        if (!empty($res['success'])) {
            krazeSetSessionUser($res['user']);
            reply(['success' => true, 'message' => 'Settings updated successfully!']);
        }
        reply($res);

    case 'record_lab_access':
        requireLogin();
        $labUrl = trim($_POST['lab_url'] ?? '');
        reply(krazeSsoCall('record_access', [
            'lab_id'       => trim($_POST['lab_id'] ?? '') ?: $labUrl,
            'lab_title'    => trim($_POST['lab_title'] ?? ''),
            'lab_badge'    => trim($_POST['lab_badge'] ?? 'LAB'),
            'lab_category' => trim($_POST['lab_category'] ?? 'Web Security'),
            'lab_url'      => $labUrl,
        ]));

    case 'export_account_data':
        requireLogin();
        $res = krazeSsoCall('export');
        if (empty($res['success'])) reply($res);
        header('Content-Disposition: attachment; filename="krazeplanet_backup_' . preg_replace('/[^A-Za-z0-9_-]/', '', $_SESSION['username'] ?? 'user') . '_' . date('Ymd_His') . '.json"');
        echo json_encode($res['backup'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;

    case 'import_account_data':
        requireLogin();
        $rawJson = trim($_POST['json_data'] ?? '');
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $rawJson = file_get_contents($_FILES['backup_file']['tmp_name']);
        }
        $data = json_decode($rawJson, true);
        if (!is_array($data)) {
            reply(['success' => false, 'error' => 'Invalid JSON backup format.']);
        }
        reply(krazeSsoCall('import', ['mode' => ($_POST['import_mode'] ?? 'merge') === 'replace' ? 'replace' : 'merge', 'data' => $data]));

    case 'fetch_notifications':
        reply(krazeSsoCall('notifications', ['limit' => intval($_GET['limit'] ?? 0)]));

    default:
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid action.']);
        exit;
}
