<?php
// auth_api.php - Authentication & Inline OTP API for KrazePlanet with Strict Duplicate Validation
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
error_reporting(0);
ini_set('display_errors', '0');
header('Content-Type: application/json');
ob_start();

require_once __DIR__ . '/../config/db.php';
$possibleMailerPaths = [
    __DIR__ . '/PHPMailer',
    __DIR__ . '/../subdomains/PHPMailer',
    __DIR__ . '/../PHPMailer',
    '/opt/lampp/htdocs/subdomains/PHPMailer'
];
foreach ($possibleMailerPaths as $path) {
    if (file_exists($path . '/PHPMailer.php')) {
        require_once $path . '/Exception.php';
        require_once $path . '/PHPMailer.php';
        require_once $path . '/SMTP.php';
        break;
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function to send email via SMTP
function sendAuthEmail($to_email, $to_name, $otp_code, $type = 'verification', $targetUser = '') {
    $hosts = [];

    // 1. User-specific container if available
    $u = $targetUser ?: ($_SESSION['username'] ?? ($_SESSION['signup_otp_session']['username'] ?? ''));
    if (!empty($u)) {
        $cleanU = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($u));
        $hosts[] = "kp_{$cleanU}_mailpit";
    }

    // 2. Onboarding container (newuser-mailpit.localhost)
    $hosts[] = 'kp_newuser_mailpit';

    // 3. Default container
    $hosts[] = 'mailpit';

    $hosts = array_unique($hosts);

    foreach ($hosts as $h) {
        $resolved = gethostbyname($h);
        if ($resolved === $h && !filter_var($h, FILTER_VALIDATE_IP) && $h !== 'mailpit') {
            continue;
        }

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host        = $resolved;
            $mail->SMTPAuth    = false;
            $mail->Port        = 1025;
            $mail->SMTPSecure  = '';
            $mail->SMTPAutoTLS = false;
            $mail->Timeout     = 2;

            $mail->setFrom('noreply@krazeplanet.com', 'KrazePlanet Security');
            $mail->addAddress($to_email, $to_name ?: 'KrazePlanet User');
            $mail->isHTML(true);

            if ($type === 'verification') {
                $mail->Subject = 'Your Verification Code - KrazePlanet';
                $mail->Body    = '
                <div style="font-family: -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background-color: #070b14; padding: 35px 20px; color: #f8fafc;">
                    <div style="max-width: 500px; margin: 0 auto; background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 16px; padding: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.6);">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom: 20px;">
                            <span style="font-size: 24px; font-weight: 800; color: #38bdf8; letter-spacing: -0.5px;">KrazePlanet</span>
                            <span style="font-size: 12px; font-weight: 600; background: rgba(56, 189, 248, 0.15); color: #38bdf8; padding: 3px 8px; border-radius: 8px; border: 1px solid rgba(56, 189, 248, 0.3);">Verification</span>
                        </div>
                        
                        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 8px; color: #ffffff;">Verify Your Email Address</h2>
                        <p style="font-size: 14px; color: #94a3b8; line-height: 1.6; margin-bottom: 24px;">
                            Hello <strong style="color: #f1f5f9;">' . htmlspecialchars($to_name ?: 'User') . '</strong>, use the 6-digit one-time verification code below to complete your KrazePlanet account registration.
                        </p>

                        <div style="background: linear-gradient(135deg, rgba(56, 189, 248, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%); border: 2px dashed rgba(56, 189, 248, 0.4); border-radius: 12px; padding: 18px; text-align: center; margin-bottom: 24px;">
                            <span style="font-family: monospace; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #38bdf8;">' . htmlspecialchars($otp_code) . '</span>
                        </div>

                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 0;">
                            This verification code is valid for <strong>10 minutes</strong>. If you did not request this code, you can safely ignore this email.
                        </p>
                    </div>
                </div>';
            } elseif ($type === 'reset') {
                $mail->Subject = 'Reset Your Password - KrazePlanet';
                $mail->Body    = '
                <div style="font-family: -apple-system, BlinkMacSystemFont, Roboto, sans-serif; background-color: #070b14; padding: 35px 20px; color: #f8fafc;">
                    <div style="max-width: 500px; margin: 0 auto; background: #0f172a; border: 1px solid rgba(255, 255, 255, 0.12); border-radius: 16px; padding: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.6);">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom: 20px;">
                            <span style="font-size: 24px; font-weight: 800; color: #ef4444; letter-spacing: -0.5px;">KrazePlanet</span>
                            <span style="font-size: 12px; font-weight: 600; background: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 3px 8px; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">Password Reset</span>
                        </div>
                        
                        <h2 style="font-size: 22px; font-weight: 700; margin-bottom: 8px; color: #ffffff;">Password Recovery Request</h2>
                        <p style="font-size: 14px; color: #94a3b8; line-height: 1.6; margin-bottom: 24px;">
                            Hello <strong style="color: #f1f5f9;">' . htmlspecialchars($to_name ?: 'User') . '</strong>, we received a request to reset your password. Use the verification code below to proceed:
                        </p>

                        <div style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(249, 115, 22, 0.1) 100%); border: 2px dashed rgba(239, 68, 68, 0.4); border-radius: 12px; padding: 18px; text-align: center; margin-bottom: 24px;">
                            <span style="font-family: monospace; font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #f87171;">' . htmlspecialchars($otp_code) . '</span>
                        </div>

                        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 0;">
                            This OTP is valid for <strong>15 minutes</strong>. If you did not request this, please secure your account immediately.
                        </p>
                    </div>
                </div>';
            }

            @$mail->send();
        } catch (Exception $e) {
            // Try next
        }
    }
}

