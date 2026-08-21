<?php
session_start();
require '../database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'company') {
    header("Location: login.php");
    exit();
}

$company_user_id = $_SESSION['user_id'];
$internship_id = $_GET['id'] ?? 0;

// Get company ID
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->bind_param("i", $company_user_id);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) die("Company not found.");

// Delete internship if it belongs to this company
$stmt = $conn->prepare("DELETE FROM internships WHERE id=? AND company_id=?");
$stmt->bind_param("ii", $internship_id, $company['id']);
$stmt->execute();

header("Location: company_dashboard.php");
exit();
