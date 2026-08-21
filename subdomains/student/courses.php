<?php
require_once 'config.php';
requireAdmin();
$page_title = "Courses";

$error   = "";
$success = "";

// Handle Add
if (isset($_POST['add_course'])) {
    $name        = sanitize($_POST['name'] ?? '');
    $code        = sanitize($_POST['code'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Course name is required.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO courses (name, code, description) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "sss", $name, $code, $description);
        if (mysqli_stmt_execute($stmt)) {
            logActivity($conn, "Added new course: $name");
            $success = "Course added successfully!";
        } else {
            $error = "Course name already exists.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id  = (int) $_GET['delete'];
    $cat = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM courses WHERE id = $id"))['name'];
    $has = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE course = '$cat'"))['count'];
    if ($has > 0) {
        $error = "Cannot delete — $has students enrolled in this course!";
    } else {
        mysqli_query($conn, "DELETE FROM courses WHERE id = $id");
        logActivity($conn, "Deleted course: $cat");
        header('Location: courses.php?success=deleted');
        exit();
    }
}

// Get all courses
$courses = mysqli_query($conn, "
    SELECT c.*, COUNT(s.id) as total_students
    FROM courses c
    LEFT JOIN students s ON s.course = c.name
    GROUP BY c.id
    ORDER BY c.name ASC
");
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>📚 Courses</h1>
            <p>Manage available courses</p>
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
        <div class="alert alert-success">✅ Course deleted successfully!</div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- Add Course -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Add Course</h2>
            </div>
            <form method="POST" action="courses.php">
                <div class="form-group">
                    <label class="form-label">Course Name *</label>
                    <input type="text" name="name" class="form-control"
                        placeholder="e.g. Web Development" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Course Code</label>
                    <input type="text" name="code" class="form-control"
                        placeholder="e.g. WD-101">
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                        placeholder="Enter course description"></textarea>
                </div>
                <button type="submit" name="add_course" class="btn btn-primary btn-block">
                    ➕ Add Course
                </button>
            </form>
        </div>

        <!-- Courses List -->
        <div class="card">
            <div class="card-header">
                <h2>📋 All Courses</h2>
                <span style="color:var(--gray); font-size:13px;">
                    <?php echo mysqli_num_rows($courses); ?> courses
                </span>
            </div>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course Name</th>
                        <th>Code</th>
                        <th>Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($courses) > 0): ?>
                        <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($c['name']); ?></strong>
                                <?php if (!empty($c['description'])): ?>
                                    <div style="font-size:11px; color:var(--gray);">
                                        <?php echo htmlspecialchars(substr($c['description'], 0, 50)); ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="background:var(--gray-light); padding:2px 8px; border-radius:5px; font-size:12px;">
                                    <?php echo htmlspecialchars($c['code'] ?? 'N/A'); ?>
                                </code>
                            </td>
                            <td>
                                <span class="badge badge-orange">
                                    <?php echo $c['total_students']; ?> students
                                </span>
                            </td>
                            <td>
                                <a href="courses.php?delete=<?php echo $c['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete <?php echo htmlspecialchars($c['name']); ?>?')">
                                    🗑️ Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:var(--gray);">
                                No courses yet. Add one!
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