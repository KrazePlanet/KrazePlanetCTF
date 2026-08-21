<?php
require_once 'config.php';
requireAdmin();
$page_title = "Grades";

$error   = "";
$success = "";

// Get students and courses
$students = mysqli_query($conn, "SELECT * FROM students WHERE status='active' ORDER BY name ASC");
$courses  = mysqli_query($conn, "SELECT * FROM courses ORDER BY name ASC");

// Handle Add Grade
if (isset($_POST['add_grade'])) {
    $student_id = (int) $_POST['student_id'];
    $course_id  = (int) $_POST['course_id'];
    $marks      = (float) $_POST['marks'];
    $semester   = sanitize($_POST['semester'] ?? '');
    $grade      = calculateGrade($marks);

    if (empty($student_id) || empty($course_id) || $marks < 0 || $marks > 100) {
        $error = "Please fill all fields correctly. Marks must be between 0-100.";
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO grades (student_id, course_id, marks, grade, semester) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iidss", $student_id, $course_id, $marks, $grade, $semester);
        if (mysqli_stmt_execute($stmt)) {
            // Get student name for log
            $sname = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM students WHERE id = $student_id"))['name'];
            logActivity($conn, "Added grade for: $sname — $marks marks ($grade)");
            $success = "Grade added successfully!";
        } else {
            $error = "Something went wrong.";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM grades WHERE id = $id");
    logActivity($conn, "Deleted a grade record");
    header('Location: grades.php?success=deleted');
    exit();
}

// Get all grades
$grades = mysqli_query($conn, "
    SELECT g.*, s.name as student_name, s.student_id as sid, c.name as course_name
    FROM grades g
    JOIN students s ON g.student_id = s.id
    JOIN courses c ON g.course_id = c.id
    ORDER BY g.created_at DESC
");

// Stats
$avg_marks    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(marks) as avg FROM grades"))['avg'];
$total_grades = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM grades"))['count'];
$pass_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM grades WHERE marks >= 50"))['count'];
$fail_count   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM grades WHERE marks < 50"))['count'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>📝 Grades</h1>
            <p>Manage student grades and marks</p>
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
        <div class="alert alert-success">✅ Grade deleted successfully!</div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card orange">
            <div class="stat-icon orange">📝</div>
            <div class="stat-info">
                <h3><?php echo $total_grades; ?></h3>
                <p>Total Grades</p>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon blue">📊</div>
            <div class="stat-info">
                <h3><?php echo number_format($avg_marks ?? 0, 1); ?>%</h3>
                <p>Average Marks</p>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?php echo $pass_count; ?></h3>
                <p>Passed</p>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon red">❌</div>
            <div class="stat-info">
                <h3><?php echo $fail_count; ?></h3>
                <p>Failed</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:24px;" class="responsive-grid">

        <!-- Add Grade Form -->
        <div class="card">
            <div class="card-header">
                <h2>➕ Add Grade</h2>
            </div>
            <form method="POST" action="grades.php">
                <div class="form-group">
                    <label class="form-label">Student *</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">Select Student</option>
                        <?php while ($s = mysqli_fetch_assoc($students)): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['student_id']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Course *</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">Select Course</option>
                        <?php while ($c = mysqli_fetch_assoc($courses)): ?>
                            <option value="<?php echo $c['id']; ?>">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Marks (0-100) *</label>
                    <input type="number" name="marks" class="form-control"
                        placeholder="Enter marks" min="0" max="100" step="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Semester</label>
                    <select name="semester" class="form-control">
                        <option value="">Select Semester</option>
                        <option value="Semester 1">Semester 1</option>
                        <option value="Semester 2">Semester 2</option>
                        <option value="Semester 3">Semester 3</option>
                        <option value="Semester 4">Semester 4</option>
                        <option value="Final">Final</option>
                    </select>
                </div>

                <!-- Grade Scale -->
                <div style="background:var(--gray-light); border-radius:var(--radius); padding:12px; margin-bottom:15px;">
                    <p style="font-size:12px; font-weight:600; color:var(--gray); margin-bottom:8px;">GRADE SCALE</p>
                    <div style="display:flex; flex-wrap:wrap; gap:6px;">
                        <span class="badge badge-success">A+ (90-100)</span>
                        <span class="badge badge-success">A (80-89)</span>
                        <span class="badge badge-info">B (70-79)</span>
                        <span class="badge badge-warning">C (60-69)</span>
                        <span class="badge badge-warning">D (50-59)</span>
                        <span class="badge badge-danger">F (0-49)</span>
                    </div>
                </div>

                <button type="submit" name="add_grade" class="btn btn-primary btn-block">
                    ➕ Add Grade
                </button>
            </form>
        </div>

        <!-- Grades List -->
        <div class="card">
            <div class="card-header">
                <h2>📋 All Grades</h2>
                <span style="color:var(--gray); font-size:13px;"><?php echo $total_grades; ?> records</span>
            </div>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Marks</th>
                        <th>Grade</th>
                        <th>Semester</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($grades) > 0): ?>
                        <?php while ($g = mysqli_fetch_assoc($grades)): ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; font-size:13px;"><?php echo htmlspecialchars($g['student_name']); ?></div>
                                <div style="font-size:11px; color:var(--gray);"><?php echo htmlspecialchars($g['sid']); ?></div>
                            </td>
                            <td><span class="badge badge-orange"><?php echo htmlspecialchars($g['course_name']); ?></span></td>
                            <td><strong><?php echo number_format($g['marks'], 1); ?>%</strong></td>
                            <td>
                                <?php
                                $grade_badge = match($g['grade']) {
                                    'A+', 'A' => 'success',
                                    'B'       => 'info',
                                    'C', 'D'  => 'warning',
                                    default   => 'danger'
                                };
                                ?>
                                <span class="badge badge-<?php echo $grade_badge; ?>">
                                    <?php echo $g['grade']; ?>
                                </span>
                            </td>
                            <td style="font-size:12px;"><?php echo htmlspecialchars($g['semester'] ?? '—'); ?></td>
                            <td>
                                <a href="grades.php?delete=<?php echo $g['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this grade?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px; color:var(--gray);">
                                No grades yet. Add one!
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