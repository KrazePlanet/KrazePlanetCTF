<?php
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
      font-family: Arial, sans-serif;
      background-color:darkslategray;
      color: white;
   }

   nav {
      background-color: #333;
      color: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px;
   }
   nav h1{
      color:gold;
   }
   nav ul {
      list-style: none;
      margin: 0;
      padding: 0;
   }

   nav li {
      display: inline-block;
      margin-left: 50px;
   }

   nav a {
      color: #fff;
      text-decoration: none;
      transition: all 0.2s ease-in-out;
   }

   nav a:hover {
      color: gold;
      text-decoration: underline;
   }

   table {
      border-collapse: collapse;
      margin: 20px auto;
      width: 80%;
   }

   table th,
   table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
   }

   table th {
      background-color: #333;
      color: #fff;
   }

   table tr:nth-child(odd) {
      background-color: black;
   }

   table tr:nth-child(even) {
      background-color: black;
   }

   table tr:hover {
      background-color: grey;
   }

   label {
      display: block;
      text-align: center;
      font-size: 24px;
      margin: 20px;
      color: gold;
   }

   div {
      margin: 0 auto;
      max-width: 800px;
   }
   a{
      text-decoration: none;
      color: gold;
   }
   @media only screen and (max-width: 768px) {
      nav ul {
         display: none;
      }

      nav li {
         display: block;
         margin: 10px 0;
      }

      nav a {
         display: block;
         padding: 10px;
         background-color: #333;
         color: #fff;
         text-align: center;
      }
   }
</style>

<body>
   <nav>
      <h1>Picture Perfect</h1>
      <ul>
         <li>
            <a href="../../index.php">Home</a>
         </li>
         <li>
            <a href="../registration/logout.php">Logout</a>
         </li>
         <li>
            <a href="fileupload.php">Dashboard</a>
         </li>
      </ul>
   </nav>
   <label>PICTURE PERFECT MAKES YOU PERFECT</label>
   <div>
      <table>
         <tr>
            <td>File Name</td>
            <td>File Type</td>
            <td>Email</td>
            <td>View</td>
         </tr>
         <?php
         $sql = "SELECT * FROM tbl_uploads";
         $result_set = mysqli_query($conn, $sql);
         while ($row = mysqli_fetch_array($result_set)) {
         ?>
            <tr>
               <td><?php echo $row['file'] ?></td>
               <td><?php echo $row['type'] ?></td>
               <td><?php echo $row['email'] ?></td>
               <td><a href="../../upload/<?php echo $row['file'] ?>" target="_blank">Download</a></td>
            </tr>
         <?php
         }
         ?>
      </table>
   </div>
</body>

</html>