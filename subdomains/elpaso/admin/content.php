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
    <title>Content - El Paso</title>
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
		<a href="index.php">Home</a>
		<a href="reservations.php">Reservations</a>
		<a href="reservation_check.php">Pending reservations</a>
        <a href="content.php">Content</a>
        <a href="register_user.php">Register</a>
		<a href="logout.php">Logout</a>
	</section>

    <?php 
    if (isset($_SESSION['username'])) {
	    $query = "SELECT about_text FROM about";
	    $result = mysqli_query($dbc, $query);
	    while($row = mysqli_fetch_array($result)) { 

        echo "<form enctype='multipart/form-data' method='POST' action='' class='content_form'>
	    <label for='about_text'>ÜBER UNS:</label><br/><br/>
	    <textarea name='about_text' style='padding:7px;' maxlength='300'>" .$row['about_text']. "</textarea><br/><br/>
		<input type='reset' style='padding:5px 10px;' value='Cancel' />
	    <input name='update' type='submit' style='padding:5px 10px;margin-left:10px;' value='Update' />
	    </form>";
        }
    }

    if(isset($_POST['update'])) {
        $about = $_POST['about_text'];
        $query = "UPDATE about SET about_text='$about' WHERE id='1'";
        $result = mysqli_query($dbc,$query) or die('Error querying database.');

        if($result) {
            echo '<script>';
            echo 'alert("Successfully updated!"); location.href="content.php"';
            echo '</script>';
        } 

        //header('Location: content.php');
    }
    ?>
</body>
</html>
