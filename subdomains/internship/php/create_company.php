<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = $_POST['company_name'];
    $description = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO companies (user_id, company_name, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $company_name, $description);
    $stmt->execute();

    header("Location: company_dashboard.php");
    exit();
}
?>
<link rel="stylesheet" href="../css/create_company.css">
<div class="form-container">
<h2>Create Your Company Profile</h2>
<form method="POST">
    <label>Company Name:</label><br>
    <input type="text" name="company_name" required><br><br>

    <label>Description:</label><br>
    <textarea name="description" rows="4" required></textarea><br><br>

    <input type="submit" value="Create Profile">
</form>
</div>