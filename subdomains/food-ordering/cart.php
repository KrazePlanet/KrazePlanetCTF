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

//fetch user data
$stmt = $conn->prepare('SELECT * FROM users WHERE email=?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Fetch cart items for the logged-in user
$stmt = $conn->prepare('SELECT * FROM cart WHERE email=?');
$stmt->bind_param('s', $email);
$stmt->execute();
$itemsResult = $stmt->get_result();
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <title>Your Cart</title>
  <link rel="stylesheet" href="cart.css" />
</head>

<body>
  <?php
  if ($_SESSION['userloggedin']) {
    include_once('nav-logged.php');
  } else {
    include_once('navbar.php');
  }
  ?>
  <div class="title mb-4">
    <h3>Hi <?php echo $user['firstName'] . " " . $user['lastName']; ?>, Here's your cart!</h3>
  </div>

  <div class="cart-details ">
    <div class="cart-items">
      <h4 class="mt-2">Cart Items</h4>
      <hr>
      <ul class="list-group">
        <?php if ($itemsResult->num_rows > 0): ?>
          <?php while ($item = $itemsResult->fetch_assoc()) : ?>
            <li class="d-flex justify-content-between align-items-center mb-3 list-group-item">
              <div class="d-flex align-items-center">
                <input type="checkbox" class="form-check-input ms-1 selection" style="width: 20px; height: 20px; border-radius: 50%; " data-price="<?= $item['price'] ?>" checked>
                <?php
                // Check if 'image' key exists and is not empty
                if (!empty($item['image'])) {
                  echo '<img src="uploads/' . htmlspecialchars($item['image']) . '" alt="Item Image" class="item-image ">';
                } else {
                  echo '<span>No image available</span>';
                }
                ?>
                <div class="ms-3">
                  <div class="mt-1"><?php echo $item['itemName']; ?></div>
                  <div class="quantity mt-2 mb-1">
                    <button class="minus-btn minus-quantity-btn" type="button" data-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>" min="1">
                      <i class="fas fa-minus"></i>
                    </button>
                    <input type="text" class="itemQty" value="<?= $item['quantity'] ?>" min="1" data-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                    <button class="plus-btn plus-quantity-btn" type="button" data-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                      <i class="fas fa-plus"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="mt-1">
                <div class="d-flex flex-row justify-content-between align-items-start quantity-price">
                  <div>
                    Rs <span class="item-price"><?= $item['price'] ?></span> x <span class="item-quantity"><?= $item['quantity'] ?></span>
                  </div>
                </div>
                <div class="d-flex flex-row justify-content-end align-items-end">
                  <span class="badge rounded-pill text-light p-2 mt-2 item-total-price" style="background-color: #fb4a36;">Rs <?= $item['total_price'] ?></span>
                </div>
              </div>
              <button class="delete-icon" data-id="<?= $item['id'] ?>">
                &times;
              </button>
            </li>
          <?php endwhile; ?>
        <?php else: ?>
          <li class="list-group-item">
            Your cart is empty!
          </li>
        <?php endif; ?>
      </ul>
    </div>


    <div class="order-summary">
      <h4 class="mt-2">Order Fee</h4>
      <hr class="mb-4">
      <div class="summary-details ">
        <p><strong>Subtotal:</strong></p>
        <p> <span id="subtotal">0</span></p>
      </div>
      <div class="summary-details payment">
        <p><strong>Payment Method:</strong></p>
        <div>
          <input type="radio" id="takeaway" name="payment_mode" value="Takeaway" checked>
          <label for="Takeaway">Takeaway</label>
        </div>
        <div>
          <input type="radio" id="cash" name="payment_mode" value="Cash">
          <label for="Cash">Cash</label>
        </div>
        <div>
          <input type="radio" id="card" name="payment_mode" value="Card" disabled style="cursor: not-allowed;">
          <label for="Card">Card</label>
        </div>
      </div>
      <div class="summary-details">
        <p><strong>Delivery Fee: </strong></p>
        <p> <span id="delivery-fee">0</span></p>
      </div>
      <div class="summary-details mb-3">
        <p><strong>Total:</strong></p>
        <p><span id="total">0</span></p>
      </div>
      <hr>

      <form id="checkout-form" action="order_review.php" method="post">
        <input type="hidden" id="selected-items" name="selected_items">
        <input type="hidden" id="payment-mode" name="payment_mode">
        <button type="button" id="checkout-button">Checkout</button>
      </form>



    </div>

  </div>

  <?php
  include_once('footer.html');
  ?>

  <!-- Bootstrap JS -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.min.js'></script>
  <script>
    $(document).ready(function() {
      console.log('Page is ready. Calling load_cart_item_number.');
      load_cart_item_number();
    });
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const checkboxInputs = document.querySelectorAll('input[type="checkbox"].form-check-input');
      const subtotalElement = document.getElementById('subtotal');
      const deliveryFeeElement = document.getElementById('delivery-fee');
      const totalElement = document.getElementById('total');
      const paymentModeInputs = document.querySelectorAll('input[name="payment_mode"]');
      const checkoutForm = document.getElementById('checkout-form');
      const itemsInput = document.getElementById('items');
      const totalPriceInput = document.getElementById('total_price');

      let subtotal = 0;
      let deliveryFee = 0;

      // Function to update the summary
      function updateSummary() {
        subtotalElement.textContent = subtotal.toFixed(2);
        deliveryFeeElement.textContent = deliveryFee.toFixed(2);
        totalElement.textContent = (subtotal + deliveryFee).toFixed(2);
      }

      // Function to update delivery fee based on payment mode
      function updateDeliveryFee() {
        const selectedPaymentMode = document.querySelector('input[name="payment_mode"]:checked').value;
        deliveryFee = selectedPaymentMode === 'Takeaway' ? 0 : 130;
        updateSummary();
      }

      // Initialize the subtotal correctly
      function initializeSubtotal() {
        subtotal = Array.from(checkboxInputs).reduce((sum, checkbox) => {
          if (checkbox.checked) {
            const itemPrice = parseFloat(checkbox.dataset.price);
            const itemQuantity = parseInt(checkbox.closest('li').querySelector('.itemQty').value);
            return sum + (itemPrice * itemQuantity);
          }
          return sum;
        }, 0);
        updateSummary();
      }

      // Function to handle checkbox changes
      function handleCheckboxChange(checkbox) {
        const itemPrice = parseFloat(checkbox.dataset.price);
        const itemQuantity = parseInt(checkbox.closest('li').querySelector('.itemQty').value);
        const itemTotalPrice = itemPrice * itemQuantity;

        if (checkbox.checked) {
          subtotal += itemTotalPrice;
        } else {
          subtotal -= itemTotalPrice;
        }

        updateSummary();
      }

      // Event listener for checkbox changes
      checkboxInputs.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
          handleCheckboxChange(this);
        });
      });

      // Function to handle quantity updates
      function updateQuantity(element, change, isInput = false) {
        let itemId = element.dataset.id;
        let itemPrice = parseFloat(element.dataset.price);
        let quantityInput = isInput ? element : element.parentElement.querySelector('.itemQty');
        let newQuantity = isInput ? parseInt(quantityInput.value) : parseInt(quantityInput.value) + change;

        if (newQuantity < 1) return; // Prevent quantity from going below 1
        quantityInput.value = newQuantity;

        let itemTotalPrice = itemPrice * newQuantity;
        let itemContainer = quantityInput.closest('li');
        itemContainer.querySelector('.item-quantity').textContent = newQuantity;
        itemContainer.querySelector('.item-total-price').textContent = 'Rs ' + itemTotalPrice;

        // Update quantity in the database
        let xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_cart_quantity.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
          if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
            // Update the cart item number after successful quantity update
            load_cart_item_number();
          }
        };

        xhr.send(`id=${itemId}&quantity=${newQuantity}&total_price=${itemTotalPrice}`);

        // If the checkbox is checked, update the subtotal accordingly
        const checkbox = itemContainer.querySelector('input[type="checkbox"].form-check-input');
        if (checkbox.checked) {
          subtotal += change * itemPrice;
          updateSummary();
        }
      }

      // Event listener for minus button
      document.querySelectorAll('.minus-btn').forEach(button => {
        button.addEventListener('click', function() {
          updateQuantity(this, -1);
        });
      });

      // Event listener for plus button
      document.querySelectorAll('.plus-btn').forEach(button => {
        button.addEventListener('click', function() {
          updateQuantity(this, 1);
        });
      });

      // Event listener for quantity input change
      document.querySelectorAll('.itemQty').forEach(input => {
        input.addEventListener('change', function() {
          updateQuantity(this, 0, true);
        });
      });

      // Event listener for delete icon
      document.querySelectorAll('.delete-icon').forEach(button => {
        button.addEventListener('click', function() {
          let itemId = this.dataset.id;
          let itemContainer = this.closest('li');
          const checkbox = itemContainer.querySelector('input[type="checkbox"].form-check-input');
          const itemPrice = parseFloat(checkbox.dataset.price);
          const itemQuantity = parseInt(itemContainer.querySelector('.itemQty').value);
          const itemTotalPrice = itemPrice * itemQuantity;

          if (checkbox.checked) {
            subtotal -= itemTotalPrice;
            updateSummary();
          }

          // Remove the item from the UI
          itemContainer.remove();

          // Send a request to the server to delete the item
          let xhr = new XMLHttpRequest();
          xhr.open('POST', 'delete_cart_item.php', true);
          xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
          xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
              // After successfully deleting the item, update the cart item number
              load_cart_item_number();
            }
          };
          xhr.send(`id=${itemId}`);
        });
      });

      // Initialize the summary and subtotal on page load
      initializeSubtotal();
      updateDeliveryFee();

      load_cart_item_number();
      // Function to update the cart item number in the UI
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



      // Event listener for payment mode changes
      paymentModeInputs.forEach(input => {
        input.addEventListener('change', updateDeliveryFee);
      });

    });
  </script>
  <script>
    document.getElementById('checkout-button').addEventListener('click', function() {
      const selectedItems = [];
      document.querySelectorAll('input[type="checkbox"].form-check-input:checked').forEach(checkbox => {
        const itemId = checkbox.closest('li').querySelector('.itemQty').dataset.id;
        const itemQuantity = checkbox.closest('li').querySelector('.itemQty').value;
        selectedItems.push({
          id: itemId,
          quantity: itemQuantity
        });
      });

      document.getElementById('selected-items').value = JSON.stringify(selectedItems);

      // Get selected payment mode
      const paymentMode = document.querySelector('input[name="payment_mode"]:checked').value;
      document.getElementById('payment-mode').value = paymentMode;

      document.getElementById('checkout-form').submit();
    });
  </script>

</body>

</html>