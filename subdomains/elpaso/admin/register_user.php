<?php
	session_start();
	include 'connect.php';
?>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
	<title>Benutzerregistrierung - El Paso</title>
    <link rel="icon" href="../img/logo.svg">
</head>
<body>
    <?php
        if(!isset($_SESSION['username'])) {
            header('Location: login.php');
        }
    ?>

    <section id="left-bar">
		<h2>EL PASO</h2>
		<h3>MANAGEMENT SYSTEM</h3>
		<a href="index.php">Home</a>
		<a href="reservations.php">Reservations</a>
		<a href="reservation_check.php">Pending reservations</a>
        <a href="content.php">Content</a>
        <a href="register_user.php">Register</a>
		<a href="logout.php">Logout</a>
	</section>

    <form method="POST" action="" name="reg" class="reg_form">
        <input name="name" id="name" type="text" class="form_name" placeholder="Name" required/>
            <br/><br/>

        <input name="username" id="username" type="text" class="form_username" placeholder="Username" required/>
            <br/><br/>

        <input name="password" id="password" type="password" class="pass" placeholder="Password" required/>
            <br/><input type="checkbox" class="showpass" onclick="myFunction1()">Show password
        <br/><br/>

            <input name="password" id="passwordRep" type="password" class="pass pass_mes" placeholder="Repeat password" required/>
            <br/><input type="checkbox" class="showpass" onclick="myFunction2()">Show password
            <br/><span id="porukaPass" class="bojaPoruke"></span>
        <br/>

        <input name="submit" type="submit" class="prijava" id="prijava" value="Registrieren" />
    </form>

        <?php
        if ($dbc && isset($_POST['submit'])) {
            $name = $_POST['name'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $hashed_password = password_hash($password, CRYPT_BLOWFISH);
            $register = false;

            $sql = "SELECT * FROM admin WHERE username = ?";
            $stmt = mysqli_stmt_init($dbc);
            if (mysqli_stmt_prepare($stmt, $sql)) {
                mysqli_stmt_bind_param($stmt, 's', $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
            }
            if (mysqli_stmt_num_rows($stmt) > 0) {
                echo "<p style='color:#EA212D;font-weight:bold;padding-bottom:10px'>Korisničko ime već postoji!</p>";
            } else {
                $sql = "INSERT INTO admin (name, username, password) VALUES (?, ?, ?)";
                $stmt = mysqli_stmt_init($dbc);
                if (mysqli_stmt_prepare($stmt, $sql)) {
                    mysqli_stmt_bind_param($stmt, 'sss', $name, $username, $hashed_password);
                    mysqli_stmt_execute($stmt);
                    $register = true;
                }
            }

            if($register == true) {
                echo '<p style="padding-bottom: 10px">Korisnik je uspješno registriran!</p>';
                header("refresh:5;url=../login.php");
            }
        }
        mysqli_close($dbc);
        ?>

  <script src="../js/reg.js"></script>
</body>
</html>
