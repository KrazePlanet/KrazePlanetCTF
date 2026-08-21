<?php
session_start();
if (!isset($_SESSION['adminloggedin'])) {
  header("Location: ../login.php");
  exit();
}

include 'db_connection.php';
$orderId = isset($_GET['orderId']) ? $_GET['orderId'] : '';

if ($orderId) {
  $orderQuery = "SELECT * FROM orders WHERE order_id = ?";
  $stmt = $conn->prepare($orderQuery);
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $orderResult = $stmt->get_result();
  $order = $orderResult->fetch_assoc();

  $itemsQuery = "SELECT * FROM order_items WHERE order_id = ?";
  $itemsQuery = "SELECT itemName, quantity, price, total_price, image FROM order_items WHERE order_id = ?";

  $stmt = $conn->prepare($itemsQuery);
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $itemsResult = $stmt->get_result();
} else {
  echo "Invalid order ID.";
  exit();
}
$paymentMode = $order['pmode'] ?? 'takeaway'; // Default to 'takeaway' if not set

// Determine the delivery fee based on the payment mode
$deliveryFee = ($paymentMode === 'takeaway') ? 0 : 130;
?>
<?php
include 'sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Orders</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="admin_orders.css">
  <link rel="stylesheet" href="view_order.css">

</head>

