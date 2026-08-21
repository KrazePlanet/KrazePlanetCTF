<?php
include('includes/config.php');

$toast_message = '';
$toast_type = '';
$redirect_url = '';

if(isset($_POST['submit']))
{
    $file = $_FILES['image']['name'] ?? '';
    $file_loc = $_FILES['image']['tmp_name'] ?? '';
    $folder = "images/"; 
    if(!is_dir($folder)) {
        @mkdir($folder, 0777, true);
    }
    $new_file_name = strtolower($file);
    $final_file = str_replace(' ','-',$new_file_name);

    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = md5($_POST['password'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $mobileno = $_POST['mobileno'] ?? '';
    $designation = $_POST['designation'] ?? '';

    $image = 'default.jpg';
    if(!empty($file_loc) && is_uploaded_file($file_loc))
    {
        if(move_uploaded_file($file_loc, $folder . $final_file))
        {
            $image = $final_file;
        }
    }
    $notitype = 'Create Account';
    $reciver = 'Admin';
    $sender = $email;

    $sqlnoti = "insert into notification (notiuser,notireciver,notitype) values (:notiuser,:notireciver,:notitype)";
    $querynoti = $dbh->prepare($sqlnoti);
    $querynoti->bindParam(':notiuser', $sender, PDO::PARAM_STR);
    $querynoti->bindParam(':notireciver', $reciver, PDO::PARAM_STR);
    $querynoti->bindParam(':notitype', $notitype, PDO::PARAM_STR);
    $querynoti->execute();    
        
    $sql = "INSERT INTO users(name,email, password, gender, mobile, designation, image, status) VALUES(:name, :email, :password, :gender, :mobileno, :designation, :image, 1)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->bindParam(':password', $password, PDO::PARAM_STR);
    $query->bindParam(':gender', $gender, PDO::PARAM_STR);
    $query->bindParam(':mobileno', $mobileno, PDO::PARAM_STR);
    $query->bindParam(':designation', $designation, PDO::PARAM_STR);
    $query->bindParam(':image', $image, PDO::PARAM_STR);
    $query->execute();
    $lastInsertId = $dbh->lastInsertId();

    if($lastInsertId)
    {
        $toast_message = "Registration Successful! Redirecting to login...";
        $toast_type = "success";
        $redirect_url = "index.php";
    }
    else 
    {
        $toast_message = "Something went wrong during registration. Please try again.";
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
    <script type="text/javascript">

	function validate()
        {
            var extensions = new Array("jpg","jpeg");
            var image_file = document.regform.image.value;
            var image_length = document.regform.image.value.length;
            var pos = image_file.lastIndexOf('.') + 1;
            var ext = image_file.substring(pos, image_length);
            var final_ext = ext.toLowerCase();
            for (i = 0; i < extensions.length; i++)
            {
                if(extensions[i] == final_ext)
                {
                return true;
                
                }
            }
            alert("Image Extension Not Valid (Use Jpg,jpeg)");
            return false;
        }
        
</script>
</head>

<body>
	<div class="login-page bk-img">
		<div class="form-content">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
						<h1 class="text-center text-bold mt-2x">Register</h1>
                        <div class="hr-dashed"></div>
						<div class="well row pt-2x pb-3x bk-light text-center">
                         <form method="post" class="form-horizontal" enctype="multipart/form-data" name="regform" onSubmit="return validate();">
                            <div class="form-group">
                            <label class="col-sm-1 control-label">Name<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <input type="text" name="name" class="form-control" required>
                            </div>
                            <label class="col-sm-1 control-label">Email<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <input type="text" name="email" class="form-control" required>
                            </div>
                            </div>

                            <div class="form-group">
                            <label class="col-sm-1 control-label">Password<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <input type="password" name="password" class="form-control" id="password" required >
                            </div>

                            <label class="col-sm-1 control-label">Designation<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <input type="text" name="designation" class="form-control" required>
                            </div>
                            </div>

                             <div class="form-group">
                            <label class="col-sm-1 control-label">Gender<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <select name="gender" class="form-control" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            </select>
                            </div>

                            <label class="col-sm-1 control-label">Phone<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <input type="number" name="mobileno" class="form-control" required>
                            </div>
                            </div>

                             <div class="form-group">
                            <label class="col-sm-1 control-label">Avtar<span style="color:red">*</span></label>
                            <div class="col-sm-5">
                            <div><input type="file" name="image" class="form-control"></div>
                            </div>
                            </div>

								<br>
                                <button class="btn btn-primary" name="submit" type="submit">Register</button>
                                </form>
                                <br>
                                <br>
								<p>Already Have Account? <a href="index.php" >Signin</a></p>
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
            }, 1200);
        <?php endif; ?>
    <?php endif; ?>
});
</script>

</body>
</html>