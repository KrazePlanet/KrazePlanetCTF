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
    <title>Admin - El Paso</title>
    <link rel="icon" href="../img/logo.svg">
    <style>
    </style>
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
        <hr/>
        <h4>USER: <?php echo $_SESSION['username']; ?></h4>
        <hr/>
		<a href="index.php">Home</a>
		<a href="reservations.php">Reservations</a>
		<a href="reservation_check.php">Pending reservations</a>
        <a href="content.php">Content</a>
        <a href="register_user.php">Register</a>
		<a href="logout.php">Logout</a>
	</section>

    <div class="reservation_status">
        <h1>RESERVATIONS MADE FOR TODAY:</h1>
        <h2>
            <?php 
            if ($dbc) {
                $counter = 0;
                $query = "SELECT * FROM reservations WHERE date = CURDATE()";
                $result = mysqli_query($dbc,$query);
                if($result) {
                    while($row = mysqli_fetch_array($result)) {
                        $counter = $counter + 1;
                    }
                echo $counter;
                }
            }
            ?>
        </h2>
        <br/><br/><h1><a href="reservation_check.php">PENDING RESERVATIONS:</a></h1>
        <h2>
            <?php 
            if ($dbc) {
                $counter = 0;
                $query = "SELECT * FROM reservations_pending WHERE date >= CURDATE()";
                $result = mysqli_query($dbc,$query);
                if($result) {
                    while($row = mysqli_fetch_array($result)) {
                        $counter = $counter + 1;
                    }
                echo $counter;
                }
            }
            ?>
        </h2>
    </div>

    <div class="revenue_status">
        <h1>THIS MONTH'S REVENUE:</h1>
        <h2>€ 3842,42</h2>
        <br/><br/><h1>YESTERDAY'S REVENUE:</h1>
        <h2>€ 1087,95</h2>
    </div>
</body>
</html>
