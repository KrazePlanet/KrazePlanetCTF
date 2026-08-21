<?php
session_start();
include('includes/config.php');

$toast_message = '';
$toast_type = '';
$redirect_url = '';

if(isset($_POST['login']))
{
    $email = $_POST['username'] ?? '';
    $password = md5($_POST['password'] ?? '');
    
    $sql = "SELECT username, password FROM admin WHERE (username=:email OR email=:email) and password=:password";
    $query = $dbh->prepare($sql);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);
    
    if($query->rowCount() > 0)
    {
        $_SESSION['alogin'] = $email;
        $toast_message = "Admin Authentication Successful! Loading Dashboard...";
        $toast_type = "success";
        $redirect_url = "dashboard.php";
    } 
    else 
    {
        $toast_message = "Invalid Admin Credentials";
        $toast_type = "error";
    }
}
?>
<!doctype html>
<html lang="en" class="no-js">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
	<meta name="description" content="">
	<meta name="author" content="">

	
	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/dataTables.bootstrap.min.css">
	<link rel="stylesheet" href="css/bootstrap-social.css">
	<link rel="stylesheet" href="css/bootstrap-select.css">
	<link rel="stylesheet" href="css/fileinput.min.css">
	<link rel="stylesheet" href="css/awesome-bootstrap-checkbox.css">
	<link rel="stylesheet" href="css/style.css">
</head>

<body>
	<div class="login-page bk-img" style="background-image: url(img/background.jpg);">
		<div class="form-content">
			<div class="container">
				<div class="row">
					<div class="col-md-6 col-md-offset-3">
						<h1 class="text-center text-bold mt-4x">Admin Login</h1>
						<div class="well row pt-2x pb-3x bk-light">
							<div class="col-md-8 col-md-offset-2">
								<form method="post">

									<label for="" class="text-uppercase text-sm">Your Username </label>
									<input type="text" placeholder="Username" name="username" class="form-control mb" required>

									<label for="" class="text-uppercase text-sm">Password</label>
									<input type="password" placeholder="Password" name="password" class="form-control mb" required>
									<button class="btn btn-primary btn-block" name="login" type="submit">LOGIN</button>
								</form>
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
</script>

<script>
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