<?php
// Load .env file from project root
(function () {
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile))
        return;
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#'))
            continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
})();

class Database
{
    private $db_host = "localhost";
    private $db_user = "root";
    private $db_password = "";
    private $db_name = "triptrip";
    protected $conn;

    protected function connect()
    {
        try {
            // 1. Ensure Database Exists
            $hosts = ['krazeplanet', '127.0.0.1', 'localhost', '172.19.0.1', 'host.docker.internal'];
            foreach ($hosts as $h) {
                $init = @new mysqli($h, $this->db_user, $this->db_password);
                if ($init && !$init->connect_error) {
                    @$init->query("CREATE DATABASE IF NOT EXISTS `" . $this->db_name . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");
                    @$init->close();
                    $this->db_host = $h;
                    break;
                }
            }

            // 2. Connect to triptrip
            $this->conn = @new mysqli($this->db_host, $this->db_user, $this->db_password, $this->db_name);
            
            // 3. Auto-provision tables and seed data if empty
            $chk = @$this->conn->query("SHOW TABLES LIKE 'users'");
            if (!$chk || $chk->num_rows == 0) {
                // Create USERS TABLE
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT(10) NOT NULL AUTO_INCREMENT,
                        `username` VARCHAR(50) DEFAULT NULL UNIQUE,
                        `user_pass` VARCHAR(255) DEFAULT NULL,
                        `email` VARCHAR(100) DEFAULT NULL UNIQUE,
                        `date_created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                        `is_admin` INT(10) DEFAULT 0,
                        `phone` VARCHAR(15) DEFAULT NULL,
                        `address` TEXT DEFAULT NULL,
                        `full_name` VARCHAR(255) DEFAULT NULL,
                        `account_status` INT(10) DEFAULT 1,
                        PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");

                // Create PACKAGES TABLE
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS `packages` (
                        `package_id` INT(10) NOT NULL AUTO_INCREMENT,
                        `package_name` VARCHAR(255) DEFAULT NULL,
                        `package_rating` FLOAT DEFAULT NULL,
                        `package_desc` TEXT DEFAULT NULL,
                        `package_start` DATE DEFAULT NULL,
                        `package_end` DATE DEFAULT NULL,
                        `package_price` INT(10) DEFAULT NULL,
                        `package_location` VARCHAR(255) DEFAULT NULL,
                        `is_hotel` INT(10) DEFAULT 0,
                        `is_transport` INT(10) DEFAULT 0,
                        `is_food` INT(10) DEFAULT 0,
                        `is_guide` INT(10) DEFAULT 0,
                        `package_capacity` INT(10) DEFAULT 0,
                        `package_booked` INT(10) UNSIGNED DEFAULT 0,
                        `map_loc` TEXT DEFAULT NULL,
                        `master_image` TEXT DEFAULT NULL,
                        `extra_image_1` TEXT DEFAULT NULL,
                        `extra_image_2` TEXT DEFAULT NULL,
                        PRIMARY KEY (`package_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");

                // Create TRANSACTIONS TABLE
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS `transactions` (
                        `id` INT(10) NOT NULL AUTO_INCREMENT,
                        `trans_id` VARCHAR(255) DEFAULT NULL,
                        `user_id` INT(10) DEFAULT NULL,
                        `package_id` INT(10) DEFAULT NULL,
                        `trans_amount` INT(10) DEFAULT NULL,
                        `trans_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                        `card_no` VARCHAR(255) DEFAULT NULL,
                        `val_id` VARCHAR(255) DEFAULT NULL,
                        `card_type` VARCHAR(255) DEFAULT NULL,
                        PRIMARY KEY (`id`),
                        CONSTRAINT `fk_transactions_user`
                            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                            ON DELETE SET NULL,
                        CONSTRAINT `fk_transactions_package`
                            FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`)
                            ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");

                // Create TESTIMONIALS TABLE
                $this->conn->query("
                    CREATE TABLE IF NOT EXISTS `testimonials` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `message` TEXT,
                        `user_id` INT,
                        `package_id` INT,
                        `rating` FLOAT,
                        `date_created` DATETIME DEFAULT CURRENT_TIMESTAMP,
                        CONSTRAINT `fk_testimonials_user`
                            FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
                            ON DELETE SET NULL,
                        CONSTRAINT `fk_testimonials_package`
                            FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`)
                            ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");

                // Seed Default Admin (admin / admin@triptrip.com / password: admin -> sha1: d033e22ae348aeb5660fc2140aec35850c4da997)
                $admin_pass = sha1("admin");
                $this->conn->query("INSERT INTO `users` (`id`, `username`, `user_pass`, `email`, `is_admin`, `phone`, `address`, `full_name`, `account_status`) VALUES
                    (1, 'admin', '{$admin_pass}', 'admin@triptrip.com', 1, '+8801700000000', 'Dhaka, Bangladesh', 'System Administrator', 1),
                    (2, 'traveler', '{$admin_pass}', 'traveler@gmail.com', 0, '+8801800000000', 'Chittagong, Bangladesh', 'Traveler Explorer', 1);");

                // Seed Sample Tour Packages
                $this->conn->query("INSERT INTO `packages` (`package_id`, `package_name`, `package_rating`, `package_desc`, `package_start`, `package_end`, `package_price`, `package_location`, `is_hotel`, `is_transport`, `is_food`, `is_guide`, `package_capacity`, `package_booked`, `map_loc`, `master_image`, `extra_image_1`, `extra_image_2`) VALUES
                    (1, 'Cox\'s Bazar Beach & Marine Drive Getaway', 4.8, 'Experience the longest natural sea beach in the world with luxury beachside resort accommodation and fresh seafood dining.', '2026-09-01', '2026-09-05', 8500, 'Cox\'s Bazar', 1, 1, 1, 1, 30, 8, 'https://maps.google.com', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800', 'https://images.unsplash.com/photo-1519046904884-53103b34b206?w=800', 'https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?w=800'),
                    (2, 'Sajek Valley Cloud Kingdom Adventure', 4.9, 'Touch the clouds from the hills of Sajek Valley with guided 4x4 Chander Gari transport and tribal culinary experiences.', '2026-09-10', '2026-09-13', 6200, 'Rangamati', 1, 1, 1, 1, 20, 14, 'https://maps.google.com', 'https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=800', 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=800', 'https://images.unsplash.com/photo-1470770841072-f978cf4d019e?w=800'),
                    (3, 'Sylhet Sreemangal Lush Tea Garden Retreat', 4.7, 'Wander through fragrant green tea estates, Lawachara rainforest, and crystal clear waters of Jaflong.', '2026-09-15', '2026-09-18', 5500, 'Sylhet', 1, 1, 1, 1, 25, 6, 'https://maps.google.com', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=800', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=800');");

                // Seed Sample Testimonials
                $this->conn->query("INSERT INTO `testimonials` (`message`, `user_id`, `package_id`, `rating`) VALUES
                    ('Unforgettable trip to Sajek! The cloud views and hospitality arranged by triptrip were flawless.', 2, 2, 5.0),
                    ('Cox\'s Bazar sunset was surreal. Highly recommend booking through this portal!', 2, 1, 4.5);");
            }

        } catch (mysqli_sql_exception $e) {
            die(header("HTTP/1.0 503 Service Unavailable Error"));
        }
    }
}

/*
    Known bugs:
    * Package price can be negative
    * Two users purchase a product at the same time not handled.
    * User purchasing same package multiple times not handled.
    * One user can add multiple reviews on same package
    * Users can write reviews before finishing the tour
*/

class Packages extends Database
{
    public function createPackage($package_name, $package_desc, $package_start, $package_end, $package_price, $package_location, $is_hotel, $is_transport, $is_food, $is_guide, $package_capacity, $map_loc, $master_image, $extra_image_1, $extra_image_2)
    {
        $this->connect();
        $package_name = mysqli_real_escape_string($this->conn, $package_name);
        $package_desc = mysqli_real_escape_string($this->conn, $package_desc);
        $package_location = mysqli_real_escape_string($this->conn, $package_location);
        $map_loc = mysqli_real_escape_string($this->conn, $map_loc);
        $master_image = mysqli_real_escape_string($this->conn, $master_image);
        $extra_image_1 = mysqli_real_escape_string($this->conn, $extra_image_1);
        $extra_image_2 = mysqli_real_escape_string($this->conn, $extra_image_2);

        $sql = "INSERT INTO packages (package_name,package_desc,package_start,package_end,package_price,package_location,is_hotel,is_transport,is_food,is_guide,package_capacity,map_loc,master_image,extra_image_1,extra_image_2)
        VALUES
        ('" . $package_name . "', '" . $package_desc . "', '" . $package_start . "', '" . $package_end . "', '" . $package_price . "', '" . $package_location . "', '" . $is_hotel . "', '" . $is_transport . "', '" . $is_food . "', '" . $is_guide . "', '" . $package_capacity . "', '" . $map_loc . "', '" . $master_image . "', '" . $extra_image_1 . "', '" . $extra_image_2 . "')";

        $this->conn->query($sql);

        $this->conn->close();
        return "200";
    }
    public function updatePackage($package_id, $package_name, $package_desc, $package_start, $package_end, $package_price, $package_location, $is_hotel, $is_transport, $is_food, $is_guide, $package_capacity, $map_loc, $master_image, $extra_image_1, $extra_image_2)
    {
        $this->connect();
        $package_name = mysqli_real_escape_string($this->conn, $package_name);
        $package_desc = mysqli_real_escape_string($this->conn, $package_desc);
        $package_location = mysqli_real_escape_string($this->conn, $package_location);
        $map_loc = mysqli_real_escape_string($this->conn, $map_loc);
        $master_image = mysqli_real_escape_string($this->conn, $master_image);
        $extra_image_1 = mysqli_real_escape_string($this->conn, $extra_image_1);
        $extra_image_2 = mysqli_real_escape_string($this->conn, $extra_image_2);

        $sql = "UPDATE packages 
                SET package_name = '$package_name', package_desc = '$package_desc', package_start = '$package_start', package_end = '$package_end',  package_price = $package_price, package_location = '$package_location', is_hotel = $is_hotel, is_transport = $is_transport, is_food = $is_food,is_guide = $is_guide,package_capacity = $package_capacity,map_loc = '$map_loc',master_image = '$master_image',extra_image_1 = '$extra_image_1',extra_image_2 = '$extra_image_2' 
                WHERE package_id = $package_id";

        $this->conn->query($sql);

        $this->conn->close();
        return "200";
    }
    public function getPackages($location, $start = 0, $end = 1000)
    {
        $this->connect();
        $location = mysqli_real_escape_string($this->conn, $location);

        if ($location == "All") {
            $sql = "SELECT * FROM packages ORDER BY package_id DESC LIMIT $start,$end";
        } else {
            $sql = "SELECT * FROM packages WHERE package_location LIKE '%$location%' ORDER BY package_id DESC LIMIT $start,$end";
        }

        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getPackage($id)
    {
        $this->connect();

        $sql = "SELECT * FROM packages WHERE package_id = $id";

        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getPackagesCount()
    {
        $this->connect();
        $sql = "SELECT * FROM packages";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result->num_rows;
    }
    public function deletePackage($id)
    {
        $this->connect();
        $sql = "DELETE FROM packages WHERE package_id = $id";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function updateBookingCount($package_id, $val)
    {
        $this->connect();
        $sql = "UPDATE packages SET package_booked = package_booked + $val WHERE package_id = $package_id";
        $this->conn->query($sql);

        $this->conn->close();
        return "200";
    }
}

class Auth extends Database
{
    public function checkUserName($username)
    {
        $this->connect();
        $username = mysqli_real_escape_string($this->conn, $username);
        $sql = "SELECT username FROM users WHERE username = '" . $username . "'";
        $result = $this->conn->query($sql);
        $this->conn->close();

        if ($result && $result->num_rows > 0) {
            return false;
        }

        return true;
    }
    public function checkEmail($email)
    {
        $this->connect();
        $email = mysqli_real_escape_string($this->conn, $email);
        $sql = "SELECT email FROM users WHERE email = '" . $email . "'";
        $result = $this->conn->query($sql);
        $this->conn->close();

        if ($result && $result->num_rows > 0) {
            return false;
        }

        return true;
    }

    public function createUser($username, $email, $pass)
    {
        $this->connect();
        $username = mysqli_real_escape_string($this->conn, $username);
        $email = mysqli_real_escape_string($this->conn, $email);
        $date_created = date("Y-m-d H:i:s");
        try {
            $sql = "INSERT INTO users(username, email, user_pass, date_created) VALUES ('$username','$email','$pass','$date_created')";
            $this->conn->query($sql);
            $this->conn->close();
            return "200";
        } catch (mysqli_sql_exception) {
            $this->conn->close();
            die(header("HTTP/1.0 500 Internal Server Error"));
        }
    }
    public function checkAccountStatus($email)
    {
        $this->connect();
        $email = mysqli_real_escape_string($this->conn, $email);
        $sql = "SELECT * FROM users WHERE email = '" . $email . "' ";
        $result = $this->conn->query($sql);
        $this->conn->close();

        if ($result && $result->num_rows > 0) {
            $user = mysqli_fetch_assoc($result);
            if ($user['account_status'] == 0) {
                return false;
            }
        }
        return true;
    }
    public function loginUser($email, $pass)
    {
        $this->connect();
        $email = mysqli_real_escape_string($this->conn, $email);
        $sql = "SELECT * FROM users WHERE email= '" . $email . "' AND user_pass= '" . $pass . "' ";
        try {
            $result = $this->conn->query($sql);
            $this->conn->close();
            if (!$result) return "500";
            $row = $result->num_rows;

            if ($row == 1) {
                $user = mysqli_fetch_assoc($result);
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];

                if ($user['is_admin'] == 1) {
                    return "201";
                }
                return "200";
            } else {
                return "404";
            }
        } catch (mysqli_sql_exception) {
            return "500";
        }
    }
}

class User extends Database
{
    public function getUserInfo($user_id)
    {
        $this->connect();
        $sql = "SELECT * FROM users WHERE id = $user_id";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function updateUserInfo($user_id, $full_name, $phone, $address)
    {
        $this->connect();
        $full_name = mysqli_real_escape_string($this->conn, $full_name);
        $phone = mysqli_real_escape_string($this->conn, $phone);
        $address = mysqli_real_escape_string($this->conn, $address);

        $sql = "UPDATE users 
                SET full_name = '$full_name', phone = '$phone', address = '$address' 
                WHERE id = $user_id";
        $this->conn->query($sql);

        $this->conn->close();
        return '200';
    }
    public function getUserActivity($user_id)
    {
        $this->connect();
        $sql = "SELECT * 
                FROM transactions 
                INNER JOIN packages ON transactions.package_id = packages.package_id 
                WHERE transactions.user_id = $user_id
                ORDER BY transactions.trans_date DESC";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getSingleActivity($trans_id)
    {
        $this->connect();
        $trans_id = mysqli_real_escape_string($this->conn, $trans_id);
        $sql = "SELECT * 
                FROM transactions 
                INNER JOIN packages ON transactions.package_id = packages.package_id 
                INNER JOIN users ON transactions.user_id = users.id 
                WHERE transactions.trans_id = '$trans_id'";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getAllUsers()
    {
        $this->connect();
        $sql = "SELECT * FROM users ORDER BY date_created DESC";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function toggleAccountStatus($user_id, $val)
    {
        $this->connect();
        $sql = "UPDATE users SET account_status = $val WHERE id = $user_id";
        $this->conn->query($sql);

        $this->conn->close();
        return '200';
    }
}

class Transactions extends Database
{
    public function createTransaction($trans_id, $user_id, $package_id, $trans_amount, $card_no, $val_id, $card_type)
    {
        $this->connect();
        $trans_id = mysqli_real_escape_string($this->conn, $trans_id);
        $card_no = mysqli_real_escape_string($this->conn, $card_no);
        $val_id = mysqli_real_escape_string($this->conn, $val_id);
        $card_type = mysqli_real_escape_string($this->conn, $card_type);

        $sql = "INSERT INTO transactions(trans_id,user_id,package_id,trans_amount,card_no,val_id,card_type) 
                VALUES ('$trans_id',$user_id,$package_id,$trans_amount,'$card_no','$val_id','$card_type')";
        $this->conn->query($sql);

        $this->conn->close();
        return '200';
    }
    public function getAllTransactions()
    {
        $this->connect();
        $sql = "SELECT * 
                FROM transactions 
                INNER JOIN users ON transactions.user_id = users.id 
                INNER JOIN packages ON transactions.package_id = packages.package_id
                ORDER BY transactions.trans_date DESC";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getRangedTransitions($days)
    {
        $this->connect();
        $sql = "SELECT * 
                FROM transactions 
                INNER JOIN users ON transactions.user_id = users.id 
                INNER JOIN packages ON transactions.package_id = packages.package_id
                WHERE trans_date > CURRENT_DATE - INTERVAL $days day";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getRangedTransitionsTotal($days)
    {
        $this->connect();
        $sql = "SELECT * FROM transactions WHERE trans_date > CURRENT_DATE - INTERVAL $days day";
        $result = $this->conn->query($sql);
        $total = 0;
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $total += $row['trans_amount'];
            }
        }
        $this->conn->close();
        return $total;
    }
}

class Testimonials extends Database
{
    public function getAllTestimonials($limit = 1000)
    {
        $this->connect();
        $sql = "SELECT * 
            FROM testimonials INNER JOIN users ON
            testimonials.user_id = users.id
            INNER JOIN packages ON
            testimonials.package_id = packages.package_id 
            ORDER BY testimonials.date_created DESC
        LIMIT $limit";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function getPackageTestimonials($package_id, $limit = 1000)
    {
        $this->connect();
        $sql = "SELECT * 
            FROM testimonials INNER JOIN users ON
            testimonials.user_id = users.id
            INNER JOIN packages ON
            testimonials.package_id = packages.package_id 
            WHERE testimonials.package_id = $package_id
            ORDER BY testimonials.date_created DESC
            LIMIT $limit";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function checkUserTestimonialStatus($user_id)
    {
        $this->connect();
        $sql = "SELECT DISTINCT package_id 
            FROM testimonials 
            WHERE user_id = $user_id";
        $result = $this->conn->query($sql);

        $this->conn->close();
        return $result;
    }
    public function addTestimonial($desc, $user_id, $package_id, $rating)
    {
        $this->connect();
        $desc = mysqli_real_escape_string($this->conn, $desc);
        $sql = "INSERT INTO testimonials (message,user_id,package_id,rating) VALUES('$desc',$user_id,$package_id,$rating)";
        $this->conn->query($sql);

        $this->conn->close();
        return '200';
    }
}
?>
