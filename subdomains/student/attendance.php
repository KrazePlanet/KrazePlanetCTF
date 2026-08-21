<?php
require_once 'config.php';
requireAdmin();
$page_title = "Attendance";

$error   = "";
$success = "";

// Selected date
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get students
$students = mysqli_query($conn, "SELECT * FROM students WHERE status='active' ORDER BY name ASC");

// Handle Save Attendance
if (isset($_POST['save_attendance'])) {
    $date       = $_POST['date'] ?? date('Y-m-d');
    $attendance = $_POST['attendance'] ?? [];

    if (!empty($attendance)) {
        foreach ($attendance as $student_id => $status) {
            $student_id = (int) $student_id;
            $status     = in_array($status, ['present','absent','late']) ? $status : 'absent';

            // Check if already exists
            $check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM attendance WHERE student_id = $student_id AND date = '$date'"));

            if ($check) {
                mysqli_query($conn, "UPDATE attendance SET status = '$status' WHERE student_id = $student_id AND date = '$date'");
            } else {
                $stmt = mysqli_prepare($conn, "INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iss", $student_id, $date, $status);
                mysqli_stmt_execute($stmt);
            }
        }
        logActivity($conn, "Saved attendance for date: $date");
        $success = "Attendance saved for $date!";
        $selected_date = $date;
    }
}

// Get existing attendance for selected date
$existing = [];
$att_result = mysqli_query($conn, "SELECT student_id, status FROM attendance WHERE date = '$selected_date'");
while ($a = mysqli_fetch_assoc($att_result)) {
    $existing[$a['student_id']] = $a['status'];
}

// Attendance stats for selected date
$present_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date='$selected_date' AND status='present'"))['count'];
$absent_count  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date='$selected_date' AND status='absent'"))['count'];
$late_count    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE date='$selected_date' AND status='late'"))['count'];

// Overall stats
$total_present = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE status='present'"))['count'];
$total_absent  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE status='absent'"))['count'];

// Recent attendance history
$history = mysqli_query($conn, "
    SELECT a.date, a.status, s.name, s.student_id as sid
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    ORDER BY a.date DESC, s.name ASC
    LIMIT 20
");
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>📅 Attendance</h1>
            <p>Track student attendance</p>
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

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card orange">
            <div class="stat-icon orange">📅</div>
            <div class="stat-info">
                <h3><?php echo $present_count + $absent_count + $late_count; ?></h3>
                <p>Marked Today</p>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon green">✅</div>
            <div class="stat-info">
                <h3><?php echo $present_count; ?></h3>
                <p>Present Today</p>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon red">❌</div>
            <div class="stat-info">
                <h3><?php echo $absent_count; ?></h3>
                <p>Absent Today</p>
            </div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon blue">⏰</div>
            <div class="stat-info">
                <h3><?php echo $late_count; ?></h3>
                <p>Late Today</p>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:2fr 1fr; gap:24px;" class="responsive-grid">

        <!-- Take Attendance -->
        <div class="card">
            <div class="card-header">
                <h2>📋 Take Attendance</h2>
                <form method="GET" action="attendance.php" style="display:flex; gap:10px;">
                    <input type="date" name="date" class="form-control"
                        value="<?php echo $selected_date; ?>"
                        style="padding:6px 12px; font-size:13px;">
                    <button type="submit" class="btn btn-primary btn-sm">📅 Load</button>
                </form>
            </div>

            <form method="POST" action="attendance.php">
                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">

                <!-- Quick Actions -->
                <div style="display:flex; gap:10px; margin-bottom:15px; flex-wrap:wrap;">
                    <button type="button" class="btn btn-success btn-sm" onclick="markAll('present')">✅ Mark All Present</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="markAll('absent')">❌ Mark All Absent</button>
                </div>

                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        mysqli_data_seek($students, 0);
                        if (mysqli_num_rows($students) > 0):
                            while ($s = mysqli_fetch_assoc($students)):
                            $current_status = $existing[$s['id']] ?? '';
                        ?>
                        <tr>
                            <td>
                                <div class="student-cell">
                                    <div class="avatar-placeholder">
                                        <?php echo strtoupper(substr($s['name'], 0, 1)); ?>
                                    </div>
                                    <div class="student-cell-info">
                                        <div class="name"><?php echo htmlspecialchars($s['name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><code style="background:var(--gray-light); padding:2px 8px; border-radius:5px; font-size:12px;"><?php echo htmlspecialchars($s['student_id'] ?? 'N/A'); ?></code></td>
                            <td style="text-align:center;">
                                <input type="radio" name="attendance[<?php echo $s['id']; ?>]"
                                    value="present" class="att-radio"
                                    <?php echo $current_status === 'present' ? 'checked' : ''; ?>>
                            </td>
                            <td style="text-align:center;">
                                <input type="radio" name="attendance[<?php echo $s['id']; ?>]"
                                    value="absent" class="att-radio"
                                    <?php echo $current_status === 'absent' ? 'checked' : ''; ?>>
                            </td>
                            <td style="text-align:center;">
                                <input type="radio" name="attendance[<?php echo $s['id']; ?>]"
                                    value="late" class="att-radio"
                                    <?php echo $current_status === 'late' ? 'checked' : ''; ?>>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:var(--gray);">
                                No active students found.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>

                <?php if (mysqli_num_rows($students) > 0): ?>
                <div style="margin-top:15px;">
                    <button type="submit" name="save_attendance" class="btn btn-primary">
                        💾 Save Attendance for <?php echo date('M d, Y', strtotime($selected_date)); ?>
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Recent History -->
        <div class="card">
            <div class="card-header">
                <h2>📜 Recent History</h2>
            </div>
            <?php if (mysqli_num_rows($history) > 0): ?>
                <?php while ($h = mysqli_fetch_assoc($history)): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($h['name']); ?></div>
                        <div style="font-size:11px; color:var(--gray);"><?php echo date('M d, Y', strtotime($h['date'])); ?></div>
                    </div>
                    <span class="badge badge-<?php echo $h['status']; ?>">
                        <?php echo ucfirst($h['status']); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No attendance records yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function markAll(status) {
    document.querySelectorAll('.att-radio').forEach(radio => {
        if (radio.value === status) radio.checked = true;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>