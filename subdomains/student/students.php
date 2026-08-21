<?php
require_once 'config.php';
requireAdmin();
$page_title = "Students";

// Search & Filter
$search  = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$filter  = isset($_GET['filter']) ? sanitize($_GET['filter']) : '';
$course  = isset($_GET['course']) ? sanitize($_GET['course']) : '';

// Pagination
$per_page = 10;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

$where = "WHERE 1=1";
if (!empty($search)) $where .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR student_id LIKE '%$search%')";
if (!empty($filter)) $where .= " AND status = '$filter'";
if (!empty($course)) $where .= " AND course = '$course'";

// Export CSV
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $export = mysqli_query($conn, "SELECT * FROM students $where ORDER BY created_at DESC");
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Student ID', 'Name', 'Email', 'Phone', 'Course', 'Status', 'Date Added']);
    while ($row = mysqli_fetch_assoc($export)) {
        fputcsv($out, [$row['id'], $row['student_id'], $row['name'], $row['email'], $row['phone'], $row['course'], $row['status'], $row['created_at']]);
    }
    fclose($out);
    exit();
}

// Bulk Delete
if (isset($_POST['bulk_delete']) && !empty($_POST['selected'])) {
    $ids     = array_map('intval', $_POST['selected']);
    $ids_str = implode(',', $ids);
    mysqli_query($conn, "DELETE FROM students WHERE id IN ($ids_str)");
    logActivity($conn, "Bulk deleted " . count($ids) . " students");
    header('Location: students.php?success=deleted');
    exit();
}

$total_rows  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students $where"))['count'];
$total_pages = ceil($total_rows / $per_page);
$students    = mysqli_query($conn, "SELECT * FROM students $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$courses     = mysqli_query($conn, "SELECT DISTINCT course FROM students ORDER BY course ASC");
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>👨‍🎓 Students</h1>
            <p><?php echo $total_rows; ?> students found</p>
        </div>
        <div class="topbar-right">
            <a href="students.php?export=csv&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>&course=<?php echo urlencode($course); ?>" class="btn btn-success">📥 Export CSV</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print</button>
            <a href="<?php echo APP_URL; ?>/add.php" class="btn btn-primary">➕ Add Student</a>
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Student <?php echo $_GET['success']; ?> successfully!</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>All Students</h2>
            <span style="color:var(--gray); font-size:13px;"><?php echo $total_rows; ?> total</span>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="students.php" style="display:flex; gap:10px; flex:1; flex-wrap:wrap;">
                <div class="search-box">
                    <input type="text" name="search" placeholder="Search by name, email or ID..."
                        value="<?php echo $search; ?>">
                </div>
                <select name="filter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="active"    <?php echo $filter=='active'    ? 'selected':''; ?>>Active</option>
                    <option value="inactive"  <?php echo $filter=='inactive'  ? 'selected':''; ?>>Inactive</option>
                    <option value="graduated" <?php echo $filter=='graduated' ? 'selected':''; ?>>Graduated</option>
                </select>
                <select name="course" class="filter-select">
                    <option value="">All Courses</option>
                    <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                        <option value="<?php echo htmlspecialchars($c['course']); ?>"
                            <?php echo $course == $c['course'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" class="btn btn-primary">🔍 Filter</button>
                <?php if (!empty($search) || !empty($filter) || !empty($course)): ?>
                    <a href="students.php" class="btn btn-secondary">✖ Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Bulk Form -->
        <form method="POST" action="students.php">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                    <label for="selectAll" style="font-size:13px; color:var(--gray);">Select All</label>
                </div>
                <button type="submit" name="bulk_delete" class="btn btn-danger btn-sm"
                    onclick="return confirmBulk()">🗑️ Delete Selected</button>
            </div>

            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th width="40"></th>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($students) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($students)): ?>
                        <tr>
                            <td><input type="checkbox" name="selected[]" value="<?php echo $s['id']; ?>" class="row-check"></td>
                            <td>
                                <div class="student-cell">
                                    <?php if (!empty($s['profile_image']) && file_exists(UPLOAD_PATH . $s['profile_image'])): ?>
                                        <img src="<?php echo UPLOAD_URL . $s['profile_image']; ?>" class="avatar-sm">
                                    <?php else: ?>
                                        <div class="avatar-placeholder">
                                            <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="student-cell-info">
                                        <div class="name"><?php echo htmlspecialchars($s['name']); ?></div>
                                        <div class="id"><?php echo htmlspecialchars($s['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code style="background:var(--gray-light); padding:2px 8px; border-radius:5px; font-size:12px;"><?php echo htmlspecialchars($s['student_id'] ?? 'N/A'); ?></code></td>
                            <td><span class="badge badge-orange"><?php echo htmlspecialchars($s['course']); ?></span></td>
                            <td style="font-size:13px;"><?php echo htmlspecialchars($s['phone'] ?? '—'); ?></td>
                            <td><span class="badge badge-<?php echo $s['status'] ?? 'active'; ?>"><?php echo ucfirst($s['status'] ?? 'active'); ?></span></td>
                            <td style="font-size:12px; color:var(--gray);"><?php echo date('M d, Y', strtotime($s['created_at'])); ?></td>
                            <td>
                            <a href="profile.php?id=<?php echo $s['id']; ?>" class="btn btn-info btn-sm">👤 View</a> 
                            <a href="edit.php?id=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                <a href="delete.php?id=<?php echo $s['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete <?php echo htmlspecialchars($s['name']); ?>?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px; color:var(--gray);">
                                No students found. <a href="add.php" style="color:var(--primary);">Add one!</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>" class="page-btn">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>"
                    class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo $filter; ?>" class="page-btn">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleAll(source) {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = source.checked);
}

function confirmBulk() {
    const checked = document.querySelectorAll('.row-check:checked');
    if (checked.length === 0) {
        alert('Please select at least one student.');
        return false;
    }
    return confirm('Delete ' + checked.length + ' selected students?');
}
</script>

<?php require_once 'includes/footer.php'; ?>