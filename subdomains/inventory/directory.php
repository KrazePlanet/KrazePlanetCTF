<?php
session_start();

$db_host = (getenv('DB_HOST') ?: (file_exists('/.dockerenv') ? 'krazeplanet' : '127.0.0.1'));
$db_user = "root";
$db_pass = "";
$db_name = "KrazePlanet_DB";

$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    die("Database connection error");
}

$search = $_GET['search'] ?? '';
$where = "";
if (!empty($search)) {
    $where = "WHERE roll_no LIKE '%$search%' OR student_name LIKE '%$search%' OR school_name LIKE '%$search%'";
}

$res = @mysqli_query($conn, "SELECT * FROM cbse_results $where ORDER BY id ASC");
$total_count = $res ? mysqli_num_rows($res) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBSE Examination 2026 — Student Records Directory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans', sans-serif; background-color: #f8fafc; color: #1e293b; }
        .top-nav { background: #ffffff; border-bottom: 1px solid #e2e8f0; padding: 14px 24px; }
        .table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); overflow: hidden; }
        .btn-test { background-color: #0066cc; color: white; font-weight: 600; font-size: 0.78rem; padding: 4px 10px; border-radius: 4px; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-test:hover { background-color: #004d99; color: white; }
    </style>
</head>
<body>

    <nav class="top-nav d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-cloud-lock2-fill fs-3 text-primary"></i>
            <div>
                <h6 class="m-0 fw-bold">DigiLocker / CBSE Results Database</h6>
                <small class="text-muted">Master Examination Records Directory (Class XII 2026)</small>
            </div>
        </div>
        <a href="index.php" class="btn btn-primary btn-sm fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Results Portal
        </a>
    </nav>

    <div class="container-fluid px-4 py-4">
        
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold m-0 text-dark">Hardcoded Student Records (<?php echo $total_count; ?> Available)</h4>
                <p class="text-muted small m-0">Click <strong>"Test in Portal"</strong> on any student to test query resolution or use these credentials for testing.</p>
            </div>
            
            <form method="GET" class="d-flex gap-2" style="max-width: 320px;">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Filter by Name, Roll No..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-sm btn-secondary">Search</button>
            </form>
        </div>

        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Roll Number</th>
                            <th>Candidate Name</th>
                            <th>Mother's Name</th>
                            <th>Father's Name</th>
                            <th>Admit Card ID</th>
                            <th>School No</th>
                            <th>School Name</th>
                            <th>Total %</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res && mysqli_num_rows($res) > 0): ?>
                            <?php $i = 1; while ($row = mysqli_fetch_assoc($res)): ?>
                                <tr>
                                    <td class="text-muted"><?php echo $i++; ?></td>
                                    <td><strong class="text-primary"><?php echo htmlspecialchars($row['roll_no']); ?></strong></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($row['student_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['mother_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['father_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($row['admit_card_id']); ?></code></td>
                                    <td><code><?php echo htmlspecialchars($row['school_no']); ?></code></td>
                                    <td class="text-muted" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($row['school_name']); ?></td>
                                    <td><span class="badge bg-success"><?php echo htmlspecialchars($row['total_percent']); ?>%</span></td>
                                    <td>
                                        <a href="index.php?roll_no=<?php echo urlencode($row['roll_no']); ?>&admit_card_id=<?php echo urlencode($row['admit_card_id']); ?>&school_no=<?php echo urlencode($row['school_no']); ?>&mother_name=<?php echo urlencode($row['mother_name']); ?>" class="btn-test">
                                            <i class="bi bi-box-arrow-in-right"></i> Test in Portal
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">No student records found matching your filter.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>