<body>
  <div class="sidebar">
    <button class="close-sidebar" id="closeSidebar">&times;</button>

    <!-- Profile Section -->
    <div class="profile-section">
      <img src="../uploads/<?php echo htmlspecialchars($admin_info['profile_image']); ?>" alt="Profile Picture">
      <div class="info">
        <h3>Welcome Back!</h3>
        <p><?php echo htmlspecialchars($admin_info['firstName']) . ' ' . htmlspecialchars($admin_info['lastName']); ?></p>
      </div>
    </div>

    <!-- Navigation Items -->
    <ul>
      <li><a href="index.php"><i class="fas fa-chart-line"></i> Overview</a></li>
      <li><a href="admin_menu.php"><i class="fas fa-utensils"></i> Menu Management</a></li>
      <li><a href="admin_orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
      <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
      <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
      <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
      <li><a href="staffs.php"><i class="fas fa-users"></i> Staffs</a></li>
      <li><a href="profile.php"><i class="fas fa-user"></i> Profile Setting</a></li>
      <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </div>
  <div class="content">
    <div class="header">
      <div class="col">
        <button id="toggleSidebar" class="toggle-button">
          <i class="fas fa-bars"></i>
        </button>
        <h2><i class="fas fa-shopping-cart"></i>#<?php echo $order['order_id']; ?> Order Details</h2>
      </div>
      <div class="col d-flex justify-content-end">
        <a href="admin_orders.php" class="button"><i class="fas fa-arrow-left"></i>&nbsp; Orders</a>
      </div>
    </div>
    <div class="details">
      <div class="order-details">
        <div class="order-items">
          <h4 class="mt-2">Order Items</h4>
          <hr>
          <ul class="list-group">
            <?php while ($item = $itemsResult->fetch_assoc()) : ?>
              <li class=" d-flex justify-content-between  mb-3">
                <div class="d-flex align-items-start">
                  <?php
                  // Check if 'image' key exists and is not empty
                  if (!empty($item['image'])) {
                    echo '<img src="../uploads/' . htmlspecialchars($item['image']) . '" alt="Item Image" style="width: 70px; height: 70px; object-fit: cover;">';
                  } else {
                    echo '<span>No image available</span>';
                  }
                  ?>
                  <?php echo $item['itemName']; ?>
                </div>
                <div>
                  <div class="d-flex flex-row justify-content-between align-items-start quantity-price">
                    <div>
                      Rs <?php echo $item['price']; ?> x <?php echo $item['quantity']; ?>
                    </div>
                  </div>
                  <div class="d-flex flex-row justify-content-end align-items-end">
                    <span class="badge rounded-pill text-light p-2 mt-2" style="background-color: #fb4a36; ">Rs <?php echo $item['total_price']; ?></span>
                  </div>
                </div>
              </li>
            <?php endwhile; ?>
          </ul>
        </div>
        <div class="order-summary">
          <h4 class="mt-2">Order Fee</h4>
          <hr>
          <div class="summary-details">
            <p><strong>Subtotal:</strong></p>
            <p> Rs <?php echo $order['sub_total']; ?></p>
          </div>

          <div class="summary-details">
            <p><strong>Fee:</strong></p>
            <p>Rs <?= number_format($deliveryFee, 2) ?></p>
          </div>
          <div class="summary-details">
            <p><strong>Total:</strong></p>
            <p> Rs <?php echo $order['grand_total']; ?></p>
          </div>
          <div class="summary-details">
            <p><strong>Payment Mode:</strong></p>
            <p><?php echo $order['pmode']; ?></p>
          </div>
          <div class="summary-details">
            <p style="width: 60%;"><strong>Payment Status:</strong></p>
            <select class="form-select" id="paymentStatus" name="payment_status">
              <option value="Pending" <?php if ($order['payment_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
              <option value="Successful" <?php if ($order['payment_status'] == 'Successful') echo 'selected'; ?>>Successful</option>
              <option value="Rejected" <?php if ($order['payment_status'] == 'Rejected') echo 'selected'; ?>>Rejected</option>
            </select>
          </div>
          <div class="summary-details">
            <p><strong>Cancel Reason:</strong></p>
            <p><?php echo $order['cancel_reason']; ?></p>
          </div>
          <hr>
          <form method="post" action="update_order_status.php" onsubmit="return validateForm()">
            <div class="status-container">
              <label for="orderStatus" class="form-label"><strong>Order Status</strong></label>
              <select class="form-select" id="orderStatus" name="order_status">
                <option value="Pending" <?php if ($order['order_status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                <option value="Processing" <?php if ($order['order_status'] == 'Processing') echo 'selected'; ?>>Processing</option>
                <option value="Completed" <?php if ($order['order_status'] == 'Completed') echo 'selected'; ?>>Completed</option>
                <option value="Cancelled" <?php if ($order['order_status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                <option value="On the way" <?php if ($order['order_status'] == 'On the way') echo 'selected'; ?>>On the way</option>
              </select>
            </div>
            <div class="mb-3" id="cancelReasonContainer" style="display: none;">
              <label for="cancelReason" class="form-label">Cancellation Reason</label>
              <textarea class="form-control" id="cancelReason" name="cancel_reason"></textarea>
            </div>
            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
            <button type="submit" id="statusbtn">Update Status</button>
          </form>
        </div>
      </div>
      <div class="customer mb-4">
        <h4 class="mt-2">Customer Details</h4>
        <hr>
        <div class="customer-details">
          <div class="summary-details">
            <p><strong>Name:</strong></p>
            <p><?php echo $order['firstName'] . ' ' . $order['lastName']; ?></p>
          </div>
          <div class="summary-details">
            <p><strong>Email:</strong></p>
            <p><?php echo $order['email']; ?></p>
          </div>
          <div class="summary-details">
            <p><strong>Contact:</strong></p>
            <p><?php echo $order['phone']; ?></p>
          </div>
          <div class="summary-details">
            <p><strong>Address:</strong></p>
            <p><?php echo $order['address']; ?></p>
          </div>
          <div class="summary-details">
            <p><strong>Order Note:</strong></p>
            <p><?php echo $order['note']; ?></p>
          </div>
        </div>
      </div>
    </div>

  </div>
  <?php
  include_once('footer.html');
  ?>
  <script src="sidebar.js"></script>
  <script>
    document.getElementById('paymentStatus').addEventListener('change', function() {
      var paymentStatus = this.value;
      var orderId = <?php echo $order['order_id']; ?>; // Get order ID from PHP

      // Create an AJAX request
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'update_payment_status.php', true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

      // Handle the response
      xhr.onload = function() {
        if (xhr.status === 200) {
          // Optionally, show a success message or handle errors
          alert('Payment status updated successfully');
        } else {
          console.error('Error updating payment status');
        }
      };

      // Send the request with order ID and payment status
      xhr.send('order_id=' + encodeURIComponent(orderId) + '&payment_status=' + encodeURIComponent(paymentStatus));
    });


    document.getElementById('orderStatus').addEventListener('change', function() {
      const cancelReasonContainer = document.getElementById('cancelReasonContainer');
      if (this.value === 'Cancelled') {
        cancelReasonContainer.style.display = 'block';
      } else {
        cancelReasonContainer.style.display = 'none';
      }
    });

    function validateForm() {
      const orderStatus = document.getElementById('orderStatus').value;
      if (orderStatus === 'Cancelled') {
        const cancelReason = document.getElementById('cancelReason').value;
        if (cancelReason.trim() === '') {
          alert('Please provide a cancellation reason.');
          return false;
        }
      }
      return true;
    }
  </script>
</body>

</html>