<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['userloggedin']) || !$_SESSION['userloggedin']) {
  header('Location: login.php');
  exit;
}

// Get the logged-in admin's email from the session
$user_email = isset($_SESSION['email']) ? $_SESSION['email'] : '';

if (empty($user_email)) {
  die("User email not found in session.");
}

// Database connection
include 'db_connection.php';
// Function to retrieve admin information
function getUserInfo($email)
{
  global $conn;
  $stmt = $conn->prepare("SELECT firstName, lastName, email, contact, password, profile_image FROM users WHERE email = ?");
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
function updateUserInfo($email, $firstName, $lastName, $contact, $password, $profile_image)
{
  global $conn;
  $stmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, contact = ?, password = ?, profile_image = ? WHERE email = ?");
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
  $profile_image = getUserInfo($user_email)['profile_image'];

  // Handle profile image upload
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);
    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);
    $profile_image = basename($_FILES["profile_image"]["name"]);
  }

  updateUserInfo($user_email, $firstName, $lastName, $contact, $password, $profile_image);

  header('Location: profile.php');
  exit;
}

$user_info = getUserInfo($user_email);
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
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/css/bootstrap.min.css' />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css" integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="profile.css">
  <style>
    @media screen and (max-width: 700px) {
        .form-row{
          display: flex;
          flex-direction: column !important;
        }
        .form-container{
          margin: 40px 30px 40px 30px !important;
         
        }
    }
    .wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: 70px;
  
}
    </style>
</head>

<body>
  <?php

  if (isset($_SESSION['userloggedin']) && $_SESSION['userloggedin']) {
    include 'nav-logged.php';
  } else {
    include 'navbar.php';
  }
  ?>


  <div class="wrapper">
    <div class="form-container">
      <h2>Profile Settings</h2>
      <img src="uploads/<?php echo htmlspecialchars($user_info['profile_image']); ?>" alt="Profile Image" class="profile-image">
      <form action="profile.php" method="post" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-group">
            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($user_info['firstName']); ?>" placeholder=" ">
            <label for="firstName">First Name:</label>
          </div>

          <div class="form-group">
            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($user_info['lastName']); ?>" placeholder=" ">
            <label for="lastName">Last Name:</label>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" readonly placeholder=" ">
            <label for="email">Email:</label>
          </div>

          <div class="form-group">
            <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($user_info['contact']); ?>" placeholder=" ">
            <label for="contact">Contact Number:</label>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <input type="text" id="password" name="password" value="<?php echo htmlspecialchars($user_info['password']); ?>" placeholder=" ">
            <label for="password">Password:</label>
          </div>

          <div class="form-group">
            <input type="file" id="profile_image" name="profile_image" placeholder=" ">

          </div>

        </div>



        <button type="submit">Save Settings</button>
      </form>
    </div>
  </div>

  <?php
include_once ('footer.html');
?>
  <!-- Bootstrap JS -->
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
  <script src='https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.5.2/js/bootstrap.min.js'></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script>
    $(document).ready(function() {
      console.log('Page is ready. Calling load_cart_item_number.');
      load_cart_item_number();

      function load_cart_item_number() {
        $.ajax({
          url: 'action.php',
          method: 'get',
          data: {
            cartItem: "cart_item"
          },
          success: function(response) {
            $("#cart-item").html(response);
          }
        });
      }
    });
  </script>

</body>

</html>

<?php $conn->close(); ?>