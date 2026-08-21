<?php
class Database
{
    private $host = "localhost";
    private $db = "yummy";
    private $user = 'root';
    private $pass = '';
    private $pdo = null;

    /**
     * connexion mysql & auto-provisioning
     * @return PDO
     */
    private function connect(): PDO
    {
        try {
            // Connect to MySQL server to ensure database exists
            $init = new PDO("mysql:host={$this->host};", $this->user, $this->pass);
            $init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $init->exec("CREATE DATABASE IF NOT EXISTS `{$this->db}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

            // Connect to yummy database
            $pdo = new PDO("mysql:host={$this->host};dbname={$this->db};charset=utf8mb4", $this->user, $this->pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

            // Auto-provision tables if not present
            $chk = $pdo->query("SHOW TABLES LIKE 'users'");
            if (!$chk || $chk->rowCount() == 0) {
                $this->initSchema($pdo);
            }

            return $pdo;
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }

    private function initSchema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `category` (
              `id_cat` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `cateoryname` varchar(50) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `commands` (
              `id_com` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `username` varchar(50) NOT NULL,
              `telephone` varchar(250) NOT NULL,
              `cmd_name` varchar(50) NOT NULL,
              `prix_unit` int(50) NOT NULL,
              `id_user` int(11) NOT NULL,
              `adress` varchar(50) NOT NULL,
              `nbr` int(11) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `product` (
              `id_prod` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `prodname` varchar(50) NOT NULL,
              `price` int(11) NOT NULL,
              `category_id` int(11) NOT NULL,
              `prodimg` varchar(250) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id_user` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
              `username` varchar(50) NOT NULL,
              `adress` varchar(50) NOT NULL,
              `tel` varchar(30) NOT NULL,
              `profil` varchar(250) NOT NULL DEFAULT 'default.jpg',
              `password` varchar(250) NOT NULL,
              `email` varchar(50) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Seed default categories
        $chkCat = $pdo->query("SELECT COUNT(*) FROM `category`")->fetchColumn();
        if ($chkCat == 0) {
            $pdo->exec("INSERT INTO `category` (`id_cat`, `cateoryname`) VALUES
                (1, 'tacos'), (2, 'soupe'), (3, 'sambos'), (4, 'gratin'),
                (5, 'boisson'), (6, 'glace'), (7, 'riz');");
        }

        // Seed default products
        $chkProd = $pdo->query("SELECT COUNT(*) FROM `product`")->fetchColumn();
        if ($chkProd == 0) {
            $pdo->exec("INSERT INTO `product` (`id_prod`, `prodname`, `price`, `category_id`, `prodimg`) VALUES
                (1, 'tacos fruit de mer', 10000, 1, 'tacosF.jpg'),
                (2, 'tacos tropical', 9000, 1, 'tacosT.jpg'),
                (3, 'soupe tropical', 6500, 2, 'soupeT.jpg'),
                (4, 'soupe special', 6000, 2, 'soupeS.jpg'),
                (5, 'demi lune', 6000, 2, 'soupeD.jpg'),
                (6, 'sambos viande', 500, 3, 'sambosV.jpg'),
                (7, 'sambos poulet', 500, 3, 'sambosP.jpg'),
                (8, 'gratin special', 15000, 4, 'gratinS.jpg'),
                (9, 'gratin tropical', 15000, 4, 'gratinT.jpg'),
                (10, 'coca cola', 2000, 5, 'coca.jpg'),
                (11, 'soda', 2000, 5, 'soda.jpg'),
                (12, 'glace cornet', 3000, 6, 'cornet.jpg'),
                (13, 'glace en boite', 2500, 6, 'boite.jpg'),
                (14, 'riz cantonnais', 5000, 7, 'rizC.jpg'),
                (15, 'riz traditionnel', 7000, 7, 'rizM.jpg');");
        }

        // Seed default user
        $chkUser = $pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
        if ($chkUser == 0) {
            $pwd = password_hash('admin', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO `users` (`id_user`, `username`, `adress`, `tel`, `profil`, `password`, `email`) VALUES
                (1, 'Brandon Fidelin', 'Avaratra Ankatso', '0389411835', 'default.jpg', '$pwd', 'brandon@gmail.com');");
        }
    }

    /**
     * retourne l'instance du PDO (singleton)
     * @return PDO
     */
    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = $this->connect();
        }

        return $this->pdo;
    }
}
