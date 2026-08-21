<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $itemName = $_POST['itemName'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $catName = $_POST['catName'];
    $dateCreated = date("Y-m-d H:i:s");
    $updatedDate = date("Y-m-d H:i:s");

    // File upload handling
    $target_dir = "../uploads/";
    $target_file = $target_dir . basename($_FILES["image"]["name"]);
    $uploadOk = 1;
    
    // Allow all file formats
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if the file is an actual image or fake image (if you still want this check)
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if ($check !== false) {
        // File is an image
        $uploadOk = 1;
    } else {
        // Remove the image check if you want to accept any file
        // echo "File is not an image.";
        // $uploadOk = 0;
    }

    // Check file size (you can set your own limit here if needed)
    if ($_FILES["image"]["size"] > 50000000) { // Set to a large value or remove the check
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
    // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // File uploaded successfully, proceed with database insertion
            $image = $_FILES["image"]["name"];
            
            // Use prepared statements to prevent SQL injection
            $stmt = $conn->prepare("INSERT INTO menuitem (itemName, price, description, image, status, catName, dateCreated, updatedDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $itemName, $price, $description, $image, $status, $catName, $dateCreated, $updatedDate);

            if ($stmt->execute()) {
                echo '<script>alert("New item added successfully."); window.location.href="admin_menu.php";</script>';
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }

    $conn->close();
    
    // Redirect to admin_menu.php after processing
    header("Location: admin_menu.php");
    exit();
}
?>
