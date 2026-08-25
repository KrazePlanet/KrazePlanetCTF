<?php
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$user = 'root';
$pass = '';
$dbname = 'restaurant_website';

// 1. Initial Connection to Ensure Database Exists
try {
    $init_con = new PDO("mysql:host={$host}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $init_con->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    unset($init_con);
} catch (PDOException $ex) {
    // Ignore if already exists or permission issues
}

// 2. Connect to restaurant_website database
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
$option = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
);

try {
    $con = new PDO($dsn, $user, $pass, $option);
} catch (PDOException $ex) {
    echo "Failed to connect with database ! " . $ex->getMessage();
    die();
}

// 3. Auto-Provision Schema and Seed Data If Empty
try {
    $chk = $con->query("SHOW TABLES LIKE 'users'")->rowCount();
    if ($chk === 0) {

        // Table: clients
        $con->exec("
            CREATE TABLE IF NOT EXISTS `clients` (
              `client_id` int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `client_name` varchar(50) NOT NULL,
              `client_phone` varchar(50) NOT NULL,
              `client_email` varchar(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: image_gallery
        $con->exec("
            CREATE TABLE IF NOT EXISTS `image_gallery` (
              `image_id` int(2) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `image_name` varchar(30) NOT NULL,
              `image` varchar(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: menu_categories
        $con->exec("
            CREATE TABLE IF NOT EXISTS `menu_categories` (
              `category_id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `category_name` varchar(50) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: menus
        $con->exec("
            CREATE TABLE IF NOT EXISTS `menus` (
              `menu_id` int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `menu_name` varchar(100) NOT NULL,
              `menu_description` varchar(255) NOT NULL,
              `menu_price` decimal(6,2) NOT NULL,
              `menu_image` varchar(255) NOT NULL,
              `category_id` int(3) NOT NULL,
              FOREIGN KEY (`category_id`) REFERENCES `menu_categories` (`category_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: tables
        $con->exec("
            CREATE TABLE IF NOT EXISTS `tables` (
              `table_id` int(3) NOT NULL AUTO_INCREMENT PRIMARY KEY
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: placed_orders
        $con->exec("
            CREATE TABLE IF NOT EXISTS `placed_orders` (
              `order_id` int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `order_time` datetime NOT NULL,
              `client_id` int(5) NOT NULL,
              `delivery_address` varchar(255) NOT NULL,
              `delivered` tinyint(1) NOT NULL DEFAULT 0,
              `canceled` tinyint(1) NOT NULL DEFAULT 0,
              `cancellation_reason` varchar(255) DEFAULT NULL,
              FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: in_order
        $con->exec("
            CREATE TABLE IF NOT EXISTS `in_order` (
              `id` int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `order_id` int(5) NOT NULL,
              `menu_id` int(5) NOT NULL,
              `quantity` int(3) NOT NULL DEFAULT 1,
              FOREIGN KEY (`order_id`) REFERENCES `placed_orders` (`order_id`) ON DELETE CASCADE,
              FOREIGN KEY (`menu_id`) REFERENCES `menus` (`menu_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: reservations
        $con->exec("
            CREATE TABLE IF NOT EXISTS `reservations` (
              `reservation_id` int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `date_created` datetime NOT NULL,
              `client_id` int(5) NOT NULL,
              `selected_time` datetime NOT NULL,
              `nbr_guests` int(2) NOT NULL,
              `table_id` int(3) NOT NULL,
              `liberated` tinyint(1) NOT NULL DEFAULT 0,
              `canceled` tinyint(1) NOT NULL DEFAULT 0,
              `cancellation_reason` varchar(255) DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: users
        $con->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `user_id` int(2) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `username` varchar(20) NOT NULL UNIQUE,
              `email` varchar(50) NOT NULL UNIQUE,
              `full_name` varchar(50) NOT NULL,
              `password` varchar(100) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: website_settings
        $con->exec("
            CREATE TABLE IF NOT EXISTS `website_settings` (
              `option_id` int(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `option_name` varchar(255) NOT NULL UNIQUE,
              `option_value` varchar(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed Users (admin / admin & admin_user / admin)
        $sha1_admin = sha1('admin');
        $sha1_pass  = sha1('password');
        $con->exec("INSERT INTO `users` (`user_id`, `username`, `email`, `full_name`, `password`) VALUES
            (1, 'admin', 'admin@restaurant.com', 'Restaurant Manager', '{$sha1_admin}'),
            (2, 'admin_user', 'user_admin@gmail.com', 'User Admin', '{$sha1_admin}')
            ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

        // Seed Website Settings
        $con->exec("INSERT INTO `website_settings` (`option_id`, `option_name`, `option_value`) VALUES
            (1, 'restaurant_name', 'VINCENT PIZZA'),
            (2, 'restaurant_email', 'vincent.pizza@gmail.com'),
            (3, 'admin_email', 'admin_email@gmail.com'),
            (4, 'restaurant_phonenumber', '088866777555'),
            (5, 'restaurant_address', '1580 Boone Street, Corpus Christi, TX, 78476 - USA')
            ON DUPLICATE KEY UPDATE `option_value`=VALUES(`option_value`);");

        // Seed Tables
        $con->exec("INSERT INTO `tables` (`table_id`) VALUES (1), (2), (3), (4), (5) ON DUPLICATE KEY UPDATE `table_id`=VALUES(`table_id`);");

        // Seed Menu Categories
        $con->exec("INSERT INTO `menu_categories` (`category_id`, `category_name`) VALUES
            (1, 'burgers'),
            (2, 'desserts'),
            (3, 'drinks'),
            (4, 'pasta'),
            (5, 'pizzas'),
            (6, 'salads'),
            (7, 'Traditional Food')
            ON DUPLICATE KEY UPDATE `category_name`=VALUES(`category_name`);");

        // Seed Menus
        $con->exec("INSERT INTO `menus` (`menu_id`, `menu_name`, `menu_description`, `menu_price`, `menu_image`, `category_id`) VALUES
            (1, 'Artisan Margherita Pizza', 'Fresh mozzarella, San Marzano tomatoes, fresh basil and extra virgin olive oil.', 14.50, 'pizza_image.png', 5),
            (2, 'Gourmet Truffle Burger', 'Angus beef patty, smoked bacon, cheddar cheese, caramelized onions and truffle mayo.', 16.00, 'food_pic.jpg', 1),
            (3, 'Classic Italian Fettuccine', 'Homemade fettuccine pasta with creamy parmesan Alfredo sauce and fresh herbs.', 13.00, 'img_1.jpg', 4),
            (4, 'Mediterranean Caesar Salad', 'Crisp romaine lettuce, garlic herb croutons, aged parmesan and Caesar dressing.', 9.50, 'img_2.jpg', 6),
            (5, 'Belgian Chocolate Souffle', 'Warm chocolate molten cake served with Bourbon vanilla bean gelato.', 8.00, 'img_3.jpg', 2),
            (6, 'Signature House Mojito', 'Fresh mint leaves, lime wedges, pure cane sugar and sparkling mineral soda.', 6.50, 'back_3.jpg', 3)
            ON DUPLICATE KEY UPDATE `menu_name`=VALUES(`menu_name`);");

        // Seed Image Gallery
        $con->exec("INSERT INTO `image_gallery` (`image_id`, `image_name`, `image`) VALUES
            (1, 'Artisan Pizza', 'pizza_image.png'),
            (2, 'Italian Pasta', 'img_1.jpg'),
            (3, 'Chef Specials', 'img_2.jpg'),
            (4, 'Crispy Bites', 'img_3.jpg'),
            (5, 'Signature Drinks', 'food_pic_2.jpg')
            ON DUPLICATE KEY UPDATE `image_name`=VALUES(`image_name`);");
    }
} catch (PDOException $e) {
    error_log("Restaurant auto-provisioning notice: " . $e->getMessage());
}
?>
