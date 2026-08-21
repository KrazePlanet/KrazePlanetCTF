<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = $_SESSION['a_id'];

// Update Space
if (isset($_POST['update_space'])) {
    $id = $_GET['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $capacity = $_POST['capacity'];
    $price_per_day = $_POST['price_per_day'];
    $amenities = $_POST['amenities'];
    $location = $_POST['location'];

    // Check if an image is uploaded
    if ($_FILES["img"]["name"]) {
        $img = $_FILES["img"]["name"];
        move_uploaded_file($_FILES["img"]["tmp_name"], "vendor/upload-img/" . $_FILES["img"]["name"]);
        $query = "UPDATE spaces SET name=?, description=?, capacity=?, price_per_day=?, amenities=?, location=?, img=? WHERE id=?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param(
            'ssissssi',
            $name,
            $description,
            $capacity,
            $price_per_day,
            $amenities,
            $location,
            $img,
            $id
        );
    } else {
        $query = "UPDATE spaces SET name=?, description=?, capacity=?, price_per_day=?, amenities=?, location=? WHERE id=?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param(
            'ssisssi',
            $name,
            $description,
            $capacity,
            $price_per_day,
            $amenities,
            $location,
            $id
        );
    }

    if ($stmt->execute()) {
        $succ = "Space Updated";
    } else {
        $err = "Please Try Again Later";
    }
}

// Retrieve space details for pre-filling the form
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = "SELECT * FROM spaces WHERE id=?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $space = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<?php include('vendor/inc/head.php');?>

<body id="page-top">
    <!-- Start Navigation Bar -->
    <?php include("vendor/inc/nav.php");?>
    <!-- Navigation Bar -->

    <div id="wrapper">

        <!-- Sidebar -->
        <?php include("vendor/inc/sidebar.php");?>
        <!-- End Sidebar -->
        <div id="content-wrapper">

            <div class="container-fluid">
                <?php if (isset($succ)) {?>
                <!-- This code for injecting an alert -->
                <script>
                    setTimeout(function () {
                        swal("Success!", "<?php echo $succ;?>", "success");
                    }, 100);
                </script>
                <?php } ?>

                <?php if (isset($err)) {?>
                <!-- This code for injecting an alert -->
                <script>
                    setTimeout(function () {
                        swal("Failed!", "<?php echo $err;?>", "error");
                    }, 100);
                </script>
                <?php } ?>

                <!-- Breadcrumbs -->
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="#">Spaces</a>
                    </li>
                    <li class="breadcrumb-item active">Update Space</li>
                </ol>
                <hr>
                <div class="card">
                    <div class="card-header">
                        Update Space
                    </div>
                    <div class="card-body">
                        <!-- Update Space Form -->
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Space Name</label>
                            <input type="text" required class="form-control" id="exampleInputEmail1" name="name" value="<?php echo isset($space['name']) ? $space['name'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Description</label>
                            <textarea required class="form-control" name="description" id="exampleInputEmail1" rows="5"><?php echo isset($space['description']) ? $space['description'] : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Capacity</label>
                            <input type="number" required class="form-control" id="exampleInputEmail1" name="capacity" value="<?php echo isset($space['capacity']) ? $space['capacity'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Price per Day</label>
                            <input type="number" step="0.01" required class="form-control" id="exampleInputEmail1" name="price_per_day" value="<?php echo isset($space['price_per_day']) ? $space['price_per_day'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Amenities</label>
                            <input type="text" required class="form-control" id="exampleInputEmail1" name="amenities" value="<?php echo isset($space['amenities']) ? $space['amenities'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Location</label>
                            <input type="text" required class="form-control" id="exampleInputEmail1" name="location" value="<?php echo isset($space['location']) ? $space['location'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Image</label>
                            <input type="file" class="form-control" id="exampleInputEmail1" name="img">
                            <small class="form-text text-muted">Leave empty to keep the current image.</small>
                        </div>

                        <button type="submit" name="update_space" class="btn btn-success">Update Space</button>
                    </form>
                    <!-- End Form -->

                    </div>
                </div>

                <hr>

                <!-- Sticky Footer -->
                <?php include("vendor/inc/footer.php");?>

            </div>
            <!-- /.content-wrapper -->

        </div>
        <!-- /#wrapper -->

        <!-- Scroll to Top Button -->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <!-- Logout Modal -->
        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                        <button class="close" type="button" data-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">Select "Logout" below if you are ready
                        to end your current session.</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button"
                            data-dismiss="modal">Cancel</button>
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
        <!-- Inject Sweet alert js-->
        <script src="vendor/js/swal.js"></script>

    </body>

    </html>
