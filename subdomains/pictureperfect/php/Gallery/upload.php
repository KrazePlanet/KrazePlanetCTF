<?php 
require "../registration/dbcon.php";
if (isset($_FILES['my_image'])) {
	// Loop through all uploaded files
	for ($i = 0; $i < count($_FILES['my_image']['name']); $i++) {
		$img_name = $_FILES['my_image']['name'][$i];
		$img_size = $_FILES['my_image']['size'][$i];
		$tmp_name = $_FILES['my_image']['tmp_name'][$i];
		$error = $_FILES['my_image']['error'][$i];

		if ($error === 0) {
			if ($img_size > 1250000000) {
				$em = "Sorry, your file is too large.";
			    header("Location:../admin/fileupload.php?error=$em");
			}else {
				$img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
				$img_ex_lc = strtolower($img_ex);

				$allowed_exs = array("jpg", "jpeg", "png"); 

				if (in_array($img_ex_lc, $allowed_exs)) {
					$new_img_name = uniqid("IMG-", true).'.'.$img_ex_lc;
					$img_upload_path = '../../media/images/gallery/'.$new_img_name;
					move_uploaded_file($tmp_name, $img_upload_path);

					// Insert into Database
					$sql = "INSERT INTO gallery(image_url) 
					        VALUES('$new_img_name')";
					$result=mysqli_query($conn, $sql);
				}else {
					$em = "You can't upload files of this type";
			        header("Location:../admin/fileupload.php?error=$em");
				}
			}
		}
	}

	header("Location: view.php");
}else {
		$em = "unknown error occurred!";
		header("Location:../admin/fileupload.php?error=$em");
	}