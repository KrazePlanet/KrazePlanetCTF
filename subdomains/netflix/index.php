<?php
// ============================================================
// Database Configuration
// ============================================================
$dbname = 'KrazePlanet';
$username = 'root';
$password = '';
$hosts = ['127.0.0.1', 'localhost'];

// Table Names Configuration
$table_users = 'netflix_users';
$table_comments = 'netflix_comments';

$pdo = null;
$lastException = null;
foreach ($hosts as $h) {
    try {
        $pdo = new PDO("mysql:host=$h;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$dbname`");
        break;
    } catch(PDOException $e) {
        $lastException = $e;
    }
}
if (!$pdo) {
    die("Connection failed: " . ($lastException ? $lastException->getMessage() : "Unknown error"));
}

// Initialize database tables
function initializeDatabase($pdo) {
    global $table_users, $table_comments;

    // Users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_users} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255),
            role ENUM('user', 'admin') DEFAULT 'user',
            avatar VARCHAR(500) DEFAULT 'https://upload.wikimedia.org/wikipedia/commons/0/0b/Netflix-avatar.png',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Comments & Movie Feelings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS {$table_comments} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            movie_id INT NOT NULL,
            movie_title VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            username VARCHAR(255) NOT NULL,
            rating INT NOT NULL DEFAULT 5,
            feeling VARCHAR(100) DEFAULT 'Mind Blown',
            comment TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert default admin user if none exists
    $checkAdmin = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE role = 'admin'");
    $checkAdmin->execute();
    if ($checkAdmin->fetchColumn() == 0) {
        $stmt = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name, role) VALUES (?, ?, ?, 'Netflix Admin', 'admin')");
        $stmt->execute([
            'admin',
            'admin@netflix.local',
            password_hash('admin123', PASSWORD_DEFAULT)
        ]);
    }
}
initializeDatabase($pdo);

// Session management
session_start();

