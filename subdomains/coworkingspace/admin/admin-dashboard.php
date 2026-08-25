<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Vehicle Booking System Transport Saccos, Matatu Industry">
  <meta name="author" content="MartDevelopers">

  <title>Coworking Space WORKVIBES - Admin Dashboard</title>

  <!-- Custom fonts for this template-->
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">

  <!-- Page level plugin CSS-->
  <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">

  <!-- Custom styles for this template-->
  <link href="vendor/css/sb-admin.css" rel="stylesheet">

</head>

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

        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
          <li class="breadcrumb-item">
            <a href="#">Dashboard</a>
          </li>
          <li class="breadcrumb-item active">Overview</li>
        </ol>

        <!-- Icon Cards-->
        <div class="row">
          <div class="col-xl-3 col-sm-6 mb-3">
            <div class="card text-white bg-danger o-hidden h-100">
              <div class="card-body">
                <div class="card-body-icon">
                  <i class="fas fa-fw fa-users"></i>
                </div>
                <?php
                  //code for summing up number of users
                  $result ="SELECT count(*) FROM users where role = 'user'";
                  $stmt = $mysqli->prepare($result);
                  $stmt->execute();
                  $stmt->bind_result($user);
                  $stmt->fetch();
                  $stmt->close();
                ?>
                <div class="mr-5"><span class="badge badge-danger"><?php echo $user;?></span> Users</div>
              </div>
              <a class="card-footer text-white clearfix small z-1" href="admin-view-user.php">
                <span class="float-left">View Details</span>
                <span class="float-right">
                  <i class="fas fa-angle-right"></i>
                </span>
              </a>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6 mb-3">
            <div class="card text-white bg-danger o-hidden h-100">
              <div class="card-body">
                <div class="card-body-icon">
                  <i class='fas fa-map-marker-alt' style="margin-right:15px;"></i>
                </div>
                <?php
                  $result ="SELECT count(location) AS 'Total locations' FROM spaces";
                  $stmt = $mysqli->prepare($result);
                  $stmt->execute();
                  $stmt->bind_result($locations);
                  $stmt->fetch();
                  $stmt->close();
                ?>
                <div class="mr-5"><span class="badge badge-danger"><?php echo $locations;?></span> locations</div>
              </div>
              <a class="card-footer text-white clearfix small z-1" href="admin-view-location.php">
                <span class="float-left">View Details</span>
                <span class="float-right">
                  <i class="fas fa-angle-right"></i>
                </span>
              </a>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6 mb-3">
            <div class="card text-white bg-danger o-hidden h-100">
              <div class="card-body">
                <div class="card-body-icon">
                  <i class='far fa-building' style="margin-right:15px;"></i>
                </div>
                <?php
                  //code for summing up number of vehicles
                  $result ="SELECT count(*) FROM spaces";
                  $stmt = $mysqli->prepare($result);
                  $stmt->execute();
                  $stmt->bind_result($vehicle);
                  $stmt->fetch();
                  $stmt->close();
                ?>
                <div class="mr-5"><span class="badge badge-danger"><?php echo $vehicle;?></span> Spaces</div>
              </div>
              <a class="card-footer text-white clearfix small z-1" href="admin-view-spaces.php">
                <span class="float-left">View Details</span>
                <span class="float-right">
                  <i class="fas fa-angle-right"></i>
                </span>
              </a>
            </div>
          </div>
          <div class="col-xl-3 col-sm-6 mb-3">
            <div class="card text-white bg-danger o-hidden h-100">
              <div class="card-body">
                <div class="card-body-icon">
                  <i class="fas fa-calendar-check" style="margin-right:18px;"></i>
                </div>
                <?php
                  //code for summing up number of booking 
                  $result ="SELECT count(*) FROM bookings where status = 'pending' ";
                  $stmt = $mysqli->prepare($result);
                  $stmt->execute();
                  $stmt->bind_result($book);
                  $stmt->fetch();
                  $stmt->close();
                ?>
                <div class="mr-5"><span class="badge badge-danger"><?php echo $book;?></span> pending Bookings</div>
              </div>
              <a class="card-footer text-white clearfix small z-1" href="admin-view-booking.php">
                <span class="float-left">View Details</span>
                <span class="float-right">
                  <i class="fas fa-angle-right"></i>
                </span>
              </a>
            </div>
          </div>
        </div>
        <!--Bookings-->
        <div class="card mb-3">
          <div class="card-header">
            <i class="fas fa-table"></i>
            Bookings</div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>user id</th>
                    <th>space id</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total price</th>
                    <th>Status</th>
                  </tr>
                </thead>
                
                <tbody>
                <?php
                $dbuser="root";
                $dbpass="";
                $host=(getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
                $db="cowork_db";
                $conn=new mysqli($host,$dbuser, $dbpass, $db);
    
                  $ret = "SELECT * FROM bookings WHERE status = 'pending' OR status = 'approved'";
                  $stmt = $conn->prepare($ret);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  $cnt = 1;
                  while ($row = $res->fetch_object()) {
                  ?>
                <tr>
                    <td><?php echo $cnt; ?></td>
                    <td><?php echo $row->user_id; ?></td>
                    <td><?php echo $row->space_id; ?></td>
                    <td><?php echo $row->start_date; ?></td>
                    <td><?php echo $row->end_date; ?></td>
                    <td><?php echo $row->total_price; ?></td>
                    <td>
                        <?php
                        if ($row->status == "pending") {
                            echo '<span class="badge badge-warning">' . $row->status . '</span>';
                        } else {
                            echo '<span class="badge badge-success">' . $row->status . '</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php $cnt++; } ?>

                  
                </tbody>
              </table>
            </div>
          </div>
          <div class="card-footer small text-muted">
            <?php
              date_default_timezone_set("Africa/Nairobi");
              echo "Generated : " . date("h:i:sa");
            ?> 
        </div>
        </div>
        
      </div>
      <!-- /.container-fluid -->

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

</body>

</html>