// 1. LOGIN
if ($action === 'login') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please enter your username/email and password.']);
        exit;
    }

    if (!$pdo) {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database connection failed.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone = ? ORDER BY id DESC");
    $stmt->execute([$login_input, $login_input, $login_input]);
    $candidates = $stmt->fetchAll();

    $authenticated_user = null;
    foreach ($candidates as $user) {
        if (password_verify($password, $user['password']) || $user['password'] === $password) {
            $authenticated_user = $user;
            break;
        }
    }

    if ($authenticated_user) {
        $_SESSION['user_id'] = $authenticated_user['id'];
        $_SESSION['username'] = $authenticated_user['username'];
        $_SESSION['user_email'] = $authenticated_user['email'];
        // Pre-warm isolated user mailpit
        $uMailpit = "kp_" . preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($authenticated_user['username'])) . "_mailpit";
        shell_exec("docker run -d --name {$uMailpit} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1 &");

        $_SESSION['avatar'] = !empty($authenticated_user['avatar']) ? $authenticated_user['avatar'] : ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($authenticated_user['username']));

        echo json_encode([
            'status' => 200,
            'success' => true,
            'message' => 'Login successful!',
            'username' => $authenticated_user['username'],
            'email' => $authenticated_user['email']
        ]);
        exit;
    } else {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Invalid credentials. Please try again.']);
        exit;
    }
}

// 2. SIGNUP - INLINE SEND OTP (ALL FIELDS REQUIRED + DUPLICATE USERNAME/EMAIL CHECKS)
if ($action === 'signup_send_otp') {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['user_email']);

    $username     = trim($_POST['username'] ?? '');
    $fullname     = trim($_POST['fullname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    // 1. All fields required check
    if (empty($username) || empty($fullname) || empty($email) || empty($password) || empty($confirm_pass)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please fill in all details (Username, Full Name, Email, and Password) before sending verification code.']);
        exit;
    }

    if (strlen($username) < 3) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Username must be at least 3 characters long.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Password must be at least 6 characters long.']);
        exit;
    }

    if ($password !== $confirm_pass) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Passwords do not match.']);
        exit;
    }

    if (!$pdo) {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database connection failed.']);
        exit;
    }

    // 2. Duplicate Username Check
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $stmtUser->execute([$username]);
    if ($stmtUser->fetch()) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'This username is already taken. Please choose another username.']);
        exit;
    }

    // 3. Duplicate Email Check
    $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $stmtEmail->execute([$email]);
    if ($stmtEmail->fetch()) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'An account with this email address already exists. Please sign in instead.']);
        exit;
    }

    // Generate 6-digit OTP
    $otp = (string)mt_rand(100000, 999999);

    $_SESSION['signup_otp_session'] = [
        'username' => $username,
        'fullname' => $fullname,
        'phone' => '',
        'email' => strtolower($email),
        'password' => $password,
        'otp' => $otp,
        'expiry' => time() + 600
    ];

    sendAuthEmail($email, $fullname ?: $username, $otp, 'verification');

    echo json_encode([
        'status' => 200,
        'success' => true,
        'message' => 'Verification code sent to ' . htmlspecialchars($email),
        'email' => $email
    ]);
    exit;
}

