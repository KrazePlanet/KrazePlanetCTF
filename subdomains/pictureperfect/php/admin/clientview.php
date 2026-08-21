<?php
session_start();
if ($_SESSION['role'] == 0) {
   header('location:../../index.php');
}
require '../registration/dbcon.php';
?>
<!DOCTYPE html>
<html>

<head>
   <!-- fav-icon -->
   <link rel="icon" type="image/png" href="../../media/images/Review/fav-icon.ico" />
   <title>PICTURE PERFECT MAKES YOU PERFECT</title>
</head>
<style>
   body {
      background-color:darkslategray;
      color: white;
   }

   nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: flex-end;
      background-color: black;
      border-bottom: 1px solid #ccc;
   }

   nav ul li {
      margin: 0;
      padding: 0;
   }

   nav ul li a {
      display: block;
      padding: 1em;
      text-decoration: none;
      color: white;
      font-weight: bold;
      position: relative;
      transition: all 0.3s ease-in-out;
   }

   nav ul li a:hover {
      color: #ffd700;
   }

   nav ul li a:before {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      height: 3px;
      width: 0;
      background-color: #ffd700;
      transition: all 0.3s ease-in-out;
   }

   nav ul li a:hover:before {
      width: 100%;
   }

   div:first-of-type {
      text-align: center;
      margin-top: 1em;
      color: #ffd700;
      animation: fadein 2s;
   }

   table {
      border-collapse: collapse;
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      animation: slidein 2s;
   }

   table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
   }

   table th {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: center;
      background-color: #ffd700;
      color: #000;
   }

   @keyframes fadein {
      from {
         opacity: 0;
      }

      to {
         opacity: 1;
      }
   }

   @keyframes slidein {
      from {
         transform: translateY(100%);
      }

      to {
         transform: translateY(0);
      }
   }
</style>

<body>
   <nav>
      <ul>
         <li>
            <a href="../../index.php">Home</a>
         </li>
         <li>
            <a href="../registration/logout.php">Logout</a>
         </li>
      </ul>
   </nav>
   <div>
      <h1>PICTURE PERFECT MAKES YOU PERFECT</h1>
   </div>
   <div>
      <table>
         <tr>
            <td>File Name</td>
            <td>File Type</td>
            <td>Email</td>
            <td>Download</td>
         </tr>
   </div>

   <?php
   $email = $_SESSION['email'];
   $sql = "SELECT * FROM tbl_uploads where email='$email'";
   $result_set = mysqli_query($conn, $sql);
   while ($row = mysqli_fetch_array($result_set)) {
   ?>
      <tr>
         <td><?php echo $row['file'] ?></td>
         <td><?php echo $row['type'] ?></td>
         <td><?php echo $row['email'] ?></td>
         <td><a style="color:gold;" href="../../upload/<?php echo $row['file'] ?>" target="_blank">Download</a></td>
      </tr>
   <?php
   }
   ?>
   </table>
</body>

</html>