<?php
// auth_api.php - Account actions for the lab pages' JavaScript.
// Sign-in, sign-up and password reset happen on the central login server
// (see config/sso.php); everything here forwards to its API so a student's
// data is the same on every KrazePlanet site.
require_once __DIR__ . '/../config/sso.php';
require_once __DIR__ . '/../config/labs.php';
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

    // Settings tab: the student's GitHub username. Every report repository has the same
    // name (KrazePlanet-Reports), so the link is always github.com/<username>/KrazePlanet-Reports.
    case 'update_github_repo':
        requireLogin();
        $user = krazeGithubUsername((string)($_POST['github_username'] ?? ''));
        if ($user === null) {
            reply(['success' => false, 'error' => 'Enter your GitHub username, for example: octocat']);
        }
        $repo = $user === '' ? '' : krazeReportsRepoUrl($user);
        $res = krazeSsoCall('update_profile', ['github_repo' => $repo]);
        if (!empty($res['success'])) {
            krazeSetSessionUser($res['user']);
            reply(['success' => true, 'github_repo' => $res['user']['github_repo'] ?? '', 'github_username' => $user,
                   'repo_found' => $user === '' ? null : krazeRepoExists($user)]);
        }
        reply($res);

    // Settings tab: X (Twitter) and LinkedIn, shown on the public profile.
    // The login server cleans them up (a pasted link becomes just the handle).
    case 'update_socials':
        requireLogin();
        $res = krazeSsoCall('update_profile', [
            'x'        => trim((string)($_POST['x'] ?? '')),
            'linkedin' => trim((string)($_POST['linkedin'] ?? '')),
        ]);
        if (!empty($res['success'])) {
            reply(['success' => true, 'x' => $res['user']['x'] ?? '', 'linkedin' => $res['user']['linkedin'] ?? '']);
        }
        reply($res);

    default:
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid action.']);
        exit;
}
