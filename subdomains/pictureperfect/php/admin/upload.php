<?php
session_start();
require '../registration/dbcon.php';
$email = $_POST['email'];
$sqll = "SELECT * FROM registration where email='$email'";
$res = mysqli_query($conn, $sqll);
$data = mysqli_fetch_assoc($res);
if ($data['email']) {
      if ($data['role'] == 2) {
            if (isset($_POST['btn-upload'])) {
                  $file = rand(1000, 100000) . "-" . $_FILES['file']['name'];
                  $file_loc = $_FILES['file']['tmp_name'];
                  $file_size = $_FILES['file']['size'];
                  $file_type = $_FILES['file']['type'];
                  $folder = "../../upload/";
                  //$email=$_SESSION['email']; 
                  // new file size in KB
                  $new_size = $file_size / 1024;
                  // new file size in KB        
                  // make file name in lower case
                  $new_file_name = strtolower($file);
                  // make file name in lower case        
                  $final_file = str_replace(' ', '-', $new_file_name);
                  if (move_uploaded_file($file_loc, $folder . $final_file)) {
                        $sql = "INSERT INTO tbl_uploads(file,type,size,email) VALUES('$final_file','$file_type','$new_size','$email')";
                        mysqli_query($conn, $sql);
?>
                        <script>
                              alert('successfully uploaded');
                              window.location.href = 'fileupload.php?success';
                        </script>
                  <?php
                  }
            } else {
                  ?>
                  <script>
                        alert('error while uploading file please select a file to upload');
                        window.location.href = 'fileupload.php?fail';
                  </script>
            <?php
            }
      } else {
            ?>
            <script>
                  alert('provided email do exist in database but is not our customer');
                  window.location.href = 'fileupload.php?fail';
            </script>
      <?php
      }
} else {
      ?>
      <script>
            alert('provided email doesnot exist in database');
            window.location.href = 'fileupload.php?fail';
      </script>
<?php
}
