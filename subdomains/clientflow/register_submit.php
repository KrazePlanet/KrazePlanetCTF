<?php
require_once __DIR__ . '/bootstrap.php'; db();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('register.php'); check_csrf();
$name=trim($_POST['name']??''); $email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<8) { flash('error','Please enter a valid name, email, and password of at least 8 characters.'); redirect('register.php'); }
try {
 $s=db()->prepare("INSERT INTO users(name,email,password_hash) VALUES(?,?,?)");
 $s->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
 $_SESSION['user_id']=(int)db()->lastInsertId(); flash('success','Account created successfully.'); redirect('account.php');
} catch(PDOException $e) { flash('error','That email is already registered.'); redirect('register.php'); }
