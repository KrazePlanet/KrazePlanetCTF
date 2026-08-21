<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'grecko_db';

// 1. Initial Connection to Ensure Database Exists
try {
    $init_pdo = new PDO("mysql:host={$host}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $init_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    unset($init_pdo);
} catch (PDOException $e) {
    // Ignore
}

// 2. Connect to grecko_db database
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Auto-Provision Schema and Seed Data If Empty
try {
    $chk = $pdo->query("SHOW TABLES LIKE 'reservations'")->rowCount();
    if ($chk === 0) {

        // Table: admins
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admins` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: reservations
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `reservations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `date` DATE NOT NULL,
                `party_size` INT NOT NULL DEFAULT 2,
                `phone` VARCHAR(50) NOT NULL,
                `message` TEXT DEFAULT NULL,
                `status` ENUM('Pending','Confirmed','Cancelled') DEFAULT 'Pending',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: contact_messages
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `contact_messages` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL,
                `phone` VARCHAR(50) DEFAULT '',
                `subject` VARCHAR(200) DEFAULT '',
                `message` TEXT NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: newsletter_subscribers
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: menu_items
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `menu_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `category` ENUM('Food','Drinks','Desserts') NOT NULL DEFAULT 'Food',
                `name` VARCHAR(200) NOT NULL,
                `description` TEXT NOT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `image` VARCHAR(255) DEFAULT '',
                `is_featured` TINYINT(1) DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed Admin Users
        $hash1 = password_hash('admin123', PASSWORD_DEFAULT);
        $hash2 = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO `admins` (`id`, `username`, `name`, `email`, `password`) VALUES
            (1, 'admin', 'Marios Sofokleous', 'admin@grecko.com', '{$hash1}'),
            (2, 'manager', 'Eleni Grecko', 'manager@grecko.com', '{$hash2}')
            ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

        // Seed Sample Reservations
        $pdo->exec("INSERT INTO `reservations` (`id`, `name`, `email`, `date`, `party_size`, `phone`, `message`, `status`) VALUES
            (1, 'Dimitris Papas', 'dimitris@gmail.com', CURDATE(), 4, '+30 691 234 5678', 'Seaside balcony table if available please.', 'Confirmed'),
            (2, 'Sophia Loren', 'sophia.loren@gmail.com', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 2, '+30 698 765 4321', 'Anniversary dinner.', 'Pending')
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

        // Seed Contact Messages
        $pdo->exec("INSERT INTO `contact_messages` (`id`, `name`, `email`, `phone`, `subject`, `message`) VALUES
            (1, 'Alexander Wright', 'alex.w@yahoo.com', '+44 7700 900077', 'Private Event Hosting', 'Hi, we would like to book the entire terrace for a wedding rehearsal dinner next month.')
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

        // Seed Newsletter Subscribers
        $pdo->exec("INSERT INTO `newsletter_subscribers` (`id`, `email`) VALUES
            (1, 'guest@mykonos.travel'),
            (2, 'foodie.explorer@outlook.com')
            ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);");

        // Seed Menu Items
        $pdo->exec("INSERT INTO `menu_items` (`id`, `category`, `name`, `description`, `price`, `image`, `is_featured`) VALUES
            (1, 'Food', 'Charcoal Grilled Aegean Octopus', 'Tender grilled octopus tentacles drizzled with Greek extra virgin olive oil, wild oregano and caper berries.', 26.00, 'menu-octopus.jpg', 1),
            (2, 'Food', 'Santorini Lobster Spaghetti', 'Fresh local lobster poached in aromatic saffron bisque with cherry tomatoes and fresh parsley.', 38.50, 'menu-lobster.jpg', 1),
            (3, 'Food', 'Authentic Greek Moussaka', 'Layered eggplant, spiced lamb ragù, crispy potatoes and golden fluffy béchamel crust.', 19.00, 'menu-moussaka.jpg', 1),
            (4, 'Drinks', 'Mykonian Sunset Spritz', 'Mastika liqueur, Aperol, sparkling Prosecco, fresh pink grapefruit juice and rosemary sprig.', 14.00, 'drink-spritz.jpg', 1),
            (5, 'Desserts', 'Artisanal Baklava Gelato Crunch', 'Flaky phyllo pastry stuffed with roasted pistachios, honey syrup and sheep milk vanilla gelato.', 11.50, 'dessert-baklava.jpg', 1)
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");
    }
} catch (PDOException $e) {
    error_log("Grecko auto-provisioning note: " . $e->getMessage());
}
?>
