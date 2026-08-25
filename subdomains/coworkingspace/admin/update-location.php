<?php
session_start();
$dbhost = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$dbname = 'cowork_db';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Update User
if (isset($_POST['update_location'])) {
    $id = $_GET['id'];
    $name = $_POST['name'];
    $location = $_POST['location'];

    $query = "UPDATE spaces SET name = ?, location = ? WHERE id = ?";
    $stmt = $pdo->prepare($query);

    $params = [
        $name,
        $location,
        $id
    ];

    if ($stmt->execute($params)) {
        $succ = "Location Updated";
    } else {
        $err = "Please Try Again Later";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php');?>

<body id="page-top">
 <!--Start Navigation Bar-->
  <?php include("vendor/inc/nav.php");?>
  <!--Navigation Bar-->

  <div id="wrapper">

    <!-- Sidebar -->
    <?php include("vendor/inc/sidebar.php");?>
    <!--End Sidebar-->
    <div id="content-wrapper">

      <div class="container-fluid">
      <?php if(isset($succ)) {?>
        <!--This code for injecting an alert-->
        <script>
            setTimeout(function () { 
                swal("Success!", "<?php echo $succ;?>", "success");
            }, 100);
        </script>
      <?php } ?>

      <?php if(isset($err)) {?>
        <!--This code for injecting an alert-->
        <script>
            setTimeout(function () { 
                swal("Failed!", "<?php echo $err;?>", "error");
            }, 100);
        </script>
      <?php } ?>

        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="#">Users</a>
          </li>
          <li class="breadcrumb-item active">update locations</li>
        </ol>
        <hr>
        <div class="card">
        <div class="card-header">
          update location
        </div>
        <div class="card-body">
          <!--Add User Form-->
          <?php
            $aid = $_GET['id'];
            $ret = "SELECT id, name, location FROM spaces WHERE id = :id";
            $stmt = $pdo->prepare($ret);
            $stmt->bindValue(':id', $aid, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetchAll(PDO::FETCH_OBJ);

            foreach ($res as $row) {
          ?>
          <form method="POST"> 
            <div class="form-group">
                <label for="exampleInputEmail1">Space Name</label>
                <input type="text" value="<?php echo $row->name;?>" required class="form-control" id="exampleInputEmail1" name="name">
            </div>
      
            <div class="form-group">
                <label for="exampleInputEmail1">Location</label>
                <input type="text" value="<?php echo $row->location;?>" class="form-control" name="location">
            </div>

            <button type="submit" name="update_location" class="btn btn-success">Update User</button>
          </form>
          <!-- End Form-->
        <?php } ?>
        </div>
      </div>
       
      <hr>
     
      <!-- Sticky Footer -->
      <?php include("vendor/inc/footer.php");?>

    </div>
    <!-- /.content-wrapper -->

  </div>
  <!-- /#wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
          <a class="btn btn-danger" href="admin-logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap core JavaScript-->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

  <!-- Core plugin JavaScript-->
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- Page level plugin JavaScript-->
  <script src="vendor/chart.js/Chart.min.js"></script>
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

  <!-- Custom scripts for all pages-->
  <script src="vendor/js/sb-admin.min.js"></script>

  <!-- Demo scripts for this page-->
  <script src="vendor/js/demo/datatables-demo.js"></script>
  <script src="vendor/js/demo/chart-area-demo.js"></script>

  <!--INject Sweet alert js-->
  <script src="vendor/js/swal.js"></script>

</body>

</html>
