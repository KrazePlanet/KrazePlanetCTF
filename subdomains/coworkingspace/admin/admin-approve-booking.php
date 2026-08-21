<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Approve Booking
if (isset($_POST['approve_booking'])) {
    $booking_id = $_GET['booking_id'];
    $status = $_POST['status'];
    $query = "UPDATE bookings SET status=? WHERE id=?";

    // Check if the statement is prepared successfully
    if ($stmt = $mysqli->prepare($query)) {
        $stmt->bind_param('si', $status, $booking_id);

        // Execute the statement
        if ($stmt->execute()) {
            if ($status === 'approved') {
                $succ = "Booking Approved";
            } else {
                $err = "Booking is successfully approved";
            }
        } else {
            $err = "Please Try Again Later";
        }
    } else {
        $err = "Prepare statement failed: " . $mysqli->$err;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
    <!--Start Navigation Bar-->
    <?php include("vendor/inc/nav.php"); ?>
    <!--Navigation Bar-->

    <div id="wrapper">

        <!-- Sidebar -->
        <?php include("vendor/inc/sidebar.php"); ?>
        <!--End Sidebar-->
        <div id="content-wrapper">

            <div class="container-fluid">
            <?php if (isset($succ)) { ?>
                    <!--This code for injecting an alert-->
                    <script>
                        setTimeout(function() {
                            alert("<?php echo $succ; ?>");
                        }, 100);
                    </script>
                <?php } ?>
                <?php if (isset($err)) { ?>
                    <!--This code for injecting an alert-->
                    <script>
                        setTimeout(function() {
                            alert("<?php echo $err; ?>");
                        }, 100);
                    </script>
                <?php } ?>

                <!-- Breadcrumbs-->
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#">Bookings</a>
                    </li>
                    <li class="breadcrumb-item active">Approve</li>
                </ol>
                <hr>
                <div class="card">
                    <div class="card-header">
                        Approve Booking
                    </div>
                    <div class="card-body">
                        <!--Add User Form-->
                        <?php
                          // Assuming $_GET['booking_id'] contains the correct booking_id value
                          if (!isset($_GET['booking_id']) || !is_numeric($_GET['booking_id'])) {
                            die("Invalid booking ID.");
                          }

                          $booking_id = $_GET['booking_id'];

                          $ret = "SELECT b.*, u.name as user_name, s.name as space_name FROM bookings b
                                  INNER JOIN users u ON b.user_id = u.id
                                  INNER JOIN spaces s ON b.space_id = s.id
                                  WHERE b.id=?";
                          $stmt = $mysqli->prepare($ret);

                          // Check if the statement is prepared successfully
                          if (!$stmt) {
                            die("Prepare statement failed: " . $mysqli->$err);
                          }

                          // Bind the parameter to the prepared statement
                          $stmt->bind_param('i', $booking_id);

                          // Execute the statement
                          if (!$stmt->execute()) {
                            die("Execute statement failed: " . $stmt->$err);
                          }

                          $res = $stmt->get_result();
                          $cnt = 1;
                          while ($row = $res->fetch_object()) {
                            // ... (Your existing code) ...
                          
                        ?>
                            <form method="POST">
                                <div class="form-group">
                                    <label for="exampleInputEmail1">User Name</label>
                                    <input type="text" readonly value="<?php echo $row->user_name; ?>" required class="form-control" name="u_name">
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Space Name</label>
                                    <input type="text" readonly value="<?php echo $row->space_name; ?>" required class="form-control" name="s_name">
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Start date</label>
                                    <input type="text" readonly value="<?php echo $row->start_date; ?>" required class="form-control" name="start_date">
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">End Space</label>
                                    <input type="text" readonly value="<?php echo $row->end_date; ?>" required class="form-control" name="end_datef">
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Total price</label>
                                    <input type="email" readonly value="<?php echo $row->total_price; ?>" class="form-control" name="total_price">
                                </div>
                                <div class="form-group">
                                    <label for="exampleInputEmail1">Created At</label>
                                    <input type="email" readonly value="<?php echo $row->created_at; ?>" class="form-control" name="created_at">
                                </div>
                                <div class="form-group">
                                    <label for="exampleFormControlSelect1">Booking Status</label>
                                    <select class="form-control" name="status" id="exampleFormControlSelect1">
                                        <option <?php if ($row->status == 'available') echo 'selected'; ?>>Approved</option>
                                        <option <?php if ($row->status == 'reserved') echo 'selected'; ?>>Pending</option>
                                    </select>
                                </div>

                                <button type="submit" name="approve_booking" class="btn btn-success">Approve Booking</button>
                            </form>
                        <?php } ?>
                        <!-- End Form-->
                    </div>
                </div>
                <hr>
                <!-- Sticky Footer -->
                <?php include("vendor/inc/footer.php"); ?>

            </div>
            <!-- /.container-fluid -->

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
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin.min.js"></script>

    <!-- Demo scripts for this page-->
    <script src="js/demo/datatables-demo.js"></script>

</body>

</html>
