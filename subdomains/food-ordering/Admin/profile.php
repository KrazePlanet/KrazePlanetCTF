<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['adminloggedin']) || !$_SESSION['adminloggedin']) {
  header('Location: login.php');
  exit;
}

// Get the logged-in admin's email from the session
$admin_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

if (empty($admin_email)) {
  die("Admin email not found in session.");
}

// Database connection
include 'db_connection.php';
// Function to retrieve admin information
function getAdminInfo($email)
{
  global $conn;
  $stmt = $conn->prepare("SELECT firstName, lastName, email, contact, password, profile_image FROM staff WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->bind_result($firstName, $lastName, $email, $contact, $password, $profile_image);
  $stmt->fetch();
  $stmt->close();
  return [
    'firstName' => $firstName ?: '',
    'lastName' => $lastName ?: '',
    'email' => $email ?: '',
    'contact' => $contact ?: '',
    'password' => $password ?: '',
    'profile_image' => $profile_image ?: 'default.jpg'
  ];
}

// Function to update admin information
function updateAdminInfo($email, $firstName, $lastName, $contact, $password, $profile_image)
{
  global $conn;
  $stmt = $conn->prepare("UPDATE staff SET firstName = ?, lastName = ?, contact = ?, password = ?, profile_image = ? WHERE email = ?");
  $stmt->bind_param("ssssss", $firstName, $lastName, $contact, $password, $profile_image, $email);
  $stmt->execute();
  $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $firstName = $_POST['firstName'];
  $lastName = $_POST['lastName'];
  $contact = $_POST['contact'];
  $password = $_POST['password'];
  $profile_image = getAdminInfo($admin_email)['profile_image'];

  // Handle profile image upload
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
    $profile_image = basename($_FILES["profile_image"]["name"]);
  }

  updateAdminInfo($admin_email, $firstName, $lastName, $contact, $password, $profile_image);

  header('Location: profile.php');
  exit;
}

$admin_info = getAdminInfo($admin_email);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Settings</title>
   <!--poppins-->
   <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <link rel="stylesheet" href="sidebar.css">
  <link rel="stylesheet" href="profile.css">
  
</head>

<body>

  <div class="sidebar">
    <button class="close-sidebar" id="closeSidebar">&times;</button>

    <!-- Profile Section -->
    <div class="profile-section">
      <img src="../uploads/<?php echo htmlspecialchars($admin_info['profile_image']); ?>" alt="Profile Picture">
      <div class="info">
        <h3>Welcome Back!</h3>
        <p><?php echo htmlspecialchars($admin_info['firstName']) . ' ' . htmlspecialchars($admin_info['lastName']); ?></p>
      </div>
    </div>

    <!-- Navigation Items -->

    <ul>
            <li><a href="index.php" ><i class="fas fa-chart-line"></i> Overview</a></li>
            <li><a href="admin_menu.php" ><i class="fas fa-utensils"></i> Menu Management</a></li>
            <li><a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="reservations.php"><i class="fas fa-calendar-alt"></i> Reservations</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="staffs.php" ><i class="fas fa-users"></i> Staffs</a></li>
            <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile Setting</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
  </div>
  <div class="content">
    <div class="header">
      <button id="toggleSidebar" class="toggle-button">
        <i class="fas fa-bars"></i>
      </button>
      <h2><i class="fas fa-user"></i> Profile Setting</h2>
    </div>
    <div class="wrapper">
      <div class="container">

        <img src="../uploads/<?php echo htmlspecialchars($admin_info['profile_image']); ?>" alt="Profile Image" class="profile-image">
        <form action="profile.php" method="post" enctype="multipart/form-data">
          <div class="form-row">
            <div class="form-group">
              <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($admin_info['firstName']); ?>" placeholder=" ">
              <label for="firstName">First Name:</label>
            </div>

            <div class="form-group">
              <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($admin_info['lastName']); ?>" placeholder=" ">
              <label for="lastName">Last Name:</label>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_info['email']); ?>" readonly placeholder=" ">
              <label for="email">Email:</label>
            </div>

            <div class="form-group">
              <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($admin_info['contact']); ?>" placeholder=" ">
              <label for="contact">Contact Number:</label>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($admin_info['password']); ?>" placeholder=" ">
              <label for="password">Password:</label>
            </div>

            <div class="form-group" >
              <input type="file" id="profile_image" name="profile_image" placeholder=" " >
             
            </div>

          </div>

    

          <button type="submit">Save Settings</button>
        </form>
      </div>
    </div>


  </div>

  <?php
    include_once ('footer.html');
    ?>
  <script src="sidebar.js"></script>
</body>

</html>

<?php $conn->close(); ?>