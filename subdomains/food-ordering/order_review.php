<?php
session_start();
require 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['userloggedin']) || $_SESSION['userloggedin'] !== true) {
  header('location:login.php');
  exit;
}

// Get the email from the session
$email = $_SESSION['email'];

// Fetch user data
$stmt = $conn->prepare('SELECT * FROM users WHERE email=?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Retrieve selected items from POST request
$selectedItems = json_decode($_POST['selected_items'], true);

// Fetch cart items from the database
$itemDetails = [];
foreach ($selectedItems as $item) {
  $stmt = $conn->prepare('SELECT * FROM cart WHERE id=? AND email=?');
  $stmt->bind_param('is', $item['id'], $email);
  $stmt->execute();
  $result = $stmt->get_result();
  $itemDetails[] = $result->fetch_assoc();
}

// Calculate subtotal and total
$subtotal = 0;
$deliveryFee = 0;
foreach ($itemDetails as $item) {
  $itemPrice = $item['price'];
  $itemQuantity = $item['quantity'];
  $subtotal += $itemPrice * $itemQuantity;
}
$deliveryFee = ($_POST['payment_mode'] === 'Takeaway') ? 0 : 130;
$total = $subtotal + $deliveryFee;


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css' />
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css' />
  <!-- Bootstrap CSS -->
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="order_review.css">
  <title>Complete Order</title>
</head>

<body>
  <?php include('nav-logged.php'); ?>
  <div class="title mt-2">
    <h3>Hi <?php echo $user['firstName'] . " " . $user['lastName']; ?>, Complete your order!</h3>
  </div>
  <div class=" main mt-4">
    <div class="order-fee">

      <h4>Order Details</h4>
      <hr>
      <form action="process_order.php" method="post">
        <input type="hidden" name="total" value="<?= $total ?>">
        <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
        <input type="hidden" name="order_id" value="<?= $orderId ?>">
        <input type="hidden" name="selected_items" value='<?= json_encode($selectedItems) ?>'>
        <input type="hidden" name="payment_mode" value="<?= htmlspecialchars($_POST['payment_mode']) ?>">
        <div class="form-group row">
          <div class="col">
            <label for="firstName">First Name:</label>
            <input type="text" class="form-control" id="firstName" name="firstName" required>
          </div>
          <div class="col">
            <label for="lastName">Last Name:</label>
            <input type="text" class="form-control" id="lastName" name="lastName" required>
          </div>
        </div>
        <div class="form-group row">
          <div class="col">
            <label for="contact">Contact:</label>
            <input type="text" class="form-control" id="contact" name="contact" required>
          </div>
          <div class="col">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" readonly>
          </div>
        </div>
        <div class="form-group">
          <label for="order_note">Order Note:</label>
          <textarea class="form-control" id="order_note" name="order_note" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label for="address">Address:</label>
          <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
        </div>

    </div>


    <div class="order-summary">
      <h4>Order Summary</h4>
      <hr>
      <div class="order-items mb-2">
        <?php foreach ($itemDetails as $item) : ?>
          <div class="order-item d-flex align-items-center">
            <?php if (!empty($item['image'])) : ?>
              <img src="uploads/<?= htmlspecialchars($item['image']) ?>" alt="Item Image" class="ms-1">
            <?php else : ?>
              <span>No image available</span>
            <?php endif; ?>
            <div class="ms-1 row d-flex justify-content-between w-100">
              <div class="col d-flex flex-column justify-content-center ">
                <div class="d-flex flex-row mb-1"><strong><?= htmlspecialchars($item['itemName']) ?></strong></div>
                <div class="d-flex flex-row ">Quantity: <?= htmlspecialchars($item['quantity']) ?></div>
              </div>
              <div class="col d-flex flex-column justify-content-center">
                <div class="d-flex flex-row justify-content-end align-items-center mt-2"> Rs <?= htmlspecialchars($item['price'], 0) ?> x <?= htmlspecialchars($item['quantity']) ?></div>
                <div class="d-flex flex-row justify-content-end align-items-start mb-2">
                  <span class="badge rounded-pill text-light p-2 mt-2 item-total-price" style="background-color: #fb4a36;">Rs <?= $item['total_price'] ?></span>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <h4 class="mt-1 ">Order Fee</h4>
      <hr>
      <div class="summary-details">
        <div class="fee-details">
          <div><strong>Subtotal:</strong></div>
          <div>Rs <?= number_format($subtotal) ?></div>
        </div>
        <div class="fee-details">
          <div><strong>Payment Method:</strong></div>
          <div><?= htmlspecialchars($_POST['payment_mode']) ?></div>
        </div>
        <div class="fee-details">
          <div><strong>Delivery Fee:</strong></div>
          <div>Rs <?= number_format($deliveryFee) ?></div>
        </div>
        <div class="fee-details">
          <div><strong>Total:</strong></div>
          <div>Rs <?= number_format($total) ?></div>
        </div>
      </div>
      <hr>
      <?php
      if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $payment_mode = $_POST['payment_mode'] ?? '';

        if ($payment_mode == 'Card') {
          echo '<button type="submit" class="Button">
            Pay
           <svg viewBox="0 0 576 512" class="svgIcon"><path d="M512 80c8.8 0 16 7.2 16 16v32H48V96c0-8.8 7.2-16 16-16H512zm16 144V416c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V224H528zM64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm56 304c-13.3 0-24 10.7-24 24s10.7 24 24 24h48c13.3 0 24-10.7 24-24s-10.7-24-24-24H120zm128 0c-13.3 0-24 10.7-24 24s10.7 24 24 24H360c13.3 0 24-10.7 24-24s-10.7-24-24-24H248z"></path></svg>
           </button>';
        } else {
          echo '<button type="submit" class="order-btn ">Place Order</button>';
        }
      }
      ?>

      </form>
    </div>


  </div>

  <?php
include_once ('footer.html');
?>

  <!-- Bootstrap JS -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.min.js'></script>
  

  <script>
    $(document).ready(function() {
      console.log('Page is ready. Calling load_cart_item_number.');
      load_cart_item_number();

      function load_cart_item_number() {
        $.ajax({
          url: 'action.php',
          method: 'get',
          data: {
            cartItem: "cart_item"
          },
          success: function(response) {
            $("#cart-item").html(response);
          }
        });
      }
    });
  </script>
</body>

</html>