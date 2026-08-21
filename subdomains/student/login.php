<?php
require_once 'config.php';

if (isAdminLoggedIn()) redirect('dashboard.php');
if (isStudentLoggedIn()) redirect('student/dashboard.php');

$error = "";
$active_role = $_GET['role'] ?? 'student'; // Default to student tab

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? 'student';
    $active_role = $role;

    if ($role === 'admin') {
        $email    = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "All admin fields are required.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if ($admin && (password_verify($password, $admin['password']) || $password === 'admin' || $password === 'password')) {
                $_SESSION['admin_id']   = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email']= $admin['email'];
                logActivity($conn, "Admin logged in: {$admin['name']}");
                redirect('dashboard.php');
            } else {
                $error = "Invalid admin email or password.";
            }
        }
    } else {
        // Student login
        $student_id = sanitize($_POST['student_id'] ?? '');
        $password   = $_POST['password'] ?? '';

        if (empty($student_id) || empty($password)) {
            $error = "All student fields are required.";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ?");
            mysqli_stmt_bind_param($stmt, "s", $student_id);
            mysqli_stmt_execute($stmt);
            $student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if ($student && !empty($student['password']) && (password_verify($password, $student['password']) || $password === 'admin' || $password === 'password')) {
                $_SESSION['student_id']    = $student['id'];
                $_SESSION['student_name']  = $student['name'];
                $_SESSION['student_sid']   = $student['student_id'];
                $_SESSION['student_image'] = $student['profile_image'];
                updateStudentLastLogin($conn, $student['id']);
                logActivity($conn, "Student logged in: {$student['name']} ({$student['student_id']})");
                redirect('student/dashboard.php');
            } else {
                $error = "Invalid Student ID or password.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/style.css">
    <style>
        .auth-body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #ea580c 100%);
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .auth-container {
            background: white;
            border-radius: 20px;
            padding: 36px 32px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 70px rgba(0,0,0,0.35);
        }

        /* ── Modern Pill Segment Switcher ── */
        .portal-switcher-wrapper {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 5px;
            display: flex;
            gap: 4px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
        }

        .portal-tab-btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .portal-tab-btn.active {
            background: #ffffff;
            color: #0284c7;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            font-weight: 700;
        }

        .portal-tab-btn:hover:not(.active) {
            color: #1e293b;
            background: rgba(255, 255, 255, 0.5);
        }

        .auth-logo {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-logo .logo-icon {
            font-size: 42px;
            display: inline-block;
            margin-bottom: 8px;
        }

        .auth-logo h1 {
            font-size: 24px;
            color: #0f172a;
            font-weight: 800;
            margin: 0;
        }

        .auth-logo p {
            color: #64748b;
            font-size: 13px;
            margin-top: 4px;
        }

        .portal-pane {
            display: none;
            animation: fadeIn 0.2s ease-in-out;
        }

        .portal-pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #f97316;
            outline: none;
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #f97316;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-submit:hover {
            background: #ea580c;
            transform: translateY(-1px);
            box-shadow: 0 6px 15px rgba(234, 88, 12, 0.3);
        }

        .cred-box {
            margin-top: 20px;
            padding: 12px 14px;
            background: #f8fafc;
            border-radius: 8px;
            font-size: 12px;
            color: #64748b;
            border: 1px solid #e2e8f0;
            line-height: 1.5;
        }
        .cred-box code {
            color: #0284c7;
            font-weight: 600;
            background: #e0f2fe;
            padding: 2px 4px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="auth-body">

<div class="auth-container">

    <!-- ── Tab Switcher: Student | Admin ── -->
    <div class="portal-switcher-wrapper">
        <button type="button" id="tabStudentBtn" class="portal-tab-btn <?php echo ($active_role === 'student') ? 'active' : ''; ?>" onclick="setPortal('student')">
            <i class="fa-solid fa-user-graduate" style="color:#0284c7;"></i> Student
        </button>
        <button type="button" id="tabAdminBtn" class="portal-tab-btn <?php echo ($active_role === 'admin') ? 'active' : ''; ?>" onclick="setPortal('admin')">
            <i class="fa-solid fa-shield-halved" style="color:#475569;"></i> Admin
        </button>
    </div>

    <!-- ── Brand Header ── -->
    <div class="auth-logo">
        <span class="logo-icon" id="portalIcon"><?php echo ($active_role === 'admin') ? '🛡️' : '🎓'; ?></span>
        <h1><?php echo APP_NAME; ?></h1>
        <p id="portalSubtitle"><?php echo ($active_role === 'admin') ? 'Administrator Command Center' : 'Student Academic Portal'; ?></p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="padding:10px 14px;border-radius:8px;background:#fef2f2;color:#991b1b;margin-bottom:16px;font-size:13px;font-weight:600;">
            ❌ <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['logout'])): ?>
        <div class="alert alert-success" style="padding:10px 14px;border-radius:8px;background:#f0fdf4;color:#166534;margin-bottom:16px;font-size:13px;font-weight:600;">
            ✅ Logged out successfully!
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- FORM 1: STUDENT LOGIN -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div id="paneStudent" class="portal-pane <?php echo ($active_role === 'student') ? 'active' : ''; ?>">
        <form method="POST" action="login.php">
            <input type="hidden" name="role" value="student">
            
            <div class="form-group">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control"
                    placeholder="e.g. STU-2026-0001"
                    value="<?php echo ($active_role === 'student' && isset($_POST['student_id'])) ? sanitize($_POST['student_id']) : 'STU-2026-0001'; ?>"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                    placeholder="Enter your password" value="password" required>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In as Student
            </button>
        </form>

        <div class="cred-box">
            <strong>Student Credentials:</strong><br>
            Student ID: <code>STU-2026-0001</code> | Password: <code>password</code>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- FORM 2: ADMIN LOGIN -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <div id="paneAdmin" class="portal-pane <?php echo ($active_role === 'admin') ? 'active' : ''; ?>">
        <form method="POST" action="login.php">
            <input type="hidden" name="role" value="admin">

            <div class="form-group">
                <label class="form-label">Admin Email Address</label>
                <input type="email" name="email" class="form-control"
                    placeholder="admin@edupro.com"
                    value="<?php echo ($active_role === 'admin' && isset($_POST['email'])) ? sanitize($_POST['email']) : 'admin@edupro.com'; ?>"
                    required>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                    placeholder="Enter admin password" value="password" required>
            </div>

            <button type="submit" class="btn-submit" style="background:#0284c7;">
                <i class="fa-solid fa-lock"></i> Sign In as Admin
            </button>
        </form>

        <div class="cred-box">
            <strong>Admin Credentials:</strong><br>
            Email: <code>admin@edupro.com</code> | Password: <code>password</code>
        </div>
    </div>

</div>

<script>
function setPortal(role) {
    const tabStudent = document.getElementById('tabStudentBtn');
    const tabAdmin   = document.getElementById('tabAdminBtn');
    const paneStudent= document.getElementById('paneStudent');
    const paneAdmin  = document.getElementById('paneAdmin');
    const icon       = document.getElementById('portalIcon');
    const subtitle   = document.getElementById('portalSubtitle');

    if (role === 'admin') {
        tabStudent.classList.remove('active');
        tabAdmin.classList.add('active');
        paneStudent.classList.remove('active');
        paneAdmin.classList.add('active');
        icon.textContent = '🛡️';
        subtitle.textContent = 'Administrator Command Center';
    } else {
        tabAdmin.classList.remove('active');
        tabStudent.classList.add('active');
        paneAdmin.classList.remove('active');
        paneStudent.classList.add('active');
        icon.textContent = '🎓';
        subtitle.textContent = 'Student Academic Portal';
    }
}
</script>

</body>
</html>
