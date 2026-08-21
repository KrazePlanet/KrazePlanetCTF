<?php
$host = 'localhost';
$port = 3306;
$dbname = 'bookstore';
$user = 'root';
$pass = '';

// 1. Initial Connection to Ensure Database Exists
try {
    $init_pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $init_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    unset($init_pdo);
} catch (PDOException $e) {
    // Ignore if already created or restricted
}

// 2. Connect to bookstore database
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 3. Auto-Provision Schema and Seed Data If Empty
try {
    $chk = $pdo->query("SHOW TABLES LIKE 'book'")->rowCount();
    if ($chk === 0) {

        // Table: users
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `UserID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `UserName` varchar(128) NOT NULL UNIQUE,
              `Password` varchar(255) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: customer
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `customer` (
              `CustomerID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `CustomerName` varchar(128) DEFAULT '',
              `CustomerPhone` varchar(20) DEFAULT '',
              `CustomerIC` varchar(20) DEFAULT '',
              `CustomerEmail` varchar(200) DEFAULT '',
              `CustomerAddress` varchar(255) DEFAULT '',
              `CustomerGender` varchar(10) DEFAULT '',
              `UserID` int DEFAULT NULL,
              FOREIGN KEY (`UserID`) REFERENCES `users`(`UserID`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: book
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `book` (
              `BookID` varchar(50) NOT NULL PRIMARY KEY,
              `BookTitle` varchar(200) NOT NULL,
              `ISBN` varchar(50) DEFAULT '',
              `Price` double(12,2) NOT NULL DEFAULT 0.00,
              `Author` varchar(128) DEFAULT '',
              `Type` varchar(128) DEFAULT '',
              `Image` varchar(255) DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: order
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `order` (
              `OrderID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `CustomerID` int DEFAULT NULL,
              `BookID` varchar(50) DEFAULT NULL,
              `DatePurchase` datetime DEFAULT CURRENT_TIMESTAMP,
              `Quantity` int NOT NULL DEFAULT 1,
              `TotalPrice` double(12,2) NOT NULL DEFAULT 0.00,
              `Status` varchar(10) DEFAULT 'N',
              FOREIGN KEY (`BookID`) REFERENCES `book`(`BookID`) ON DELETE SET NULL ON UPDATE CASCADE,
              FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: cart
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `cart` (
              `CartID` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `CustomerID` int DEFAULT NULL,
              `BookID` varchar(50) DEFAULT NULL,
              `Price` double(12,2) NOT NULL DEFAULT 0.00,
              `Quantity` int NOT NULL DEFAULT 1,
              `TotalPrice` double(12,2) NOT NULL DEFAULT 0.00,
              FOREIGN KEY (`BookID`) REFERENCES `book`(`BookID`) ON DELETE SET NULL ON UPDATE CASCADE,
              FOREIGN KEY (`CustomerID`) REFERENCES `customer`(`CustomerID`) ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Create Uppercase compatibility views for case-sensitive queries
        $pdo->exec("CREATE OR REPLACE VIEW Users AS SELECT * FROM users;");
        $pdo->exec("CREATE OR REPLACE VIEW Customer AS SELECT * FROM customer;");
        $pdo->exec("CREATE OR REPLACE VIEW Book AS SELECT * FROM book;");
        $pdo->exec("CREATE OR REPLACE VIEW Cart AS SELECT * FROM cart;");
        $pdo->exec("CREATE OR REPLACE VIEW `Order` AS SELECT * FROM `order`;");

        // Seed Default Users
        $pdo->exec("INSERT INTO `users` (`UserID`, `UserName`, `Password`) VALUES
            (1, 'admin', 'admin'),
            (2, 'john', 'password')
            ON DUPLICATE KEY UPDATE `Password`=VALUES(`Password`);");

        // Seed Customer Profiles
        $pdo->exec("INSERT INTO `customer` (`CustomerID`, `CustomerName`, `CustomerPhone`, `CustomerIC`, `CustomerEmail`, `CustomerAddress`, `CustomerGender`, `UserID`) VALUES
            (1, 'Administrator', '0123456789', '900101015555', 'admin@bookstore.com', 'Suite 100, Tech Park', 'Male', 1),
            (2, 'John Doe', '0198765432', '950202086666', 'john@gmail.com', '742 Evergreen Terrace', 'Male', 2)
            ON DUPLICATE KEY UPDATE `CustomerName`=VALUES(`CustomerName`);");

        // Seed Books Inventory
        $pdo->exec("INSERT INTO `book` (`BookID`, `BookTitle`, `ISBN`, `Price`, `Author`, `Type`, `Image`) VALUES
            ('B-001', 'Lonely Planet Australia (Travel Guide)', '123-456-789-1', 136.00, 'Lonely Planet', 'Travel', 'image/travel.jpg'),
            ('B-002', 'Crew Resource Management, Second Edition', '123-456-789-2', 599.00, 'Barbara Kanki', 'Technical', 'image/technical.jpg'),
            ('B-003', 'CCNA Routing and Switching Official Guide', '123-456-789-3', 329.00, 'Cisco Press', 'Technology', 'image/technology.jpg'),
            ('B-004', 'Easy Vegetarian Slow Cooker Cookbook', '123-456-789-4', 75.90, 'Rockridge Press', 'Food', 'image/food.jpg')
            ON DUPLICATE KEY UPDATE `BookTitle`=VALUES(`BookTitle`);");
    }
} catch (PDOException $e) {
    error_log("Bookstore auto-provisioning note: " . $e->getMessage());
}
?>
