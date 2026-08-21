<?php
session_start();
if (!isset($_SESSION['userloggedin'])) {
    header("Location: login.php");
    exit();
}
include 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css' />
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.9.0/css/all.min.css' />
    <!--Bootstrap CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>My Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 100px;
            background: #fef0e8;
        }

        .tabs {
            display: flex;
            cursor: pointer;
            justify-content: space-evenly;
            background-color: #faa79d;
            color: black;
            padding: 10px 0 15px 0;
        }

        .tab {
            padding: 10px 20px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-size: 1.2rem;
        }

        .tab:hover {
            background-color: rgba(255, 99, 132, 0.4);
        }

        .tab.active {
            border-bottom: 2px solid rgba(255, 99, 132, 5);
        }

        .tab-content {
            display: none;
            padding: 40px 60px;
            background-color: #fdd9c9;
            margin-bottom: 50px;
        }

        .tab-content.active {
            display: block;
        }

        .order {
            background-color: #fcbbb3;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(255, 99, 132, 0.2);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid rgba(255, 99, 132, 0.8);
            padding-bottom: 10px;
        }

        .order-header div {
            font-weight: bold;
        }

        .order-details {
            margin-bottom: 10px;
        }

        .order-items {
            border-top: 1px solid rgba(255, 99, 132, 0.8);
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 99, 132, 0.8);
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-total {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
        }

        .cancel-btn {
            background-color: red;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container-div {
            width: 85%;
        }

        .customer-details {
            display: flex;
            justify-content: space-between;
            font-size: 1.1rem;
        }

        .customer-details strong,
        .order-items strong {
            font-weight: 600;
        }

        .order-items strong {
            padding-right: 5px;
        }

        .status-pending .status-text {
            color: #fb4a36;
            /* Orange color for pending */
        }

        .status-processing .status-text {
            color: #f39c12;
            /* Yellow color for processing */
        }

        .status-on-the-way .status-text {
            color: #3498db;
            /* Blue color for on the way */
        }

        .status-completed .status-text {
            color: #27ae60;
            /* Green color for completed */
        }

        .status-cancelled .status-text {
            color: #e74c3c;
            /* Red color for cancelled */
        }

        /* Modal Background */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        /* Modal Content */
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
            position: relative;

        }

        /* Close Button */
        .modal-close {
            position: absolute;
            color: red;
            font-size: 30px;
            font-weight: bold;
            top: 0px;
            right: 10px;
        }

        .modal-close:hover,
        .modal-close:focus {
            color: orangered;
            text-decoration: none;
            cursor: pointer;
        }

        /* Cancel Reason Textarea */
        textarea {
            width: 100%;
            height: 100px;
            margin-bottom: 20px !important;
            padding: 10px;
            border-radius: 5px;
            border: 2px solid #ccc;
            box-sizing: border-box;
        }

        /* Cancel Order Button */
        button {
            background-color: #f44336;
            /* Red */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px !important;
            cursor: pointer;
            border-radius: 5px;
        }

        button:hover {
            background-color: #c62828;
            /* Darker Red */
        }

        .star-rating {
            direction: rtl;
            display: inline-block;
            font-size: 2rem;
            unicode-bidi: bidi-override;
        }

        .review-btn {
            background: #27ae60 !important;
            transition: background 0.3s ease;
        }

        .review-btn:hover {
            background: green !important;
        }

        .star-rating input[type="radio"] {
            display: none;
        }

        .star-rating label {
            color: #ccc;
            /* Gray color for unselected stars */
            cursor: pointer;
        }

        .star-rating label:hover,
        .star-rating label:hover~label {
            color: #ffcc00;
            /* Yellow color for hovered stars */
        }

        .star-rating input[type="radio"]:checked~label {
            color: #ffcc00;
            /* Yellow color for selected stars */
        }

        .review-section strong {
            font-weight: 600;
            font-size: 1.1rem;
            text-align: left;
        }

        .review-section span {
            font-size: 1.1rem;
        }

        .review {
            display: flex;
            justify-content: space-between;

        }

        @media screen and (max-width: 900px) {
            .tabs {
                display: none;
            }

            .tab-content {
                display: none;
                padding: 0px;
                background-color: none !important;
                background: none !important;
                margin-bottom: 50px;
            }

            .customer-details {
                display: flex;
                justify-content: flex-start !important;
                gap: 20px;
                font-size: 1rem;
            }

            .order-header {
                font-size: 1rem !important;
            }

            .review {
                display: flex;
                justify-content: flex-start !important;
                gap: 20px;
                font-size: 1rem;
            }
        }

        #reviewModal .review-btn {
            margin-top: 0px !important;
            width: 100%;
        }
    </style>