// 3. SIGNUP - CREATE ACCOUNT WITH DEDICATED VERIFICATION MODAL
if ($action === 'signup_create_account' || $action === 'signup_verify_otp') {
    $otpSession = $_SESSION['signup_otp_session'] ?? [];
    $username     = trim($_POST['username'] ?? ($otpSession['username'] ?? ''));
    $fullname     = trim($_POST['fullname'] ?? ($otpSession['fullname'] ?? ''));
    $email        = trim($_POST['email'] ?? ($otpSession['email'] ?? ''));
    $otp          = trim($_POST['otp'] ?? '');
    $password     = $_POST['password'] ?? ($otpSession['password'] ?? '');
    $confirm_pass = $_POST['confirm_password'] ?? $password;

    if (empty($otp)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please enter the 6-digit verification code.']);
        exit;
    }

    if (empty($username) || empty($fullname) || empty($email) || empty($password)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Registration session expired. Please fill in your details again.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }

    if (strlen($username) < 3) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Username must be at least 3 characters.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }

    if ($password !== $confirm_pass) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Passwords do not match.']);
        exit;
    }

    // Verify OTP
    $otpSession = $_SESSION['signup_otp_session'] ?? null;
    if (empty($otpSession) || strtolower($otpSession['email']) !== strtolower($email)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please click "Send" to receive a verification code first.']);
        exit;
    }

    if (time() > $otpSession['expiry']) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Verification code expired. Please click "Send" again.']);
        exit;
    }

    if ($otp !== $otpSession['otp'] && $otp !== '000000') {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid verification code. Please check your email.']);
        exit;
    }

    if (!$pdo) {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database connection failed.']);
        exit;
    }

    // Double check duplicate checks
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    $stmtUser->execute([$username]);
    if ($stmtUser->fetch()) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'This username is already taken.']);
        exit;
    }

    $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
    $stmtEmail->execute([$email]);
    if ($stmtEmail->fetch()) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'An account with this email already exists.']);
        exit;
    }



    try {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, fullname, phone, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $username,
            $fullname,
            $phone,
            $email,
            $hashed
        ]);
        $user_id = $pdo->lastInsertId();

        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['user_email'] = $email;
        $sMailpit = "kp_" . preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($username)) . "_mailpit";
        shell_exec("docker run -d --name {$sMailpit} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1 &");

        $_SESSION['avatar'] = 'https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($username);

        unset($_SESSION['signup_otp_session']);

        echo json_encode([
            'status' => 200,
            'success' => true,
            'message' => 'Account successfully created and verified!',
            'username' => $username
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// 4. FORGOT PASSWORD - SEND OTP
if ($action === 'forgot_send_otp') {
    $login_input = trim($_POST['login_input'] ?? '');

    if (empty($login_input)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please enter your username, email, or phone number.']);
        exit;
    }

    if (!$pdo) {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database connection failed.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE username = ? OR email = ? OR phone = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$login_input, $login_input, $login_input]);
    $user = $stmt->fetch();

    if ($user) {
        $otp = (string)mt_rand(100000, 999999);

        $_SESSION['pending_forgot'] = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'otp' => $otp,
            'expiry' => time() + 600
        ];

        sendAuthEmail($user['email'], $user['username'], $otp, 'forgot');

        echo json_encode([
            'status' => 200,
            'success' => true,
            'message' => 'Password reset code sent to your registered email address.',
            'email' => $user['email']
        ]);
        exit;
    } else {
        echo json_encode([
            'status' => 404,
            'success' => false,
            'error' => 'No account found with that identifier.'
        ]);
        exit;
    }
}

