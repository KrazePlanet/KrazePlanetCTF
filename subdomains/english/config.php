<?php
class Database
{
    private $conn;

    public function __construct()
    {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "english";

        // 1. Initial Connection to Ensure Database Exists
        $init = @new mysqli($servername, $username, $password);
        if ($init && !$init->connect_error) {
            @$init->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
            @$init->close();
        }

        $this->conn = new mysqli($servername, $username, $password, $dbname);

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }

        $this->autoProvision();
    }

    private function autoProvision()
    {
        $chk = @$this->conn->query("SHOW TABLES LIKE 'users'");
        if (!$chk || $chk->num_rows == 0) {
            $this->conn->query("
                CREATE TABLE IF NOT EXISTS users (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    first_name VARCHAR(30) NOT NULL,
                    last_name VARCHAR(30) NOT NULL,
                    email VARCHAR(100) NOT NULL UNIQUE,
                    username VARCHAR(30) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    profile_picture VARCHAR(255) DEFAULT 'default.png',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $this->conn->query("
                CREATE TABLE IF NOT EXISTS words (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    word VARCHAR(150) NOT NULL,
                    translation VARCHAR(150),
                    definition VARCHAR(255),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $this->conn->query("
                CREATE TABLE IF NOT EXISTS sentences (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    word_id INT NOT NULL,
                    sentence VARCHAR(255) NOT NULL,
                    translation VARCHAR(255) NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $this->conn->query("
                CREATE TABLE IF NOT EXISTS texts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    title VARCHAR(150) NOT NULL,
                    content VARCHAR(2000) NOT NULL,
                    translation VARCHAR(2000) NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $this->conn->query("
                CREATE TABLE IF NOT EXISTS liked_words (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    word_id INT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (word_id) REFERENCES words(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $this->conn->query("
                CREATE TABLE IF NOT EXISTS liked_sentences (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    sentence_id INT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (sentence_id) REFERENCES sentences(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $this->conn->query("
                CREATE TABLE IF NOT EXISTS liked_texts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    text_id INT NOT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (text_id) REFERENCES texts(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            $iqbolshoh_pwd = $this->hashPassword('IQBOLSHOH');
            $admin_pwd = $this->hashPassword('admin');

            $this->conn->query("INSERT INTO users (id, first_name, last_name, email, username, password) VALUES
                (1, 'Iqbolshoh', 'Ilhomjonov', 'iilhomjonov777@gmail.com', 'iqbolshoh', '{$iqbolshoh_pwd}'),
                (2, 'Admin', 'User', 'admin@iqbolshoh.uz', 'admin', '{$admin_pwd}')
                ON DUPLICATE KEY UPDATE password=VALUES(password);");

            // Seed sample vocabulary
            $this->conn->query("INSERT INTO `words` (`id`, `user_id`, `word`, `translation`, `definition`) VALUES
                (1, 2, 'apple', 'olma', 'A fruit that is usually round, red, green, or yellow and has a sweet taste.'),
                (2, 2, 'book', 'kitob', 'A set of written, printed, or blank pages fastened together between a cover.'),
                (3, 2, 'computer', 'kompyuter', 'An electronic device for storing and processing data.'),
                (4, 2, 'education', 'talim', 'The process of receiving or giving systematic instruction.'),
                (5, 2, 'knowledge', 'bilim', 'Facts, information, and skills acquired through experience or education.')
                ON DUPLICATE KEY UPDATE word=VALUES(word);");

            // Seed sample sentences
            $this->conn->query("INSERT INTO `sentences` (`id`, `user_id`, `word_id`, `sentence`, `translation`) VALUES
                (1, 2, 1, 'She eats a fresh red apple every morning.', 'U har kuni ertalab yangi qizil olma yeydi.'),
                (2, 2, 2, 'Reading a good book expands your imagination.', 'Yaxshi kitob o`qish tasavvuringizni kengaytiradi.'),
                (3, 2, 3, 'The computer is a powerful tool for modern learning.', 'Kompyuter zamonaviy ta`lim uchun kuchli vositadir.')
                ON DUPLICATE KEY UPDATE sentence=VALUES(sentence);");

            // Seed sample texts
            $this->conn->query("INSERT INTO `texts` (`id`, `user_id`, `title`, `content`, `translation`) VALUES
                (1, 2, 'The Importance of Language', 'Learning a new language opens up doors to understanding new cultures, forming global friendships, and discovering unique opportunities.', 'Yangi tilni o`rganish yangi madaniyatlarni tushunish, global do`stliklarni o`rnatish va noyob imkoniyatlarni kashf etish uchun eshiklarni ochadi.')
                ON DUPLICATE KEY UPDATE title=VALUES(title);");
        }
    }

    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    function validate($value)
    {
        $value = str_replace(['‘', '’', '“', '”', '"', '„', '‟', '‹', '›', '«', '»', '`', '´', '❛', '❜', '❝', '❞', '〝', '〞'], "'", $value);
        $value = trim($value);
        $value = stripslashes($value);
        return $value;
    }

    public function executeQuery($sql, $params = [], $types = "")
    {
        $stmt = $this->conn->prepare($sql);

        if ($params) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }

        return $stmt;
    }

    public function select($table, $columns = "*", $condition = "", $params = [], $types = "")
    {
        $sql = "SELECT $columns FROM $table $condition";
        $stmt = $this->executeQuery($sql, $params, $types);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function insert($table, $data)
    {
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($keys) VALUES ($placeholders)";
        $types = str_repeat('s', count($data));
        $this->executeQuery($sql, array_values($data), $types);
        return $this->conn->insert_id;
    }

    public function update($table, $data, $condition = "", $params = [], $types = "")
    {
        $set = '';
        foreach ($data as $key => $value) {
            $set .= "$key = ?, ";
        }
        $set = rtrim($set, ', ');

        if ($condition) {
            $condition = "WHERE " . $condition;
        } else {
            $condition = "";
        }

        $sql = "UPDATE $table SET $set $condition";
        $types = str_repeat('s', count($data)) . $types;
        $this->executeQuery($sql, array_merge(array_values($data), $params), $types);
    }

    public function delete($table, $condition = "", $params = [], $types = "")
    {
        if ($condition) {
            $condition = "WHERE " . $condition;
        } else {
            $condition = "";
        }

        $sql = "DELETE FROM $table $condition";
        $this->executeQuery($sql, $params, $types);
    }

    public function hashPassword($password)
    {
        return hash_hmac('sha256', $password, 'iqbolshoh');
    }

    public function emailExists($email)
    {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    public function usernameExists($username)
    {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    public function registerUser($fullname, $email, $username, $password)
    {
        $sql = "INSERT INTO users (fullname, email, username, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $fullname, $email, $username, $password);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    public function getUserIdByUsername($username)
    {
        $result = $this->select('users', 'id', 'WHERE username = ?', [$username], 's');
        return $result[0]['id'];
    }

    public function find($table, $id)
    {
        $id = $this->validate($id);
        $condition = "WHERE id = ?";
        $params = [$id];
        return $this->select($table, "*", $condition, $params, 'i');
    }

    public function search($table, $columns = "*", $condition = "", $params = [], $types = "")
    {
        return $this->select($table, $columns, $condition, $params, $types);
    }

    public function fetchAll($table, $columns = "*")
    {
        $sql = "SELECT $columns FROM $table";
        $stmt = $this->executeQuery($sql);
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getLastInsertId()
    {
        return $this->conn->insert_id;
    }

    public function removeLike($userId, $wordId)
    {
        $stmt = $this->conn->prepare("DELETE FROM liked_words WHERE user_id = ? AND word_id = ?");
        $stmt->execute([$userId, $wordId]);
    }

    public function addLike($userId, $wordId)
    {
        $stmt = $this->conn->prepare("INSERT INTO liked_words (user_id, word_id) VALUES (?, ?)");
        $stmt->execute([$userId, $wordId]);
    }

    public function checkLiked($userId, $wordId)
    {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS count FROM liked_words WHERE user_id = ? AND word_id = ?");
        $stmt->bind_param("ii", $userId, $wordId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    public function count($table, $condition = '', $params = [], $types = '')
    {
        $sql = "SELECT COUNT(*) AS count FROM $table";
        if (!empty($condition)) {
            $sql .= " $condition";
        }

        $stmt = $this->conn->prepare($sql);

        if (!empty($params)) {
            $this->bindParams($stmt, $params, $types);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row['count'];
    }

    private function bindParams($stmt, $params, $types)
    {
        $typeArray = str_split($types);
        foreach ($params as $key => $param) {
            $stmt->bind_param($typeArray[$key], $param);
        }
    }

    public function addText($userId, $textTitle, $textContent, $translation)
    {
        $textTitle = $this->validate($textTitle);
        $textContent = $this->validate($textContent);
        $translation = $this->validate($translation);

        $sql = "INSERT INTO texts (user_id, title, content, translation) VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if ($stmt === false) {
            die("SQL prepare error: " . $this->conn->error);
        }

        $stmt->bind_param("isss", $userId, $textTitle, $textContent, $translation);

        if ($stmt->execute()) {
            $insertedId = $this->conn->insert_id;
            $stmt->close();
            return $insertedId;
        } else {
            die("SQL execute error: " . $stmt->error);
        }
    }
}
?>
