<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

if (isset($_POST['add_booking'])) {
    $user_id = $_POST['user_id'];
    $space_id = $_POST['space_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $total_price = $_POST['total_price'];
    $created_at = $_POST['created_at'];
    $status = $_POST['status'];

    // Check if the selected user and space exist in the respective tables
    $user_query = "SELECT id FROM users WHERE id = ?";
    $space_query = "SELECT id FROM spaces WHERE id = ?";
    
    $user_stmt = $mysqli->prepare($user_query);
    $space_stmt = $mysqli->prepare($space_query);

    $user_stmt->bind_param('i', $user_id);
    $space_stmt->bind_param('i', $space_id);

    $user_stmt->execute();
    $user_stmt->store_result();

    $space_stmt->execute();
    $space_stmt->store_result();

    if ($user_stmt->num_rows === 0) {
        $err = "Selected user does not exist.";
    } elseif ($space_stmt->num_rows === 0) {
        $err = "Selected space does not exist.";
    } else {
        // Insert the booking into the bookings table
        $query = "INSERT INTO bookings (user_id, space_id, start_date, end_date, total_price, created_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('iisssds', $user_id, $space_id, $start_date, $end_date, $total_price, $created_at, $status);
        $stmt->execute();

        if ($stmt) {
            $succ = "Booking Added Successfully";
        } else {
            $err = "Failed to Add Booking. Please Try Again Later";
        }
    }

    // Close the previous prepared statements
    $user_stmt->close();
    $space_stmt->close();
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
                        <a href="#">Bookings</a>
                    </li>
                    <li class="breadcrumb-item active">Add Booking</li>
                </ol>
                <hr>
                <div class="card">
                    <div class="card-header">
                        Add Booking
                    </div>
                    <div class="card-body">
                        <!--Add Space Form-->
                    <form method="POST">
                        <div class="form-group">
                                <label for="exampleFormControlSelect1">User</label>
                                <select class="form-control" name="user_id" id="exampleFormControlSelect1">
                                    <?php
                                    $user_query = "SELECT id, name FROM users";
                                    $user_result = $mysqli->query($user_query);

                                    if ($user_result->num_rows > 0) {
                                        while ($user_row = $user_result->fetch_assoc()) {
                                            echo '<option value="' . $user_row['id'] . '">' . $user_row['name'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="exampleFormControlSelect2">Space</label>
                                <select class="form-control" name="space_id" id="exampleFormControlSelect2">
                                    <?php
                                    $space_query = "SELECT id, name FROM spaces";
                                    $space_result = $mysqli->query($space_query);

                                    if ($space_result->num_rows > 0) {
                                        while ($space_row = $space_result->fetch_assoc()) {
                                            echo '<option value="' . $space_row['id'] . '">' . $space_row['name'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Start Date</label>
                                <input type="date" class="form-control" id="exampleInputEmail1" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">End Date</label>
                                <input type="date" class="form-control" id="exampleInputEmail1" name="end_date" required>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Total Price</label>
                                <input type="number" class="form-control" id="exampleInputEmail1" name="total_price" required>
                            </div>
                            <div class="form-group">
                                <label for="exampleInputEmail1">Created At</label>
                                <input type="date" class="form-control" id="exampleInputEmail1" name="created_at" required>
                            </div>
                            <div class="form-group">
                                <label for="exampleFormControlSelect1">Status</label>
                                <select class="form-control" name="status" id="exampleFormControlSelect1" required>
                                    <option value="pending">pending</option>
                                    <option value="approved">approved</option>
                                </select>
                            </div>
                            <button type="submit" name="add_booking" class="btn btn-success">Confirm Booking</button>
                        </form>
                        <!-- End Form-->
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
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
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