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
    <title>Pending reservations - El Paso</title>
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

    <input type="date" id="myInput" onkeyup="myFunction()" placeholder="Search by booking number...">
	<a href="reservations.php" class="table-refresh">REFRESH</a>

  	<div class="container_booked">
    	<ul class="responsive-table" id="myUL">
      	<li class="table-header">
			<div class="space1">ID</div>
			<div class="space">Name</div>
			<div class="space">Date</div>
			<div class="space">Time</div>
			<div class="space1">Guests</div>
			<div class="space1">Info</div>
		</li>

		<?php
		if ($dbc) {
			
			$query = "SELECT * FROM reservations_pending ORDER BY date, time";
			$result = mysqli_query($dbc,$query);

			if($result) {
			while($row = mysqli_fetch_array($result)) {
				echo "<li class='table-row'>
				<div class='space1'>".$row['id']."</div>
				<div class='space'>".$row['name']."</div>
				<div class='space'>".$row['date']."</div>
				<div class='space'>".$row['time']."</div>
				<div class='space1'>".$row['visitors']."</div>
				<div class='space1'><a href='pending.php?id=".$row['id']."'>Details</a></div>
				</li>";
			}
			}
		}
		?>
   		</ul>
	</div>

	<script>
	function myFunction() {
	    var input, filter, ul, li, a, i, txtValue;
	    input = document.getElementById("myInput");
	    filter = input.value.toUpperCase();
	    ul = document.getElementById("myUL");
	    li = ul.getElementsByTagName("li");
	    for (i = 0; i < li.length; i++) {
	        a = li[i].getElementsByTagName("div")[1];
	        txtValue = a.textContent || a.innerText;
	        if (txtValue.toUpperCase().indexOf(filter) > -1) {
	            li[i].style.display = "";
	        } else {
	            li[i].style.display = "none";
	        }
	    }
	}

	</script>
</body>
</html>
