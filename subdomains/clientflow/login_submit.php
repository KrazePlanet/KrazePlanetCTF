<?php
require_once __DIR__ . '/bootstrap.php'; db();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('login.php'); check_csrf();
$email=strtolower(trim($_POST['email']??'')); $password=$_POST['password']??'';
$s=db()->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $s->execute([$email]); $u=$s->fetch();
if($u && $u['status']==='active' && password_verify($password,$u['password_hash'])) { session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id']; redirect('account.php'); }
flash('error','Invalid credentials or deactivated account.'); redirect('login.php');
