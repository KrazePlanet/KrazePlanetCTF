<?php
session_start();
require '../registration/dbcon.php';
if ($_SESSION['role'] != 1) {
  header('location:../../index.php');
}
?>
<!DOCTYPE html>
<html>

<head>
  <!-- fav-icon -->
  <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
  <title>PICTURE PERFECT MAKES YOU PERFECT</title>
  <style>
    body {
      background-color: darkslategray;
      color: white;
    }

    h1{
      text-align: center;
    }

    nav {
      background-color: grey;
      /* deep purple */
      padding: 10px;
    }

    nav ul {
      list-style-type: none;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
    }

    nav li {
      margin: 0 10px;
    }

    nav a {
      display: block;
      padding: 8px 15px;
      /* white */
      color: white;
      text-decoration: none;
    }

    nav a:hover {
      background-color: gold;
      /* white */
      color: #2E0854;
      /* deep purple */
    }

    form {
      margin-top: 50px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    label {
      margin-top: 20px;
      /* deep purple */
      font-weight: bold;
    }

    input[type="email"],
    input[type="file"],
    button {
      display: block;
      margin: 10px 0;
      padding: 10px;
      /* deep purple */
      background-color: #FFFFFF;
      /* white */
      border: none;
      width: 100%;
      max-width: 500px;
    }

    input[type="email"] {
      border: 2px solid #2E0854;
      /* deep purple */
    }

    input[type="file"] {
      background-color: grey;
      /* light gray */
    }

    button {
      background-color: gold;
      /* deep purple */
      /* white */
      color: black;
      cursor: pointer;
    }

    button:hover {
      background-color: goldenrod;
    }
  </style>
</head>

<body>
  <h1>Picture Perfect</h1>
  <!-- Navbar -->
  <nav>
    <ul>
      <li>
        <a href="../../index.php">Home</a>
      </li>
      <li>
        <a href="../registration/logout.php">Logout</a>
      </li>
      <li>
        <a href="view.php">client Photograph folder</a>
      </li>
    </ul>
  </nav>
  <form action="upload.php" method="post" enctype="multipart/form-data">
    <input type="email" name="email" required="required" placeholder="email where you want to send data">
    <label for="username">Enter The Client's Email</label>
    <input type="file" name="file" />
    <p style="text-align: center;">upload only rar and zip files</p>
    <button type="submit" name="btn-upload">upload</button>
  </form>
  <?php
  if (isset($_GET['success'])) {
  ?>
    <label>File Uploaded Successfully... <a href="view.php">click here to view file.</a></label>
  <?php
  } else if (isset($_GET['fail'])) {
  ?>
    <label>Problem While File Uploading !</label>
  <?php
  } else {
  ?>
  
  <?php
  }
  ?>
      <br> <hr>
  	<?php if (isset($_GET['error'])): ?>
		<p style="text-align: center;"><?php echo $_GET['error']; ?></p>
	<?php endif ?>
  <form action="../Gallery/upload.php"
      method="post"
      enctype="multipart/form-data">
  <h2 style="text-align: center;">Add new Image in Gallery Section(Limit 5 at once)</h2>
  <input type="file" 
         name="my_image[]"
         multiple />
  <p style="text-align: center;">upload only jpg , jpeg & png </p>
  <button type="submit" name="btn-upload">upload</button>
</form>
    <a href="../Gallery/view.php">View</a>
</body>

</html>