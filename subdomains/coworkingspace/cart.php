<?php

include 'components/connect.php';

session_start();

if(isset($_SESSION['user_id'])){
   $user_id = $_SESSION['user_id'];
}else{
   $user_id = '';
   header('location:home.php');
};

include('components/user_header.php');

$_SESSION['arrival'] = date("Y/m/d");
$_SESSION['departure'] =  date("Y/m/d");

if(isset($_GET['view']) && $_GET['view'] == 'process_cart' && isset($_GET['id'])){	
	$booking->room_id = $_GET['id'];
    $booking->removeFromCart();
}

if (isset($_POST['emptyCart'])){
   unset($_SESSION['pay']);
   unset($_SESSION['booking_cart']);   
}

if(isset($_POST['book_now'])){	
	$days = 0;
	$totalPrice = 0;
	if($days <= 0){
		$totalPrice = $_POST['room_price'] *1;
		$days = 1;
	} else {
		$totalPrice = $_POST['room_price'] * $days;
		$days = $days;
	}
	$booking->room_id = $_POST['room_id'];
	$booking->days = $days;
	$booking->total_price = $totalPrice;	
	$booking->addToCart();
}
?>
<title>COWORKING SPACE || WORKVIBES</title>
<link rel="stylesheet" type="text/css" href="styles/bootstrap-4.1.2/bootstrap.min.css">
<link href="plugins/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" type="text/css" href="css/style.css">
<link rel="stylesheet" href="css/footer.css">
<script src="js/general.js"></script>

					<tbody>
						<?php
						$payable = 0;
						if (isset( $_SESSION['booking_cart'])){
							$cartCount = count($_SESSION['booking_cart']);
							for ($i=0; $i < $cartCount  ; $i++) {								
								$rooms->room_id = $_SESSION['booking_cart'][$i]['bookingroomid'];
								$roomsResult = $rooms->getRoomDetails();
								while ($room = $roomsResult->fetch_assoc()) { 				
								?>
								<tr>
									<td><?php echo $room['description']; ?></td>
									<td><?php echo date_format(date_create($_SESSION['booking_cart'][$i]['bookingcheckin']),"m/d/Y"); ?></td>
									<td><?php echo date_format(date_create($_SESSION['booking_cart'][$i]['bookingcheckin']),"m/d/Y"); ?></td>
									<td>$<?php echo $room['price']; ?></td>
									<td><?php echo $_SESSION['booking_cart'][$i]['bookingday']; ?></td>
									<td><?php echo $_SESSION['booking_cart'][$i]['bookingroomprice']; ?></td>
									<td><a href="booking.php?view=process_cart&id=<?php echo $room['id']; ?>">Remove</a></td>
								</tr>
						<?php 
								}
								$payable += $_SESSION['booking_cart'][$i]['bookingroomprice'];
							}
							$_SESSION['pay'] = $payable;
						} 
						?>
					</tbody>				
					<tfoot>
						<tr>
							<td colspan="6"><h4 align="right">Total:</h4></td>
							<td colspan="4">
								<h4><b><span id="sum"><?php  echo isset($_SESSION['pay']) ?  '$'.$_SESSION['pay'] :'Cart is empty.';?></span></b></h4>
							</td>
						</tr>
					</tfoot> 				
				</table>				
			</div>
			
			<form method="post" action="">
				<div class="row" >
				<?php
				if (isset($_SESSION['booking_cart'])){
				?> 
					<button type="submit" class="button" name="emptyCart">Clear Cart</button> 
					<?php

					if (isset($_SESSION['GUESTID'])){
					?>
						<div  class="button"><a href="booking.php?view=payment" name="continue">Continue Booking</a></div>
					<?php 
					} else { ?>
						<div  class="button"><a href="booking.php?view=checkout"  name="continue">Continue Booking</a></div>
					<?php
					}
				}
				?>
				</div>
			</form>				
		</div>
	</div>
</div>
<?php include('components/footer.php');?>