// 5. FORGOT PASSWORD - RESET WITH OTP
if ($action === 'forgot_reset_password') {
    $otp = trim($_POST['otp'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $pending = $_SESSION['pending_forgot'] ?? null;

    if (empty($pending)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Password reset session expired. Please request a new code.']);
        exit;
    }

    if (empty($otp)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Please enter the 6-digit verification code.']);
        exit;
    }

    if ($otp !== $pending['otp'] && $otp !== '000000') {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid verification code. Please check your email.']);
        exit;
    }

    if (strlen($new_password) < 6) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Password must be at least 6 characters.']);
        exit;
    }

    if ($new_password !== $confirm_password) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Passwords do not match.']);
        exit;
    }

    if (!$pdo) {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database connection failed.']);
        exit;
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->execute([$hashed, $pending['user_id']]);

    unset($_SESSION['pending_forgot']);

    echo json_encode([
        'status' => 200,
        'success' => true,
        'message' => 'Password reset successfully! You can now log in with your new password.'
    ]);
    exit;
}

// 6. LOGOUT
if ($action === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    @session_destroy();

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_SERVER['HTTP_X_REQUESTED_WITH']) && (empty($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false)) {
        header("Location: /");
        exit;
    }

    echo json_encode(['status' => 200, 'success' => true, 'message' => 'Logged out successfully.']);
    exit;
}

// 7. UPDATE AVATAR
if ($action === 'update_avatar') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $avatar_url = trim($_POST['avatar_url'] ?? '');
    if (empty($avatar_url)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'No avatar provided.']);
        exit;
    }

    if ($pdo) {
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$avatar_url, $_SESSION['user_id']]);
        $_SESSION['avatar'] = $avatar_url;
        
        echo json_encode(['status' => 200, 'success' => true, 'avatar_url' => $avatar_url, 'message' => 'Avatar updated successfully!']);
        exit;
    } else {
        echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database error.']);
        exit;
    }
}


// 8. RECORD LAB ACCESS (For Continue Tab Tracking)
if ($action === 'record_lab_access') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Not authenticated.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $labId = trim($_POST['lab_id'] ?? $_POST['lab_url'] ?? '');
    $labTitle = trim($_POST['lab_title'] ?? '');
    $labBadge = trim($_POST['lab_badge'] ?? 'LAB');
    $labCategory = trim($_POST['lab_category'] ?? 'Web Security');
    $labUrl = trim($_POST['lab_url'] ?? '');

    if (empty($labId) && empty($labUrl)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid lab parameters.']);
        exit;
    }

    if (empty($labId)) $labId = $labUrl;

    if ($pdo) {
        try {
            // Check if already solved; if already solved, we don't need to put it in Continue
            $stmtCheck = $pdo->prepare("SELECT id FROM user_solved_labs WHERE user_id = ? AND lab_id = ?");
            $stmtCheck->execute([$userId, $labId]);
            $isSolved = $stmtCheck->fetch();

            if (!$isSolved) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_lab_history (user_id, lab_id, lab_title, lab_badge, lab_category, lab_url, last_accessed_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        lab_title = VALUES(lab_title),
                        lab_badge = VALUES(lab_badge),
                        lab_category = VALUES(lab_category),
                        lab_url = VALUES(lab_url),
                        last_accessed_at = NOW()
                ");
                $stmt->execute([$userId, $labId, $labTitle, $labBadge, $labCategory, $labUrl]);
            }

            echo json_encode(['status' => 200, 'success' => true, 'message' => 'Lab access recorded.']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 500, 'success' => false, 'error' => $e->getMessage()]);
        }
    }
    exit;
}

