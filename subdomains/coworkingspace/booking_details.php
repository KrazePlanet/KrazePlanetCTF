<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/css/bootstrap.min.css">
    <title>Booking | WORKVIBES Coworking Space</title>

    <!-- font awesome cdn link  -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- custom css file link  -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/booking.css">
	<link rel="stylesheet" type="" href="css/footer.css">
</head>
<body>
<!-- header section starts  -->
<?php include 'components/user_header.php'; ?>
<!-- header section ends -->
    <div class="heading">
        <h3>Booking Space</h3>
        <p><a href="home.php">home</a> <span> / Booking details</span></p>
    </div>

	?><br><br><br>
	<div class="container-fluid"style="margin-top:2%;">
		<div class="continer">
			<div class="row">
				<div class="col-sm-2"></div>
				<div class="col-sm-7">
					<div id="myCarousel" class="carousel slide" data-ride="carousel">
					<!-- Indicators -->
					<ol class="carousel-indicators">
						<li data-target="#myCarousel" data-slide-to="0" class="active"></li>
						<li data-target="#myCarousel" data-slide-to="1"></li>
						<li data-target="#myCarousel" data-slide-to="2"></li>
						<li data-target="#myCarousel" data-slide-to="3"></li>
						<li data-target="#myCarousel" data-slide-to="4"></li>
						<li data-target="#myCarousel" data-slide-to="5"></li>
					</ol>

					<!-- Wrapper for slides -->
					<div class="carousel-inner">
						<div class="item active">
						<img src="img/bg.jpg"class="thumbnail" alt="img1">
						</div>

						<div class="item">
						<img src="img/bg.jpg"class="thumbnail" alt="im2">
						</div>

						<div class="item">
						<img src="img/bg.jpg"class="thumbnail" alt="im3">
						</div>

						<div class="item">
						<img src="img/bg.jpg"class="thumbnail" alt="img4">
						</div>

						<div class="item">
						<img src="img/bg.jpg"class="thumbnail" alt="img5">
						</div>

						<div class="item">
						<img src="img/bg.jpg"class="thumbnail" alt="img7">
						</div>
					</div>

					<!-- Left and right controls -->
					<a class="left carousel-control" href="#myCarousel" data-slide="prev">
						<span class="glyphicon glyphicon-chevron-left"></span>
						<span class="sr-only">Previous</span>
					</a>
					<a class="right carousel-control" href="#myCarousel" data-slide="next">
						<span class="glyphicon glyphicon-chevron-right"></span>
						<span class="sr-only">Next</span>
					</a>
					</div>
					<?php 
					include('connect.php');
					$space_id=$_GET['space_id'];
					$sql=mysqli_query($con,"select * from bookings where space_id='$space_id' ");
					$res=mysqli_fetch_assoc($sql);
					?>

							<h2 class="Ac_Room_Text"><?php echo $res['type']; ?></h2>
						<h3 class="Ac_Room_Text"><?php echo $res['price']; ?></h3>
							<p class="text-justify">
						<?php echo $res['details']; ?>
					</p>
						<div class="row">
						<h2>Amenities & Facilities</h2>
						<img src="img/bg.jpg"class="img-responsive">
						<a href="login.php" class="btn btn-danger">Book Now</a><br><br>
						</div>
						</div>
									<div class="col-sm-3">
										<div class="panel panel-primary">
										<div class="panel-heading">
											<h4 align="center">Space Type</h4>
										</div><br>
										<div class="panel-body-right text-center">
						<!--Fatch Mysql Database Select Query Room Details -->
											<?php
								include('connection.php');
								$sql1=mysqli_query($con,"select * from spaces");
							while($result1= mysqli_fetch_assoc($sql1))
							{

								?>
								<a href="booking_details.php?space_id=<?php echo $result1['space_id']; ?>"><?php echo $result1['type']; ?></a><hr>
								<?php } ?>
						<!--Fatch Mysql Database Select Query Room Details -->
											
										</div>
									</div>
									</div>
								</div>
							</div>
						</div>
							<!-- footer section starts  -->
							<?php include 'components/footer.php'; ?>
							<!-- footer section ends -->


        <!-- custom js file link  -->
        <script src="js/script.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.0/js/bootstrap.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
        <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>

</body>
</html>