<?php
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "admin") {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "internship_management");

// Fetch stats
$students = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$companies = $conn->query("SELECT COUNT(*) AS total FROM companies")->fetch_assoc()['total'];
$internships = $conn->query("SELECT COUNT(*) AS total FROM internships")->fetch_assoc()['total'];
$applications = $conn->query("SELECT COUNT(*) AS total FROM applications")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <h2>Admin Panel</h2>
      <a href="admin.php">Dashboard</a>
      <a href="manage_users.php">Manage Users</a>
      <a href="manage_companies.php">Manage Companies</a>
      <a href="manage_internships.php">Manage Internships</a>
      <a href="manage_applications.php">Manage Applications</a>
      <a href="logout.php" class="btn btn-danger mt-3">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="col content">
      <!-- Top bar -->
      <div class="d-flex justify-content-between align-items-center mt-3 mb-4">
        <!-- Toggle button for small screens -->
        <button class="menu-btn d-lg-none ms-2" onclick="toggleSidebar()">☰</button>
        <!-- Logout button for large screens -->
        <a href="logout.php" class="btn btn-danger d-none d-lg-block me-3">Logout</a>
      </div>

      <h1 class="mb-4">Welcome, Admin</h1>
      <div class="row g-3">
        <div class="col-md-3">
          <div class="card text-white bg-students">
            <div class="card-body">
              <h5 class="card-title">Students</h5>
              <p class="card-text fs-4"><?php echo $students; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-companies">
            <div class="card-body">
              <h5 class="card-title">Companies</h5>
              <p class="card-text fs-4"><?php echo $companies; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-internships">
            <div class="card-body">
              <h5 class="card-title">Internships</h5>
              <p class="card-text fs-4"><?php echo $internships; ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-applications">
            <div class="card-body">
              <h5 class="card-title">Applications</h5>
              <p class="card-text fs-4"><?php echo $applications; ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- Extra Dashboard Section -->
      <div class="row g-3 mt-4">
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Recent Activity</h5>
              <ul class="mb-0">
                <li>New student registered</li>
                <li>Company ABC posted internship</li>
                <li>5 applications submitted today</li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">System Status</h5>
              <p>Everything running smoothly ✅</p>
              <p>Last backup: 1 hour ago</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="mt-5">
        <h3>Quick Links</h3>
        <a href="manage_users.php" class="btn btn-outline-primary m-2">Manage Users</a>
        <a href="manage_companies.php" class="btn btn-outline-success m-2">Manage Companies</a>
        <a href="manage_internships.php" class="btn btn-outline-warning m-2">Manage Internships</a>
        <a href="manage_applications.php" class="btn btn-outline-danger m-2">Manage Applications</a>
      </div>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  document.getElementById("sidebar").classList.toggle("active");
}
</script>
</body>
</html>
