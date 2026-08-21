<?php
include 'config.php';
//error_reporting(0); // For not showing any error

session_start();
error_reporting(0);
$username = $_SESSION["username"];

$sql = "SELECT * FROM student_details WHERE email IN ('$username')";

function myfunction($a, $b)
{
    if ($a === $b) {
        return 0;
    }
    return ($a > $b) ? 1 : -1;
}


if (isset($_POST['submit'])) {
    $d_no  = $_POST['d_no'];
    $r_no  = $_POST['r_no'];
    $desc  = $_POST['desc'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // retrieve the selected values from the form data
        if ($_POST['selectedOptions'] != NULL) {
            $selectedOptions = $_POST['selectedOptions'];
            // process the selected values as needed
            var_dump($selectedOptions);
            $length = count($selectedOptions);
        }
    }
    if (is_uploaded_file($_FILES["file"]["tmp_name"]) && ($_FILES["file"]["type"] == 'application/pdf')) {
        #echo "file is valid";
        #retrieve file title
        $title = $_POST["title"];
        #file name with a random number so that similar dont get replaced
        $pname = $_FILES["file"]["name"];
        #temporary file name to store file
        $tname = $_FILES["file"]["tmp_name"];
        #upload directory path
        $uploads_dir = 'uploads';
        #TO move the uploaded file to specific location
        #sql query to insert into database
        for ($i = 0; $i < $length; $i++) {
            $sector  = $selectedOptions[$i];
            $unique_id = rand(100000, 999999);
            $check_file_sql = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no' and sector = '$sector'";
            #echo $check_file_sql;
            #echo "<br/>";
            $check_file_result = mysqli_query($link, $check_file_sql);
            $unique_name = $unique_id . $pname;
            if (mysqli_num_rows($check_file_result) > 0) {
                #    $check_file_row = mysqli_fetch_assoc($check_file_result);
                #   $file_path_name = $check_file_row['file'];
                #echo $file_path_name;
                #  $file_path = $uploads_dir . '/' . $file_path_name; // Replace with the actual file path
                #echo $file_path;
                # if (file_exists($file_path)) {
                #    if (unlink($file_path)) {
                #echo "File deleted successfully.";
                #   } else {
                #echo "Unable to delete the file.";
                #  }
                #  } else {
                #echo "File not found.";
                #echo "<br/>";
                # }
                #if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                #echo "Uploaded";
                #echo "<br/>";
                #     }
                #echo "<br/>";
                #    $sql = "UPDATE `whale_enterprises`.`software_model` SET revision_number = '$r_no' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                #   $result = mysqli_query($link, $sql);
                #echo "<br/>";
                #  $sql = "UPDATE `whale_enterprises`.`software_model` SET sector = '$sector' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                # $result = mysqli_query($link, $sql);
                #echo "<br/>";
                #$sql = "UPDATE `whale_enterprises`.`software_model` SET `file` = '$unique_name' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                #echo "<br/>";
                #$result = mysqli_query($link, $sql);
                #$sql = "UPDATE `whale_enterprises`.`software_model` SET `description` = '$desc' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                #if (mysqli_query($link, $sql)) {
                #            setcookie("status", "Successfully Updated!", time() + (7), "/");
                #echo "<center><p style='color:green';>Successfully Updated!</p></center>";
                #       } else {
                #          setcookie("status", "Update Failed!", time() + (7), "/");
                #echo "<center><p style='color:red';>Update Failed!</p></center>";
                #     }
                setcookie("status", "Drawing number already exists! Try Replacing.", time() + (7), "/");
            } else {
                $check_file_sql = "SELECT * FROM `whale_enterprises`.`software_model`";
                #   echo $check_file_sql;
                #  echo "<br/>";
                $check_file_result = mysqli_query($link, $check_file_sql);
                $temp = 0;
                # echo $unique_name;
                #echo "<br/>";
                while ($check_file_row = mysqli_fetch_assoc($check_file_result)) {
                    #     echo $check_file_row['file'];
                    #    echo "<br/>";
                    if (strval($check_file_row['file']) == strval($unique_name)) {
                        #       echo $unique_name;
                        $temp = 1;
                    }
                }
                if ($temp == 1) {
                    setcookie("status", "Update Failed! There is already a file with same name.", time() + (7), "/");
                    #  echo "<center><p style='color:red';>Update Failed! There is already a file with same name.</p></center>";
                } else {
                    $sql = "INSERT INTO `whale_enterprises`.`software_model`(`drawing_number`, `revision_number`, `description`, `file`, `sector`, `inserted_user`) VALUES ('$d_no', '$r_no', '$desc', '$unique_name', '$sector','$username')";
                    #  echo "fine";
                    # echo "<br/>";
                }
                echo $sql;
                if (mysqli_query($link, $sql)) {
                    if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                        #  echo "Uploaded";
                        # echo "<br/>";
                    }
                    setcookie("status", "Successfully Uploaded!", time() + (7), "/");
                    # echo "<center><p style='color:green';>Successfully Uploaded!</p></center>";
                } else {
                    $sql = "DELETE from `whale_enterprises`.`software_model` Where `drawing_number` = '$d_no'  and `revision_number` = '$r_no'";
                    $result = mysqli_query($link, $sql);
                    setcookie("status", "Action Failed! Please try again with proper Details.", time() + (7), "/");
                }
            }
            #  echo "loop over";
        }
    } else {
        #   echo "file is not valid type";
    }
    header('Location: index.php');
}
if (isset($_POST['replace_submit'])) {
    $upload_dir = './uploads';
    $d_no  = $_POST['d_no'];
    $r_no  = $_POST['r_no'];
    $selectedOptions = array();
    $get_sector_sql = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no'";
    $get_sector_result = mysqli_query($link, $get_sector_sql);
    if (mysqli_num_rows($get_sector_result) > 0) {
        while ($sector_row = mysqli_fetch_assoc($get_sector_result)) {
            array_push($selectedOptions, $sector_row['sector']);
        }
        $length = count($selectedOptions);
    } else {
        setcookie("status", "Replace Failed! There is no file found with the Drawing number: '" . $d_no . "'", time() + (7), "/");
        header('Location: index.php');
    }

    if (is_uploaded_file($_FILES["file"]["tmp_name"]) && ($_FILES["file"]["type"] == 'application/pdf')) {
        #echo "file is valid";
        #retrieve file title
        $title = $_POST["title"];
        #file name with a random number so that similar dont get replaced
        $pname = $_FILES["file"]["name"];
        #temporary file name to store file
        $tname = $_FILES["file"]["tmp_name"];
        #upload directory path
        $uploads_dir = 'uploads';
        #TO move the uploaded file to specific location
        #sql query to insert into database
        for ($i = 0; $i < $length; $i++) {
            $sector  = $selectedOptions[$i];
            $unique_id = rand(100000, 999999);
            $check_file_sql = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no' and sector = '$sector'";
            #    echo $check_file_sql;
            #    echo "<br/>";
            $check_file_result = mysqli_query($link, $check_file_sql);
            $unique_name = $unique_id . $pname;
            if (mysqli_num_rows($check_file_result) > 0) {
                $check_file_row = mysqli_fetch_assoc($check_file_result);
                $file_path_name = $check_file_row['file'];
                #   echo $file_path_name;
                $file_path = $uploads_dir . '/' . $file_path_name; // Replace with the actual file path
                #   echo $file_path;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        #          echo "File deleted successfully.";
                    } else {
                        #          echo "Unable to delete the file.";
                    }
                } else {
                    #       echo "File not found.";
                    #     echo "<br/>";
                }
                if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                    #    echo "Uploaded";
                    #   echo "<br/>";
                }
                #  echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET revision_number = '$r_no' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #  echo $sql;
                $result = mysqli_query($link, $sql);
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET `file` = '$unique_name' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                # echo "<br/>";
                #echo $sql;
                if (mysqli_query($link, $sql)) {
                    setcookie("status", "Successfully Updated!", time() + (7), "/");
                    #echo "<center><p style='color:green';>Successfully Updated!</p></center>";
                } else {
                    setcookie("status", "Update Failed!", time() + (7), "/");
                    #echo "<center><p style='color:red';>Update Failed!</p></center>";
                }
            } else {
                setcookie("status", "Replace Failed! There is no file found with the Drawing number", time() + (7), "/");
            }
        }
    } else {
        #echo "file is not valid type";
    }
    header('Location: index.php');
}
if (isset($_POST['delete-user'])) {
    $emp_username = $_POST['emp_username'];
    $sql = "DELETE FROM users WHERE username = '$emp_username'";
    if (mysqli_query($link, $sql)) {
        setcookie("status", "Successfully Deleted the Details of user : $emp_username", time() + (7), "/");
        #echo "<center><p style='color:green';>Successfully Deleted the Details of user : $emp_username</p></center>";
    } else {
        setcookie("status", "Deletion Failed for the user : $emp_username.", time() + (7), "/");
        #echo "<center><p style='color:red';>Deletion Failed for the user : $emp_username.</p></center>";
    }
    header('Location: useraccess.php');
}
if (isset($_POST['update-details'])) {
    $emp_name = $_POST['emp_name'];
    $emp_number = $_POST['emp_number'];
    $privilege = trim($_POST['privilege']);
    $emp_username = $_POST['emp_username'];
    $get_sector = array();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // retrieve the selected values from the form data
        if ($_POST['selectedOptions'] != NULL) {
            $selectedOptions = $_POST['selectedOptions'];
            // process the selected values as needed
            $length = count($selectedOptions);
        }
        if ($selectedOptions != NULL) {
            $sector_sql = "DELETE FROM `sectors` WHERE username = '$emp_username'";
            #echo $sector_sql;
            $sector_result = mysqli_query($link, $sector_sql);
            for ($i = 0; $i < $length; $i++) {
                $sql = "INSERT INTO `sectors` (username, sector) VALUES ('$emp_username', '$selectedOptions[$i]')";
                mysqli_query($link, $sql);
            }
        }
    } else {
        $get_sector_sql = "SELECT * FROM `sectors` WHERE username = '$emp_username'";
        $get_sector_result = mysqli_query($link, $get_sector_sql);
        if (mysqli_num_rows($get_sector_result) > 0) {
            while ($sector_row = mysqli_fetch_assoc($get_sector_result)) {
                array_push($get_sector, $sector_row['sector']);
            }
            $length = count($get_sector);
            $sector_sql = "DELETE FROM `sectors` WHERE username = '$emp_username'";
            #echo $sector_sql;
            $sector_result = mysqli_query($link, $sector_sql);
            for ($i = 0; $i < $length; $i++) {
                $sql = "INSERT INTO `sectors` (username, sector) VALUES ('$emp_username', '$get_sector[$i]')";
                mysqli_query($link, $sql);
            }
        }
    }

    if ($privilege != '') {
        $update_sql = "UPDATE `whale_enterprises`.`users` SET name = '$emp_name', phone_number = '$emp_number', designation = '$privilege' where username = '$emp_username'";
    } else {
        $update_sql = "UPDATE `whale_enterprises`.`users` SET name = '$emp_name', phone_number = '$emp_number' where username = '$emp_username'";
    }
    #echo $update_sql;
    if (mysqli_query($link, $update_sql)) {
        setcookie("status", "Successfully Updated the Details of user : $emp_username", time() + (7), "/");
        #echo "<center><p style='color:green';>Successfully Updated the Details of user : $emp_username</p></center>";
    } else {
        setcookie("status", "Update Failed for the user : $emp_username.", time() + (7), "/");
        #echo "<center><p style='color:red';>Update Failed for the user : $emp_username.</p></center>";
    }
    header('Location: useraccess.php');
}
if (isset($_POST['profile-update-details'])) {
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $sql = "UPDATE `whale_enterprises`.`users` SET name = '$name', phone_number = '$phone_number' where username = '$username'";
    #echo $sql;
    if (mysqli_query($link, $sql)) {
        setcookie("profile_status", "Successfully Updated the Details of user : $emp_username", time() + (7), "/");
        #echo "<center><p style='color:green';>Successfully Updated the Details.</p></center>";
    } else {
        setcookie("profile_status", "Update Failed for the user : $emp_username.", time() + (7), "/");
        #echo "<center><p style='color:red';>Update Failed.</p></center>";
    }
    header('Location: profile.php');
}

