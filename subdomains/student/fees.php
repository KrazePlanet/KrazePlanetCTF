<?php
require_once 'config.php';
requireAdmin();
$page_title = "Fees";

$error   = "";
$success = "";

// Get students
$students = mysqli_query($conn, "SELECT * FROM students WHERE status='active' ORDER BY name ASC");

// Handle Add Fee
if (isset($_POST['add_fee'])) {
    $student_id  = (int) $_POST['student_id'];
    $amount      = (float) $_POST['amount'];
    $paid_amount = (float) ($_POST['paid_amount'] ?? 0);
    $due_date    = $_POST['due_date'] ?? null;
    $description = sanitize($_POST['description'] ?? '');

    if (empty($student_id) || $amount <= 0) {
        $error = "Please select a student and enter a valid amount.";
    } else {
        $status = 'unpaid';
        if ($paid_amount >= $amount) $status = 'paid';
        elseif ($paid_amount > 0) $status = 'partial';

        $stmt = mysqli_prepare($conn, "INSERT INTO fees (student_id, amount, paid_amount, due_date, status, description) VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iddsss", $student_id, $amount, $paid_amount, $due_date, $status, $description);

        if (mysqli_stmt_execute($stmt)) {
            $sname = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM students WHERE id = $student_id"))['name'];
            logActivity($conn, "Added fee record for: $sname — $amount PKR");
            $success = "Fee record added successfully!";
        } else {
            $error = "Something went wrong.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM fees WHERE id = $id");
    logActivity($conn, "Deleted a fee record");
    header('Location: fees.php?success=deleted');
    exit();
}

// Handle Mark as Paid
if (isset($_GET['paid'])) {
    $id = (int) $_GET['paid'];
    $fee = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM fees WHERE id = $id"));
    if ($fee) {
        mysqli_query($conn, "UPDATE fees SET paid_amount = amount, status = 'paid' WHERE id = $id");
        logActivity($conn, "Marked fee as paid (ID: $id)");
        header('Location: fees.php?success=paid');
        exit();
    }
}

// Stats
$total_fees    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total FROM fees"))['total'];
$total_paid    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(paid_amount) as total FROM fees"))['total'];
$total_due     = ($total_fees ?? 0) - ($total_paid ?? 0);
$unpaid_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM fees WHERE status='unpaid'"))['count'];

// Get all fees
$fees = mysqli_query($conn, "
    SELECT f.*, s.name as student_name, s.student_id as sid
    FROM fees f
    JOIN students s ON f.student_id = s.id
    ORDER BY f.created_at DESC
");
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>💰 Fee Management</h1>
            <p>Track student fee payments</p>
        </div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Action completed successfully!</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card orange">
            <div class="stat-icon orange">💰</div>
            <div class="stat-info">
                <h3><?php echo number_format($total_fees ?? 0, 0); ?></h3>
                <p>Total Fees (PKR)</p>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?php echo number_format($total_paid ?? 0, 0); ?></h3>
                <p>Total Paid (PKR)</p>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon red">⚠️</div>
            <div class="stat-info">
                <h3><?php echo number_format($total_due ?? 0, 0); ?></h3>
                <p>Total Due (PKR)</p>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon blue">📋</div>
            <div class="stat-info">
                <h3><?php echo $unpaid_count; ?></h3>
                <p>Unpaid Records</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- Add Fee Form -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Add Fee Record</h2>
            </div>
            <form method="POST" action="fees.php">
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Select Student</option>
                        <?php while ($s = mysqli_fetch_assoc($students)): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Amount (PKR) *</label>
                    <input type="number" name="amount" class="form-control"
                        placeholder="e.g. 15000" min="0" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Paid Amount (PKR)</label>
                    <input type="number" name="paid_amount" class="form-control"
                        placeholder="e.g. 5000" min="0" step="0.01" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control"
                        placeholder="e.g. Semester 1 Fee">
                </div>
                <button type="submit" name="add_fee" class="btn btn-primary btn-block">
                    ➕ Add Fee Record
                </button>
            </form>
        </div>

        <!-- Fees List -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Fee Records</h2>
                <span style="color:var(--gray); font-size:13px;">
                    <?php echo mysqli_num_rows($fees); ?> records
                </span>
            </div>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($fees) > 0): ?>
                        <?php while ($f = mysqli_fetch_assoc($fees)): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; font-size:13px;"><?php echo htmlspecialchars($f['student_name']); ?></div>
                                <div style="font-size:11px; color:var(--gray);"><?php echo htmlspecialchars($f['description'] ?? ''); ?></div>
                            </td>
                            <td><strong><?php echo number_format($f['amount'], 0); ?> PKR</strong></td>
                            <td style="color:var(--success);"><?php echo number_format($f['paid_amount'], 0); ?> PKR</td>
                            <td style="color:var(--danger);"><?php echo number_format($f['amount'] - $f['paid_amount'], 0); ?> PKR</td>
                            <td><span class="badge badge-<?php echo $f['status']; ?>"><?php echo ucfirst($f['status']); ?></span></td>
                            <td style="font-size:12px; color:var(--gray);">
                                <?php echo $f['due_date'] ? date('M d, Y', strtotime($f['due_date'])) : '—'; ?>
                            </td>
                            <td>
                                <?php if ($f['status'] !== 'paid'): ?>
                                    <a href="fees.php?paid=<?php echo $f['id']; ?>"
                                        class="btn btn-success btn-sm"
                                        onclick="return confirm('Mark as fully paid?')">✅ Paid</a>
                                <?php endif; ?>
                                <a href="fees.php?delete=<?php echo $f['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this fee record?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:var(--gray);">
                                No fee records yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>