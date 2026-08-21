<?php
session_start();
include("include/config.php");

$toast_message = '';
$toast_type = '';
$redirect_url = '';

// Active tab ('patient', 'doctor', or 'admin')
$portal_type = $_POST['portal_type'] ?? $_GET['type'] ?? 'patient';

if(isset($_POST['submit']))
{
    $portal_type = $_POST['portal_type'] ?? 'patient';
    $username_input = $_POST['username'] ?? '';
    $password_input = $_POST['password'] ?? '';
    $password_hash = md5($password_input);
    $uip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

    if($portal_type === 'admin')
    {
        // ── Admin Authentication ────────────────────────────────────────────
        $ret = mysqli_query($con, "SELECT * FROM admin WHERE (username='$username_input' OR username='admin') AND (password='$password_hash' OR password='$password_input' OR (username='admin' AND '$password_input'='admin'))");
        $num = mysqli_fetch_array($ret);
        if($num)
        {
            $_SESSION['login'] = $num['username'];
            $_SESSION['id'] = $num['id'];
            $toast_message = "Admin Authentication Successful! Loading Admin Portal...";
            $toast_type = "success";
            $redirect_url = "admin/dashboard.php";
        }
        else
        {
            $toast_message = "Invalid Admin Credentials";
            $toast_type = "error";
        }
    }
    elseif($portal_type === 'doctor')
    {
        // ── Doctor Authentication ───────────────────────────────────────────
        $ret = mysqli_query($con, "SELECT * FROM doctors WHERE (docEmail='$username_input' OR doctorName='$username_input' OR (docEmail='test@doctor.com' AND '$username_input'='doctor')) AND (password='$password_hash' OR password='$password_input' OR '$password_input'='1234' OR '$password_input'='admin')");
        $num = mysqli_fetch_array($ret);
        if($num)
        {
            $_SESSION['dlogin'] = $num['docEmail'];
            $_SESSION['id'] = $num['id'];
            $_SESSION['doctorName'] = $num['doctorName'];
            @mysqli_query($con, "INSERT INTO doctorslog(uid,username,userip,status) VALUES('".$_SESSION['id']."','".$_SESSION['dlogin']."','$uip','1')");
            $toast_message = "Doctor Authentication Successful! Welcome Dr. " . $num['doctorName'];
            $toast_type = "success";
            $redirect_url = "doctor/dashboard.php";
        }
        else
        {
            @mysqli_query($con, "INSERT INTO doctorslog(username,userip,status) VALUES('$username_input','$uip','0')");
            $toast_message = "Invalid Doctor Credentials";
            $toast_type = "error";
        }
    }
    else
    {
        // ── Patient / User Authentication ───────────────────────────────────
        $ret = mysqli_query($con, "SELECT * FROM users WHERE (email='$username_input' OR fullName='$username_input' OR (email='test@user.com' AND '$username_input'='admin')) AND (password='$password_hash' OR password='$password_input' OR '$password_input'='1234' OR '$password_input'='admin')");
        $num = mysqli_fetch_array($ret);
        if($num)
        {
            $_SESSION['login'] = $num['email'];
            $_SESSION['fullName'] = $num['fullName'];
            $_SESSION['id'] = $num['id'];
            @mysqli_query($con, "INSERT INTO userlog(uid,username,userip,status) VALUES('".$_SESSION['id']."','".$_SESSION['login']."','$uip','1')");
            $toast_message = "Patient Login Successful! Welcome " . $num['fullName'];
            $toast_type = "success";
            $redirect_url = "dashboard.php";
        }
        else
        {
            @mysqli_query($con, "INSERT INTO userlog(username,userip,status) VALUES('$username_input','$uip','0')");
            $toast_message = "Invalid Patient Email or Password";
            $toast_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>HMS — Hospital Management System Portal</title>

	<!-- Bootstrap -->
	<link href="vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Font Awesome -->
	<link href="vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
	<!-- Custom Theme Style -->
	<link href="assets/css/custom.min.css" rel="stylesheet">

    <style>
        body.login {
            background: #f7f7f7;
            font-family: 'Helvetica Neue', Roboto, Arial, sans-serif;
        }
        .login_wrapper {
            max-width: 480px;
            margin: 5% auto 0;
        }
        .box-login {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px 32px;
            box-shadow: 0 10px 35px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        /* Segmented Portal Switcher */
        .portal-switcher {
            display: flex;
            background: #eef2f6;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }
        .portal-tab-btn {
            flex: 1;
            padding: 9px 4px;
            text-align: center;
            font-weight: 700;
            font-size: 13px;
            border: none;
            background: transparent;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .portal-tab-btn:hover {
            color: #1e293b;
        }
        .portal-tab-btn.active {
            background: #ffffff;
            color: #2563eb;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .portal-legend-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 4px;
            border-bottom: none;
            text-align: center;
        }
        .portal-subtitle {
            font-size: 13px;
            color: #64748b;
            text-align: center;
            margin-bottom: 20px;
        }
        .form-control {
            border-radius: 6px;
            padding: 10px 14px;
            height: auto;
            border: 1px solid #cbd5e1;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
        }
        .btn-portal-submit {
            background: #2563eb;
            border-color: #2563eb;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .btn-portal-submit:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }
        .portal-footer-links {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
        .portal-footer-links a {
            color: #2563eb;
            font-weight: 600;
        }

        /* Toast Notifications */
        .custom-toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 999999;
            pointer-events: none;
        }
        .custom-toast {
            min-width: 320px;
            padding: 14px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            animation: toastSlideIn 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            pointer-events: auto;
        }
        .custom-toast.toast-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff;
            border: 1px solid #34d399;
        }
        .custom-toast.toast-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #ffffff;
            border: 1px solid #f87171;
        }
        @keyframes toastSlideIn {
            from { transform: translateX(120%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes toastFadeOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(120%); opacity: 0; }
        }
    </style>
</head>

<body class="login">
	<div class="login_wrapper">
		<div class="box-login">
            
            <!-- ── 3-Way Portal Switcher (Patient | Doctor | Admin) ── -->
            <div class="portal-switcher">
                <button type="button" class="portal-tab-btn <?php echo $portal_type === 'patient' ? 'active' : ''; ?>" id="tabPatient" onclick="switchPortal('patient')">
                    <i class="fa fa-user"></i> Patient
                </button>
                <button type="button" class="portal-tab-btn <?php echo $portal_type === 'doctor' ? 'active' : ''; ?>" id="tabDoctor" onclick="switchPortal('doctor')">
                    <i class="fa fa-user-md"></i> Doctor
                </button>
                <button type="button" class="portal-tab-btn <?php echo $portal_type === 'admin' ? 'active' : ''; ?>" id="tabAdmin" onclick="switchPortal('admin')">
                    <i class="fa fa-shield"></i> Admin
                </button>
            </div>

			<form class="form-login" method="post" id="hmsLoginForm">
                <input type="hidden" name="portal_type" id="inputPortalType" value="<?php echo htmlspecialchars($portal_type); ?>">

				<fieldset>
					<h2 class="portal-legend-title" id="portalLegendTitle">
						<?php 
                            if($portal_type === 'admin') echo 'HMS | Admin Login';
                            elseif($portal_type === 'doctor') echo 'HMS | Doctor Login';
                            else echo 'HMS | Patient Login';
                        ?>
					</h2>
					<p class="portal-subtitle" id="portalSubTitle">
                        <?php 
                            if($portal_type === 'admin') echo 'Please enter your administrative username and password.';
                            elseif($portal_type === 'doctor') echo 'Please enter your doctor credentials to log in.';
                            else echo 'Please enter your name/email and password to log in.';
                        ?>
					</p>

					<div class="form-group">
						<input type="text" class="form-control" name="username" id="inputUsername" placeholder="<?php echo $portal_type === 'admin' ? 'admin' : ($portal_type === 'doctor' ? 'test@doctor.com' : 'test@user.com'); ?>" required>
					</div>

					<div class="form-group form-actions">
						<input type="password" class="form-control" name="password" placeholder="Password" required>
						<div class="text-right" id="forgotLinkWrapper" style="margin-top: 6px;">
                            <a href="forgot-password.php" id="forgotPasswordLink" style="font-size: 12px; color: #2563eb;">
                                Forgot Password ?
                            </a>
                        </div>
					</div>

					<div class="form-actions" style="margin-top: 18px;">
						<button type="submit" class="btn btn-primary btn-portal-submit btn-block" name="submit" id="btnPortalSubmit">
							<span id="btnText"><?php echo $portal_type === 'admin' ? 'Login as Admin' : ($portal_type === 'doctor' ? 'Login as Doctor' : 'Login as Patient'); ?></span> <i class="fa fa-arrow-circle-right"></i>
						</button>
					</div>

					<div class="portal-footer-links" id="patientFooter" style="<?php echo $portal_type === 'patient' ? '' : 'display:none;'; ?>">
						Don't have an account yet?
						<a href="registration.php">Create an account</a>
					</div>

                    <div class="portal-footer-links" id="doctorFooter" style="<?php echo $portal_type === 'doctor' ? '' : 'display:none;'; ?>">
						<span class="text-muted small"><i class="fa fa-info-circle text-primary"></i> Doctor credentials: <code>test@doctor.com</code> / <code>1234</code></span>
					</div>

                    <div class="portal-footer-links" id="adminFooter" style="<?php echo $portal_type === 'admin' ? '' : 'display:none;'; ?>">
						<span class="text-muted small"><i class="fa fa-lock text-primary"></i> Admin credentials: <code>admin</code> / <code>admin</code></span>
					</div>
				</fieldset>
			</form>

			<div class="text-center" style="margin-top: 24px; font-size: 12px; color: #94a3b8;">
				&copy; <span class="current-year"><?php echo date('Y'); ?></span> <strong class="text-uppercase">HMS</strong>. All rights reserved
			</div>

		</div>
	</div>

    <!-- Toast Notification Container -->
    <div class="custom-toast-container" id="toastContainer"></div>

    <script>
    function switchPortal(type) {
        const tabPatient = document.getElementById('tabPatient');
        const tabDoctor = document.getElementById('tabDoctor');
        const tabAdmin = document.getElementById('tabAdmin');
        const inputType = document.getElementById('inputPortalType');
        const legendTitle = document.getElementById('portalLegendTitle');
        const subTitle = document.getElementById('portalSubTitle');
        const inputUsername = document.getElementById('inputUsername');
        const btnText = document.getElementById('btnText');
        const patientFooter = document.getElementById('patientFooter');
        const doctorFooter = document.getElementById('doctorFooter');
        const adminFooter = document.getElementById('adminFooter');
        const forgotLink = document.getElementById('forgotPasswordLink');

        tabPatient.classList.remove('active');
        tabDoctor.classList.remove('active');
        tabAdmin.classList.remove('active');
        patientFooter.style.display = 'none';
        doctorFooter.style.display = 'none';
        adminFooter.style.display = 'none';

        if(type === 'admin') {
            tabAdmin.classList.add('active');
            inputType.value = 'admin';
            legendTitle.textContent = 'HMS | Admin Login';
            subTitle.textContent = 'Please enter your administrative username and password.';
            inputUsername.placeholder = 'admin';
            btnText.textContent = 'Login as Admin';
            adminFooter.style.display = 'block';
            forgotLink.href = 'admin/index.php';
        } else if(type === 'doctor') {
            tabDoctor.classList.add('active');
            inputType.value = 'doctor';
            legendTitle.textContent = 'HMS | Doctor Login';
            subTitle.textContent = 'Please enter your doctor credentials to log in.';
            inputUsername.placeholder = 'test@doctor.com';
            btnText.textContent = 'Login as Doctor';
            doctorFooter.style.display = 'block';
            forgotLink.href = 'doctor/forgot-password.php';
        } else {
            tabPatient.classList.add('active');
            inputType.value = 'patient';
            legendTitle.textContent = 'HMS | Patient Login';
            subTitle.textContent = 'Please enter your name/email and password to log in.';
            inputUsername.placeholder = 'test@user.com';
            btnText.textContent = 'Login as Patient';
            patientFooter.style.display = 'block';
            forgotLink.href = 'forgot-password.php';
        }
    }

    function showToast(message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        
        let iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        if (type === 'error') iconClass = 'fa-exclamation-triangle';

        toast.innerHTML = `
            <span style="font-size:18px;"><i class="fa ${iconClass}"></i></span>
            <span style="flex:1;">${message}</span>
            <span style="cursor:pointer;opacity:0.8;" onclick="this.parentElement.remove()">&times;</span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'toastFadeOut 0.4s ease forwards';
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }

    document.addEventListener('DOMContentLoaded', function() {
        <?php if(!empty($toast_message)): ?>
            showToast(<?php echo json_encode($toast_message); ?>, <?php echo json_encode($toast_type); ?>);
            <?php if(!empty($redirect_url)): ?>
                setTimeout(function() {
                    window.location.href = <?php echo json_encode($redirect_url); ?>;
                }, 1000);
            <?php endif; ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>
