<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default to city ID 1 (New Delhi) if not set so product catalog is always visible
if (!isset($_SESSION['utm_source']) && !isset($_GET['utm_source'])) {
    $_SESSION['utm_source'] = '1';
}

$host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$user = 'root';
$pass = '';
$db   = 'grocerry';

// 1. Initial Connection to Ensure Database Exists
$init_con = @mysqli_connect($host, $user, $pass);
if ($init_con) {
    @mysqli_query($init_con, "CREATE DATABASE IF NOT EXISTS `" . $db . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
    @mysqli_close($init_con);
}

// 2. Connect to grocerry database
$con = @mysqli_connect($host, $user, $pass, $db);

if (!$con) {
    die("Database Connection failed: " . mysqli_connect_error());
}

// 3. Auto-Provision Schema and Seed Data If Empty
$chk = @mysqli_query($con, "SHOW TABLES LIKE 'admin'");
if (!$chk || mysqli_num_rows($chk) == 0) {
    $statements = [
        "CREATE TABLE IF NOT EXISTS `admin` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `username` varchar(255) NOT NULL UNIQUE, `password` text NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `business_type` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `type` varchar(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `categories` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `category` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1, `order_by` int(11) NOT NULL DEFAULT 0, `img` varchar(255) DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `sub_cat` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `p_cat` varchar(255) NOT NULL, `sub_cat` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1, `cat_id` int(11) DEFAULT 1, `img` varchar(255) DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `subcategories` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `cat_id` int(11) DEFAULT 1, `subcat` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `country` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `cntry_name` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `state` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `state_name` varchar(255) NOT NULL, `country_id` int(11) NOT NULL DEFAULT 1, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `city` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `city_name` varchar(255) NOT NULL, `state_id` int(11) NOT NULL DEFAULT 1, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `pin` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `pin_code` varchar(255) NOT NULL, `city_id` int(11) NOT NULL DEFAULT 1, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `expin` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `pin_code` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `dv_time` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `from` varchar(255) NOT NULL, `tto` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `delivery_boy` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) NOT NULL, `mobile` varchar(255) NOT NULL, `email` varchar(255) NOT NULL, `password` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1, `city_id` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `sellers` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `f_name` varchar(255) NOT NULL, `l_name` varchar(255) NOT NULL, `email` varchar(255) NOT NULL UNIQUE, `mobile` varchar(255) NOT NULL, `password` varchar(255) NOT NULL, `isapp` int(11) NOT NULL DEFAULT 1, `is_cp` int(11) NOT NULL DEFAULT 1, `status` int(11) NOT NULL DEFAULT 1, `b_name` varchar(255) DEFAULT '', `b_type` varchar(255) DEFAULT '', `acc_no` varchar(255) DEFAULT '', `ifsc` varchar(255) DEFAULT '', `bank_name` varchar(255) DEFAULT '', `branch` varchar(255) DEFAULT '', `pan` varchar(255) DEFAULT '', `gst` varchar(255) DEFAULT '', `city` varchar(255) DEFAULT '1', `state` varchar(255) DEFAULT '1', `pin` varchar(255) DEFAULT '', `address` text DEFAULT NULL, `img` varchar(255) DEFAULT '', `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `commission` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `scat_id` int(11) NOT NULL DEFAULT 1, `com` float NOT NULL DEFAULT 5.0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `dc` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `dc` float NOT NULL DEFAULT 20.0, `min_val` float NOT NULL DEFAULT 200.0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `product` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `product_name` varchar(255) NOT NULL, `category` varchar(255) DEFAULT '1', `subcat` varchar(255) DEFAULT '1', `cat_id` int(11) DEFAULT 1, `scat_id` int(11) DEFAULT 1, `price` float NOT NULL DEFAULT 0.0, `fa` float NOT NULL DEFAULT 0.0, `sku` varchar(255) DEFAULT '', `qty` int(11) NOT NULL DEFAULT 100, `status` int(11) NOT NULL DEFAULT 1, `isapp` int(11) NOT NULL DEFAULT 1, `isappp` int(11) NOT NULL DEFAULT 1, `bs` int(11) NOT NULL DEFAULT 1, `belonging_city` int(11) NOT NULL DEFAULT 1, `img1` varchar(255) DEFAULT '', `img2` varchar(255) DEFAULT '', `img3` varchar(255) DEFAULT '', `img4` varchar(255) DEFAULT '', `description` text DEFAULT NULL, `added_by` int(11) NOT NULL DEFAULT 1, `is_best` int(11) NOT NULL DEFAULT 1, `is_new` int(11) NOT NULL DEFAULT 1, `is_feature` int(11) NOT NULL DEFAULT 1, `trending` int(11) NOT NULL DEFAULT 1, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `filters` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `subcat` varchar(255) NOT NULL, `name` varchar(255) NOT NULL, `filter_name` varchar(255) DEFAULT '', `subcat_id` int(11) DEFAULT 1, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `subfilter` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `p_filter` varchar(255) NOT NULL, `subfilter_name` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `product_subfilters` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `pid` int(11) NOT NULL, `sfid` int(11) NOT NULL, `subfilter` varchar(255) DEFAULT '') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `users` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `name` varchar(255) NOT NULL, `email` varchar(255) NOT NULL UNIQUE, `mobile` varchar(255) NOT NULL, `password` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1, `email_verify` int(11) NOT NULL DEFAULT 1, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `user_address` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `uid` int(11) NOT NULL, `user_name` varchar(255) NOT NULL, `user_mobile` varchar(255) NOT NULL, `user_add` text NOT NULL, `user_pin` varchar(255) NOT NULL, `user_city` int(11) NOT NULL DEFAULT 1, `user_local` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `user_wallet` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` int(11) NOT NULL UNIQUE, `ballance` float NOT NULL DEFAULT 1000.0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `user_wallet_history` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `user_id` int(11) NOT NULL, `amt` float NOT NULL DEFAULT 0.0, `type` varchar(255) NOT NULL, `msg` text DEFAULT NULL, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `cart` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `u_id` int(11) NOT NULL, `belonging_city` int(11) NOT NULL DEFAULT 1, `total` float NOT NULL DEFAULT 0.0, `promo` varchar(255) DEFAULT '', `is_add_w` int(11) NOT NULL DEFAULT 0, `wl_amt` float NOT NULL DEFAULT 0.0, `is_applied` int(11) NOT NULL DEFAULT 0, `final_amt` float NOT NULL DEFAULT 0.0, `ship_fee` float NOT NULL DEFAULT 0.0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `cart_detail` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `cart_id` int(11) NOT NULL, `p_id` int(11) NOT NULL, `qty` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `order_status` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `o_status` varchar(255) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `orders` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `o_id` varchar(255) NOT NULL, `u_id` int(11) NOT NULL, `ad_id` int(11) NOT NULL, `dv_date` int(11) NOT NULL DEFAULT 1, `dv_time` varchar(255) DEFAULT '1', `total_amt` float NOT NULL DEFAULT 0.0, `ship_fee_order` float NOT NULL DEFAULT 0.0, `final_val` float NOT NULL DEFAULT 0.0, `payment_type` varchar(255) NOT NULL, `order_status` int(11) NOT NULL DEFAULT 1, `isnew` int(11) NOT NULL DEFAULT 1, `u_cnfrm` int(11) NOT NULL DEFAULT 0, `udvc` int(11) NOT NULL DEFAULT 0, `ptu` int(11) NOT NULL DEFAULT 0, `seller_id` int(11) DEFAULT 1, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `order_detail` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `oid` int(11) NOT NULL, `p_id` int(11) NOT NULL, `qty` int(11) NOT NULL DEFAULT 1, `delivered_qty` int(11) NOT NULL DEFAULT 0, `hover` int(11) NOT NULL DEFAULT 0, `rcvd` int(11) NOT NULL DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `order_time` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `oid` int(11) NOT NULL, `o_status` int(11) NOT NULL, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `order_stlmnt` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `oid` int(11) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `wishlist` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `u_id` int(11) NOT NULL, `p_id` int(11) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `seller_wallet` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `seller_id` int(11) NOT NULL, `ballance` float NOT NULL DEFAULT 5000.0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `seller_wallet_history` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `seller_id` int(11) NOT NULL, `amt` float NOT NULL DEFAULT 0.0, `type` varchar(255) NOT NULL, `msg` text DEFAULT NULL, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `promo` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `code` varchar(255) NOT NULL, `discount` float NOT NULL DEFAULT 10.0, `type` varchar(255) NOT NULL DEFAULT 'percentage', `min_val` float NOT NULL DEFAULT 100.0, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        "CREATE TABLE IF NOT EXISTS `settelment_detail` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `seller_id` int(11) NOT NULL, `order_id` int(11) NOT NULL, `amount` float NOT NULL, `status` int(11) NOT NULL DEFAULT 1, `added_on` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    ];

    foreach ($statements as $st) {
        @mysqli_query($con, $st);
    }
}

// Always ensure subcategories table has records from sub_cat
@mysqli_query($con, "CREATE TABLE IF NOT EXISTS `subcategories` (`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, `cat_id` int(11) DEFAULT 1, `subcat` varchar(255) NOT NULL, `status` int(11) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
$subcat_cnt = @mysqli_fetch_row(@mysqli_query($con, "SELECT COUNT(*) FROM `subcategories`"))[0] ?? 0;
if ($subcat_cnt == 0) {
    @mysqli_query($con, "INSERT INTO `subcategories` (`id`, `cat_id`, `subcat`, `status`) VALUES
        (1, 1, 'Daily Greens', 1),
        (2, 1, 'Root Vegetables', 1),
        (3, 2, 'Seasonal Fruits', 1),
        (4, 3, 'Milk & Butter', 1),
        (5, 4, 'Cold Drinks & Juices', 1)
        ON DUPLICATE KEY UPDATE `subcat`=VALUES(`subcat`);");
}

// Ensure products are populated with active images and proper attributes
$prod_cnt = @mysqli_fetch_row(@mysqli_query($con, "SELECT COUNT(*) FROM `product`"))[0] ?? 0;
if ($prod_cnt < 8) {
    @mysqli_query($con, "DELETE FROM `product`");
    @mysqli_query($con, "INSERT INTO `product` (`id`, `product_name`, `category`, `subcat`, `cat_id`, `scat_id`, `price`, `fa`, `sku`, `qty`, `status`, `isapp`, `isappp`, `bs`, `belonging_city`, `img1`, `description`, `added_by`, `is_best`, `is_new`, `is_feature`, `trending`) VALUES
        (1, 'Organic Farm Fresh Tomatoes 1kg', '1', '1', 1, 1, 40.0, 32.0, 'SKU-TOM-01', 100, 1, 1, 1, 1, 1, 'img-1.jpg', 'Farm fresh plump organic red tomatoes harvested daily.', 1, 1, 1, 1, 1),
        (2, 'Crisp Green Spinach 500g', '1', '1', 1, 1, 30.0, 24.0, 'SKU-SPI-01', 80, 1, 1, 1, 1, 1, 'img-2.jpg', 'Tender hydroponic baby green spinach rich in iron.', 1, 1, 1, 1, 1),
        (3, 'Fresh Alphonso Mangoes Box', '2', '3', 2, 3, 350.0, 299.0, 'SKU-MNG-01', 50, 1, 1, 1, 1, 1, 'img-3.jpg', 'Sweet hand-picked premium Alphonso mangoes.', 1, 1, 1, 1, 1),
        (4, 'Farm Fresh Whole Milk 1L', '3', '4', 3, 4, 60.0, 56.0, 'SKU-MLK-01', 120, 1, 1, 1, 1, 1, 'img-4.jpg', 'Pasteurized pure cow milk delivered chilled.', 1, 1, 1, 1, 1),
        (5, 'Organic Sweet Corn (2 Pack)', '1', '1', 1, 1, 50.0, 42.0, 'SKU-CRN-01', 90, 1, 1, 1, 1, 1, 'img-5.jpg', 'Golden sweet corn directly from organic fields.', 1, 1, 1, 1, 1),
        (6, 'Natural Orange Juice 1L', '4', '5', 4, 5, 120.0, 99.0, 'SKU-JUC-01', 65, 1, 1, 1, 1, 1, 'img-6.jpg', 'Fresh squeezed 100% natural orange juice.', 1, 1, 1, 1, 1),
        (7, 'Organic Red Apples 1kg', '2', '3', 2, 3, 180.0, 149.0, 'SKU-APL-01', 75, 1, 1, 1, 1, 1, 'img-7.jpg', 'Crisp mountain grown sweet red apples.', 1, 1, 1, 1, 1),
        (8, 'Artisan Cheddar Cheese 200g', '3', '4', 3, 4, 210.0, 175.0, 'SKU-CHS-01', 40, 1, 1, 1, 1, 1, 'img-8.jpg', 'Rich aged cheddar cheese made from whole milk.', 1, 1, 1, 1, 1)
        ON DUPLICATE KEY UPDATE `product_name`=VALUES(`product_name`);");
}

// Dynamic Site Root Path Constant
if (!defined('D')) {
    define('D', "/ecommercepro");
}
?>