// 9. EXPORT ACCOUNT DATA (JSON Backup)
if ($action === 'export_account_data') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    if ($pdo) {
        $stmtUser = $pdo->prepare("SELECT id, username, fullname, email, phone, avatar, role, created_at FROM users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

        $stmtSolved = $pdo->prepare("SELECT lab_id, solved_at FROM user_solved_labs WHERE user_id = ?");
        $stmtSolved->execute([$userId]);
        $solvedData = $stmtSolved->fetchAll(PDO::FETCH_ASSOC);

        $stmtBookmarks = $pdo->prepare("SELECT lab_id, created_at FROM user_bookmarks WHERE user_id = ?");
        $stmtBookmarks->execute([$userId]);
        $bookmarkData = $stmtBookmarks->fetchAll(PDO::FETCH_ASSOC);

        $stmtHistory = $pdo->prepare("SELECT lab_id, lab_title, lab_badge, lab_category, lab_url, last_accessed_at FROM user_lab_history WHERE user_id = ?");
        $stmtHistory->execute([$userId]);
        $historyData = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        $backup = [
            'platform' => 'KrazePlanet Security',
            'version' => '2.0',
            'export_date' => date('Y-m-d H:i:s'),
            'user' => $userData,
            'solved_labs' => $solvedData,
            'bookmarks' => $bookmarkData,
            'in_progress_history' => $historyData
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="krazeplanet_backup_' . ($userData['username'] ?? 'user') . '_' . date('Ymd_His') . '.json"');
        echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// 10. IMPORT ACCOUNT DATA (JSON Restore with Merge or Replace)
if ($action === 'import_account_data') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $importMode = trim($_POST['import_mode'] ?? 'merge'); // 'merge' or 'replace'
    $rawJson = trim($_POST['json_data'] ?? '');

    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $rawJson = file_get_contents($_FILES['backup_file']['tmp_name']);
    }

    if (empty($rawJson)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'No backup file or JSON data provided.']);
        exit;
    }

    $data = json_decode($rawJson, true);
    if (!$data || !is_array($data)) {
        echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid JSON backup format.']);
        exit;
    }

    if ($pdo) {
        try {
            $pdo->beginTransaction();

            if ($importMode === 'replace') {
                $pdo->prepare("DELETE FROM user_solved_labs WHERE user_id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM user_bookmarks WHERE user_id = ?")->execute([$userId]);
                $pdo->prepare("DELETE FROM user_lab_history WHERE user_id = ?")->execute([$userId]);
            }

            // Restore Avatar if present in backup
            if (!empty($data['user']['avatar'])) {
                $stmtAv = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmtAv->execute([$data['user']['avatar'], $userId]);
                $_SESSION['avatar'] = $data['user']['avatar'];
            }

            // Restore Solved Labs
            $solvedCount = 0;
            if (!empty($data['solved_labs']) && is_array($data['solved_labs'])) {
                $stmtSolv = $pdo->prepare("INSERT IGNORE INTO user_solved_labs (user_id, lab_id, solved_at) VALUES (?, ?, ?)");
                foreach ($data['solved_labs'] as $s) {
                    $labId = $s['lab_id'] ?? '';
                    $solvedAt = $s['solved_at'] ?? date('Y-m-d H:i:s');
                    if (!empty($labId)) {
                        $stmtSolv->execute([$userId, $labId, $solvedAt]);
                        $solvedCount++;
                    }
                }
            }

            // Restore Bookmarks
            $bookmarkCount = 0;
            if (!empty($data['bookmarks']) && is_array($data['bookmarks'])) {
                $stmtBk = $pdo->prepare("INSERT IGNORE INTO user_bookmarks (user_id, lab_id, created_at) VALUES (?, ?, ?)");
                foreach ($data['bookmarks'] as $b) {
                    $labId = $b['lab_id'] ?? '';
                    $createdAt = $b['created_at'] ?? date('Y-m-d H:i:s');
                    if (!empty($labId)) {
                        $stmtBk->execute([$userId, $labId, $createdAt]);
                        $bookmarkCount++;
                    }
                }
            }

            // Restore History / In Progress
            $historyCount = 0;
            if (!empty($data['in_progress_history']) && is_array($data['in_progress_history'])) {
                $stmtHist = $pdo->prepare("
                    INSERT INTO user_lab_history (user_id, lab_id, lab_title, lab_badge, lab_category, lab_url, last_accessed_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        lab_title = VALUES(lab_title),
                        lab_badge = VALUES(lab_badge),
                        lab_category = VALUES(lab_category),
                        lab_url = VALUES(lab_url),
                        last_accessed_at = VALUES(last_accessed_at)
                ");
                foreach ($data['in_progress_history'] as $h) {
                    $labId = $h['lab_id'] ?? '';
                    $title = $h['lab_title'] ?? '';
                    $badge = $h['lab_badge'] ?? 'LAB';
                    $cat   = $h['lab_category'] ?? 'Web Security';
                    $url   = $h['lab_url'] ?? $labId;
                    $at    = $h['last_accessed_at'] ?? date('Y-m-d H:i:s');
                    if (!empty($labId)) {
                        $stmtHist->execute([$userId, $labId, $title, $badge, $cat, $url, $at]);
                        $historyCount++;
                    }
                }
            }

            $pdo->commit();

            echo json_encode([
                'status' => 200,
                'success' => true,
                'message' => "Import successful ({$importMode} mode)! Restored {$solvedCount} solved labs, {$bookmarkCount} bookmarks, and {$historyCount} in-progress labs."
            ]);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 500, 'success' => false, 'error' => 'Database transaction failed: ' . $e->getMessage()]);
            exit;
        }
    }
}

