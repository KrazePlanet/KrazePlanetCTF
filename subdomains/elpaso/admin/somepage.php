<?php
    include 'connect.php';
    
    $id = $_GET['id'];
    if ($dbc) {
        $query = "SELECT * FROM reservations WHERE id=$id";
        $result = mysqli_query($dbc, $query);
        while($row = mysqli_fetch_array($result)) {
            $famName = $row['name'];
            $famMail = $row['mail'];
            $famPhone = $row['phone'];
            $famDate = $row['date'];
            $famDate2 = date("d.m.Y",strtotime($famDate));
            $famTime = $row['time'];
        }
    }
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
        <link rel="icon" href="../img/logo.svg">
        <title>
        <?php
			$id = $_GET['id'];
			$query = "SELECT name FROM reservations WHERE id=$id";
			$result = mysqli_query($dbc, $query);
			while($row = mysqli_fetch_array($result)) {
				echo $row['name'];
			}
		?>
        </title>
        <style>
        </style>
    </head>
    <body>
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

        <div class="reservation_detail">
            <div class="modal-header">
                <h1 class="modal-title" id="fam_id">Name: <b><?php echo $famName;?></b></h1>
            </div>
            <div class="modal-body">
                <h2>Mail: <b><?php echo $famMail; ?></b></h2>
                <h2>Phone number: <b><?php echo $famPhone; ?></b></h2>
                <h2>Reservation date: <b><?php echo $famDate2; ?>.</b></h2>
                <h2>Reservation time: <b><?php echo $famTime; ?></b></h2>
            </div>
        </div>

        <button onclick="document.getElementById('confirm').style.display='block'" class="reservation_delete">DELETE</button>

        <div id="confirm" class="modal">
            <span onclick="document.getElementById('confirm').style.display='none'" class="close" title="Close Modal">×</span>
            <form class="modal-content" action="">
                <div class="container">
                    <h1>DELETE RESERVATION</h1>
                    <p>Are you sure you want to delete this pending reservation?</p>
                    <div class="clearfix">
                        <button type="button" onclick="document.getElementById('confirm').style.display='none'" class="cancelbtn">CANCEL</button>
                        <button type="button" onclick="window.location.href='reservation_delete.php?id=<?php echo $id; ?>'" class="deletebtn">DELETE</button>
                        <!--<a href="reservation_delete.php?id=" class="deletebtn" >DELETE</a>
                        <input name='delete' type='submit' class="deletebtn" value="DELETE"/>-->
                    </div>
                </div>
            </form>
      
        </div>

        <script>
        var modal = document.getElementById('confirm');
        window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
        }
        </script>
    </body>
</html>