<?php
// start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// create constants
if (!defined('SITEURL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('SITEURL', $protocol . $host . '/fooddelivery/');
}

define('LOCALHOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'foodorder');

// 1. Ensure Database Exists
$init_conn = @mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD);
if ($init_conn) {
    @mysqli_query($init_conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_conn);
}

// 2. Connect to database
$conn = mysqli_connect(LOCALHOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($conn, "SHOW TABLES LIKE 'tbl_admin1'");
if (!$chk || mysqli_num_rows($chk) == 0) {

    // Table: tbl_admin1
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_admin1` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `full_name` varchar(100) NOT NULL,
            `username` varchar(100) NOT NULL UNIQUE,
            `password` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: tbl_category1
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_category1` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` varchar(100) NOT NULL,
            `image_name` varchar(255) DEFAULT '',
            `featured` varchar(10) DEFAULT 'Yes',
            `active` varchar(10) DEFAULT 'Yes'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: tbl_food1
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_food1` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` varchar(100) NOT NULL,
            `description` text NOT NULL,
            `price` decimal(10,2) NOT NULL DEFAULT 0.00,
            `image_name` varchar(255) DEFAULT '',
            `category_id` int(10) UNSIGNED NOT NULL,
            `featured` varchar(10) DEFAULT 'Yes',
            `active` varchar(10) DEFAULT 'Yes'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: tbl_order1
    mysqli_query($conn, "
        CREATE TABLE IF NOT EXISTS `tbl_order1` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `food` varchar(150) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `qty` int(11) NOT NULL DEFAULT 1,
            `total` decimal(10,2) NOT NULL DEFAULT 0.00,
            `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` varchar(50) NOT NULL DEFAULT 'Ordered',
            `customer_name` varchar(150) NOT NULL,
            `customer_contact` varchar(20) NOT NULL,
            `customer_email` varchar(150) NOT NULL,
            `customer_address` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Admin Account (Username: admin, Password: admin123 & admin)
    $pwd_admin123 = md5('admin123');
    $pwd_admin = md5('admin');
    mysqli_query($conn, "INSERT INTO `tbl_admin1` (`id`, `full_name`, `username`, `password`) VALUES
        (1, 'Administrator', 'admin', '{$pwd_admin123}'),
        (2, 'Master Chef', 'chef', '{$pwd_admin}')
        ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

    // Seed Categories
    mysqli_query($conn, "INSERT INTO `tbl_category1` (`id`, `title`, `image_name`, `featured`, `active`) VALUES
        (1, 'Pizza', 'Food_Category_287.jpg', 'Yes', 'Yes'),
        (2, 'Burger', 'Food_Category_294.jpg', 'Yes', 'Yes'),
        (3, 'Momo', 'Food_Category_329.jpg', 'Yes', 'Yes'),
        (4, 'Sandwich', 'Food_Category_360.jpg', 'Yes', 'Yes')
        ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);");

    // Seed Foods
    mysqli_query($conn, "INSERT INTO `tbl_food1` (`id`, `title`, `description`, `price`, `image_name`, `category_id`, `featured`, `active`) VALUES
        (1, 'Smoky BBQ Chicken Pizza', 'Loaded with marinated chicken, barbecue sauce, fresh peppers and extra mozzarella cheese.', 12.00, 'Food-Name-1250.jpg', 1, 'Yes', 'Yes'),
        (2, 'Double Cheeseburger Supreme', 'Juicy grilled double beef patty topped with aged cheddar, crisp lettuce, tomato and secret relish.', 8.50, 'Food-Name-1258.jpg', 2, 'Yes', 'Yes'),
        (3, 'Steamed Himalayan Dumplings Momo', 'Handcrafted traditional spiced dumplings served with fiery sesame tomato chutney.', 7.00, 'Food-Name-1262.jpg', 3, 'Yes', 'Yes'),
        (4, 'Italian Margherita Pizza', 'Classic sourdough pizza crust with authentic San Marzano tomatoes, buffalo mozzarella and basil.', 10.00, 'Food-Name-1490.jpg', 1, 'Yes', 'Yes'),
        (5, 'Crispy Zinger Chicken Burger', 'Crunchy deep-fried chicken fillet with garlic aioli and fresh coleslaw on a brioche bun.', 7.50, 'Food-Name-1823.jpg', 2, 'Yes', 'Yes'),
        (6, 'Pan-Fried Chili Momo', 'Crisp pan-seared momos tossed in a spicy garlic bell pepper glaze.', 8.00, 'Food-Name-2305.jpg', 3, 'Yes', 'Yes')
        ON DUPLICATE KEY UPDATE `title`=VALUES(`title`);");

    // Seed Sample Orders
    mysqli_query($conn, "INSERT INTO `tbl_order1` (`id`, `food`, `price`, `qty`, `total`, `order_date`, `status`, `customer_name`, `customer_contact`, `customer_email`, `customer_address`) VALUES
        (1, 'Smoky BBQ Chicken Pizza', 12.00, 2, 24.00, NOW(), 'Delivered', 'John Doe', '0123456789', 'john@gmail.com', '123 Main Street, Apt 4B'),
        (2, 'Double Cheeseburger Supreme', 8.50, 1, 8.50, NOW(), 'On Delivery', 'Sarah Jenkins', '0198765432', 'sarah@gmail.com', '742 Evergreen Terrace')
        ON DUPLICATE KEY UPDATE `customer_name`=VALUES(`customer_name`);");
}
?>
