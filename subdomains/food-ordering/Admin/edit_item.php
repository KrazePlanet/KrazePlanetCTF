<?php
include 'db_connection.php'; // Make sure to include your DB connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Debugging: Output all POST data
  echo "<pre>";
  print_r($_POST);
  echo "</pre>";
  
  // Retrieve form data
  $itemId = $_POST['itemId'];
  $itemName = $_POST['itemName'];
  $description = $_POST['description'];
  $price = $_POST['price'];
  $status = $_POST['status'];
  $catName = $_POST['catName'];
  $image = $_FILES['image']['name'] ? $_FILES['image']['name'] : $_POST['existingImage'];
  
  // Output debug information
  echo "Item ID: $itemId<br>";
  echo "Item Name: $itemName<br>";
  echo "Description: $description<br>";
  echo "Price: $price<br>";
  echo "Status: $status<br>";
  echo "Category Name: $catName<br>";
  echo "Image: $image<br>";
    
    // Handle file upload
    if ($_FILES['image']['name']) {
        $image = $_FILES['image']['name'];
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($image);
        
        // Check if image file is a valid image type
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // File uploaded successfully
            } else {
                // Handle upload error
                die("Error uploading file.");
            }
        } else {
            die("File is not an image.");
        }
    } else {
        $image = $_POST['existingImage']; // Use the existing image if no new image is uploaded
    }
    
    // Update query
    $sql = "UPDATE menuitem SET itemName=?, description=?, price=?, status=?, catName=?, image=? WHERE itemId=?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssssi", $itemName, $description, $price, $status, $catName, $image, $itemId);
        if ($stmt->execute()) {
            header("Location: admin_menu.php");
            exit();
        } else {
            echo "Error updating item: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing statement: " . $conn->error;
    }
    
    $conn->close();

    
}


?>