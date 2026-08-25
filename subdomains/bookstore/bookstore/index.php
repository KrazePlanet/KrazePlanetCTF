<?php
require_once __DIR__ . '/connectDB.php';
session_start();

$servername = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$username = "root";
$password = "";
$dbname = "bookstore";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_POST['ac'])){
    $bookID_escaped = $conn->real_escape_string($_POST['ac']);
    $sql = "SELECT * FROM book WHERE BookID = '{$bookID_escaped}'";
    $result = $conn->query($sql);

    if($row = $result->fetch_assoc()){
        $bookID = $row['BookID'];
        $quantity = max(1, (int)$_POST['quantity']);
        $price = (float)$row['Price'];
        $total = $price * $quantity;

        $sql = "INSERT INTO cart(BookID, Quantity, Price, TotalPrice) VALUES('{$bookID}', {$quantity}, {$price}, {$total})";
        $conn->query($sql);
    }
}

if(isset($_POST['delc'])){
    $conn->query("DELETE FROM cart");
}

$sql = "SELECT * FROM book";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BookStore</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
if(isset($_SESSION['id'])){
	echo '<header>';
	echo '<blockquote>';
	echo '<a href="index.php"><img src="image/logo.png" alt="BookStore Logo"></a>';
	echo '<form class="hf" action="logout.php"><input class="hi" type="submit" name="submitButton" value="Logout"></form>';
	echo '<form class="hf" action="edituser.php"><input class="hi" type="submit" name="submitButton" value="Edit Profile"></form>';
	echo '</blockquote>';
	echo '</header>';
} else {
	echo '<header>';
	echo '<blockquote>';
	echo '<a href="index.php"><img src="image/logo.png" alt="BookStore Logo"></a>';
	echo '<form class="hf" action="register.php"><input class="hi" type="submit" name="submitButton" value="Register"></form>';
	echo '<form class="hf" action="login.php"><input class="hi" type="submit" name="submitButton" value="Login"></form>';
	echo '</blockquote>';
	echo '</header>';
}

echo '<blockquote>';
echo "<table id='myTable' style='width:75%; float:left; border-collapse: separate; border-spacing: 15px;'>";
echo "<tr>";
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
	    echo "<td style='background:#fff; border:1px solid #ddd; border-radius:8px; padding:15px; vertical-align:top; width:25%; box-shadow:0 2px 5px rgba(0,0,0,0.05);'>";
	    echo "<table style='width:100%; text-align:center;'>";
	   	echo '<tr><td style="text-align:center;"><img src="'.htmlspecialchars($row["Image"]).'" style="max-height:180px; width:auto; max-width:100%; border-radius:4px; margin-bottom:10px;" alt="Book Cover"></td></tr>';
        echo '<tr><td style="padding: 5px; font-weight:bold; font-size:14px; text-align:left; min-height:40px;">'.htmlspecialchars($row["BookTitle"]).'</td></tr>';
        echo '<tr><td style="padding: 3px 5px; font-size:12px; color:#666; text-align:left;"><strong>ISBN:</strong> '.htmlspecialchars($row["ISBN"]).'</td></tr>';
        echo '<tr><td style="padding: 3px 5px; font-size:12px; color:#666; text-align:left;"><strong>Author:</strong> '.htmlspecialchars($row["Author"]).'</td></tr>';
        echo '<tr><td style="padding: 3px 5px; font-size:12px; color:#666; text-align:left;"><strong>Type:</strong> <span style="background:#e8f4fd; color:#0d6efd; padding:2px 6px; border-radius:4px;">'.htmlspecialchars($row["Type"]).'</span></td></tr>';
        echo '<tr><td style="padding: 8px 5px; font-size:16px; color:#b12704; font-weight:bold; text-align:left;">RM '.number_format($row["Price"], 2).'</td></tr>';
        echo '<tr><td style="padding: 5px; text-align:left;">
	   	<form action="" method="post">
	   	Quantity: <input type="number" value="1" min="1" name="quantity" style="width: 45px; padding:3px; border:1px solid #ccc; border-radius:4px;"/><br><br>
	   	<input type="hidden" value="'.htmlspecialchars($row['BookID']).'" name="ac"/>
	   	<input class="button" type="submit" style="width:100%; cursor:pointer;" value="Add to Cart"/>
	   	</form></td></tr>';
	   	echo "</table>";
	   	echo "</td>";
    }
} else {
    echo "<td><p>No books currently available in catalog.</p></td>";
}
echo "</tr>";
echo "</table>";

$sql_cart = "SELECT book.BookTitle, book.Image, cart.Price, cart.Quantity, cart.TotalPrice FROM book, cart WHERE book.BookID = cart.BookID;";
$result_cart = $conn->query($sql_cart);

echo "<table style='width:23%; float:right; background:#f9f9f9; border:1px solid #ddd; border-radius:8px; padding:10px;'>";
echo "<th style='text-align:left; padding:10px; border-bottom:2px solid #ddd;'><i class='fa fa-shopping-cart' style='font-size:20px'></i> Cart <form style='float:right;' action='' method='post'><input type='hidden' name='delc'/><input class='cbtn' type='submit' value='Empty Cart'></form></th>";
$total = 0;
if ($result_cart) {
    while($row = $result_cart->fetch_assoc()){
    	echo "<tr><td style='padding:8px 5px; border-bottom:1px solid #eee;'>";
    	echo '<img src="'.htmlspecialchars($row["Image"]).'" width="35" style="float:left; margin-right:8px; border-radius:2px;">';
    	echo '<strong>'.htmlspecialchars($row['BookTitle'])."</strong><br>RM ".number_format($row['Price'], 2)."<br>";
    	echo "<small>Qty: ".$row['Quantity']." | Total: RM ".number_format($row['TotalPrice'], 2)."</small></td></tr>";
    	$total += $row['TotalPrice'];
    }
}
echo "<tr><td style='text-align: right; background-color: #f2f2f2; padding:12px 8px; border-radius:0 0 8px 8px;'>";
echo "Total: <b style='font-size:16px; color:#b12704;'>RM ".number_format($total, 2)."</b><center><form action='checkout.php' method='post'><input class='button' type='submit' name='checkout' style='width:100%; margin-top:8px; cursor:pointer;' value='CHECKOUT'></form></center>";
echo "</td></tr>";
echo "</table>";
echo '</blockquote>';
?>
</body>
</html>
