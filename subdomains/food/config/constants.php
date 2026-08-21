<?php
// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamic Site URL resolution
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('SITEURL', $protocol . '://' . $host . '/food/');
define('LOCALHOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'food_order');

// 1. Initial connection to server to ensure database exists
$init_conn = @mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to food_order database
$conn = @mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

// 3. Auto-provision tables and seed data if empty
$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_admin'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Create tbl_admin
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_admin` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `full_name` varchar(100) NOT NULL,
          `username` varchar(100) NOT NULL UNIQUE,
          `password` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create tbl_category
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_category` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `title` varchar(100) NOT NULL,
          `image_name` varchar(255) NOT NULL,
          `featured` varchar(10) NOT NULL DEFAULT 'Yes',
          `active` varchar(10) NOT NULL DEFAULT 'Yes'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create tbl_food
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_food` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `title` varchar(100) NOT NULL,
          `description` text NOT NULL,
          `price` decimal(10,2) NOT NULL,
          `image_name` varchar(255) NOT NULL,
          `category_id` int(10) unsigned NOT NULL,
          `featured` varchar(10) NOT NULL DEFAULT 'Yes',
          `active` varchar(10) NOT NULL DEFAULT 'Yes'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Create tbl_order
    @mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_order` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `food` varchar(150) NOT NULL,
          `price` decimal(10,2) NOT NULL,
          `qty` int(11) NOT NULL,
          `total` decimal(10,2) NOT NULL,
          `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `status` varchar(50) NOT NULL DEFAULT 'Ordered',
          `customer_name` varchar(150) NOT NULL,
          `customer_contact` varchar(20) NOT NULL,
          `customer_email` varchar(150) NOT NULL,
          `customer_address` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Admin: admin / admin (MD5: 21232f297a57a5a743894a0e4a801fc3)
    @mysqli_query($conn, "INSERT INTO `tbl_admin` (`id`, `full_name`, `username`, `password`) VALUES
        (1, 'System Administrator', 'admin', '21232f297a57a5a743894a0e4a801fc3')
        ON DUPLICATE KEY UPDATE `password`='21232f297a57a5a743894a0e4a801fc3';");

    // Seed Categories
    @mysqli_query($conn, "INSERT INTO `tbl_category` (`id`, `title`, `image_name`, `featured`, `active`) VALUES
        (1, 'Pizza', 'Food_Category_844.jpg', 'Yes', 'Yes'),
        (2, 'Burger', 'Food_Category_534.jpg', 'Yes', 'Yes'),
        (3, 'Momo & Dumplings', 'Food_Category_304.jpg', 'Yes', 'Yes'),
        (4, 'Sandwich', 'Food_Category_647.jpg', 'Yes', 'Yes');");

    // Seed Foods
    @mysqli_query($conn, "INSERT INTO `tbl_food` (`id`, `title`, `description`, `price`, `image_name`, `category_id`, `featured`, `active`) VALUES
        (1, 'Smoky BBQ Pizza', 'Hand-stretched dough with Italian mozzarella, smoked bacon and barbecue glaze.', 14.99, 'Food-Name-7040.jpg', 1, 'Yes', 'Yes'),
        (2, 'Gourmet Angus Burger', 'Flame-grilled prime Angus beef patty with cheddar, crisp lettuce and brioche bun.', 9.99, 'Food-name-5157.jpg', 2, 'Yes', 'Yes'),
        (3, 'Steamed Himalayan Momo', 'Authentic spiced chicken dumplings served with fiery sesame-chili dipping sauce.', 8.50, 'Food-name-4546.jpg', 3, 'Yes', 'Yes'),
        (4, 'Cheesy Pepperoni Deluxe', 'Double pepperoni slices with melted aged provolone and rich tomato sauce.', 16.50, 'Food-Name-8400.jpg', 1, 'Yes', 'Yes'),
        (5, 'Crispy Zinger Chicken Burger', 'Golden fried chicken breast fillet with spicy mayo, pickles and cheddar.', 10.99, 'Food-name-4197.jpg', 2, 'Yes', 'Yes'),
        (6, 'Grilled Club Sandwich', 'Triple decker toast with roasted turkey, smoked bacon, cheddar and herbs.', 7.99, 'Food-name-2986.jpg', 4, 'Yes', 'Yes');");

    // Seed Sample Orders
    @mysqli_query($conn, "INSERT INTO `tbl_order` (`food`, `price`, `qty`, `total`, `status`, `customer_name`, `customer_contact`, `customer_email`, `customer_address`) VALUES
        ('Smoky BBQ Pizza', 14.99, 2, 29.98, 'Delivered', 'Julian Vance', '+1 555-019-2831', 'julian@example.com', '742 Evergreen Terrace'),
        ('Gourmet Angus Burger', 9.99, 1, 9.99, 'On Delivery', 'Elena Rostova', '+1 555-829-1049', 'elena@example.com', '124 Conch Street'),
        ('Steamed Himalayan Momo', 8.50, 3, 25.50, 'Ordered', 'Marcus Brody', '+1 555-391-7720', 'mbrody@example.com', '350 5th Avenue');");
} else {
    // Ensure default admin password is MD5('admin')
    @mysqli_query($conn, "INSERT INTO `tbl_admin` (`id`, `full_name`, `username`, `password`) VALUES
        (1, 'System Administrator', 'admin', '21232f297a57a5a743894a0e4a801fc3')
        ON DUPLICATE KEY UPDATE `password`='21232f297a57a5a743894a0e4a801fc3';");
}
?>
