<?php
// includes/db.php - Resilient Database Connection, Automatic Schema Creation & Self-Healing Migration

$db_host = "127.0.0.1";
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet";

// Include existing config if present
if (file_exists(__DIR__ . '/database.php')) {
    include_once __DIR__ . '/database.php';
}

$pdo = null;
$db_error = null;

// Try combinations of hosts & common default passwords for local XAMPP/LAMPP
$hosts = array_unique([$db_host, "krazeplanet", "127.0.0.1", "localhost", "172.19.0.1", "host.docker.internal"]);
$passwords = array_unique([$db_pass, "", "secret123", "root"]);

$connected = false;
foreach ($hosts as $host) {
    foreach ($passwords as $pass) {
        try {
            // 1. Connect without DB selected to ensure database exists
            $pdo_init = new PDO("mysql:host=$host;charset=utf8mb4", $db_user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2
            ]);
            
            $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

            // 2. Connect to the specific database
            $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $db_user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            $connected = true;
            break 2;
        } catch (PDOException $e) {
            $db_error = $e->getMessage();
        }
    }
}

if ($pdo && $connected) {
    try {
        // 3. Auto-Create All Complete Tables with all columns
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `username` VARCHAR(50) NOT NULL UNIQUE,
              `fullname` VARCHAR(100) DEFAULT '',
              `phone` VARCHAR(30) DEFAULT '',
              `email` VARCHAR(100) NOT NULL UNIQUE,
              `password` VARCHAR(255) NOT NULL,
              `country` VARCHAR(10) DEFAULT 'IN',
              `avatar` VARCHAR(500) DEFAULT NULL,
              `role` VARCHAR(20) DEFAULT 'trainee',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `tasks` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `title` VARCHAR(255) NOT NULL,
              `description` TEXT DEFAULT NULL,
              `category_name` VARCHAR(100) DEFAULT 'Web Security',
              `assigned_users` VARCHAR(255) DEFAULT 'All Trainees',
              `submission_date` DATE DEFAULT NULL,
              `labs_json` LONGTEXT DEFAULT NULL,
              `created_by` VARCHAR(100) DEFAULT 'admin',
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `user_solved_labs` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `lab_id` VARCHAR(255) NOT NULL,
              `difficulty` VARCHAR(20) DEFAULT 'easy',
              `points` INT DEFAULT 20,
              `solved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `user_lab_unique` (`user_id`, `lab_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `user_bookmarks` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `lab_id` VARCHAR(255) NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY `user_bookmark_unique` (`user_id`, `lab_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `user_lab_history` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `lab_id` VARCHAR(255) NOT NULL,
              `lab_title` VARCHAR(255) DEFAULT NULL,
              `lab_badge` VARCHAR(50) DEFAULT 'LAB',
              `lab_category` VARCHAR(100) DEFAULT 'Web Security',
              `lab_url` VARCHAR(500) DEFAULT NULL,
              `last_accessed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY `user_lab_hist_unique` (`user_id`, `lab_id`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            
            CREATE TABLE IF NOT EXISTS `lab_instances` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `username` VARCHAR(50) NOT NULL,
              `lab_id` VARCHAR(100) NOT NULL,
              `lab_title` VARCHAR(255) DEFAULT NULL,
              `subdomain` VARCHAR(255) NOT NULL,
              `instance_dir` VARCHAR(500) NOT NULL,
              `db_name` VARCHAR(100) DEFAULT NULL,
              `status` ENUM('active', 'expired', 'destroyed') DEFAULT 'active',
              `expires_at` DATETIME NOT NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX(`username`, `lab_id`),
              INDEX(`status`, `expires_at`),
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `user_notifications` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NULL,
              `title` VARCHAR(255) NOT NULL,
              `message` TEXT NOT NULL,
              `link` VARCHAR(255) DEFAULT 'assignments.php',
              `icon` VARCHAR(50) DEFAULT 'bi-bell-fill',
              `icon_bg` VARCHAR(50) DEFAULT 'bg-info bg-opacity-10 text-info',
              `is_read` TINYINT(1) DEFAULT 0,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Self-Healing Schema Alterations (Checks existing columns in users and user_solved_labs)
        $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('fullname', $userCols)) $pdo->exec("ALTER TABLE users ADD COLUMN fullname VARCHAR(100) DEFAULT '' AFTER username");
        if (!in_array('phone', $userCols)) $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(30) DEFAULT '' AFTER fullname");
        if (!in_array('country', $userCols)) $pdo->exec("ALTER TABLE users ADD COLUMN country VARCHAR(10) DEFAULT 'IN' AFTER phone");
        if (!in_array('avatar', $userCols)) $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(500) DEFAULT NULL AFTER email");
        if (!in_array('role', $userCols)) $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'trainee' AFTER avatar");

        $solvedCols = $pdo->query("DESCRIBE user_solved_labs")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('difficulty', $solvedCols)) $pdo->exec("ALTER TABLE user_solved_labs ADD COLUMN difficulty VARCHAR(20) DEFAULT 'easy' AFTER lab_id");
        if (!in_array('points', $solvedCols)) $pdo->exec("ALTER TABLE user_solved_labs ADD COLUMN points INT DEFAULT 20 AFTER difficulty");

        // 5. Default Admin User creation if users table is empty
        $countUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        if ($countUsers == 0) {
            $passHash = password_hash('admin', PASSWORD_DEFAULT);
            $stmtAdm = $pdo->prepare("
                INSERT INTO users (username, fullname, phone, email, password, country, avatar, role)
                VALUES ('admin', 'System Administrator', '+91 9876543210', 'admin@krazeplanet.com', ?, 'IN', 'https://api.dicebear.com/7.x/adventurer/svg?seed=Admin&hair=short01', 'admin')
            ");
            $stmtAdm->execute([$passHash]);
        }

        // 6. Default Tasks/Assignments creation if tasks table is empty
        $countTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        if ($countTasks == 0) {
            $defaultLabs1 = json_encode([
                [
                    'badge' => 'LAB 1',
                    'difficulty' => 'easy',
                    'title' => 'HTML Injection - Reflected (GET Parameter)',
                    'desc' => 'Inspect parameter reflections and execute arbitrary HTML element payload injection.',
                    'url' => '/htmli_reflected_get.php',
                    'report_url' => 'HackerOneReport/1.md'
                ],
                [
                    'badge' => 'LAB 2',
                    'difficulty' => 'easy',
                    'title' => 'HTML Injection - Reflected (POST Parameter)',
                    'desc' => 'Bypass client validations to submit payload through HTTP POST body.',
                    'url' => '/htmli_reflected_post.php',
                    'report_url' => 'HackerOneReport/2.md'
                ]
            ]);

            $defaultLabs2 = json_encode([
                [
                    'badge' => 'LAB 1',
                    'difficulty' => 'easy',
                    'title' => 'Reflected XSS - Basic Script Injection',
                    'desc' => 'Inject unencoded script elements into query string reflection sinks.',
                    'url' => '/reflected_xss_basic.php',
                    'report_url' => 'HackerOneReport/1.md'
                ],
                [
                    'badge' => 'LAB 19',
                    'difficulty' => 'hard',
                    'title' => 'Reflected XSS - Multi-Parameter Filter Evasion',
                    'desc' => 'Advanced filter evasion techniques across multiple interdependent reflection points.',
                    'url' => '/checkout',
                    'report_url' => 'HackerOneReport/1.md'
                ]
            ]);

            $stmtTask = $pdo->prepare("
                INSERT INTO tasks (title, description, category_name, assigned_users, submission_date, labs_json, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtTask->execute([
                'HTML Injection (HTMLI) Penetration Testing Assessment',
                'Complete hands-on HTML injection exploit laboratories, document attack vectors, and construct security audit reports.',
                'HTML Injection (HTMLI)',
                'All Trainees',
                date('Y-m-d', strtotime('+7 days')),
                $defaultLabs1,
                'admin'
            ]);

            $stmtTask->execute([
                'Cross-Site Scripting (XSS) Core Challenge Track',
                'Master client-side security testing across reflected, stored, and filter-evasion attack surfaces.',
                'Cross-Site Scripting (XSS)',
                'All Trainees',
                date('Y-m-d', strtotime('+14 days')),
                $defaultLabs2,
                'admin'
            ]);
        }

        // 7. Default Notifications if empty
        $countNotifs = $pdo->query("SELECT COUNT(*) FROM user_notifications")->fetchColumn();
        if ($countNotifs == 0) {
            $pdo->exec("
                INSERT INTO user_notifications (user_id, title, message, link, icon, icon_bg, created_at)
                VALUES 
                (NULL, 'New Assignment: HTML Injection', 'Complete the HTML Injection (HTMLI) penetration testing report assignment.', 'assignments.php', 'bi-journal-code', 'bg-info bg-opacity-10 text-info', NOW()),
                (NULL, 'Platform Labs Updated', '260+ interactive vulnerability training laboratories are active and ready.', 'index.php', 'bi-shield-check', 'bg-success bg-opacity-10 text-success', DATE_SUB(NOW(), INTERVAL 1 DAY)),
                (NULL, 'Welcome to KrazePlanet', 'Track your solved labs, bookmarks, and certifications directly in your dashboard.', 'profile.php', 'bi-person-check-fill', 'bg-warning bg-opacity-10 text-warning', DATE_SUB(NOW(), INTERVAL 2 DAY)),
                (NULL, 'CTF Leaderboard Active', 'Check the top security researchers and solve labs to climb the ranking.', 'leaderboard.php', 'bi-trophy-fill', 'bg-danger bg-opacity-10 text-danger', DATE_SUB(NOW(), INTERVAL 3 DAY)),
                (NULL, 'WhatsApp Support Live', 'Direct WhatsApp mentoring and lab assistance is now connected.', 'contact.php', 'bi-whatsapp', 'bg-success bg-opacity-10 text-success', DATE_SUB(NOW(), INTERVAL 4 DAY))
            ");
        }
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}