if (isset($_POST['delete-pdf-btn'])) {
    $file_name = $_POST['delete-pdf'];
    $id = $_POST['id'];
    $sector_sql = "SELECT * FROM `software_model` WHERE id = '" . $id . "'";
    $sector_result = mysqli_query($link, $sector_sql);
    while ($sector_row = mysqli_fetch_assoc($sector_result)) {
        $sector = $sector_row['sector'];
        $d_no = $sector_row['drawing_number'];
    }
    if ($file_name != "not_uploaded.pdf" && $sector != "Not Selected") {
        echo $filename;
        $path = "./uploads/";
        $file = $path . $file_name;
        echo $file;
        $status = unlink($file);
    }
    echo "File deleted successfully";
    $sql = "DELETE from `software_model` WHERE id = '" . $id . "'";
    echo $sql;
    if (mysqli_query($link, $sql)) {
        setcookie("status", "Successfully removed the drawing number '" . $d_no . "' from '" . $sector . "'", time() + (7), "/");
        echo "<center><p style='color:green';>Successfully removed the file : '" . $file_name . "'</p></center>";
    } else {
        setcookie("status", "Failed to remove the file : '" . $file_name . "'", time() + (7), "/");
        echo "<center><p style='color:red';>Failed to Delete the Data</p></center>";
    }
}
if (isset($_POST['update-pdf-details-file'])) {
    $d_no = $_POST['drawing_number'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // retrieve the selected values from the form data
        $loop_flag = 0;

        if ($_POST['selectedOptions']) {
            $selectedOptions = $_POST['selectedOptions'];
            // process the selected values as needed
            var_dump($selectedOptions);
            $loop_flag = 1;
            $length = count($selectedOptions);
        }
    }
    if ($loop_flag == 0) {
        setcookie("status", "Update Failed! Please select the sectors for the drawing number: '" . $d_no . "'", time() + (7), "/");
        header('Location: view-only.php');
    } else {
        $d_no = $_POST['drawing_number'];
        $r_no = $_POST['revision_number'];
        $desc = $_POST['description'];
        $id = $_POST['id'];
        $sector = $_POST['sector'];
        $check_file_sql = "SELECT * FROM `software_model` WHERE id = '$id' and sector = '$sector'";
        echo $check_file_sql;
        echo "<br/>";
        $check_file_result = mysqli_query($link, $check_file_sql);
        $unique_id = rand(100000, 999999);
        $file_retrive = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no'";
        $file_retrive_result = mysqli_query($link, $file_retrive);
        $file_retrive_row = mysqli_fetch_assoc($file_retrive_result);
        $pname = $file_retrive_row['file'];
        echo $pname;
        $unique_name = $unique_id . $pname;
        $uploads_dir = 'uploads';
        $tname = $uploads_dir . '/' . $pname;

        echo "<br/>";
        $sql = "DELETE FROM `software_model` WHERE id = '$id' and `sector` = 'Not Selected'";
        echo $sql;
        echo "<br/>";
        mysqli_query($link, $sql);

        for ($i = 0; $i < $length; $i++) {
            $sector  = $selectedOptions[$i];
            $unique_id = rand(100000, 999999);
            $check_file_sql = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no' and sector = '$sector'";
            #echo $check_file_sql;
            #echo "<br/>";
            #echo "file is valid";
            #file name with a random number so that similar dont get replaced
            $pname = $_FILES["file"]["name"];
            #temporary file name to store file
            $tname = $_FILES["file"]["tmp_name"];
            #upload directory path
            $uploads_dir = 'uploads';
            $check_file_result = mysqli_query($link, $check_file_sql);
            $unique_name = $unique_id . $pname;

            $targetDir = "uploads/";  // Directory where files will be uploaded
            $targetFile = $targetDir . basename($_FILES["fileToUpload"]["name"]);
            $uploadOk = 1;
            $pdfFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

            if (mysqli_num_rows($check_file_result) > 0) {
                $check_file_row = mysqli_fetch_assoc($check_file_result);
                $file_path_name = $check_file_row['file'];
                #echo $file_path_name;
                $file_path = $uploads_dir . '/' . $file_path_name; // Replace with the actual file path
                #echo $file_path;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        #echo "File deleted successfully.";
                    } else {
                        #echo "Unable to delete the file.";
                    }
                } else {
                    #echo "File not found.";
                    #echo "<br/>";
                }
                if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                    echo "Uploaded";
                    #echo "<br/>";
                }
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET revision_number = '$r_no' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                $result = mysqli_query($link, $sql);
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET sector = '$sector' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                $result = mysqli_query($link, $sql);
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET `file` = '$unique_name' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                #echo "<br/>";
                $result = mysqli_query($link, $sql);
                $sql = "UPDATE `whale_enterprises`.`software_model` SET `description` = '$desc' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                if (mysqli_query($link, $sql)) {
                    setcookie("status", "Successfully Updated!", time() + (7), "/");
                    #echo "<center><p style='color:green';>Successfully Updated!</p></center>";
                } else {
                    setcookie("status", "Update Failed!", time() + (7), "/");
                    #echo "<center><p style='color:red';>Update Failed!</p></center>";
                }
            } else {
                $check_file_sql = "SELECT * FROM `whale_enterprises`.`software_model`";
                #echo $check_file_sql;
                #echo "<br/>";
                $check_file_result = mysqli_query($link, $check_file_sql);
                $temp = 0;
                #echo $unique_name;
                #echo "<br/>";
                while ($check_file_row = mysqli_fetch_assoc($check_file_result)) {
                    #echo $check_file_row['file'];
                    #echo "<br/>";
                    if (strval($check_file_row['file']) == strval($unique_name)) {
                        #echo $unique_name;
                        $temp = 1;
                    }
                }
                if ($temp == 1) {
                    setcookie("status", "Update Failed! There is already a file with same name.", time() + (7), "/");
                    #echo "<center><p style='color:red';>Update Failed! There is already a file with same name.</p></center>";
                } else {
                    $sql = "INSERT INTO `whale_enterprises`.`software_model`(`drawing_number`, `revision_number`, `description`, `file`, `sector`, `inserted_user`) VALUES ('$d_no', '$r_no', '$desc', '$unique_name', '$sector','$username')";
                    #echo "fine";
                    #echo "<br/>";
                }
                echo $sql;
                if (mysqli_query($link, $sql)) {
                    if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                        echo "Uploaded";
                        #echo "<br/>";
                    }
                    setcookie("status", "Successfully Uploaded!", time() + (7), "/");
                    #echo "<center><p style='color:green';>Successfully Uploaded!</p></center>";
                }
            }
            #echo "loop over";
        }
    }
}

