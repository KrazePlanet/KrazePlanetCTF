<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'restaurantdb');

// 1. Initial Connection to Ensure Database Exists
$init_link = @new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($init_link && !$init_link->connect_error) {
    @$init_link->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @$init_link->close();
}

// 2. Connect to restaurantdb database
$link = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($link->connect_error) {
    die('Connection Failed: ' . $link->connect_error);
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @$link->query("SHOW TABLES LIKE 'Accounts'");
if (!$chk || $chk->num_rows == 0) {

    // Table: Accounts
    $link->query("
        CREATE TABLE IF NOT EXISTS `Accounts` (
          `account_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `email` varchar(255) NOT NULL,
          `register_date` date NOT NULL,
          `phone_number` varchar(20) NOT NULL,
          `password` varchar(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Staffs
    $link->query("
        CREATE TABLE IF NOT EXISTS `Staffs` (
          `staff_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `staff_name` varchar(255) NOT NULL,
          `role` varchar(255) NOT NULL,
          `account_id` int(11) NOT NULL,
          FOREIGN KEY (`account_id`) REFERENCES `Accounts` (`account_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Memberships
    $link->query("
        CREATE TABLE IF NOT EXISTS `Memberships` (
          `member_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `member_name` varchar(255) NOT NULL,
          `points` int(11) NOT NULL DEFAULT 0,
          `account_id` int(11) NOT NULL,
          FOREIGN KEY (`account_id`) REFERENCES `Accounts` (`account_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Restaurant_Tables
    $link->query("
        CREATE TABLE IF NOT EXISTS `Restaurant_Tables` (
          `table_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `capacity` int(11) NOT NULL,
          `is_available` tinyint(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Menu
    $link->query("
        CREATE TABLE IF NOT EXISTS `Menu` (
          `item_id` varchar(10) NOT NULL PRIMARY KEY,
          `item_name` varchar(255) NOT NULL,
          `item_type` varchar(255) NOT NULL,
          `item_category` varchar(255) NOT NULL,
          `item_price` decimal(10,2) NOT NULL,
          `item_description` text NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Reservations
    $link->query("
        CREATE TABLE IF NOT EXISTS `Reservations` (
          `reservation_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `customer_name` varchar(255) NOT NULL,
          `table_id` int(11) NOT NULL,
          `reservation_time` time NOT NULL,
          `reservation_date` date NOT NULL,
          `head_count` int(11) NOT NULL,
          `special_request` text DEFAULT NULL,
          FOREIGN KEY (`table_id`) REFERENCES `Restaurant_Tables` (`table_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: card_payments
    $link->query("
        CREATE TABLE IF NOT EXISTS `card_payments` (
          `card_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `account_holder_name` varchar(255) NOT NULL,
          `card_number` varchar(255) NOT NULL,
          `expiry_date` varchar(10) NOT NULL,
          `security_code` varchar(10) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Bills
    $link->query("
        CREATE TABLE IF NOT EXISTS `Bills` (
          `bill_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `staff_id` int(11) DEFAULT NULL,
          `member_id` int(11) DEFAULT NULL,
          `reservation_id` int(11) DEFAULT NULL,
          `table_id` int(11) NOT NULL,
          `card_id` int(11) DEFAULT NULL,
          `payment_method` varchar(50) NOT NULL,
          `bill_time` datetime NOT NULL,
          `payment_time` datetime DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Bill_Items
    $link->query("
        CREATE TABLE IF NOT EXISTS `Bill_Items` (
          `bill_item_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `bill_id` int(11) NOT NULL,
          `item_id` varchar(10) NOT NULL,
          `quantity` int(11) NOT NULL,
          FOREIGN KEY (`bill_id`) REFERENCES `Bills` (`bill_id`) ON DELETE CASCADE,
          FOREIGN KEY (`item_id`) REFERENCES `Menu` (`item_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Table: Kitchen
    $link->query("
        CREATE TABLE IF NOT EXISTS `Kitchen` (
          `kitchen_id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `table_id` int(11) NOT NULL,
          `item_id` varchar(10) NOT NULL,
          `quantity` int(11) NOT NULL,
          `time_submitted` datetime NOT NULL,
          `time_ended` datetime DEFAULT NULL,
          FOREIGN KEY (`item_id`) REFERENCES `Menu` (`item_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Seed Accounts & Staffs
    $link->query("INSERT INTO `Accounts` (`account_id`, `email`, `register_date`, `phone_number`, `password`) VALUES
        (1, 'johnny@gmail.com', '2023-09-15', '0123456789', '123456'),
        (2, 'steven@gmail.com', '2023-09-16', '0123456780', '123456'),
        (3, 'charlene@gmail.com', '2023-09-17', '0123456781', '123456'),
        (4, 'crystal@gmail.com', '2023-09-18', '0123456782', '123456'),
        (5, 'alice@gmail.com', '2023-09-19', '0123456783', '123456')
        ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

    $link->query("INSERT INTO `Staffs` (`staff_id`, `staff_name`, `role`, `account_id`) VALUES
        (1, 'Johnny', 'Manager', 1),
        (2, 'Steven', 'Chef', 2),
        (3, 'Charlene', 'Waiter', 3),
        (4, 'Crystal', 'Cashier', 4),
        (5, 'Alice', 'Waitress', 5)
        ON DUPLICATE KEY UPDATE `staff_name`=VALUES(`staff_name`);");

    // Seed Memberships
    $link->query("INSERT INTO `Memberships` (`member_id`, `member_name`, `points`, `account_id`) VALUES
        (1, 'Johnny VIP Member', 500, 1),
        (2, 'Steven Regular', 150, 2)
        ON DUPLICATE KEY UPDATE `member_name`=VALUES(`member_name`);");

    // Seed Restaurant Tables
    $link->query("INSERT INTO `Restaurant_Tables` (`table_id`, `capacity`, `is_available`) VALUES
        (1, 4, 1),
        (2, 2, 1),
        (3, 6, 1),
        (4, 8, 1),
        (5, 4, 1),
        (6, 2, 1),
        (7, 4, 1),
        (8, 6, 1),
        (9, 2, 1),
        (10, 4, 1)
        ON DUPLICATE KEY UPDATE `capacity`=VALUES(`capacity`);");

    // Seed Menu Items
    $link->query("INSERT INTO `Menu` (`item_id`, `item_name`, `item_type`, `item_category`, `item_price`, `item_description`) VALUES
        ('MD1', 'Prime Ribeye Steak', 'Steak', 'Main Dishes', 38.00, 'Juicy char-grilled 12oz prime ribeye with herb garlic butter.'),
        ('MD2', 'Signature Wagyu Burger', 'Burger', 'Main Dishes', 22.00, 'A5 Wagyu beef patty, aged cheddar and caramelized onion on brioche.'),
        ('MD5', 'Pan-Seared Atlantic Salmon', 'Seafood', 'Main Dishes', 28.00, 'Crispy skin salmon fillet with lemon caper reduction.'),
        ('MD15', 'BBQ Smoked Baby Back Ribs', 'Pork', 'Main Dishes', 26.00, 'Slow smoked tender ribs glazed in house bourbon BBQ sauce.'),
        ('S1', 'Truffle Parmesan Fries', 'Side', 'Side Dishes', 9.00, 'Crispy cut potatoes tossed in white truffle oil and aged parmesan.'),
        ('S3', 'Crispy Calamari Rings', 'Side', 'Side Dishes', 12.00, 'Lightly battered calamari served with smoked chipotle dip.'),
        ('L1', 'Classic Caesar Salad', 'Salad', 'Side Dishes', 10.00, 'Crisp romaine, shaved parmesan, garlic croutons and house dressing.'),
        ('HC2', 'Craft Draft IPA', 'Beer', 'Drinks', 8.50, 'Locally brewed hoppy IPA with bright citrus undertones.'),
        ('HC3', 'Vintage Cabernet Sauvignon', 'Wine', 'Drinks', 14.00, 'Full-bodied red wine with rich blackberry and oak aromas.')
        ON DUPLICATE KEY UPDATE `item_name`=VALUES(`item_name`);");

    // Seed Reservations
    $today = date('Y-m-d');
    $link->query("INSERT INTO `Reservations` (`reservation_id`, `customer_name`, `table_id`, `reservation_time`, `reservation_date`, `head_count`, `special_request`) VALUES
        (1, 'Michael Scott', 1, '19:00:00', '{$today}', 4, 'Corner booth if available.'),
        (2, 'Dwight Schrute', 2, '20:30:00', '{$today}', 2, 'Quiet dining area.')
        ON DUPLICATE KEY UPDATE `customer_name`=VALUES(`customer_name`);");
}
?>
