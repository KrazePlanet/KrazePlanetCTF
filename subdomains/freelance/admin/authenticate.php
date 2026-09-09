<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) { flash('error','Invalid request.'); redirect('login.php'); }
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$stmt = db()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
$stmt->execute([':email'=>$email]);
$admin = $stmt->fetch();
if (!$admin || !password_verify($password, $admin['password'])) {
    flash('error','Invalid email or password.');
    redirect('login.php');
}
session_regenerate_id(true);
$_SESSION['admin_id'] = (int)$admin['id'];
$_SESSION['admin_name'] = $admin['name'];
db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id')->execute([':id'=>$admin['id']]);
redirect('index.php');