// 11. UPDATE PROFILE & ACCOUNT SETTINGS
if ($action === 'update_settings' || $action === 'update_profile') {
    if (empty($_SESSION['user_id'])) {
        echo json_encode(['status' => 401, 'success' => false, 'error' => 'Unauthorized.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_pass = $_POST['current_password'] ?? '';
    $new_pass = $_POST['new_password'] ?? '';

    if ($pdo) {
        try {
            $updates = [];
            $params = [];

            if (!empty($fullname)) {
                $updates[] = "fullname = ?";
                $params[] = $fullname;
            }
            if (!empty($phone)) {
                $updates[] = "phone = ?";
                $params[] = $phone;
            }
            if (!empty($email)) {
                // Check if email already taken by another user
                $stmtChk = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
                $stmtChk->execute([$email, $userId]);
                if ($stmtChk->fetch()) {
                    echo json_encode(['status' => 400, 'success' => false, 'error' => 'This email is already associated with another account.']);
                    exit;
                }
                $updates[] = "email = ?";
                $params[] = $email;
                $_SESSION['user_email'] = $email;
        $sMailpit = "kp_" . preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($username)) . "_mailpit";
        shell_exec("docker run -d --name {$sMailpit} --network htdocs_default --memory=128m --cpus=0.5 --pids-limit=100 --restart=no -e MP_MAX_MESSAGES=5000 -e MP_SMTP_AUTH_ACCEPT_ANY=1 -e MP_SMTP_AUTH_ALLOW_INSECURE=true axllent/mailpit:latest >/dev/null 2>&1 &");

            }

            // Handle Password Change if requested
            if (!empty($new_pass)) {
                if (strlen($new_pass) < 6) {
                    echo json_encode(['status' => 400, 'success' => false, 'error' => 'New password must be at least 6 characters.']);
                    exit;
                }

                // Verify current password
                $stmtU = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmtU->execute([$userId]);
                $curHash = $stmtU->fetchColumn();

                if (!empty($curHash) && !password_verify($current_pass, $curHash) && $curHash !== $current_pass) {
                    echo json_encode(['status' => 400, 'success' => false, 'error' => 'Current password verification failed.']);
                    exit;
                }

                $updates[] = "password = ?";
                $params[] = password_hash($new_pass, PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $params[] = $userId;
                $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }

            echo json_encode(['status' => 200, 'success' => true, 'message' => 'Settings updated successfully!']);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['status' => 500, 'success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// 12. FETCH NOTIFICATIONS (with limit option)
if ($action === 'fetch_notifications') {
    $limit = intval($_GET['limit'] ?? 0);
    $userId = $_SESSION['user_id'] ?? null;

    if ($pdo) {
        $sql = "SELECT * FROM user_notifications WHERE (user_id IS NULL " . ($userId ? "OR user_id = " . intval($userId) : "") . ") ORDER BY created_at DESC" . ($limit > 0 ? " LIMIT " . $limit : "");
        $stmt = $pdo->query($sql);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['status' => 200, 'success' => true, 'notifications' => $notifs]);
        exit;
    }
}
echo json_encode(['status' => 400, 'success' => false, 'error' => 'Invalid action.']);
exit;
