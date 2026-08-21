<?php
$host = 'localhost';
$user_name = 'root';
$user_password = '';
$dbname = 'cowork_db';

// 1. Initial Connection to Ensure Database Exists
try {
    $init_pdo = new PDO("mysql:host={$host}", $user_name, $user_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $init_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    unset($init_pdo);
} catch (PDOException $e) {
    // Ignore
}

// 2. Connect to cowork_db database
$db_name = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
try {
    $conn = new PDO($db_name, $user_name, $user_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Auto-Provision Schema and Seed Data If Empty
try {
    $chk_stmt = $conn->query("SHOW TABLES LIKE 'users'");
    $tables = $chk_stmt ? $chk_stmt->fetchAll() : [];
    
    if (empty($tables)) {

        // Table: users
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `name` varchar(255) NOT NULL,
              `email` varchar(255) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `phone` varchar(50) DEFAULT '',
              `Address` varchar(255) DEFAULT '',
              `role` varchar(50) DEFAULT 'user',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: spaces
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `spaces` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `name` varchar(255) NOT NULL,
              `description` text NOT NULL,
              `capacity` int NOT NULL DEFAULT 1,
              `price_per_day` decimal(10,2) NOT NULL DEFAULT 0.00,
              `amenities` varchar(255) NOT NULL DEFAULT '',
              `location` varchar(255) NOT NULL DEFAULT '',
              `img` varchar(255) DEFAULT 'MeetingRoom.jpg',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: bookings
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `bookings` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `user_id` int NOT NULL,
              `space_id` int NOT NULL,
              `start_date` date NOT NULL,
              `end_date` date NOT NULL,
              `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
              `status` varchar(50) DEFAULT 'approved',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
              FOREIGN KEY (`space_id`) REFERENCES `spaces`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: reviews
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `reviews` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `user_id` int NOT NULL,
              `space_id` int NOT NULL,
              `rating` int NOT NULL DEFAULT 5,
              `comment` text NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
              FOREIGN KEY (`space_id`) REFERENCES `spaces`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: invoices
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `invoices` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `booking_id` int NOT NULL,
              `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
              `status` varchar(50) DEFAULT 'paid',
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: invoice_items
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `invoice_items` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `invoice` varchar(100) NOT NULL,
              `item` varchar(255) NOT NULL,
              `qty` int NOT NULL DEFAULT 1,
              `price` decimal(10,2) NOT NULL DEFAULT 0.00,
              `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
              `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: admin
        $conn->exec("
            CREATE TABLE IF NOT EXISTS `admin` (
              `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `name` varchar(255) NOT NULL,
              `email` varchar(255) NOT NULL UNIQUE,
              `password` varchar(255) NOT NULL,
              `created_at` timestamp DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed Admin Users
        $conn->exec("INSERT INTO `admin` (`id`, `name`, `email`, `password`) VALUES
            (1, 'Admin Master', 'admin@example.com', 'adminpassword123'),
            (2, 'Super Admin', 'admin@cowork.com', 'admin')
            ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

        // Seed Customers
        $conn->exec("INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `Address`, `role`) VALUES
            (1, 'John Doe', 'johndoe@example.com', 'password123', '+1-555-0123', '100 Silicon Blvd, CA', 'User'),
            (2, 'Jane Smith', 'janesmith@example.com', 'password456', '+1-555-0456', '250 Startup Way, NY', 'User')
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

        // Seed Spaces
        $conn->exec("INSERT INTO `spaces` (`id`, `name`, `description`, `capacity`, `price_per_day`, `amenities`, `location`, `img`) VALUES
            (1, 'Executive Boardroom Suite', 'Premium meeting and boardroom equipped with smart display and video conferencing.', 12, 120.00, 'Gigabit Fiber, 4K Screen, Video Conferencing, Gourmet Coffee', 'Downtown Financial Center', 'MeetingRoom.jpg'),
            (2, 'Dedicated Team Hub', 'Collaborative private workstation area for high-velocity teams.', 8, 85.00, 'Ergonomic Chairs, Standing Desks, Lockable Storage', 'Tech Innovation Park', 'dedicateddesk1.jpg'),
            (3, 'Hot Desk Flex Pass', 'Flexible open-plan seating with modern amenities and vibrant community.', 1, 25.00, 'High-Speed WiFi, Unlimited Tea/Coffee, Lounge Access', 'Creative Arts Quarter', 'Hot-Desk.jpg'),
            (4, 'Private Office Suite', 'Quiet fully furnished office suite with 24/7 secure access.', 4, 95.00, '24/7 Access, Mailing Address, Private Phone Booth', 'Metro Towers', 'officespace1.jpg')
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

        // Seed Bookings
        $conn->exec("INSERT INTO `bookings` (`id`, `user_id`, `space_id`, `start_date`, `end_date`, `total_price`, `status`) VALUES
            (1, 1, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 240.00, 'approved'),
            (2, 2, 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 125.00, 'approved')
            ON DUPLICATE KEY UPDATE `total_price`=VALUES(`total_price`);");

        // Seed Reviews
        $conn->exec("INSERT INTO `reviews` (`id`, `user_id`, `space_id`, `rating`, `comment`) VALUES
            (1, 1, 1, 5, 'Phenomenal space with blazing fast WiFi and top-notch audio/video equipment!'),
            (2, 2, 3, 5, 'A great productive environment with friendly staff and excellent coffee.')
            ON DUPLICATE KEY UPDATE `comment`=VALUES(`comment`);");
    }
} catch (PDOException $e) {
    error_log("Cowork auto-provisioning note: " . $e->getMessage());
}
?>
