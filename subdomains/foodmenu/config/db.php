<?php
$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$user = 'root';
$pass = '';
$dbname = 'foodmenu_db';

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

// 2. Connect to foodmenu_db database
$dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}

// 3. Auto-Provision Tables and Seed Data
try {
    $chk = $pdo->query("SHOW TABLES LIKE 'categories'")->rowCount();
    if ($chk === 0) {

        // Table: restaurant_settings
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `restaurant_settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `restaurant_name` VARCHAR(255) NOT NULL DEFAULT 'Buffet Box Cloud Kitchen',
                `tagline` VARCHAR(255) DEFAULT 'Artisanal Flavors & Express Gourmet Dining',
                `currency` VARCHAR(10) DEFAULT '$',
                `tax_percent` DECIMAL(5,2) DEFAULT 5.00,
                `phone` VARCHAR(50) DEFAULT '+1 (555) 890-FOOD',
                `email` VARCHAR(100) DEFAULT 'hello@buffetbox.kitchen',
                `address` VARCHAR(255) DEFAULT '742 Culinary Boulevard, Suite 400',
                `wifi_ssid` VARCHAR(100) DEFAULT 'BuffetBox_Guest',
                `wifi_pass` VARCHAR(100) DEFAULT 'YummyFood2026'
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: admins
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `admins` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(100) NOT NULL UNIQUE,
                `name` VARCHAR(150) NOT NULL,
                `email` VARCHAR(150) NOT NULL UNIQUE,
                `password` VARCHAR(255) NOT NULL,
                `role` VARCHAR(50) DEFAULT 'Kitchen Manager',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: categories
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `icon` VARCHAR(100) DEFAULT 'fas fa-utensils',
                `image` VARCHAR(255) DEFAULT '',
                `display_order` INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: menu_items
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `menu_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `category_id` INT NOT NULL,
                `name` VARCHAR(200) NOT NULL,
                `description` TEXT NOT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `discount_price` DECIMAL(10,2) DEFAULT NULL,
                `is_veg` TINYINT(1) DEFAULT 0,
                `is_spicy` TINYINT(1) DEFAULT 0,
                `is_featured` TINYINT(1) DEFAULT 0,
                `is_available` TINYINT(1) DEFAULT 1,
                `image` VARCHAR(500) DEFAULT '',
                `calories` INT DEFAULT 0,
                `rating` DECIMAL(3,2) DEFAULT 4.80,
                `rating_count` INT DEFAULT 42,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: orders
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `orders` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_code` VARCHAR(50) NOT NULL UNIQUE,
                `table_number` VARCHAR(50) DEFAULT 'Takeaway',
                `customer_name` VARCHAR(150) NOT NULL,
                `customer_phone` VARCHAR(50) NOT NULL,
                `order_type` ENUM('Dine-In','Takeaway','Delivery') DEFAULT 'Dine-In',
                `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `status` ENUM('Pending','Preparing','Ready','Delivered','Cancelled') DEFAULT 'Pending',
                `payment_method` VARCHAR(50) DEFAULT 'Cash',
                `payment_status` ENUM('Unpaid','Paid') DEFAULT 'Paid',
                `special_instructions` TEXT DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Table: order_items
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `order_items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `order_id` INT NOT NULL,
                `item_id` INT NOT NULL,
                `item_name` VARCHAR(200) NOT NULL,
                `price` DECIMAL(10,2) NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `subtotal` DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed Settings
        $pdo->exec("INSERT INTO `restaurant_settings` (`id`, `restaurant_name`, `tagline`, `currency`, `tax_percent`, `phone`, `email`, `address`) VALUES
            (1, 'Buffet Box Cloud Kitchen', 'Artisanal Flavors & Express Gourmet Dining', '$', 5.00, '+1 (555) 890-FOOD', 'orders@buffetbox.kitchen', '742 Culinary Boulevard, Suite 400')
            ON DUPLICATE KEY UPDATE `restaurant_name`=VALUES(`restaurant_name`);");

        // Seed Admin Users (admin / admin123, admin / admin)
        $hashed1 = password_hash('admin123', PASSWORD_DEFAULT);
        $hashed2 = password_hash('admin', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO `admins` (`id`, `username`, `name`, `email`, `password`, `role`) VALUES
            (1, 'admin', 'Master Chef Alex', 'admin@buffetbox.kitchen', '{$hashed1}', 'Executive Chef & Manager'),
            (2, 'manager', 'Sarah Jenkins', 'sarah@buffetbox.kitchen', '{$hashed2}', 'Front Desk Manager')
            ON DUPLICATE KEY UPDATE `password`=VALUES(`password`);");

        // Seed Categories
        $pdo->exec("INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `display_order`) VALUES
            (1, 'Starters & Appetizers', 'starters', 'fas fa-bowl-food', 1),
            (2, 'Chef Signature Mains', 'mains', 'fas fa-fire', 2),
            (3, 'Gourmet Burgers & Pizzas', 'burgers-pizza', 'fas fa-burger', 3),
            (4, 'Cloud Kitchen Bowls', 'bowls', 'fas fa-bowl-rice', 4),
            (5, 'Artisanal Desserts', 'desserts', 'fas fa-ice-cream', 5),
            (6, 'Mocktails & Beverages', 'beverages', 'fas fa-cocktail', 6)
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

        // Seed Menu Items
        $pdo->exec("INSERT INTO `menu_items` (`id`, `category_id`, `name`, `description`, `price`, `discount_price`, `is_veg`, `is_spicy`, `is_featured`, `image`, `calories`, `rating`, `rating_count`) VALUES
            (1, 1, 'Truffle Parmesan Crispy Fries', 'Hand-cut russet potatoes tossed with black truffle oil, aged parmesan, and fresh rosemary aioli.', 8.50, NULL, 1, 0, 1, 'https://images.unsplash.com/photo-1576107232684-1279f3908594?w=600&auto=format&fit=crop&q=80', 420, 4.9, 88),
            (2, 1, 'Smoked Paprika Calamari', 'Tender flash-fried calamari dusted with smoked paprika and lime zest, served with roasted garlic dip.', 12.00, 10.50, 0, 1, 1, 'https://images.unsplash.com/photo-1599488615731-7e5c2823ff28?w=600&auto=format&fit=crop&q=80', 360, 4.8, 64),
            (3, 1, 'Firecracker Paneer Tikka', 'Clay-oven charred cottage cheese cubes marinated in Kashmiri chili and aromatic whole spices.', 11.00, NULL, 1, 1, 0, 'https://images.unsplash.com/photo-1567188040759-fb8a883dc6d8?w=600&auto=format&fit=crop&q=80', 310, 4.7, 52),
            (4, 2, 'Slow-Braised Angus Short Rib', '12-hour braised beef short rib served over creamy garlic mashed potatoes and glazed heirloom carrots.', 24.50, NULL, 0, 0, 1, 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&auto=format&fit=crop&q=80', 780, 5.0, 110),
            (5, 2, 'Grilled Wild Salmon Piccata', 'Pan-seared Atlantic salmon fillet with caper-lemon beurre blanc sauce and charred asparagus.', 21.00, 18.50, 0, 0, 1, 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=600&auto=format&fit=crop&q=80', 520, 4.9, 95),
            (6, 2, 'Creamy Wild Mushroom Risotto', 'Arborio rice cooked with porcini broth, shaved black truffles, mascarpone, and micro herbs.', 16.50, NULL, 1, 0, 0, 'https://images.unsplash.com/photo-1633964913295-ceb43826e7c9?w=600&auto=format&fit=crop&q=80', 490, 4.8, 73),
            (7, 3, 'The Double Wagyu Smash Burger', 'Two 100% Wagyu patties, smoked cheddar, caramelized shallots, house pickles, and secret relish on a brioche bun.', 15.50, NULL, 0, 0, 1, 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=600&auto=format&fit=crop&q=80', 850, 4.9, 140),
            (8, 3, 'Neapolitan Margherita D.O.P.', 'San Marzano tomato sauce, fresh buffalo mozzarella, fragrant Genovese basil, and extra virgin olive oil.', 14.00, 12.00, 1, 0, 0, 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=600&auto=format&fit=crop&q=80', 650, 4.8, 89),
            (9, 4, 'Spicy Teriyaki Glazed Chicken Bowl', 'Flame-grilled chicken thigh glazed with house teriyaki over steamed jasmine rice, edamame, and sesame wok greens.', 13.50, NULL, 0, 1, 1, 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=600&auto=format&fit=crop&q=80', 580, 4.9, 98),
            (10, 4, 'Green Goddess Quinoa Buddha Bowl', 'Organic tri-color quinoa, roasted sweet potatoes, avocado roses, crispy chickpeas, and green goddess dressing.', 12.50, NULL, 1, 0, 0, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=600&auto=format&fit=crop&q=80', 440, 4.7, 45),
            (11, 5, 'Molten Belgian Chocolate Lava Cake', 'Warm molten center chocolate fondant served with Madagascar vanilla bean gelato and berry coulis.', 8.50, NULL, 1, 0, 1, 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?w=600&auto=format&fit=crop&q=80', 510, 5.0, 125),
            (12, 6, 'Passionfruit Mint Sparkling Fizz', 'Fresh passionfruit pulp, crushed mint leaves, lime juice, sparkling soda water, and crushed ice.', 5.50, NULL, 1, 0, 1, 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&auto=format&fit=crop&q=80', 120, 4.9, 67)
            ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);");

        // Seed Sample Live Orders
        $pdo->exec("INSERT INTO `orders` (`id`, `order_code`, `table_number`, `customer_name`, `customer_phone`, `order_type`, `total_amount`, `status`, `payment_method`, `payment_status`, `special_instructions`) VALUES
            (1, 'ORD-8921', 'Table 4', 'Marcus Vance', '+1 555 123 4567', 'Dine-In', 40.00, 'Preparing', 'Credit Card', 'Paid', 'Extra napkins and burger well done please.'),
            (2, 'ORD-8922', 'Table 7', 'Elena Rostova', '+1 555 987 6543', 'Dine-In', 29.50, 'Ready', 'Cash', 'Paid', 'No onions in the bowl.')
            ON DUPLICATE KEY UPDATE `customer_name`=VALUES(`customer_name`);");

        $pdo->exec("INSERT INTO `order_items` (`id`, `order_id`, `item_id`, `item_name`, `price`, `quantity`, `subtotal`) VALUES
            (1, 1, 7, 'The Double Wagyu Smash Burger', 15.50, 2, 31.00),
            (2, 1, 1, 'Truffle Parmesan Crispy Fries', 8.50, 1, 8.50),
            (3, 2, 9, 'Spicy Teriyaki Glazed Chicken Bowl', 13.50, 1, 13.50),
            (4, 2, 8, 'Neapolitan Margherita D.O.P.', 12.00, 1, 12.00)
            ON DUPLICATE KEY UPDATE `item_name`=VALUES(`item_name`);");
    }
} catch (PDOException $e) {
    error_log("FoodMenu auto-provisioning note: " . $e->getMessage());
}
?>