// Authentication Helpers
function getCurrentUser($pdo) {
    global $table_users;
    if (!isset($_SESSION['netflix_user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE id = ?");
    $stmt->execute([$_SESSION['netflix_user_id']]);
    $u = $stmt->fetch();
    if (!$u) {
        unset($_SESSION['netflix_user_id']);
        unset($_SESSION['netflix_username']);
        unset($_SESSION['netflix_role']);
        return null;
    }
    return $u;
}

function loginUser($pdo, $username, $password) {
    global $table_users;
    $stmt = $pdo->prepare("SELECT * FROM {$table_users} WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['netflix_user_id'] = $user['id'];
        $_SESSION['netflix_username'] = $user['username'];
        $_SESSION['netflix_role'] = $user['role'];
        return true;
    }
    return false;
}

function logoutUser() {
    unset($_SESSION['netflix_user_id']);
    unset($_SESSION['netflix_username']);
    unset($_SESSION['netflix_role']);
    header("Location: index.php");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logoutUser();
}

$currentUser = getCurrentUser($pdo);

// Complete Movie Catalog Dictionary (All 48 Movies Populated)
$movie_catalog_map = [
    // Popular (101 - 108)
    101 => [
        'id' => 101, 'title' => 'Inception', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '2010', 'genre' => 'Sci-Fi / Action', 'duration' => '2h 28m',
        'overview' => "A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O."
    ],
    102 => [
        'id' => 102, 'title' => 'Interstellar', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2014', 'genre' => 'Sci-Fi / Adventure', 'duration' => '2h 49m',
        'overview' => "A team of explorers travel through a wormhole in space in an attempt to ensure humanity's survival."
    ],
    103 => [
        'id' => 103, 'title' => 'The Dark Knight', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.0, 'year' => '2008', 'genre' => 'Action / Crime', 'duration' => '2h 32m',
        'overview' => "When the menace known as the Joker wreaks havoc on Gotham, Batman must accept one of the greatest psychological tests."
    ],
    104 => [
        'id' => 104, 'title' => 'Fight Club', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOTgyOGQ1NDItNGU3Ny00MjU3LTg2YWEtNmEyYjBiMjI1Y2M5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '1999', 'genre' => 'Drama', 'duration' => '2h 19m',
        'overview' => "An insomniac office worker and a devil-may-care soap maker form an underground fight club that evolves into much more."
    ],
    105 => [
        'id' => 105, 'title' => 'Avengers: Endgame', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTc5MDE2ODcwNV5BMl5BanBnXkFtZTgwMzI2NzQ2NzM@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1607604276583-eef5d076aa5f?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.4, 'year' => '2019', 'genre' => 'Action / Sci-Fi', 'duration' => '3h 01m',
        'overview' => "After the devastating events of Infinity War, the universe is in ruins. With the help of remaining allies, the Avengers assemble once more."
    ],
    106 => [
        'id' => 106, 'title' => 'Dune', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMGJlMGM3NDAtOWNhMy00MWExLWI2MzEtMDQ0ZDIzZDY5ZmQ2XkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.0, 'year' => '2021', 'genre' => 'Sci-Fi / Adventure', 'duration' => '2h 35m',
        'overview' => "Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding, must travel to the most dangerous planet."
    ],
    107 => [
        'id' => 107, 'title' => 'Oppenheimer', 'poster' => 'https://m.media-amazon.com/images/M/MV5BN2JkMDc5MGQtZjg3YS00NmFiLWIyZmQtZTJmNTM5MjVmYTQ4XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.9, 'year' => '2023', 'genre' => 'Biography / Drama', 'duration' => '3h 00m',
        'overview' => "The story of J. Robert Oppenheimer's role in the development of the atomic bomb during World War II."
    ],
    108 => [
        'id' => 108, 'title' => 'Everything Everywhere All at Once', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOWNmMzAzZmQtNDQ1NC00Nzk5LTkyMmUtNGI2N2NkOWM4MzEyXkEyXkFqcGc@._V1_QL75_UY562_CR4,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '2022', 'genre' => 'Sci-Fi / Comedy', 'duration' => '2h 19m',
        'overview' => "A middle-aged Chinese immigrant is swept up into an insane adventure in which she alone can save existence by exploring other universes."
    ],

    // Trending (109 - 116)
    109 => [
        'id' => 109, 'title' => 'Stranger Things', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNjRiMTA4NWUtNmE0ZC00NGM0LWJhMDUtZWIzMDM5ZDIzNTg3XkEyXkFqcGc@._V1_QL75_UY562_CR35,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2016', 'genre' => 'Sci-Fi / Horror', 'duration' => '4 Seasons',
        'overview' => "When a young boy vanishes, a small town uncovers a mystery involving secret experiments, terrifying supernatural forces and one strange little girl."
    ],
    110 => [
        'id' => 110, 'title' => 'Squid Game', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYTU3ZDVhNmMtMDVlNC00MDc0LTgwNDMtYWE5MTI2ZGI4YWIwXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.0, 'year' => '2021', 'genre' => 'Thriller / Drama', 'duration' => '2 Seasons',
        'overview' => "Hundreds of cash-strapped players accept a strange invitation to compete in children's games. Inside, a tempting prize awaits with deadly high stakes."
    ],
    111 => [
        'id' => 111, 'title' => 'Wednesday', 'poster' => 'https://m.media-amazon.com/images/M/MV5BY2E1NDI5OWEtODJmYi00Nzg2LWI4MjUtODFiMTU2YWViOTU3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.1, 'year' => '2022', 'genre' => 'Comedy / Fantasy', 'duration' => '1 Season',
        'overview' => "Follows Wednesday Addams' years as a student, attempting to master her emerging psychic ability and solve the monster mystery."
    ],
    112 => [
        'id' => 112, 'title' => 'Breaking Bad', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMzU5ZGYzNmQtMTdhYy00OGRiLTg0NmQtYjVjNzliZTg1ZGE4XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.5, 'year' => '2008', 'genre' => 'Crime / Drama', 'duration' => '5 Seasons',
        'overview' => "A chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine with a former student."
    ],
    113 => [
        'id' => 113, 'title' => 'Money Heist', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZjkxZWJiNTUtYjQwYS00MTBlLTgwODQtM2FkNWMyMjMwOGZiXkEyXkFqcGc@._V1_QL75_UX380_CR0,5,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.2, 'year' => '2017', 'genre' => 'Action / Crime', 'duration' => '5 Parts',
        'overview' => "An unusual group of robbers attempt to carry out the most perfect robbery in Spanish history - stealing 2.4 billion euros from the Royal Mint."
    ],
    114 => [
        'id' => 114, 'title' => 'Peaky Blinders', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOGM0NGY3ZmItOGE2ZC00OWIxLTk0N2EtZWY4Yzg3ZDlhNGI3XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '2013', 'genre' => 'Crime / Drama', 'duration' => '6 Seasons',
        'overview' => "A gangster family epic set in 1900s England, centering on a gang who sew razor blades in the peaks of their caps, and their fierce boss Tommy Shelby."
    ],
    115 => [
        'id' => 115, 'title' => 'Arcane', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYjA2NzhlMDItNWRmZC00MzRjLWE3ZjAtZjBlZDAwOWY2ODdjXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.0, 'year' => '2021', 'genre' => 'Animation / Sci-Fi', 'duration' => '2 Seasons',
        'overview' => "Set in the utopian region of Piltover and the oppressed underground of Zaun, the story follows the origins of two iconic League champions."
    ],
    116 => [
        'id' => 116, 'title' => 'Dark', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOWJjMGViY2UtNTAzNS00ZGFjLWFkNTMtMDBiMDMyZTM1NTY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,57,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1508739773434-c26b3d09e071?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2017', 'genre' => 'Sci-Fi / Mystery', 'duration' => '3 Seasons',
        'overview' => "A family saga with a supernatural twist, set in a German town where the disappearance of two young children exposes the double lives of four families."
    ],

    // Action (117 - 124)
    117 => [
        'id' => 117, 'title' => 'The Batman', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMmU5NGJlMzAtMGNmOC00YjJjLTgyMzUtNjAyYmE4Njg5YWMyXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 7.9, 'year' => '2022', 'genre' => 'Action / Crime', 'duration' => '2h 56m',
        'overview' => "When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate the city's hidden corruption."
    ],
    118 => [
        'id' => 118, 'title' => 'Top Gun: Maverick', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMDBkZDNjMWEtOTdmMi00NmExLTg5MmMtNTFlYTJlNWY5YTdmXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1517976487492-5750f3195933?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.3, 'year' => '2022', 'genre' => 'Action / Drama', 'duration' => '2h 11m',
        'overview' => "After thirty years, Maverick is still pushing the envelope as a top naval aviator, but must confront ghosts of his past."
    ],
    119 => [
        'id' => 119, 'title' => 'Mad Max: Fury Road', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZDRkODJhOTgtOTc1OC00NTgzLTk4NjItNDgxZDY4jlmNDY2XkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.1, 'year' => '2015', 'genre' => 'Action / Sci-Fi', 'duration' => '2h 00m',
        'overview' => "In a post-apocalyptic wasteland, a woman rebels against a tyrannical ruler in search for her homeland with the aid of a group of female prisoners."
    ],
    120 => [
        'id' => 120, 'title' => 'Pulp Fiction', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYTViYTE3ZGQtNDBlMC00ZTAyLTkyODMtZGRiZDg0MjA2YThkXkEyXkFqcGc@._V1_QL75_UY562_CR3,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.9, 'year' => '1994', 'genre' => 'Crime / Drama', 'duration' => '2h 34m',
        'overview' => "The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption."
    ],
    121 => [
        'id' => 121, 'title' => 'Gladiator', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYWQ4YmNjYjEtOWE1Zi00Y2U4LWI4NTAtMTU0MjkxNWQ1ZmJiXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.5, 'year' => '2000', 'genre' => 'Action / Adventure', 'duration' => '2h 35m',
        'overview' => "A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery."
    ],
    122 => [
        'id' => 122, 'title' => 'Spider-Man: Across the Spider-Verse', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNThiZjA3MjItZGY5Ni00ZmJhLWEwN2EtOTBlYTA4Y2E0M2ZmXkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1635805737707-575885ab0820?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2023', 'genre' => 'Animation / Action', 'duration' => '2h 20m',
        'overview' => "Miles Morales catapults across the Multiverse, where he encounters a team of Spider-People charged with protecting its very existence."
    ],
    123 => [
        'id' => 123, 'title' => 'Avatar: The Way of Water', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNWI0Y2NkOWEtMmM2OC00MjQ3LWI1YzItZGQxYzQ3NzI4NWZmXkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1600&auto=format&fit=crop&q=80', 'rating' => 7.8, 'year' => '2022', 'genre' => 'Sci-Fi / Action', 'duration' => '3h 12m',
        'overview' => "Jake Sully lives with his newfound family formed on the planet of Pandora. Once a familiar threat returns to finish what was previously started."
    ],
    124 => [
        'id' => 124, 'title' => 'Cyberpunk: Edgerunners', 'poster' => 'https://m.media-amazon.com/images/M/MV5BM2JkMzM2ZmYtNWU4MS00MjZhLWFhZWUtYWFjYTJkN2RhZDliXkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.6, 'year' => '2022', 'genre' => 'Animation / Sci-Fi', 'duration' => '1 Season',
        'overview' => "A street kid trying to survive in a technology and body modification-obsessed city of the future."
    ],

    // Sci-Fi (125 - 132)
    125 => [
        'id' => 125, 'title' => 'The Matrix', 'poster' => 'https://m.media-amazon.com/images/M/MV5BN2NmN2VhMTQtMDNiOS00NDlhLTliMjgtODE2ZTY0ODQyNDRhXkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1510511459019-5dda7724fd87?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '1999', 'genre' => 'Sci-Fi / Action', 'duration' => '2h 16m',
        'overview' => "When a beautiful stranger leads computer hacker Neo to a forbidding underworld, he discovers the shocking truth--the life he knows is the elaborate deception."
    ],
    126 => [
        'id' => 126, 'title' => 'Interstellar', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYzdjMDAxZGItMjI2My00ODA1LTlkNzItOWFjMDU5ZDJlYWY3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2014', 'genre' => 'Sci-Fi / Adventure', 'duration' => '2h 49m',
        'overview' => "A team of explorers travel through a wormhole in space in an attempt to ensure humanity's survival."
    ],
    127 => [
        'id' => 127, 'title' => 'Inception', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMjAxMzY3NjcxNF5BMl5BanBnXkFtZTcwNTI5OTM0Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '2010', 'genre' => 'Sci-Fi / Action', 'duration' => '2h 28m',
        'overview' => "A thief who steals corporate secrets through dream-sharing technology is given the inverse task of planting an idea into the mind of a CEO."
    ],
    128 => [
        'id' => 128, 'title' => 'Dune', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMGJlMGM3NDAtOWNhMy00MWExLWI2MzEtMDQ0ZDIzZDY5ZmQ2XkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.0, 'year' => '2021', 'genre' => 'Sci-Fi / Adventure', 'duration' => '2h 35m',
        'overview' => "Paul Atreides, a brilliant and gifted young man born into a great destiny beyond his understanding, must travel to the most dangerous planet."
    ],
    129 => [
        'id' => 129, 'title' => 'Cyberpunk: Edgerunners', 'poster' => 'https://m.media-amazon.com/images/M/MV5BM2JkMzM2ZmYtNWU4MS00MjZhLWFhZWUtYWFjYTJkN2RhZDliXkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.6, 'year' => '2022', 'genre' => 'Animation / Sci-Fi', 'duration' => '1 Season',
        'overview' => "A street kid trying to survive in a technology and body modification-obsessed city of the future."
    ],
    130 => [
        'id' => 130, 'title' => 'Avatar: The Way of Water', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNWI0Y2NkOWEtMmM2OC00MjQ3LWI1YzItZGQxYzQ3NzI4NWZmXkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1600&auto=format&fit=crop&q=80', 'rating' => 7.8, 'year' => '2022', 'genre' => 'Sci-Fi / Action', 'duration' => '3h 12m',
        'overview' => "Jake Sully lives with his newfound family formed on the planet of Pandora. Once a familiar threat returns to finish what was previously started."
    ],
    131 => [
        'id' => 131, 'title' => 'Sherlock', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNTQzNGZjNDEtOTMwYi00MzFjLWE2ZTYtYzYxYzMwMjZkZDc5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.1, 'year' => '2010', 'genre' => 'Crime / Mystery', 'duration' => '4 Seasons',
        'overview' => "A modern update finds the famous sleuth and his doctor partner solving crime in 21st century London."
    ],
    132 => [
        'id' => 132, 'title' => 'Stranger Things', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNjRiMTA4NWUtNmE0ZC00NGM0LWJhMDUtZWIzMDM5ZDIzNTg3XkEyXkFqcGc@._V1_QL75_UY562_CR35,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2016', 'genre' => 'Sci-Fi / Horror', 'duration' => '4 Seasons',
        'overview' => "When a young boy vanishes, a small town uncovers a mystery involving secret experiments, terrifying supernatural forces and one strange little girl."
    ],

    // Crime (133 - 140)
    133 => [
        'id' => 133, 'title' => 'Peaky Blinders', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOGM0NGY3ZmItOGE2ZC00OWIxLTk0N2EtZWY4Yzg3ZDlhNGI3XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '2013', 'genre' => 'Crime / Drama', 'duration' => '6 Seasons',
        'overview' => "A gangster family epic set in 1900s England, centering on a gang who sew razor blades in the peaks of their caps, and their fierce boss Tommy Shelby."
    ],
    134 => [
        'id' => 134, 'title' => 'Breaking Bad', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMzU5ZGYzNmQtMTdhYy00OGRiLTg0NmQtYjVjNzliZTg1ZGE4XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.5, 'year' => '2008', 'genre' => 'Crime / Drama', 'duration' => '5 Seasons',
        'overview' => "A chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine with a former student."
    ],
    135 => [
        'id' => 135, 'title' => 'Money Heist', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZjkxZWJiNTUtYjQwYS00MTBlLTgwODQtM2FkNWMyMjMwOGZiXkEyXkFqcGc@._V1_QL75_UX380_CR0,5,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.2, 'year' => '2017', 'genre' => 'Action / Crime', 'duration' => '5 Parts',
        'overview' => "An unusual group of robbers attempt to carry out the most perfect robbery in Spanish history - stealing 2.4 billion euros from the Royal Mint."
    ],
    136 => [
        'id' => 136, 'title' => 'Pulp Fiction', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYTViYTE3ZGQtNDBlMC00ZTAyLTkyODMtZGRiZDg0MjA2YThkXkEyXkFqcGc@._V1_QL75_UY562_CR3,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.9, 'year' => '1994', 'genre' => 'Crime / Drama', 'duration' => '2h 34m',
        'overview' => "The lives of two mob hitmen, a boxer, a gangster and his wife intertwine in four tales of violence and redemption."
    ],
    137 => [
        'id' => 137, 'title' => 'The Dark Knight', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMTMxNTMwODM0NF5BMl5BanBnXkFtZTcwODAyMTk2Mw@@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.0, 'year' => '2008', 'genre' => 'Action / Crime', 'duration' => '2h 32m',
        'overview' => "When the menace known as the Joker wreaks havoc on Gotham, Batman must accept one of the greatest psychological tests."
    ],
    138 => [
        'id' => 138, 'title' => 'The Batman', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMmU5NGJlMzAtMGNmOC00YjJjLTgyMzUtNjAyYmE4Njg5YWMyXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 7.9, 'year' => '2022', 'genre' => 'Action / Crime', 'duration' => '2h 56m',
        'overview' => "When a sadistic serial killer begins murdering key political figures in Gotham, Batman is forced to investigate the city's hidden corruption."
    ],
    139 => [
        'id' => 139, 'title' => 'Fight Club', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOTgyOGQ1NDItNGU3Ny00MjU3LTg2YWEtNmEyYjBiMjI1Y2M5XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '1999', 'genre' => 'Drama', 'duration' => '2h 19m',
        'overview' => "An insomniac office worker and a devil-may-care soap maker form an underground fight club that evolves into much more."
    ],
    140 => [
        'id' => 140, 'title' => 'Gladiator', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYWQ4YmNjYjEtOWE1Zi00Y2U4LWI4NTAtMTU0MjkxNWQ1ZmJiXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.5, 'year' => '2000', 'genre' => 'Action / Drama', 'duration' => '2h 35m',
        'overview' => "A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery."
    ],

    // TV Shows (141 - 148)
    141 => [
        'id' => 141, 'title' => 'Stranger Things', 'poster' => 'https://m.media-amazon.com/images/M/MV5BNjRiMTA4NWUtNmE0ZC00NGM0LWJhMDUtZWIzMDM5ZDIzNTg3XkEyXkFqcGc@._V1_QL75_UY562_CR35,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.7, 'year' => '2016', 'genre' => 'Sci-Fi / Horror', 'duration' => '4 Seasons',
        'overview' => "When a young boy vanishes, a small town uncovers a mystery involving secret experiments, terrifying supernatural forces and one strange little girl."
    ],
    142 => [
        'id' => 142, 'title' => 'Squid Game', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYTU3ZDVhNmMtMDVlNC00MDc0LTgwNDMtYWE5MTI2ZGI4YWIwXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.0, 'year' => '2021', 'genre' => 'Thriller / Drama', 'duration' => '2 Seasons',
        'overview' => "Hundreds of cash-strapped players accept a strange invitation to compete in children's games. Inside, a tempting prize awaits with deadly high stakes."
    ],
    143 => [
        'id' => 143, 'title' => 'Wednesday', 'poster' => 'https://m.media-amazon.com/images/M/MV5BY2E1NDI5OWEtODJmYi00Nzg2LWI4MjUtODFiMTU2YWViOTU3XkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.1, 'year' => '2022', 'genre' => 'Comedy / Fantasy', 'duration' => '1 Season',
        'overview' => "Follows Wednesday Addams' years as a student, attempting to master her emerging psychic ability and solve the monster mystery."
    ],
    144 => [
        'id' => 144, 'title' => 'Breaking Bad', 'poster' => 'https://m.media-amazon.com/images/M/MV5BMzU5ZGYzNmQtMTdhYy00OGRiLTg0NmQtYjVjNzliZTg1ZGE4XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.5, 'year' => '2008', 'genre' => 'Crime / Drama', 'duration' => '5 Seasons',
        'overview' => "A chemistry teacher diagnosed with inoperable lung cancer turns to manufacturing and selling methamphetamine with a former student."
    ],
    145 => [
        'id' => 145, 'title' => 'Money Heist', 'poster' => 'https://m.media-amazon.com/images/M/MV5BZjkxZWJiNTUtYjQwYS00MTBlLTgwODQtM2FkNWMyMjMwOGZiXkEyXkFqcGc@._V1_QL75_UX380_CR0,5,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.2, 'year' => '2017', 'genre' => 'Action / Crime', 'duration' => '5 Parts',
        'overview' => "An unusual group of robbers attempt to carry out the most perfect robbery in Spanish history - stealing 2.4 billion euros from the Royal Mint."
    ],
    146 => [
        'id' => 146, 'title' => 'Peaky Blinders', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOGM0NGY3ZmItOGE2ZC00OWIxLTk0N2EtZWY4Yzg3ZDlhNGI3XkEyXkFqcGc@._V1_QL75_UX380_CR0,4,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.8, 'year' => '2013', 'genre' => 'Crime / Drama', 'duration' => '6 Seasons',
        'overview' => "A gangster family epic set in 1900s England, centering on a gang who sew razor blades in the peaks of their caps, and their fierce boss Tommy Shelby."
    ],
    147 => [
        'id' => 147, 'title' => 'Arcane', 'poster' => 'https://m.media-amazon.com/images/M/MV5BYjA2NzhlMDItNWRmZC00MzRjLWE3ZjAtZjBlZDAwOWY2ODdjXkEyXkFqcGc@._V1_QL75_UX380_CR0,0,380,562_.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=1600&auto=format&fit=crop&q=80', 'rating' => 9.0, 'year' => '2021', 'genre' => 'Animation / Sci-Fi', 'duration' => '2 Seasons',
        'overview' => "Set in the utopian region of Piltover and the oppressed underground of Zaun, the story follows the origins of two iconic League champions."
    ],
    148 => [
        'id' => 148, 'title' => 'The Witcher', 'poster' => 'https://m.media-amazon.com/images/M/MV5BOTQzMzNmMzUtODgwNS00YTdhLTg5N2MtOWU1YTc4YWY3NjRlXkEyXkFqcGc@._V1_SX300.jpg',
        'backdrop' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1600&auto=format&fit=crop&q=80', 'rating' => 8.0, 'year' => '2019', 'genre' => 'Action / Fantasy', 'duration' => '3 Seasons',
        'overview' => "Geralt of Rivia, a mutated monster-hunter for hire, journeys toward his destiny in a turbulent world where people often prove more wicked than beasts."
    ]
];

// Detect Movie Route Parameter (e.g. /netflix/101 or index.php?id=101)
$selectedMovieId = 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $selectedMovieId = intval($_GET['id']);
} elseif (isset($_GET['movie_id']) && intval($_GET['movie_id']) > 0) {
    $selectedMovieId = intval($_GET['movie_id']);
} else {
    // Check REQUEST_URI for pattern /netflix/101
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('#/netflix/([0-9]+)#', $uri, $matches)) {
        $selectedMovieId = intval($matches[1]);
    }
}

$currentMovie = ($selectedMovieId && isset($movie_catalog_map[$selectedMovieId])) ? $movie_catalog_map[$selectedMovieId] : null;

// Handle Add Comment Submission (REQUIRES AUTHENTICATION)
$commentError = ''; $commentSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    // SECURITY CHECK: User MUST be logged in to post comments
    if (!$currentUser) {
        $commentError = 'You must be logged in to post a reaction.';
    } else {
        $movie_id = intval($_POST['movie_id'] ?? 0);
        $movie_title = trim($_POST['movie_title'] ?? 'Movie');
        $rating = intval($_POST['rating'] ?? 5);
        $feeling = trim($_POST['feeling'] ?? 'Mind Blown 🤯');
        $comment = trim($_POST['comment'] ?? '');

        $user_id = $currentUser['id'];
        // Raw username (NO htmlspecialchars for CTF Stored XSS lab)
        $username = $currentUser['username'];

        if ($movie_id > 0 && !empty($comment)) {
            try {
                // CTF LAB VULNERABILITY: Inserted raw without htmlspecialchars for Stored XSS training
                $stmt = $pdo->prepare("INSERT INTO {$table_comments} (movie_id, movie_title, user_id, username, rating, feeling, comment) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$movie_id, $movie_title, $user_id, $username, $rating, $feeling, $comment]);

                $commentSuccess = 'Your feeling and comment have been posted!';
            } catch (PDOException $e) {
                $commentError = 'Failed to post comment: ' . $e->getMessage();
            }
        } else {
            $commentError = 'Comment content cannot be empty.';
        }
    }
}

// Handle Login / Register Submission
$authError = ''; $authSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auth_type'])) {
    if ($_POST['auth_type'] === 'login') {
        if (loginUser($pdo, $_POST['username'] ?? '', $_POST['password'] ?? '')) {
            header("Location: " . $_SERVER['REQUEST_URI']); exit();
        } else {
            $authError = 'Invalid username or password';
        }
    } elseif ($_POST['auth_type'] === 'register') {
        $u  = trim($_POST['username']  ?? '');
        $e  = trim($_POST['email']     ?? '');
        $p  =      $_POST['password']  ?? '';
        $fn = trim($_POST['full_name'] ?? '');
        if (!empty($u) && !empty($e) && !empty($p)) {
            try {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM {$table_users} WHERE username = ? OR email = ?");
                $chk->execute([$u, $e]);
                if ($chk->fetchColumn()) {
                    $authError = 'Username or email already exists';
                } else {
                    $ins = $pdo->prepare("INSERT INTO {$table_users} (username, email, password, full_name) VALUES (?, ?, ?, ?)");
                    $ins->execute([$u, $e, password_hash($p, PASSWORD_DEFAULT), $fn]);
                    loginUser($pdo, $u, $p);
                    header("Location: " . $_SERVER['REQUEST_URI']); exit();
                }
            } catch (PDOException $ex) { $authError = 'Registration failed: ' . $ex->getMessage(); }
        } else {
            $authError = 'All fields are required';
        }
    }
}

