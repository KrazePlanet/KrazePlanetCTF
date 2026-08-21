<?php

class UserModel extends Database
{

    /**
     * Va retourner la liste des utilisateurs
     * @return array
     */
    public function UserList(): array
    {
        $query = parent::getPdo()->query('SELECT * FROM users');
        $query->execute();
        return $query->fetchAll();
    }

    /**
     * Va Creer un nouveau utilisateur
     */
    public function CreateUser(string $username, string $email, string $password, string $adress, string $tel, string $profil = 'default.jpg'): void
    {
        if ($this->CheckExistEmail($email) === true) {
            $query = parent::getPdo()->prepare('INSERT INTO users(`username`,`email`,`password`,`adress`,`tel`, `profil`) VALUES (?,?,?,?,?,?)');
            $query->execute([$username, $email, $password, $adress, $tel, $profil]);
            if (!headers_sent()) {
                header('location:../login/login.php');
            } else {
                echo "<script>window.location.href='../login/login.php';</script>";
            }
            exit();
        } else {
            echo '<div style="background:#fee2e2;color:#b91c1c;padding:12px;border-radius:6px;margin:10px auto;max-width:400px;text-align:center;font-weight:600;">Cet email existe déjà!</div>';
        }
    }

    /**
     * Va tester si un email existe deja dans la table users
     * @return bool
     */
    public function CheckExistEmail($email): bool
    {
        $query = parent::getPdo()->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute([$email]);
        return ($query->rowCount() === 0);
    }

    /**
     * @return bool
     */
    public function ConnecUser($email, $password): bool
    {
        if ($this->CheckExistEmail($email) === false) {
            $user = $this->getUser($email);
            if ($user && password_verify($password, $user['password'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['users'] = [
                    'id' => $user['id_user'],
                    'name' => $user['username'],
                    'email' => $user['email'],
                    'tel' => $user['tel'],
                    'adress' => $user['adress'],
                    'profil' => $user['profil']
                ];
                return true;
            }
        }
        return false;
    }

    /**
     * Va retourner les infos d'un utilisateur
     */
    public function getUser(string $email)
    {
        $query = parent::getPdo()->prepare('SELECT * FROM users WHERE email = ?');
        $query->execute([$email]);
        return $query->fetch();
    }
}
