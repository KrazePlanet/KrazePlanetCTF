<?php
session_start();
include('includes/config.php');

$toast_message = '';
$toast_type = '';
$redirect_url = '';

// Active tab ('user' or 'admin')
$login_type = $_POST['login_type'] ?? $_GET['type'] ?? 'user';

if(isset($_POST['login']))
{
    $login_type = $_POST['login_type'] ?? 'user';
    $username_input = $_POST['username'] ?? '';
    $password = md5($_POST['password'] ?? '');

    if($login_type === 'admin')
    {
        // ── Admin Login Logic ───────────────────────────────────────────────
        $sql = "SELECT username, password FROM admin WHERE (username=:email OR email=:email) and password=:password";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $username_input, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        
        if($query->rowCount() > 0)
        {
            $_SESSION['alogin'] = $username_input;
            $toast_message = "Admin Authentication Successful! Loading Dashboard...";
            $toast_type = "success";
            $redirect_url = "admin/dashboard.php";
        } 
        else 
        {
            $toast_message = "Invalid Admin Credentials";
            $toast_type = "error";
        }
    }
    else
    {
        // ── User Login Logic ────────────────────────────────────────────────
        $status = '1';
        $sql = "SELECT email, password, name FROM users WHERE (email=:email OR name=:email) and password=:password and status=(:status)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':email', $username_input, PDO::PARAM_STR);
        $query->bindParam(':password', $password, PDO::PARAM_STR);
        $query->bindParam(':status', $status, PDO::PARAM_STR);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        
        if($query->rowCount() > 0)
        {
            $_SESSION['alogin'] = $username_input;
            $toast_message = "Login Successful! Redirecting to your profile...";
            $toast_type = "success";
            $redirect_url = "profile.php";
        } 
        else 
        {
            $toast_message = "Invalid Details Or Account Not Confirmed";
            $toast_type = "error";
        }
    }
}
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<title>Armentum Portal — User & Admin Login</title>
	
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="css/bootstrap-social.css">
	<link rel="stylesheet" href="css/bootstrap-select.css">
	<link rel="stylesheet" href="css/fileinput.min.css">
	<link rel="stylesheet" href="css/awesome-bootstrap-checkbox.css">
	<link rel="stylesheet" href="css/style.css">

    <style>
        .login-switcher {
            display: flex;
            background: #e2e8f0;
            border-radius: 8px;
            padding: 4px;
            margin-bottom: 24px;
        }
        .switcher-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-weight: 700;
            font-size: 14px;
            border: none;
            background: transparent;
            color: #64748b;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none !important;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .switcher-btn.active {
            background: #ffffff;
            color: #1e3a8a;
            box-shadow: 0 2px 8px rgba(0,0,0,0.12);
        }
        .login-card-title {
            font-weight: 800;
            margin-bottom: 6px;
            color: #0f172a;
        }
        .login-card-subtitle {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
	<div class="login-page bk-img">
		<div class="form-content">
			<div class="container">
				<div class="row">
					<div class="col-md-6 col-md-offset-3">
						
                        <div class="text-center mt-4x mb-2x">
                            <h1 class="login-card-title" id="pageMainTitle">
                                <?php echo $login_type === 'admin' ? 'Admin Portal' : 'User Portal'; ?>
                            </h1>
                            <p class="login-card-subtitle" id="pageSubTitle">
                                <?php echo $login_type === 'admin' ? 'Sign in with your administrative credentials' : 'Sign in to access your employee profile'; ?>
                            </p>
                        </div>

						<div class="well row pt-2x pb-3x bk-light" style="border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
							<div class="col-md-10 col-md-offset-1">
								
                                <!-- ── User Login | Admin Login Segmented Switcher ── -->
                                <div class="login-switcher">
                                    <button type="button" class="switcher-btn <?php echo $login_type !== 'admin' ? 'active' : ''; ?>" id="tabUser" onclick="switchLoginType('user')">
                                        <i class="fa fa-user"></i> User Login
                                    </button>
                                    <button type="button" class="switcher-btn <?php echo $login_type === 'admin' ? 'active' : ''; ?>" id="tabAdmin" onclick="switchLoginType('admin')">
                                        <i class="fa fa-shield"></i> Admin Login
                                    </button>
                                </div>

								<form method="post" id="loginForm">
                                    <input type="hidden" name="login_type" id="inputLoginType" value="<?php echo htmlspecialchars($login_type); ?>">

									<label for="" class="text-uppercase text-sm" id="labelUsername">
                                        <?php echo $login_type === 'admin' ? 'Admin Username / Email' : 'Your Email / Username'; ?>
                                    </label>
									<input type="text" placeholder="<?php echo $login_type === 'admin' ? 'admin' : 'Username or Email'; ?>" name="username" id="inputUsername" class="form-control mb" required>

									<label for="" class="text-uppercase text-sm">Password</label>
									<input type="password" placeholder="Password" name="password" class="form-control mb" required>
									
									<button class="btn btn-primary btn-block" name="login" type="submit" id="btnSubmit" style="font-weight: 700; padding: 11px; font-size: 15px; border-radius: 6px;">
                                        <?php echo $login_type === 'admin' ? 'LOGIN AS ADMIN' : 'LOGIN AS USER'; ?>
                                    </button>
								</form>

								<div id="userFooter" style="<?php echo $login_type === 'admin' ? 'display:none;' : ''; ?>; margin-top: 18px; text-align: center;">
									<p class="text-muted">Don't Have an Account? <a href="register.php" style="font-weight: 700; color: #3b82f6;">Signup</a></p>
								</div>

                                <div id="adminFooter" style="<?php echo $login_type === 'admin' ? '' : 'display:none;'; ?>; margin-top: 18px; text-align: center;">
									<p class="text-muted small"><i class="fa fa-lock text-primary"></i> Default credentials: <code>admin</code> / <code>admin</code></p>
								</div>

							</div>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Loading Scripts -->
	<script src="js/jquery.min.js"></script>
	<script src="js/bootstrap-select.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/jquery.dataTables.min.js"></script>
	<script src="js/dataTables.bootstrap.min.js"></script>
	<script src="js/Chart.min.js"></script>
	<script src="js/fileinput.js"></script>
	<script src="js/chartData.js"></script>
	<script src="js/main.js"></script>

    <!-- Switcher Script -->
    <script>
    function switchLoginType(type) {
        const tabUser = document.getElementById('tabUser');
        const tabAdmin = document.getElementById('tabAdmin');
        const inputType = document.getElementById('inputLoginType');
        const pageTitle = document.getElementById('pageMainTitle');
        const pageSub = document.getElementById('pageSubTitle');
        const labelUser = document.getElementById('labelUsername');
        const inputUser = document.getElementById('inputUsername');
        const btnSubmit = document.getElementById('btnSubmit');
        const userFooter = document.getElementById('userFooter');
        const adminFooter = document.getElementById('adminFooter');

        if(type === 'admin') {
            tabUser.classList.remove('active');
            tabAdmin.classList.add('active');
            inputType.value = 'admin';
            pageTitle.textContent = 'Admin Portal';
            pageSub.textContent = 'Sign in with your administrative credentials';
            labelUser.textContent = 'Admin Username / Email';
            inputUser.placeholder = 'admin';
            btnSubmit.textContent = 'LOGIN AS ADMIN';
            userFooter.style.display = 'none';
            adminFooter.style.display = 'block';
        } else {
            tabAdmin.classList.remove('active');
            tabUser.classList.add('active');
            inputType.value = 'user';
            pageTitle.textContent = 'User Portal';
            pageSub.textContent = 'Sign in to access your employee profile';
            labelUser.textContent = 'Your Email / Username';
            inputUser.placeholder = 'Username or Email';
            btnSubmit.textContent = 'LOGIN AS USER';
            userFooter.style.display = 'block';
            adminFooter.style.display = 'none';
        }
    }
    </script>

    <!-- Toast Notification System -->
    <style>
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
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
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
    .custom-toast.toast-info {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #ffffff;
        border: 1px solid #60a5fa;
    }
    .toast-icon {
        font-size: 20px;
        display: flex;
        align-items: center;
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

    <div class="custom-toast-container" id="toastContainer"></div>

    <script>
    function showToast(message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `custom-toast toast-${type}`;
        
        let iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        if (type === 'error') iconClass = 'fa-exclamation-triangle';

        toast.innerHTML = `
            <span class="toast-icon"><i class="fa ${iconClass}"></i></span>
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
