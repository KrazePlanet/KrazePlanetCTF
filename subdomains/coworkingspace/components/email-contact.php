<?php
    require_once(__DIR__.'/autoload.php');
    use Mailjet\Resources;

    define('API_USER', 'YOUR_KEY');
    define('API_LOGIN', 'YOUR_KEY');
    $mj = new \Mailjet\Client(API_PUBLIC_KEY, API_PRIVATE_KEY,true,['version' => 'v3.1']);

    
    if(!empty($_POST['name']) && !empty($_POST['Address'])&& !empty($_POST['phone']) && !empty($_POST['email']) && !empty($_POST['message'])){
        $name = htmlspecialchars($_POST['name']);
        $Address = htmlspecialchars($_POST['Address']);
        $email = htmlspecialchars($_POST['email']);
        $phone = htmlspecialchars($_POST['phone']);
        $message = htmlspecialchars($_POST['message']);

        if(filter_var($email, FILTER_VALIDATE_EMAIL)){

        }else{
            echo "Invalid email";
        }
    }
    else{
        header('Location:contact.php');
        die();
    }
?>