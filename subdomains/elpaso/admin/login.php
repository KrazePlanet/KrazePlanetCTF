<?php
	session_start();
	include 'connect.php';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <title>El Paso - Login</title>
    <link rel="icon" href="../img/logo.svg">
    <style>
    </style>
</head>
<body>
    <div class="login-box">
        <form method="POST" class="login_form" action="">
            <div class="user-box">
                <input type="text" id="username" name="username" class="form-control" required="">
                <label>Nutzername</label>
            </div>
            <div class="user-box">
                <input type="password" id="password" name="password" class="form-control" required="">
                <label>Passwort</label>
            </div>

            <div class="label"><input type="submit" name="login" class="login" id="login" value="Anmelden" /></div>

            <?php
            if (isset($_POST['login'])) {
                $user = $_POST['username'];
                $pass = $_POST['password'];
                $sql = "SELECT username, password FROM admin WHERE username = ?";
                $stmt = mysqli_stmt_init($dbc);
                if (mysqli_stmt_prepare($stmt, $sql)) {
                    mysqli_stmt_bind_param($stmt, 's', $user);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                }
                mysqli_stmt_bind_result($stmt, $userName, $userPass);
                mysqli_stmt_fetch($stmt);

                if (password_verify($_POST['password'], $userPass) && mysqli_stmt_num_rows($stmt) > 0) {
                    $login_success = true;
                    $_SESSION['username'] = $userName;
                    header('Location: index.php');
                } else {
                    $login_success = false;
                }
            }
            ?>
        </form>
    </div> 
</body>
</html>
