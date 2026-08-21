<?php
include 'components/connect.php';
?>

<header class="header">
    <section class="flex">
        <a href="home.php" class="logo">
            <img src="img/logo.png" alt="Logo">
        </a>

        <nav class="navbar">
            <a href="home.php">home</a>
            <a href="about.php">about</a>
            <a href="spaces.php">Spaces</a>
            <a href="booking.php">booking</a>
            <a href="pricing.php">Pricing</a>
            <a href="contact.php">contact</a>
        </nav>

        <div class="icons">
            <?php
            if (isset($message)) {
                $count_cart_items = $conn->prepare("SELECT * FROM `bookings` WHERE user_id = ?");
                $count_cart_items->bindValue(1, $user_id);
                $count_cart_items->execute();
                $total_cart_items = $count_cart_items->rowCount();
            }
            ?>
            <a href="search.php"><i class="fas fa-search"></i></a>
            <!--<a href="cart.php">
                <i class="fas fa-shopping-cart"></i>
                <span>(<?= $total_cart_items ?? 0; ?>)</span>
            </a>-->
            <div id="user-btn" class="fas fa-user"></div>
            <div id="menu-btn" class="fas fa-bars"></div>
        </div>

        <div class="profile">
            <?php
            $fetch_profile = null; // Initialize the variable with a default value
            if (isset($user_id)) {
                $select_profile = $conn->prepare("SELECT * FROM `users` WHERE id = ?");
                $select_profile->execute([$user_id]);
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
            }
            if ($fetch_profile) {
            ?>
                <p class="name"><?= $fetch_profile['name']; ?></p>
                <div class="flex">
                    <a href="profile.php" class="btn">profile</a>
                    <a href="components/user_logout.php" onclick="return confirm('logout from this website?');" class="delete-btn">logout</a>
                </div>
            <?php
            } else {
            ?>
                <p class="name">please login first!</p>
                <a href="login.php" class="btn">login</a>
            <?php
            }
            ?>
            <p class="account">
                <a href="admin/admin-login.php">login Admin</a> or
                <a href="register.php">register</a>
            </p>
        </div>
    </section>

    <?php
    if (isset($message)) {
        foreach ($message as $msg) {
    ?>
            <div class="message">
                <span><?= $msg ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
    <?php
        }
    }
    ?>
</header>

