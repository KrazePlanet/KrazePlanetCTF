<?php 
// DB credentials for local XAMPP/LAMPP environment
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'armentum');

// Auto-initialize Database and Tables if they do not exist
try
{
    // Connect to server to ensure database exists
    $pdo_init = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;");

    // Establish persistent PDO connection to armentum database
    $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, array(
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"
    ));
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

    // Auto-create Tables
    $dbh->exec("
        CREATE TABLE IF NOT EXISTS `admin` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `username` varchar(50) NOT NULL,
          `email` varchar(50) NOT NULL,
          `password` varchar(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

    $dbh->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `name` varchar(50) NOT NULL,
          `email` varchar(50) NOT NULL,
          `password` varchar(50) NOT NULL,
          `gender` varchar(50) NOT NULL,
          `mobile` varchar(50) NOT NULL,
          `designation` varchar(50) NOT NULL,
          `image` varchar(150) NOT NULL DEFAULT 'default.jpg',
          `status` int(10) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

    $dbh->exec("
        CREATE TABLE IF NOT EXISTS `notification` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `notiuser` varchar(50) NOT NULL,
          `notireciver` varchar(50) NOT NULL,
          `notitype` varchar(50) NOT NULL,
          `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

    $dbh->exec("
        CREATE TABLE IF NOT EXISTS `feedback` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `sender` varchar(50) NOT NULL,
          `reciver` varchar(50) NOT NULL,
          `title` varchar(100) NOT NULL,
          `feedbackdata` varchar(500) NOT NULL,
          `attachment` varchar(50) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

    $dbh->exec("
        CREATE TABLE IF NOT EXISTS `deleteduser` (
          `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
          `email` varchar(50) NOT NULL,
          `deltime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");

    // Ensure default admin account exists with username: admin, password: admin (md5: 21232f297a57a5a743894a0e4a801fc3)
    $chkAdmin = $dbh->query("SELECT COUNT(*) FROM `admin` WHERE username='admin'")->fetchColumn();
    if ($chkAdmin == 0) {
        $dbh->exec("INSERT INTO `admin` (`id`, `username`, `email`, `password`) VALUES (1, 'admin', 'admin@admin.com', '21232f297a57a5a743894a0e4a801fc3');");
    } else {
        $dbh->exec("UPDATE `admin` SET `password`='21232f297a57a5a743894a0e4a801fc3' WHERE `username`='admin';");
    }

    // Also ensure admin user exists in users table
    $chkUserAdmin = $dbh->query("SELECT COUNT(*) FROM `users` WHERE email='admin@admin.com' OR name='admin'")->fetchColumn();
    if ($chkUserAdmin == 0) {
        $dbh->exec("INSERT INTO `users` (`name`, `email`, `password`, `gender`, `mobile`, `designation`, `image`, `status`) VALUES ('admin', 'admin@admin.com', '21232f297a57a5a743894a0e4a801fc3', 'Male', '9876543210', 'System Administrator', 'default.jpg', 1);");
    } else {
        $dbh->exec("UPDATE `users` SET `password`='21232f297a57a5a743894a0e4a801fc3', `status`=1 WHERE `email`='admin@admin.com' OR `name`='admin';");
    }
}
catch (PDOException $e)
{
    exit("Database Connection Error: " . $e->getMessage());
}
?>
