<?php
session_start();
if (!isset($_SESSION['adminloggedin'])) {
    header("Location: ../login.php");
    exit();
}
include 'db_connection.php';

$search = '';
if (isset($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
}

$sql = "SELECT * FROM staff";
if (!empty($search)) {
    $sql .= " WHERE id LIKE '%$search%' OR email LIKE '%$search%' OR firstName LIKE '%$search%' OR lastName LIKE '%$search%' OR role LIKE '%$search%'";
}
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $passwordMasked = str_repeat('*', strlen($row['password']));
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['createdAt']}</td>
                <td>{$row['email']}</td>
                <td>{$row['firstName']}</td>
                <td>{$row['lastName']}</td>
                <td>{$row['contact']}</td>
                <td>{$row['role']}</td>
                <td>
                    <span class='password-masked'>{$passwordMasked}</span>
                    <span class='password-visible' style='display: none;'>{$row['password']}</span>
                    <i class='fas fa-eye toggle-password' onclick='togglePassword(this)'></i>
                </td>
                <td>
                    <button id='editbtn' onclick='openEditUserModal(this)' data-email='{$row['email']}' data-firstname='{$row['firstName']}' data-lastname='{$row['lastName']}' data-contact='{$row['contact']}' data-role='{$row['role']}' data-password='{$row['password']}'><i class='fas fa-edit'></i></button>
                    <button id='deletebtn' onclick=\'deleteItem('{$row['email']}')\'><i class='fas fa-trash'></i></button>
                </td>
              </tr>";
       
    }
} else {
    echo "<tr><td colspan='9' style='text-align: center;'>No Staffs Found</td></tr>";
}

$conn->close();
?>
