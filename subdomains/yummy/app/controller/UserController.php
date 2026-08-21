<?php

class UserController extends UserModel
{

    /**
     * Va retouner l'email entrer par l'utilisateur
     * @return String
     */
    private function GetEmail(): string
    {
        if (isset($_POST['email'])) {
            return htmlentities(htmlspecialchars($_POST['email']));
        }
        return '';
    }

    /**
     * Va retouner le mot de passe entrer par l'utilisateur
     * @return String
     */
    private function GetPassword(): string
    {
        if (isset($_POST['password'])) {
            return $_POST['password'];
        }
        return '';
    }

    /**
     * Va retouner l'adress entrer par l'utilisateur
     * @return String
     */
    private function GetAdress(): string
    {
        if (isset($_POST['adress'])) {
            return htmlentities(htmlspecialchars($_POST['adress']));
        }
        return '';
    }

    /**
     * Va retouner le telephone entrer par l'utilisateur
     * @return String
     */
    private function GetTel(): string
    {
        if (isset($_POST['tel'])) {
            return htmlentities(htmlspecialchars($_POST['tel']));
        }
        return '';
    }

    /**
     * Va retouner le nom entrer par l'utilisateur
     * @return String
     */
    private function GetUsername(): string
    {
        if (isset($_POST['username'])) {
            return htmlentities(htmlspecialchars($_POST['username']));
        }
        return '';
    }

    /**
     * Va retouner la photo de profil de l'utilisateur
     * @return String
     */
    public function GetProfil(): string
    {
        if (isset($_FILES['profil']) && !empty($_FILES['profil']['tmp_name'])) {
            $image = $_FILES['profil'];
            $image_tmp = $image['tmp_name'];
            $image_name = $image['name'];
            $image_exploded = explode('.', $image_name);
            $image_ext = strtolower(end($image_exploded));
            $allowed_ext = ['jpg', 'png', 'jpeg', 'webp', 'gif'];
            if(in_array($image_ext, $allowed_ext)){
                $target_dir = dirname(__DIR__).'/images/';
                if(!is_dir($target_dir)) {
                    @mkdir($target_dir, 0777, true);
                }
                $new_image = time().'.'.$image_ext;
                if(move_uploaded_file($image_tmp, $target_dir.$new_image)){
                    return $new_image;
                }
            }
        }

        return 'default.jpg';
    }

    /**
     * Va connecter un utilisateur existant
     * @return void
     */
    public function ConnectUser(): void
    {
        if(isset($_POST['envoyer'])){
            if (parent::ConnecUser($this->GetEmail(), $this->GetPassword())) {
                header('location:../../../index.php');
            } else {
                echo '<div class="alert alert-danger" style="margin:10px auto;max-width:400px;text-align:center;">Vérifiez vos informations de connexion</div>';
            }
        }
    }

    public function Create(): void
    {
        if(isset($_POST['envoyer'])){
            parent::CreateUser($this->GetUsername(), $this->GetEmail(), password_hash($this->GetPassword(),PASSWORD_DEFAULT), $this->GetAdress(), $this->GetTel(), $this->GetProfil());
        }
    }
}
