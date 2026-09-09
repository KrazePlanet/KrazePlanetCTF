<?php
require_once __DIR__ . '/../bootstrap.php'; db();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('login.php'); check_csrf();
$email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
$s=db()->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");$s->execute([$email]);$a=$s->fetch();
if($a && password_verify($password,$a['password_hash'])){session_regenerate_id(true);$_SESSION['admin_id']=(int)$a['id'];redirect('dashboard.php');}
flash('error','Invalid admin email or password.');redirect('login.php');