// Fetch comments for current movie if selected
$movieComments = [];
if ($currentMovie) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.avatar 
        FROM {$table_comments} c 
        LEFT JOIN {$table_users} u ON c.user_id = u.id 
        WHERE c.movie_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$currentMovie['id']]);
    $movieComments = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $currentMovie ? $currentMovie['title'] . " - Watch on Netflix" : "Netflix - Watch TV Shows & Movies Online"; ?></title>
  <link rel="icon" href="https://assets.nflxext.com/us/ffe/siteui/common/icons/nficon2016.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --netflix-red: #E50914;
      --netflix-dark: #141414;
      --netflix-card-bg: #181818;
      --netflix-gray: #2f2f2f;
      --netflix-light-gray: #aaa;
    }
    body {
      background-color: var(--netflix-dark);
      color: #ffffff;
      font-family: 'Inter', sans-serif;
      overflow-x: hidden;
      margin: 0;
      padding: 0;
    }

    /* Navbar */
    .netflix-navbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      padding: 1rem 3rem;
      background: linear-gradient(180deg, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0) 100%);
      transition: background-color 0.4s ease;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .netflix-navbar.scrolled {
      background-color: #141414;
      box-shadow: 0 4px 20px rgba(0,0,0,0.7);
    }
    .netflix-brand {
      font-family: 'Bebas Neue', cursive;
      font-size: 2.4rem;
      color: var(--netflix-red);
      text-decoration: none;
      letter-spacing: 1.5px;
      line-height: 1;
    }
    .netflix-nav-links {
      display: flex;
      align-items: center;
      gap: 1.5rem;
      list-style: none;
      margin: 0;
      padding: 0;
    }
    .netflix-nav-links a {
      color: #e5e5e5;
      text-decoration: none;
      font-size: 0.9rem;
      font-weight: 400;
      transition: color 0.2s;
    }
    .netflix-nav-links a:hover, .netflix-nav-links a.active {
      color: #ffffff;
      font-weight: 600;
    }
    .search-box-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .search-input {
      background: rgba(0,0,0,0.75);
      border: 1px solid rgba(255,255,255,0.3);
      color: white;
      border-radius: 4px;
      padding: 0.45rem 0.8rem 0.45rem 2.2rem;
      font-size: 0.85rem;
      width: 220px;
      transition: width 0.3s ease, border-color 0.3s;
    }
    .search-input:focus {
      outline: none;
      width: 320px;
      border-color: var(--netflix-red);
      background: rgba(0,0,0,0.95);
    }
    .search-icon {
      position: absolute;
      left: 0.75rem;
      color: #bbb;
      font-size: 0.9rem;
      pointer-events: none;
    }

    /* Hero Banner */
    .hero-banner {
      position: relative;
      height: 75vh;
      min-height: 550px;
      background-size: cover;
      background-position: center top;
      display: flex;
      align-items: center;
      padding: 0 4rem;
    }
    .hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(77deg, rgba(0,0,0,0.88) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0.88) 100%),
                  linear-gradient(180deg, rgba(20,20,20,0) 60%, rgba(20,20,20,1) 100%);
    }
    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 680px;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: rgba(229, 9, 20, 0.9);
      color: white;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      padding: 0.35rem 0.8rem;
      border-radius: 3px;
      letter-spacing: 1px;
      margin-bottom: 1rem;
    }
    .hero-title {
      font-size: 3.8rem;
      font-weight: 800;
      margin-bottom: 1rem;
      line-height: 1.05;
      text-shadow: 0 2px 10px rgba(0,0,0,0.8);
    }
    .hero-meta {
      display: flex;
      align-items: center;
      gap: 1rem;
      font-size: 0.9rem;
      color: #ddd;
      margin-bottom: 1rem;
    }
    .hero-match {
      color: #46d369;
      font-weight: 700;
    }
    .hero-desc {
      font-size: 1.05rem;
      color: #cccccc;
      line-height: 1.5;
      margin-bottom: 1.8rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-shadow: 0 1px 4px rgba(0,0,0,0.8);
    }
    .btn-hero-play {
      background-color: #ffffff;
      color: #000000;
      font-weight: 700;
      padding: 0.75rem 2.2rem;
      border-radius: 4px;
      border: none;
      font-size: 1.1rem;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
      transition: background-color 0.2s, transform 0.2s;
    }
    .btn-hero-play:hover {
      background-color: rgba(255,255,255,0.75);
      color: #000000;
      transform: scale(1.03);
    }

    /* Content Rows */
    .content-container {
      position: relative;
      z-index: 10;
      margin-top: -3rem;
      padding-bottom: 5rem;
    }
    .row-section {
      margin-bottom: 2.8rem;
      padding: 0 3rem;
    }
    .row-title {
      font-size: 1.45rem;
      font-weight: 700;
      color: #e5e5e5;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .row-title i {
      color: var(--netflix-red);
    }
    .movie-scroll-row {
      display: flex;
      gap: 1.2rem;
      overflow-x: auto;
      scroll-behavior: smooth;
      padding: 0.5rem 0 1.5rem 0;
    }
    .movie-scroll-row::-webkit-scrollbar {
      height: 6px;
    }
    .movie-scroll-row::-webkit-scrollbar-track {
      background: rgba(255,255,255,0.05);
    }
    .movie-scroll-row::-webkit-scrollbar-thumb {
      background: rgba(229, 9, 20, 0.6);
      border-radius: 4px;
    }

    /* Movie Cards */
    .movie-card {
      position: relative;
      flex: 0 0 220px;
      height: 330px;
      border-radius: 6px;
      overflow: hidden;
      cursor: pointer;
      background-color: var(--netflix-card-bg);
      box-shadow: 0 4px 15px rgba(0,0,0,0.5);
      transition: transform 0.3s cubic-bezier(0.25, 1, 0.5, 1), box-shadow 0.3s;
      text-decoration: none;
      display: block;
      color: inherit;
    }
    .movie-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }
    .movie-card:hover {
      transform: scale(1.08);
      z-index: 100;
      box-shadow: 0 12px 30px rgba(0,0,0,0.9);
      color: inherit;
    }
    .card-title-footer {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.9) 30%, #141414 100%);
      padding: 0.6rem 0.8rem 0.5rem 0.8rem;
      z-index: 2;
      transition: opacity 0.2s;
    }
    .movie-card:hover .card-title-footer {
      opacity: 0;
    }
    .card-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.4) 50%, rgba(0,0,0,0) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 1.2rem;
    }
    .movie-card:hover .card-overlay {
      opacity: 1;
    }
    .card-title {
      font-size: 1.05rem;
      font-weight: 700;
      color: white;
      margin-bottom: 0.3rem;
      line-height: 1.2;
    }
    .card-meta {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.78rem;
      color: #ccc;
      margin-bottom: 0.5rem;
    }
    .card-match {
      color: #46d369;
      font-weight: 700;
    }
    .card-btn-play {
      background: white;
      color: black;
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }

    /* Dedicated Movie Detail Page */
    .movie-detail-hero {
      position: relative;
      min-height: 60vh;
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: flex-end;
      padding: 6rem 4rem 3rem 4rem;
    }
    .movie-detail-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(0deg, #141414 0%, rgba(20,20,20,0.6) 60%, rgba(0,0,0,0.85) 100%);
    }
    .movie-detail-container {
      position: relative;
      z-index: 5;
      max-width: 1200px;
      margin: 0 auto;
    }

    /* Feelings & Comments Form */
    .feeling-badge-opt {
      cursor: pointer;
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 20px;
      padding: 0.4rem 0.9rem;
      font-size: 0.82rem;
      background: rgba(255,255,255,0.05);
      color: #ddd;
      transition: all 0.2s ease;
      user-select: none;
    }
    .feeling-badge-opt:hover, .feeling-badge-opt.active {
      background: var(--netflix-red);
      border-color: var(--netflix-red);
      color: white;
      font-weight: 600;
    }
    .comment-card {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 6px;
      padding: 1.2rem;
      margin-bottom: 1rem;
    }
    .comment-author-avatar {
      width: 36px;
      height: 36px;
      border-radius: 4px;
      object-fit: cover;
    }
    .comment-feeling-tag {
      background: rgba(229, 9, 20, 0.2);
      color: var(--netflix-red);
      border: 1px solid rgba(229, 9, 20, 0.4);
      font-size: 0.75rem;
      padding: 0.2rem 0.6rem;
      border-radius: 12px;
      font-weight: 600;
    }

    /* Auth Modal Tabs Styling */
    #authModal .nav-tabs .nav-link.active {
      background: transparent;
      color: #ffffff !important;
      border-bottom: 3px solid var(--netflix-red) !important;
    }
    #authModal .nav-tabs .nav-link {
      background: transparent;
      color: #8c8c8c !important;
      border-bottom: 3px solid transparent;
      transition: all 0.2s;
    }
    #authModal .nav-tabs .nav-link:hover {
      color: #ffffff !important;
    }

    /* Footer */
    footer.netflix-footer {
      background-color: #101010;
      color: #757575;
      padding: 3rem 4rem 1.5rem 4rem;
      font-size: 0.82rem;
      border-top: 1px solid rgba(255,255,255,0.05);
    }
    footer.netflix-footer a {
      color: #757575;
      text-decoration: none;
    }
    footer.netflix-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
  <nav class="netflix-navbar scrolled" id="mainNavbar">
    <div class="d-flex align-items-center gap-4">
      <a href="index.php" class="netflix-brand">NETFLIX</a>
      <ul class="netflix-nav-links d-none d-md-flex">
        <li><a href="index.php" class="<?php echo !$currentMovie ? 'active' : ''; ?>">Home</a></li>
        <li><a href="index.php#popularRow">Popular</a></li>
        <li><a href="index.php#trendingRow">Trending</a></li>
        <li><a href="index.php#actionRow">Action</a></li>
        <li><a href="index.php#scifiRow">Sci-Fi</a></li>
        <li><a href="index.php#crimeRow">Crime</a></li>
        <li><a href="index.php#showsRow">TV Shows</a></li>
      </ul>
    </div>

    <div class="d-flex align-items-center gap-3">
      <?php if (!$currentMovie): ?>
      <div class="search-box-wrap">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="searchInput" class="search-input" placeholder="Search titles, genres..." onkeyup="handleSearch()">
      </div>
      <?php endif; ?>

      <?php if ($currentUser): ?>
        <div class="dropdown">
          <button class="btn btn-dark btn-sm dropdown-toggle d-flex align-items-center gap-2 border-0 bg-transparent" type="button" data-bs-toggle="dropdown">
            <img src="<?php echo $currentUser['avatar']; ?>" style="width: 32px; height: 32px; border-radius: 4px;">
            <!-- CTF LAB: Raw username rendered for Stored XSS -->
            <span class="d-none d-sm-inline fw-semibold"><?php echo $currentUser['username']; ?></span>
          </button>
          <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
            <li><a class="dropdown-item text-danger" href="index.php?action=logout"><i class="bi bi-box-arrow-right me-2"></i>Sign out of Netflix</a></li>
          </ul>
        </div>
      <?php else: ?>
        <button class="btn btn-danger btn-sm px-3 fw-bold" style="background-color: var(--netflix-red); border: none;" data-bs-toggle="modal" data-bs-target="#authModal">Sign In</button>
      <?php endif; ?>
    </div>
  </nav>

  <?php if ($currentMovie): ?>
    <!-- ============================================================ -->
    <!-- DEDICATED MOVIE DETAIL VIEW (e.g. /netflix/101 or index.php?id=101) -->
    <!-- ============================================================ -->
    <header class="movie-detail-hero" style="background-image: url('<?php echo $currentMovie['backdrop']; ?>');">
      <div class="movie-detail-overlay"></div>
      <div class="movie-detail-container w-100">
        <a href="index.php" class="btn btn-outline-light btn-sm mb-4 fw-semibold"><i class="bi bi-arrow-left me-1"></i> Back to Browse</a>
        <h1 class="display-3 fw-extrabold mb-2" style="font-weight: 800;"><?php echo $currentMovie['title']; ?></h1>
        <div class="d-flex align-items-center gap-3 text-muted mb-3">
          <span class="text-success fw-bold"><?php echo round($currentMovie['rating'] * 10); ?>% Match</span>
          <span><?php echo $currentMovie['year']; ?></span>
          <span class="border border-secondary px-1 rounded small text-light">4K ULTRA HD</span>
          <span><?php echo $currentMovie['duration']; ?></span>
          <span class="badge bg-secondary"><?php echo $currentMovie['genre']; ?></span>
          <span class="text-warning fw-bold"><i class="bi bi-star-fill me-1"></i><?php echo $currentMovie['rating']; ?> / 10</span>
        </div>
      </div>
    </header>

    <main class="container py-4" style="max-width: 1200px;">
      <div class="row g-5">
        <!-- Left: Overview & Reaction Form -->
        <div class="col-lg-7">
          <h4 class="fw-bold mb-2">Overview</h4>
          <p class="text-secondary leading-relaxed fs-5 mb-4">
            <?php echo $currentMovie['overview']; ?>
          </p>

          <hr class="border-secondary border-opacity-25 my-5">

          <!-- Feelings & Comment Form -->
          <h4 class="fw-bold mb-3 d-flex align-items-center gap-2 text-white">
            <i class="bi bi-chat-heart-fill text-danger"></i> How are you feeling about this movie?
          </h4>

          <?php if ($commentError): ?>
            <div class="alert alert-danger p-2 small mb-3"><?php echo $commentError; ?></div>
          <?php endif; ?>
          <?php if ($commentSuccess): ?>
            <div class="alert alert-success p-2 small mb-3"><?php echo $commentSuccess; ?></div>
          <?php endif; ?>

          <?php if ($currentUser): ?>
            <!-- ALLOWED COMMENT FORM FOR LOGGED IN USERS -->
            <form method="POST" action="">
              <input type="hidden" name="action" value="add_comment">
              <input type="hidden" name="movie_id" value="<?php echo $currentMovie['id']; ?>">
              <input type="hidden" name="movie_title" value="<?php echo $currentMovie['title']; ?>">
              <input type="hidden" name="feeling" id="selectedFeeling" value="Mind Blown 🤯">

              <div class="mb-4">
                <label class="form-label text-muted small fw-bold d-block">Select Your Mood / Feeling</label>
                <div class="d-flex flex-wrap gap-2 mb-2">
                  <span class="feeling-badge-opt active" onclick="selectFeeling(this, 'Mind Blown 🤯')">Mind Blown 🤯</span>
                  <span class="feeling-badge-opt" onclick="selectFeeling(this, 'Hypnotized 🌀')">Hypnotized 🌀</span>
                  <span class="feeling-badge-opt" onclick="selectFeeling(this, 'Emotional 😭')">Emotional 😭</span>
                  <span class="feeling-badge-opt" onclick="selectFeeling(this, 'Pumped Up ⚡')">Pumped Up ⚡</span>
                  <span class="feeling-badge-opt" onclick="selectFeeling(this, 'Pure Gold 🍿')">Pure Gold 🍿</span>
                  <span class="feeling-badge-opt" onclick="selectFeeling(this, 'Chilling 🥶')">Chilling 🥶</span>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Rating (1 - 5 Stars)</label>
                <select class="form-select bg-dark text-white border-secondary" name="rating">
                  <option value="5">⭐⭐⭐⭐⭐ (5/5) Incredible</option>
                  <option value="4">⭐⭐⭐⭐ (4/5) Great Movie</option>
                  <option value="3">⭐⭐⭐ (3/5) Average Watch</option>
                  <option value="2">⭐⭐ (2/5) Disappointing</option>
                  <option value="1">⭐ (1/5) Terrible</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label text-muted small fw-bold">Share Your Thoughts &amp; Reactions</label>
                <textarea class="form-control bg-dark text-white border-secondary" name="comment" rows="4" placeholder="Tell the community how this movie made you feel..." required></textarea>
              </div>

              <button type="submit" class="btn btn-danger w-100 fw-bold py-2" style="background-color: var(--netflix-red); border: none;">
                <i class="bi bi-send-fill me-1"></i> Post Reaction &amp; Feeling
              </button>
            </form>
          <?php else: ?>
            <!-- REQUIRE LOGIN MESSAGE FOR GUESTS -->
            <div class="alert alert-dark border-danger border-opacity-50 text-center p-4 rounded-3">
              <i class="bi bi-lock-fill fs-2 text-danger mb-2 d-block"></i>
              <h5 class="fw-bold text-white mb-2">Sign in to leave a reaction</h5>
              <p class="text-muted small mb-3">You must be logged in to share your thoughts and reactions about this movie.</p>
              <button class="btn btn-danger fw-bold px-4 py-2" style="background-color: var(--netflix-red);" data-bs-toggle="modal" data-bs-target="#authModal">
                Sign In / Register Now
              </button>
            </div>
          <?php endif; ?>

        </div>

        <!-- Right: Viewer Reactions Stream -->
        <div class="col-lg-5">
          <h4 class="fw-bold mb-4 d-flex align-items-center justify-content-between">
            <span>Viewer Reactions</span>
            <span class="badge bg-danger fs-6"><?php echo count($movieComments); ?></span>
          </h4>

          <div id="commentsStreamContainer">
            <?php if (empty($movieComments)): ?>
              <p class="text-muted text-center py-4">No reactions yet. Be the first to share how you felt!</p>
            <?php else: ?>
              <?php foreach ($movieComments as $c): ?>
                <div class="comment-card">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                      <img src="<?php echo $c['avatar'] ?: 'https://upload.wikimedia.org/wikipedia/commons/0/0b/Netflix-avatar.png'; ?>" class="comment-author-avatar">
                      <!-- CTF LAB VULNERABILITY: Raw username rendered without htmlspecialchars for Stored XSS -->
                      <span class="fw-bold text-white small">@<?php echo $c['username']; ?></span>
                    </div>
                    <!-- CTF LAB VULNERABILITY: Raw feeling rendered without htmlspecialchars -->
                    <span class="comment-feeling-tag"><?php echo $c['feeling']; ?></span>
                  </div>
                  <div class="text-warning small mb-2">
                    <?php echo str_repeat('★', intval($c['rating'])) . str_repeat('☆', 5 - intval($c['rating'])); ?>
                  </div>
                  <!-- CTF LAB VULNERABILITY: Raw comment rendered without htmlspecialchars for Stored XSS -->
                  <div class="text-light small leading-snug">
                    <?php echo $c['comment']; ?>
                  </div>
                  <div class="text-muted text-end" style="font-size: 0.68rem; margin-top: 6px;">
                    <?php echo date('M j, Y - g:i A', strtotime($c['created_at'])); ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </main>

  <?php else: ?>

    <!-- ============================================================ -->
    <!-- NETFLIX HOME BROWSE CATALOG VIEW -->
    <!-- ============================================================ -->

    <!-- Hero Featured Movie Banner -->
    <header class="hero-banner" id="heroBanner" style="background-image: url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=1600&auto=format&fit=crop&q=80');">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <div class="hero-badge"><i class="bi bi-fire"></i> #1 IN MOVIES TODAY</div>
        <h1 class="hero-title" id="heroTitle">INCEPTION</h1>
        <div class="hero-meta">
          <span class="hero-match">98% Match</span>
          <span>2010</span>
          <span class="border px-1 border-secondary rounded small">HDR</span>
          <span>2h 28m</span>
          <span class="badge bg-secondary">Sci-Fi / Action</span>
        </div>
        <p class="hero-desc" id="heroDesc">
          A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O., but his tragic past may doom the project.
        </p>
        <div>
          <a href="101" class="btn-hero-play">
            <i class="bi bi-play-fill fs-4"></i> Watch &amp; Review
          </a>
        </div>
      </div>
    </header>

    <!-- Movie Content Rows -->
    <main class="content-container">
      
      <!-- Row 1: Popular Movies -->
      <section class="row-section" id="popularRow">
        <h2 class="row-title"><i class="bi bi-fire"></i> Popular on Netflix</h2>
        <div class="movie-scroll-row" id="popularRowContainer"></div>
      </section>

      <!-- Row 2: Trending Now -->
      <section class="row-section" id="trendingRow">
        <h2 class="row-title"><i class="bi bi-graph-up-arrow"></i> Trending Now</h2>
        <div class="movie-scroll-row" id="trendingRowContainer"></div>
      </section>

      <!-- Row 3: Action & Thrillers -->
      <section class="row-section" id="actionRow">
        <h2 class="row-title"><i class="bi bi-lightning-charge-fill"></i> Action &amp; Blockbusters</h2>
        <div class="movie-scroll-row" id="actionRowContainer"></div>
      </section>

      <!-- Row 4: Sci-Fi & Cyberpunk -->
      <section class="row-section" id="scifiRow">
        <h2 class="row-title"><i class="bi bi-cpu-fill"></i> Sci-Fi &amp; Cyberpunk Masterpieces</h2>
        <div class="movie-scroll-row" id="scifiRowContainer"></div>
      </section>

      <!-- Row 5: Crime & Dark Thrillers -->
      <section class="row-section" id="crimeRow">
        <h2 class="row-title"><i class="bi bi-mask"></i> Crime &amp; Dark Thrillers</h2>
        <div class="movie-scroll-row" id="crimeRowContainer"></div>
      </section>

      <!-- Row 6: TV Shows & Series -->
      <section class="row-section" id="showsRow">
        <h2 class="row-title"><i class="bi bi-tv-fill"></i> Binge-Worthy TV Shows &amp; Series</h2>
        <div class="movie-scroll-row" id="showsRowContainer"></div>
      </section>

    </main>

  <?php endif; ?>

  <!-- Auth Modal (Sign In / Register) -->
  <div class="modal fade" id="authModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content bg-dark border-secondary">
        
        <div class="modal-header border-secondary pb-0">
          <ul class="nav nav-tabs border-0 w-100" id="authTabs" role="tablist">
            <li class="nav-item flex-fill text-center" role="presentation">
              <button class="nav-link active fw-bold w-100 border-0 pb-3" id="login-tab" data-bs-toggle="tab" data-bs-target="#loginTabContent" type="button" role="tab">
                Sign In
              </button>
            </li>
            <li class="nav-item flex-fill text-center" role="presentation">
              <button class="nav-link fw-bold w-100 border-0 pb-3" id="register-tab" data-bs-toggle="tab" data-bs-target="#registerTabContent" type="button" role="tab">
                Sign Up
              </button>
            </li>
          </ul>
          <button type="button" class="btn-close btn-close-white align-self-start mt-2" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body p-4">
          <?php if ($authError): ?>
            <div class="alert alert-danger p-2 small"><?php echo $authError; ?></div>
          <?php endif; ?>

          <div class="tab-content" id="authTabContent">
            
            <!-- Tab 1: Sign In -->
            <div class="tab-pane fade show active" id="loginTabContent" role="tabpanel">
              <form method="POST">
                <input type="hidden" name="auth_type" value="login">
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Email or Username</label>
                  <input type="text" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" name="username" placeholder="Enter username or email" required>
                </div>
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Password</label>
                  <input type="password" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold py-2 mb-3" style="background-color: var(--netflix-red); border: none;">
                  Sign In
                </button>
                <div class="d-flex justify-content-between align-items-center text-muted small mb-3">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" checked>
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                  </div>
                  <a href="#" class="text-muted text-decoration-none">Need help?</a>
                </div>
                <div class="text-center text-muted small border-top border-secondary border-opacity-25 pt-3">
                  New to Netflix? <a href="#" class="text-white fw-bold text-decoration-none" onclick="document.getElementById('register-tab').click(); return false;">Sign up now.</a>
                </div>
                <div class="text-center text-muted small mt-2" style="font-size: 0.75rem;">
                  Demo Admin: <strong>admin</strong> / <strong>admin123</strong>
                </div>
              </form>
            </div>

            <!-- Tab 2: Sign Up / Create Account -->
            <div class="tab-pane fade" id="registerTabContent" role="tabpanel">
              <form method="POST">
                <input type="hidden" name="auth_type" value="register">
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Full Name</label>
                  <input type="text" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" name="full_name" placeholder="John Doe" required>
                </div>
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Username</label>
                  <input type="text" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" name="username" placeholder="Choose a username" required>
                </div>
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Email Address</label>
                  <input type="email" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" name="email" placeholder="name@example.com" required>
                </div>
                <div class="mb-3">
                  <label class="form-label text-muted small fw-bold">Password</label>
                  <input type="password" class="form-control bg-secondary bg-opacity-25 border-secondary text-white" name="password" placeholder="Create a password" required>
                </div>
                <button type="submit" class="btn btn-danger w-100 fw-bold py-2 mb-3" style="background-color: var(--netflix-red); border: none;">
                  Create Account &amp; Sign Up
                </button>
                <div class="text-center text-muted small border-top border-secondary border-opacity-25 pt-3">
                  Already have an account? <a href="#" class="text-white fw-bold text-decoration-none" onclick="document.getElementById('login-tab').click(); return false;">Sign in now.</a>
                </div>
              </form>
            </div>

          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="netflix-footer">
    <div class="row g-4 mb-4">
      <div class="col-6 col-md-3">
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="#">Audio Description</a></li>
          <li><a href="#">Investor Relations</a></li>
          <li><a href="#">Legal Notices</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="#">Help Center</a></li>
          <li><a href="#">Jobs</a></li>
          <li><a href="#">Cookie Preferences</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="#">Gift Cards</a></li>
          <li><a href="#">Terms of Use</a></li>
          <li><a href="#">Corporate Information</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <ul class="list-unstyled d-flex flex-column gap-2">
          <li><a href="#">Media Center</a></li>
          <li><a href="#">Privacy</a></li>
          <li><a href="#">Contact Us</a></li>
        </ul>
      </div>
    </div>
    <p class="mb-0">&copy; 2026 Netflix Clone. Built for CTF &amp; Security Lab Testing.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    <?php if (!$currentMovie): ?>
    const catalogData = {
      "popular": [101, 102, 103, 104, 105, 106, 107, 108],
      "trending": [109, 110, 111, 112, 113, 114, 115, 116],
      "action": [117, 118, 119, 120, 121, 122, 123, 124],
      "scifi": [125, 126, 127, 128, 129, 130, 131, 132],
      "crime": [133, 134, 135, 136, 137, 138, 139, 140],
      "shows": [141, 142, 143, 144, 145, 146, 147, 148]
    };

    const movieCatalogMap = <?php echo json_encode($movie_catalog_map); ?>;

    function createMovieCard(m) {
      const posterUrl = m.poster;
      const year = m.year || '2024';
      const rating = parseFloat(m.rating || 8.0).toFixed(1);

      return `
        <a href="${m.id}" class="movie-card">
          <img src="${posterUrl}" alt="${m.title}" loading="lazy">
          <div class="card-title-footer">
            <div class="fw-bold text-white text-truncate small">${m.title}</div>
            <div class="d-flex align-items-center justify-content-between text-muted" style="font-size: 0.72rem;">
              <span>${year}</span>
              <span class="text-warning fw-bold"><i class="bi bi-star-fill me-1"></i>${rating}</span>
            </div>
          </div>
          <div class="card-overlay">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <button class="card-btn-play"><i class="bi bi-play-fill"></i></button>
              <span class="card-match">${Math.round(rating * 10)}% Match</span>
            </div>
            <div class="card-title">${m.title}</div>
            <div class="card-meta">
              <span>${year}</span>
              <span class="border border-secondary px-1 rounded">HD</span>
              <span><i class="bi bi-star-fill text-warning me-1"></i>${rating}</span>
            </div>
          </div>
        </a>
      `;
    }

    function renderCategoryRows() {
      ['popular', 'trending', 'action', 'scifi', 'crime', 'shows'].forEach(cat => {
        const container = document.getElementById(cat + 'RowContainer');
        if (container) {
          container.innerHTML = catalogData[cat].map(id => {
            const m = movieCatalogMap[id];
            return m ? createMovieCard(m) : '';
          }).join('');
        }
      });
    }

    // Search filter
    function handleSearch() {
      const q = document.getElementById('searchInput').value.toLowerCase();
      const cards = document.querySelectorAll('.movie-card');
      cards.forEach(c => {
        const title = c.innerText.toLowerCase();
        if (!q || title.includes(q)) {
          c.style.display = 'block';
        } else {
          c.style.display = 'none';
        }
      });
    }

    document.addEventListener('DOMContentLoaded', renderCategoryRows);
    <?php endif; ?>

    function selectFeeling(el, feeling) {
      document.querySelectorAll('.feeling-badge-opt').forEach(b => b.classList.remove('active'));
      el.classList.add('active');
      const hiddenInput = document.getElementById('selectedFeeling');
      if (hiddenInput) hiddenInput.value = feeling;
    }
  </script>
</body>
</html>
