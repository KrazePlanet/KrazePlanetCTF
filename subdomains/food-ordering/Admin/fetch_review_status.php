<?php
// Include the database connection
include 'db_connection.php';

// Get the status filter from the request
$statusFilter = isset($_POST['status']) ? $_POST['status'] : '';

// Prepare the SQL query based on the status filter
if ($statusFilter) {
    $sql = "SELECT * FROM reviews WHERE status = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $statusFilter);
} else {
    $sql = "SELECT * FROM reviews";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

// Output the results as HTML
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Convert rating to stars
        $ratingStars = str_repeat('&#9733;', $row['rating']) . str_repeat('&#9734;', 5 - $row['rating']);

        echo "<tr>
                <td>{$row['order_id']}</td>
                <td>{$row['email']}</td>
                <td>{$row['review_text']}</td>
                <td class='rating-stars'>{$ratingStars}</td>
                <td>
                    <select id='status-{$row['order_id']}' onchange='updateStatus({$row['order_id']}, this.value)' class='status-select'>
                        <option value='pending' " . ($row['status'] == 'pending' ? 'selected' : '') . ">Pending</option>
                        <option value='approved' " . ($row['status'] == 'approved' ? 'selected' : '') . ">Approved</option>
                        <option value='rejected' " . ($row['status'] == 'rejected' ? 'selected' : '') . ">Rejected</option>
                    </select>
                </td>
                <td>{$row['response']}</td>
                <td>
                    <button id='editbtn' onclick='openEditReviewModal(this)' data-id='{$row['order_id']}' data-email='{$row['email']}' data-review_text='{$row['review_text']}' data-rating='{$row['rating']}' data-response='{$row['response']}'><i class='fas fa-edit'></i></button>
                    <button id='deletebtn' onclick=\"deleteReview('{$row['order_id']}', '{$row['email']}')\"><i class='fas fa-trash'></i></button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' style='text-align: center;'>No Reviews</td></tr>";
}

// Close the database connection
$stmt->close();
$conn->close();
?>
