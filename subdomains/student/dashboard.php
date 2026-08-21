<?php
require_once 'config.php';
requireAdmin();
$page_title = "Dashboard";

// Stats
$total_students  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students"))['count'];
$active_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM students WHERE status='active'"))['count'];
$total_courses   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];
$total_fees_due  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM fees WHERE status='unpaid' OR status='partial'"))['count'];

// Recent students
$recent_students = mysqli_query($conn, "SELECT * FROM students ORDER BY created_at DESC LIMIT 5");

// Recent activity
$recent_activity = mysqli_query($conn, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 8");

// Students per course
$course_stats = mysqli_query($conn, "
    SELECT c.name, COUNT(s.id) as total
    FROM courses c
    LEFT JOIN students s ON s.course = c.name
    GROUP BY c.name
    ORDER BY total DESC
    LIMIT 5
");

// Attendance stats
$present_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE status='present' AND date = CURDATE()"))['count'];
$absent_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE status='absent' AND date = CURDATE()"))['count'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>🏠 Dashboard</h1>
            <p>Welcome to <?php echo APP_NAME; ?> — Student Management System</p>
        </div>
        <div class="topbar-right">
            <a href="<?php echo APP_URL; ?>/add.php" class="btn btn-primary">➕ Add Student</a>
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card orange">
            <div class="stat-icon orange">👨‍🎓</div>
            <div class="stat-info">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?php echo $active_students; ?></h3>
                <p>Active Students</p>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon blue">📚</div>
            <div class="stat-info">
                <h3><?php echo $total_courses; ?></h3>
                <p>Total Courses</p>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon red">💰</div>
            <div class="stat-info">
                <h3><?php echo $total_fees_due; ?></h3>
                <p>Fees Due</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;" class="responsive-grid">

        <!-- Recent Students -->
        <div class="card">
            <div class="card-header">
                <h2>👨‍🎓 Recent Students</h2>
                <a href="<?php echo APP_URL; ?>/students.php" class="btn btn-secondary btn-sm">View All</a>
            </div>
            <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($recent_students) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($recent_students)): ?>
                        <tr>
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
                                        <div class="id"><?php echo htmlspecialchars($s['student_id'] ?? 'N/A'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge badge-orange"><?php echo htmlspecialchars($s['course']); ?></span></td>
                            <td><span class="badge badge-<?php echo $s['status'] ?? 'active'; ?>"><?php echo ucfirst($s['status'] ?? 'active'); ?></span></td>
                            <td>
                                <a href="<?php echo APP_URL; ?>/edit.php?id=<?php echo $s['id']; ?>" class="btn btn-warning btn-sm">✏️</a>
                                <a href="<?php echo APP_URL; ?>/delete.php?id=<?php echo $s['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this student?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px; color:var(--gray);">
                                No students yet. <a href="add.php" style="color:var(--primary);">Add one!</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <!-- Today's Attendance -->
            <div class="card">
                <div class="card-header">
                    <h2>📅 Today's Attendance</h2>
                    <a href="<?php echo APP_URL; ?>/attendance.php" class="btn btn-secondary btn-sm">Manage</a>
                </div>
                <div style="display:flex; gap:15px; justify-content:center; padding:10px 0;">
                    <div style="text-align:center;">
                        <div style="font-size:32px; font-weight:700; color:var(--success);"><?php echo $present_count; ?></div>
                        <div style="font-size:12px; color:var(--gray);">Present</div>
                    </div>
                    <div style="width:1px; background:var(--border);"></div>
                    <div style="text-align:center;">
                        <div style="font-size:32px; font-weight:700; color:var(--danger);"><?php echo $absent_count; ?></div>
                        <div style="font-size:12px; color:var(--gray);">Absent</div>
                    </div>
                </div>
            </div>

            <!-- Course Stats -->
            <div class="card">
                <div class="card-header">
                    <h2>📚 Students per Course</h2>
                </div>
                <?php while ($c = mysqli_fetch_assoc($course_stats)): ?>
                <div style="margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                        <span style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($c['name']); ?></span>
                        <span style="font-size:12px; color:var(--gray);"><?php echo $c['total']; ?> students</span>
                    </div>
                    <div style="background:var(--border); border-radius:10px; height:6px;">
                        <div style="width:<?php echo $total_students > 0 ? ($c['total']/$total_students)*100 : 0; ?>%; background:var(--primary); border-radius:10px; height:6px;"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">
            <h2>📜 Recent Activity</h2>
            <a href="<?php echo APP_URL; ?>/activity.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent_activity) > 0): ?>
                    <?php while ($a = mysqli_fetch_assoc($recent_activity)): ?>
                    <tr>
                        <td><?php echo $a['id']; ?></td>
                        <td><?php echo htmlspecialchars($a['action']); ?></td>
                        <td style="color:var(--gray); font-size:12px;"><?php echo timeAgo($a['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" style="text-align:center; padding:20px; color:var(--gray);">No activity yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>