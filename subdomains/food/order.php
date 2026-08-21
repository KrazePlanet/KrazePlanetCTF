<?php
include('config/constants.php');

if (isset($_GET['food_id'])) {
    // Get the food id and details of the selected food
    $food_id = intval($_GET['food_id']);
    $sql = "SELECT * FROM tbl_food WHERE id=$food_id";
    $res = mysqli_query($conn, $sql);
    $count = mysqli_num_rows($res);

    if ($count == 1) {
        $row = mysqli_fetch_assoc($res);
        $title = $row['title'];
        $price = $row['price'];
        $image_name = $row['image_name'];
    } else {
        header('location:'.SITEURL);
        exit();
    }
} else {
    header('location:'.SITEURL);
    exit();
}

// Handle Order Submission BEFORE any HTML output
if (isset($_POST['submit'])) {
    $food = mysqli_real_escape_string($conn, $_POST['food']);
    $price = floatval($_POST['price']);
    $qty = intval($_POST['qty']);
    if ($qty <= 0) $qty = 1;
    
    $total = $price * $qty;
    $order_date = date("Y-m-d H:i:s"); // Valid MySQL standard datetime
    $status = "Ordered";

    $customer_name = mysqli_real_escape_string($conn, $_POST['full-name']);
    $customer_contact = mysqli_real_escape_string($conn, $_POST['contact']);
    $customer_email = mysqli_real_escape_string($conn, $_POST['email']);
    $customer_address = mysqli_real_escape_string($conn, $_POST['address']);

    $sql2 = "INSERT INTO tbl_order (food, price, qty, total, order_date, status, customer_name, customer_contact, customer_email, customer_address) VALUES (
        '$food',
        $price,
        $qty,
        $total,
        '$order_date',
        '$status',
        '$customer_name',
        '$customer_contact',
        '$customer_email',
        '$customer_address'
    )";

    $res2 = mysqli_query($conn, $sql2);

    if($res2 == true){
        $_SESSION['order'] = "<div class='success text-center' style='padding:14px;background:#d4edda;color:#155724;border-radius:8px;margin:20px auto;max-width:560px;font-weight:bold;box-shadow:0 4px 12px rgba(0,0,0,0.08);'>🎉 Order Placed Successfully! We are preparing your delicious {$food}.</div>";
        header('location:'.SITEURL);
        exit();
    } else {
        $db_err = mysqli_error($conn);
        $_SESSION['order'] = "<div class='error text-center' style='padding:14px;background:#f8d7da;color:#721c24;border-radius:8px;margin:20px auto;max-width:560px;font-weight:bold;'>Failed to order food: {$db_err}</div>";
        header('location:'.SITEURL);
        exit();
    }
}

include('partials-front/menu.php');
?>
    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search">
        <div class="container">
            
            <h2 class="text-center text-white">Fill this form to confirm your order.</h2>

            <form method="POST" class="order">
                <fieldset>
                    <legend>Selected Food</legend>

                    <div class="food-menu-img">
                        <?php 
                            if ($image_name == "") {
                                echo "<div class='error'>Image not Available</div>";
                            } else {
                                ?>
                                <img src="<?php echo SITEURL; ?>images/food/<?php echo $image_name; ?>" alt="<?php echo htmlspecialchars($title); ?>" class="img-responsive img-curve">
                               <?php
                            }
                        ?>
                    </div>
    
                    <div class="food-menu-desc">
                        <h3><?php echo htmlspecialchars($title); ?></h3>
                        <input type="hidden" name="food" value="<?php echo htmlspecialchars($title); ?>">
                        <p class="food-price">$<?php echo number_format($price, 2); ?></p>
                        <input type="hidden" name="price" value="<?php echo $price; ?>">

                        <div class="order-label">Quantity</div>
                        <input type="number" name="qty" class="input-responsive" value="1" min="1" max="50" required>
                    </div>
                </fieldset>
                
                <fieldset>
                    <legend>Delivery Details</legend>
                    <div class="order-label">Full Name</div>
                    <input type="text" name="full-name" placeholder="E.g. Vijay Thapa" class="input-responsive" required>

                    <div class="order-label">Phone Number</div>
                    <input type="tel" name="contact" placeholder="E.g. 9843xxxxxx" class="input-responsive" required>

                    <div class="order-label">Email</div>
                    <input type="email" name="email" placeholder="E.g. hi@vijaythapa.com" class="input-responsive" required>

                    <div class="order-label">Address</div>
                    <textarea name="address" rows="5" placeholder="E.g. Street, City, Country" class="input-responsive" required></textarea>

                    <input type="submit" name="submit" value="Confirm Order" class="btn btn-primary">
                </fieldset>
            </form>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->

<?php
    include('partials-front/footer.php');
?>
