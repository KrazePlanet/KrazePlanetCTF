<?php
// Foodie Database Configuration & Auto-Provisioning Engine
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'foodie');

try {
    // 1. Ensure Database Exists
    $pdo_init = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

    // 2. Connect to Foodie Database
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 3. Auto-Create Tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `food_items` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` varchar(150) NOT NULL,
            `category` varchar(50) NOT NULL,
            `price` decimal(10,2) NOT NULL,
            `discount_price` decimal(10,2) DEFAULT NULL,
            `rating` decimal(3,1) NOT NULL DEFAULT 5.0,
            `badge` varchar(50) DEFAULT NULL,
            `image` varchar(255) NOT NULL,
            `description` text,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `order_code` varchar(30) NOT NULL,
            `customer_name` varchar(100) NOT NULL,
            `customer_email` varchar(100) NOT NULL,
            `customer_phone` varchar(30) NOT NULL,
            `delivery_address` text NOT NULL,
            `item_id` int(11) NOT NULL,
            `item_name` varchar(150) NOT NULL,
            `quantity` int(11) NOT NULL DEFAULT 1,
            `total_price` decimal(10,2) NOT NULL,
            `order_notes` text,
            `status` enum('Pending','Cooking','Out for Delivery','Delivered','Cancelled') NOT NULL DEFAULT 'Pending',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `reservations` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `phone` varchar(30) NOT NULL,
            `num_guests` int(11) NOT NULL DEFAULT 2,
            `reservation_date` date NOT NULL,
            `reservation_time` varchar(20) NOT NULL,
            `special_request` text,
            `status` enum('Confirmed','Pending','Cancelled') NOT NULL DEFAULT 'Pending',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `testimonials` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `author_name` varchar(100) NOT NULL,
            `author_role` varchar(100) NOT NULL,
            `author_image` varchar(255) NOT NULL,
            `rating` int(11) NOT NULL DEFAULT 5,
            `feedback_text` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `blogs` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `title` varchar(255) NOT NULL,
            `category` varchar(50) NOT NULL DEFAULT 'Food Drink',
            `author_name` varchar(100) NOT NULL DEFAULT 'Foodie Chef',
            `publish_date` date NOT NULL,
            `image` varchar(255) NOT NULL,
            `excerpt` text NOT NULL,
            `content` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `subscribers` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `email` varchar(150) NOT NULL UNIQUE,
            `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contact_inquiries` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `name` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `subject` varchar(150) NOT NULL,
            `message` text NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `username` varchar(50) NOT NULL UNIQUE,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4. Seed Default Admin
    $chkAdmin = $pdo->query("SELECT COUNT(*) FROM `admin_users` WHERE username='admin'")->fetchColumn();
    if ($chkAdmin == 0) {
        $pdo->exec("INSERT INTO `admin_users` (`username`, `email`, `password`) VALUES ('admin', 'admin@foodie.com', MD5('admin'))");
    }

    // 5. Seed Default Food Items if empty
    $chkFood = $pdo->query("SELECT COUNT(*) FROM `food_items`")->fetchColumn();
    if ($chkFood == 0) {
        $food_data = [
            // Pizza
            ['Fried Chicken Unlimited', 'Pizza', 49.00, 69.00, 5.0, '-15%', './assets/images/food-menu-1.png', 'Crispy, juicy gourmet golden fried chicken with secret herb glaze.'],
            ['Burger King Whopper', 'Burger', 29.00, 39.00, 5.0, '-10%', './assets/images/food-menu-2.png', 'Flame-grilled prime beef patty topped with fresh lettuce and pickles.'],
            ['White Castle Deluxe', 'Pizza', 49.00, 69.00, 5.0, '-25%', './assets/images/food-menu-3.png', 'Artisan hand-stretched stone baked pizza with fresh mozzarella and basil.'],
            ['Bell Burrito Supreme', 'Pizza', 49.00, 69.00, 5.0, '-20%', './assets/images/food-menu-4.png', 'Stuffed with spiced meat, black beans, salsa fresca and shredded cheddar.'],
            ['Kung Pao Chicken Wings', 'Burger', 49.00, 69.00, 5.0, '-5%', './assets/images/food-menu-5.png', 'Tossed in a sweet and spicy soy-chili reduction with toasted peanuts.'],
            ['Wendy\'s Smoked Chicken', 'Pizza', 49.00, 69.00, 5.0, '-15%', './assets/images/food-menu-6.png', 'Smoked farm-fresh chicken strips drizzled with tangy barbecue sauce.'],
            // Drinks & Sandwiches
            ['Artisan Iced Caramel Latte', 'Drinks', 15.00, 20.00, 4.8, 'Popular', './assets/images/promo-2.png', 'Cold brew espresso layered with creamy whole milk and salted caramel.'],
            ['Fresh Mojito Lime Sparkler', 'Drinks', 12.00, 16.00, 4.9, 'New', './assets/images/promo-2.png', 'Refreshing mint leaves, fresh squeezed lime, cane syrup and soda.'],
            ['Double Decker Club Sandwich', 'Sandwich', 34.00, 42.00, 5.0, '-12%', './assets/images/promo-4.png', 'Toasted brioche layered with roasted turkey, smoked bacon, and avocado.'],
            ['Grilled BBQ Chicken Sub', 'Sandwich', 38.00, 45.00, 4.7, 'Chef Special', './assets/images/promo-4.png', 'Slow-cooked chicken brisket smothered in signature hickory sauce.'],
            ['Mexican Fiesta Pizza', 'Pizza', 55.00, 75.00, 5.0, '-20%', './assets/images/promo-1.png', 'Spicy jalapeños, chorizo sausage, corn, and fresh cilantro on crisp crust.'],
            ['Crispy French Fry Bucket', 'Burger', 18.00, 22.00, 4.9, 'Best Seller', './assets/images/promo-3.png', 'Double-fried hand-cut russet potatoes dusted with rosemary sea salt.']
        ];

        $stmt = $pdo->prepare("INSERT INTO `food_items` (`title`, `category`, `price`, `discount_price`, `rating`, `badge`, `image`, `description`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($food_data as $item) {
            $stmt->execute($item);
        }
    }

    // 6. Seed Default Testimonials if empty
    $chkTesti = $pdo->query("SELECT COUNT(*) FROM `testimonials`")->fetchColumn();
    if ($chkTesti == 0) {
        $testi_data = [
            ['Robert William', 'CEO & Founder Kingo', './assets/images/avatar-1.jpg', 5, 'Foodie completely changed our team lunch experience! The Burgers and Pizzas arrived piping hot within 20 minutes, beautifully packaged.'],
            ['Thomas Josef', 'Food Blogger & Critic', './assets/images/avatar-2.jpg', 5, 'The Mexican Fiesta Pizza had incredible depth of flavor. The artisanal dough fermentation is evident in every bite.'],
            ['Charles Richard', 'Executive Chef at Luxe', './assets/images/avatar-3.jpg', 5, 'Consistently top-tier culinary standards and flawless table reservation service. Highly recommended for food lovers!']
        ];
        $t_stmt = $pdo->prepare("INSERT INTO `testimonials` (`author_name`, `author_role`, `author_image`, `rating`, `feedback_text`) VALUES (?, ?, ?, ?, ?)");
        foreach ($testi_data as $t) {
            $t_stmt->execute($t);
        }
    }

    // 7. Seed Default Blogs if empty
    $chkBlogs = $pdo->query("SELECT COUNT(*) FROM `blogs`")->fetchColumn();
    if ($chkBlogs == 0) {
        $blog_data = [
            ['What Do You Think About Pizza Recipes?', 'Food Drink', 'Chef Marco', '2026-08-10', './assets/images/blog-1.jpg', 'Discover the secret behind traditional Neapolitan pizza dough hydration and high-heat oven baking.', 'Pizza making is an art form rooted in simplicity, quality flour, fresh mozzarella, and slow fermentation...'],
            ['Making Chicken Strips with New Seasoning', 'Recipe Secret', 'Chef Sophia', '2026-08-12', './assets/images/blog-2.jpg', 'Learn how buttermilk brining and a triple-dredge coating creates exceptionally crunchy chicken strips.', 'The key to golden chicken strips with irresistible crispness lies in the cornstarch-to-flour ratio and temperature control...'],
            ['Innovations in Contemporary Fast Food Service', 'Culinary Trends', 'Chef Liam', '2026-08-14', './assets/images/blog-3.jpg', 'How farm-to-table sourcing and instant digital reservations are revolutionizing modern dining.', 'Diners today expect rapid service without compromising on organic ingredients, artisanal preparation, and sustainability...']
        ];
        $b_stmt = $pdo->prepare("INSERT INTO `blogs` (`title`, `category`, `author_name`, `publish_date`, `image`, `excerpt`, `content`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($blog_data as $b) {
            $b_stmt->execute($b);
        }
    }

    // 8. Seed Initial Orders for demonstration
    $chkOrders = $pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    if ($chkOrders == 0) {
        $pdo->exec("INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `item_id`, `item_name`, `quantity`, `total_price`, `status`) VALUES
            ('ORD-98214', 'Alexander Vance', 'alex.vance@example.com', '+1 555-019-2831', '742 Evergreen Terrace, Sector 4', 1, 'Fried Chicken Unlimited', 2, 98.00, 'Cooking'),
            ('ORD-74192', 'Elena Rostova', 'elena.rostova@example.com', '+1 555-829-1049', '124 Conch Street, Floor 2', 2, 'Burger King Whopper', 1, 29.00, 'Out for Delivery'),
            ('ORD-31849', 'Marcus Brody', 'mbrody@museum.org', '+1 555-391-7720', '350 5th Ave, Manhattan, NY', 3, 'White Castle Deluxe Pizza', 3, 147.00, 'Delivered');");
    }

    // 9. Seed Initial Reservations
    $chkRes = $pdo->query("SELECT COUNT(*) FROM `reservations`")->fetchColumn();
    if ($chkRes == 0) {
        $pdo->exec("INSERT INTO `reservations` (`name`, `email`, `phone`, `num_guests`, `reservation_date`, `reservation_time`, `special_request`, `status`) VALUES
            ('Julian Vance', 'julian@example.com', '+1 555-224-8190', 4, '2026-08-16', '19:30', 'Window table with city view for anniversary', 'Confirmed'),
            ('Claire Bennett', 'claire.b@example.com', '+1 555-901-4433', 2, '2026-08-17', '20:00', 'Quiet corner table', 'Pending');");
    }

} catch (PDOException $e) {
    die("Database Connection / Setup Failed: " . $e->getMessage());
}
?>