if (isset($_POST['update-pdf-details'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // retrieve the selected values from the form data
        $loop_flag = 0;

        if ($_POST['selectedOptions']) {
            $selectedOptions = $_POST['selectedOptions'];
            // process the selected values as needed
            var_dump($selectedOptions);
            $loop_flag = 1;
            $length = count($selectedOptions);
        }
    }
    if ($loop_flag == 0) {
        $d_no = $_POST['drawing_number'];
        $r_no = $_POST['revision_number'];
        $desc = $_POST['description'];
        $id = $_POST['id'];
        $sector = $_POST['sector'];
        $check_file_sql = "SELECT * FROM `software_model` WHERE id = '$id' and sector = '$sector'";
        echo $check_file_sql;
        echo "<br/>";
        $check_file_result = mysqli_query($link, $check_file_sql);
        $unique_name = $unique_id . $pname;
        if (mysqli_num_rows($check_file_result) > 0) {
            $sql = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no' and `sector` = '$sector' and id != '$id'";
            $result = mysqli_query($link, $sql);
            $row = mysqli_fetch_assoc($result);
            if (mysqli_num_rows($result) > 0) {
                $sql = "DELETE FROM `software_model` WHERE id = '" . $row['id'] . "' and `sector` = '$sector'";
                echo $sql;
                echo "<br/>";
                mysqli_query($link, $sql);
                $uploads_dir = 'uploads';
                $file_path_name = $row['file'];
                $file_path = $uploads_dir . '/' . $file_path_name; // Replace with the actual file path
                #echo $file_path;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        #echo "File deleted successfully.";
                    } else {
                        #echo "Unable to delete the file.";
                    }
                } else {
                    #echo "File not found.";
                    #echo "<br/>";
                }
            }
            $sql = "UPDATE `software_model` SET  drawing_number = '$d_no' WHERE id = '$id'";
            mysqli_query($link, $sql);
            echo $sql;
            echo "<br/>";
            $sql = "UPDATE `software_model` SET  revision_number = '$r_no' WHERE id = '$id'";
            mysqli_query($link, $sql);
            echo $sql;
            echo "<br/>";
            $sql = "UPDATE `software_model` SET  `description` = '$desc' WHERE id = '$id'";
            echo $sql;
            echo "<br/>";
            if (mysqli_query($link, $sql)) {
                setcookie("status", "Successfully Updated!", time() + (7), "/");
                #echo "<center><p style='color:green';>Successfully Updated!</p></center>";
            } else {
                setcookie("status", "Update Failed!", time() + (7), "/");
                #echo "<center><p style='color:red';>Update Failed!</p></center>";
            }
        } else {
            setcookie("status", "No file found!", time() + (7), "/");
        }
    } else {
        $d_no = $_POST['drawing_number'];
        $r_no = $_POST['revision_number'];
        $desc = $_POST['description'];
        $id = $_POST['id'];
        $sector = $_POST['sector'];
        $check_file_sql = "SELECT * FROM `software_model` WHERE id = '$id' and sector = '$sector'";
        echo $check_file_sql;
        echo "<br/>";
        $check_file_result = mysqli_query($link, $check_file_sql);
        $unique_id = rand(100000, 999999);
        $file_retrive = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no'";
        $file_retrive_result = mysqli_query($link, $file_retrive);
        $file_retrive_row = mysqli_fetch_assoc($file_retrive_result);
        $pname = $file_retrive_row['file'];
        echo $pname;
        $unique_name = $unique_id . $pname;
        $uploads_dir = 'uploads';
        $tname = $uploads_dir . '/' . $pname;

        echo "<br/>";
        $sql = "DELETE FROM `software_model` WHERE id = '$id' and `sector` = 'Not Selected'";
        echo $sql;
        echo "<br/>";
        mysqli_query($link, $sql);

        for ($i = 0; $i < $length; $i++) {
            $sector  = $selectedOptions[$i];
            $unique_id = rand(100000, 999999);
            $check_file_sql = "SELECT * FROM `software_model` WHERE drawing_number = '$d_no' and sector = '$sector'";
            #echo $check_file_sql;
            #echo "<br/>";
            $check_file_result = mysqli_query($link, $check_file_sql);
            $unique_name = $unique_id . $pname;
            if (mysqli_num_rows($check_file_result) > 0) {
                $check_file_row = mysqli_fetch_assoc($check_file_result);
                $file_path_name = $check_file_row['file'];
                #echo $file_path_name;
                $file_path = $uploads_dir . '/' . $file_path_name; // Replace with the actual file path
                #echo $file_path;
                if (file_exists($file_path)) {
                    if (unlink($file_path)) {
                        #echo "File deleted successfully.";
                    } else {
                        #echo "Unable to delete the file.";
                    }
                } else {
                    #echo "File not found.";
                    #echo "<br/>";
                }
                if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                    echo "Uploaded";
                    #echo "<br/>";
                }
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET revision_number = '$r_no' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                $result = mysqli_query($link, $sql);
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET sector = '$sector' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                $result = mysqli_query($link, $sql);
                #echo "<br/>";
                $sql = "UPDATE `whale_enterprises`.`software_model` SET `description` = '$desc' WHERE drawing_number = '$d_no' and `sector` = '$sector'";
                #echo $sql;
                if (mysqli_query($link, $sql)) {
                    setcookie("status", "Successfully Updated!", time() + (7), "/");
                    #echo "<center><p style='color:green';>Successfully Updated!</p></center>";
                } else {
                    setcookie("status", "Update Failed!", time() + (7), "/");
                    #echo "<center><p style='color:red';>Update Failed!</p></center>";
                }
            } else {
                $check_file_sql = "SELECT * FROM `whale_enterprises`.`software_model`";
                #echo $check_file_sql;
                #echo "<br/>";
                $check_file_result = mysqli_query($link, $check_file_sql);
                $temp = 0;
                #echo $unique_name;
                #echo "<br/>";
                while ($check_file_row = mysqli_fetch_assoc($check_file_result)) {
                    #echo $check_file_row['file'];
                    #echo "<br/>";
                    if (strval($check_file_row['file']) == strval($unique_name)) {
                        #echo $unique_name;
                        $temp = 1;
                    }
                }
                if ($temp == 1) {
                    setcookie("status", "Update Failed! There is already a file with same name.", time() + (7), "/");
                    #echo "<center><p style='color:red';>Update Failed! There is already a file with same name.</p></center>";
                } else {
                    $sql = "INSERT INTO `whale_enterprises`.`software_model`(`drawing_number`, `revision_number`, `description`, `sector`, `inserted_user`) VALUES ('$d_no', '$r_no', '$desc', '$sector','$username')";
                    #echo "fine";
                    #echo "<br/>";
                }
                echo $sql;
                if (mysqli_query($link, $sql)) {
                    if (copy($tname, $uploads_dir . '/' . $unique_name)) {
                        echo "Uploaded";
                        #echo "<br/>";
                    }
                    setcookie("status", "Successfully Uploaded!", time() + (7), "/");
                    #echo "<center><p style='color:green';>Successfully Uploaded!</p></center>";
                }
            }
            #echo "loop over";
        }
    }
}
header('Location: view-only.php');
