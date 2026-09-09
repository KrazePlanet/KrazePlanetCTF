<?php require_once __DIR__.'/bootstrap.php'; db(); if(!user_logged_in()) redirect('login.php'); if($_SERVER['REQUEST_METHOD']!=='POST') redirect('account.php'); check_csrf();
$s=db()->prepare("UPDATE users SET status='deactivated' WHERE id=?");$s->execute([$_SESSION['user_id']]);unset($_SESSION['user_id']);redirect('feedback.php?reason=deactivated');