</head>

<body>
    <?php
    include_once("nav-logged.php");
    ?>
    <div class="main-container">
        <div class="container-div">
            <div class="tabs">
                <div class="tab active" data-status="All">All</div>
                <div class="tab" data-status="Pending">Pending</div>
                <div class="tab" data-status="Processing">Processing</div>
                <div class="tab" data-status="On the way">On the way</div>
                <div class="tab" data-status="Completed">Completed</div>
                <div class="tab" data-status="Cancelled">Cancelled</div>
            </div>
            <div id="orders">
                <div class="tab-content active" id="all-orders"></div>
                <div class="tab-content" id="pending-orders"></div>
                <div class="tab-content" id="processing-orders"></div>
                <div class="tab-content" id="on-the-way-orders"></div>
                <div class="tab-content" id="completed-orders"></div>
                <div class="tab-content" id="cancelled-orders"></div>
            </div>
        </div>
    </div>


    <!-- Cancel Reason Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Cancel Order</h2>
            <textarea id="cancelReason" placeholder="Enter reason for cancellation..."></textarea>
            <button id="cancelOrderBtn">Cancel Order</button>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Submit Your Review</h2>
            <form id="reviewForm" action="submit_reviews.php" method="POST">

                <input type="hidden" name="email" value="<?php echo $userEmail; ?>"> <!-- Hidden email field -->
                <input type="hidden" id="reviewOrderId" name="orderId">
                <!-- Display Stars -->
                <div class="star-rating">
                    <input type="radio" id="star5" name="rating" value="5" />
                    <label for="star5" title="5 stars">&#9733;</label>
                    <input type="radio" id="star4" name="rating" value="4" />
                    <label for="star4" title="4 stars">&#9733;</label>
                    <input type="radio" id="star3" name="rating" value="3" />
                    <label for="star3" title="3 stars">&#9733;</label>
                    <input type="radio" id="star2" name="rating" value="2" />
                    <label for="star2" title="2 stars">&#9733;</label>
                    <input type="radio" id="star1" name="rating" value="1" />
                    <label for="star1" title="1 star">&#9733;</label>
                </div>
                <br>
                <label for="reviewText">Review:</label>
                <textarea id="reviewText" name="reviewText" rows="4" cols="50"></textarea>
                <br>

                <br>
                <button type="submit" id="submitReviewBtn" class="review-btn">Submit Review</button>
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
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('.star-rating input[type="radio"]');

            stars.forEach(star => {
                star.addEventListener('change', function() {
                    const rating = this.value;
                    console.log('Selected rating:', rating);
                    // You can send this rating value to your server via AJAX or form submission
                });
            });
        });
    </script>
    <script>
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelector('.tab.active').classList.remove('active');
                this.classList.add('active');

                const status = this.getAttribute('data-status');
                document.querySelector('.tab-content.active').classList.remove('active');
                document.getElementById(`${status.toLowerCase().replace(/ /g, '-')}-orders`).classList.add('active');

                fetchOrders(status);
            });
        });

        function fetchOrders(status) {
            fetch(`fetch_orders.php?status=${status}`)
                .then(response => response.json())
                .then(data => {
                    data.sort((a, b) => new Date(b.order_date) - new Date(a.order_date)); // Sort orders in descending order based on order_date

                    const ordersContainer = document.getElementById(`${status.toLowerCase().replace(/ /g, '-')}-orders`);
                    ordersContainer.innerHTML = data.map(order => `
                        <div class="order container" style="padding: 20px 30px;">
                            <div class="order-header" style="font-size: 1.3rem;">
                                <div>Order ID: #${order.order_id}</div>
                                <div class="status ${getStatusClass(order.order_status)}">Status: <span class="status-text">${order.order_status}</span></div>
                            </div>
                            <div class="order-details">
                                <div class="customer-details">
                                    <div><p><strong>Name: </strong></p></div>
                                    <div><p>${order.firstName} ${order.lastName}</p></div>
                                </div>
                                <div class="customer-details">
                                    <div><p><strong>Address: </strong></p></div>
                                    <div><p>${order.address}</p></div>
                                </div>
                                <div class="customer-details">
                                    <div><p><strong>Contact: </strong></p></div>
                                    <div><p>${order.phone}</p></div>
                                </div>
                                <div class="customer-details">
                                    <div><p><strong>Payment Method: </strong></p></div>
                                    <div><p>${order.pmode}</p></div>
                                </div>
                                <div class="customer-details">
                                    <div><p><strong>Order Date: </strong></p></div>
                                    <div><p>${new Date(order.order_date).toLocaleString()}</p></div>
                                </div>
                                <div class="customer-details">
                                    <div><p><strong>Order Note: </strong></p></div>
                                    <div><p>${order.note || 'Null'}</p></div>
                                </div>
                            </div>
                            <div class="order-items" style="font-size: 1.1rem;">
                                ${order.items.map(item => `
                                    <div class="order-item">
                                        <div>${item.itemName} (x${item.quantity})</div>
                                        <div>${item.total_price}</div>
                                    </div>
                                `).join('')}
                                 <div class="order-total">Grand Total: ${order.grand_total}</div>
                        ${order.order_status === 'Cancelled' ? `
                        <div class="review mt-3">
                        <div><p><strong>Cancellation Reason: </strong></div>
                        <div><p>${order.cancel_reason}</p></div>
                        </div>` : ''}
                    </div>
                   ${order.order_status !== 'Completed' && order.order_status !== 'Cancelled' ? `<button class="cancel-btn" onclick="openCancelModal(${order.order_id})">Cancel Order</button>` : ''}
                    ${(order.order_status === 'Completed' || order.order_status === 'Cancelled') && !order.review_text ? `
                        <button class="review-btn" onclick="openReviewModal(${order.order_id})">Write a Review</button>
                    ` : ''}
                    ${(order.order_status === 'Completed' || order.order_status === 'Cancelled') && order.review_text ? `
                        <div class="review-section">
                         <div class=" review">
                            <div><p><strong>Your Review: </strong></p></div>
                            <div><p><span>${order.review_text}</span></p></div>
                         </div>
                            ${order.response ? `
                            <div class=" review">
                              <div><p><strong>Response: </strong></p></div>
                              <div><p><span>${order.response}</span></p></div>
                            </div>` : ''}
                        </div>
                    ` : ''}
                </div>
            `).join('');
                })
                .catch(error => console.error('Error fetching orders:', error));
        }

        function getStatusClass(status) {
            switch (status) {
                case 'Pending':
                    return 'status-pending';
                case 'Processing':
                    return 'status-processing';
                case 'On the way':
                    return 'status-on-the-way';
                case 'Completed':
                    return 'status-completed';
                case 'Cancelled':
                    return 'status-cancelled';
                default:
                    return '';
            }
        }




        // Load all orders by default
        fetchOrders('All');
    </script>

    <script>
        // Function to open Cancel Modal
        function openCancelModal(orderId) {
            document.getElementById("cancelModal").setAttribute("data-order-id", orderId);
            document.getElementById("cancelModal").style.display = "block";
        }



        // Close Cancel Modal
        document.querySelector(".modal-close").onclick = function() {
            document.getElementById("cancelModal").style.display = "none";
        };

        // Handle Cancel Order button click
        document.getElementById("cancelOrderBtn").onclick = function() {
            var cancelReason = document.getElementById("cancelReason").value;
            var orderId = document.getElementById("cancelModal").getAttribute("data-order-id");

            if (cancelReason.trim() === "") {
                alert("Please enter a reason for cancellation.");
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "cancel_order.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert("Order has been cancelled.");
                    window.location.href = "orders.php"; // Redirect after cancellation
                } else {
                    alert("Failed to cancel the order. Please try again.");
                }
            };
            xhr.onerror = function() {
                console.error("Request failed.");
            };
            xhr.send("orderId=" + encodeURIComponent(orderId) + "&reason=" + encodeURIComponent(cancelReason));

            document.getElementById("cancelModal").style.display = "none";
        };

        // Open Review Modal
        function openReviewModal(orderId) {
            document.getElementById("reviewOrderId").value = orderId; // Set the hidden order ID field
            document.getElementById("reviewModal").style.display = "block"; // Show the modal
        }

        // Close Review Modal
        function closeReviewModal() {
            document.getElementById("reviewModal").style.display = "none"; // Hide the modal
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById("reviewModal");
            if (event.target === modal) { // Check if the click is outside the modal content
                closeReviewModal();
            }
        };

        // Optional: Close modal when clicking on the close button inside the modal
        document.querySelector(".modal-close").addEventListener("click", closeReviewModal);


        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById("cancelModal")) {
                document.getElementById("cancelModal").style.display = "none";
            } else if (event.target == document.getElementById("reviewModal")) {
                closeReviewModal();
            }
        };

        // Load all orders by default
        fetchOrders('All');
    </script>





</body>

</html>