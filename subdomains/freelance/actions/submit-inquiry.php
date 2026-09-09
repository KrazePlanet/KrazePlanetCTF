<?php
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('../index.php#contact');
if (!verify_csrf()) { flash('error','Your session expired. Please try again.'); redirect('../index.php#contact'); }
if (!empty($_POST['website'])) { flash('success','Thanks! Your project inquiry has been received.'); redirect('../index.php#contact'); }

$name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$service = trim($_POST['service'] ?? '');
$budget = trim($_POST['budget'] ?? '');
$timeline = trim($_POST['timeline'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '' || mb_strlen($name) > 150) $errors[] = 'Please enter your name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255) $errors[] = 'Please enter a valid email.';
if ($service === '') $errors[] = 'Please select a service.';
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) $errors[] = 'Please provide a message between 10 and 5000 characters.';

if ($errors) {
    flash('error', implode(' ', $errors));
    redirect('../index.php#contact');
}

try {
    $stmt = db()->prepare('INSERT INTO inquiries (full_name,email,phone,service,budget,timeline,message) VALUES (:name,:email,:phone,:service,:budget,:timeline,:message)');
    $stmt->execute([
        ':name'=>$name, ':email'=>$email, ':phone'=>$phone ?: null, ':service'=>$service,
        ':budget'=>$budget ?: null, ':timeline'=>$timeline ?: null, ':message'=>$message
    ]);
    flash('success','Thanks! Your project inquiry has been received. I\'ll get back to you soon.');
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash('error','We could not submit your inquiry right now. Please try again.');
}
redirect('../index.php#contact');
