<?php
require_once 'config.php';
requireAdmin();

$page_title = "Student Profile";

if (!isset($_GET['id']) || empty($_GET['id'])) redirect('students.php');

$id   = (int) $_GET['id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) redirect('students.php');

// Performance
$attendance_pct = getAttendancePercentage($conn, $id);
$avg_marks      = getAverageMarks($conn, $id);
$fee_pct        = getFeePercentage($conn, $id);
$grade          = calculateGrade($avg_marks);

// Grades
$grades = mysqli_query($conn, "
    SELECT g.*, c.name as course_name
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = $id
    ORDER BY g.created_at DESC
");

// Attendance
$attendance = mysqli_query($conn, "
    SELECT * FROM attendance
    WHERE student_id = $id
    ORDER BY date DESC
    LIMIT 10
");

// Fees
$fees       = mysqli_query($conn, "SELECT * FROM fees WHERE student_id = $id ORDER BY created_at DESC");
$total_fees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, SUM(paid_amount) as paid FROM fees WHERE student_id = $id"));

// Attendance counts
$total_att   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $id"))['count'];
$present_att = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $id AND status='present'"))['count'];
$absent_att  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $id AND status='absent'"))['count'];
$late_att    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM attendance WHERE student_id = $id AND status='late'"))['count'];
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>👤 Student Profile</h1>
            <p>Complete academic overview</p>
        </div>
        <div class="topbar-right">
            <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-warning">✏️ Edit Student</a>
            <a href="students.php" class="btn btn-secondary">← Back</a>
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="card" style="margin-bottom:24px;">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <?php if (!empty($student['profile_image']) && file_exists(UPLOAD_PATH . $student['profile_image'])): ?>
                <img src="<?php echo UPLOAD_URL . $student['profile_image']; ?>"
                    style="width:100px; height:100px; border-radius:50%; object-fit:cover; border:4px solid var(--primary);">
            <?php else: ?>
                <div style="width:100px; height:100px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:40px; font-weight:700; flex-shrink:0;">
                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div style="flex:1;">
                <h2 style="font-size:22px; font-weight:700;"><?php echo htmlspecialchars($student['name']); ?></h2>
                <p style="color:var(--gray); font-size:13px; margin-top:3px;">📧 <?php echo htmlspecialchars($student['email']); ?></p>
                <p style="color:var(--gray); font-size:13px;">📱 <?php echo htmlspecialchars($student['phone'] ?? '—'); ?></p>
                <p style="color:var(--gray); font-size:13px;">📚 <?php echo htmlspecialchars($student['course']); ?></p>
                <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
                    <code style="background:var(--gray-light); padding:3px 10px; border-radius:5px; font-size:12px;"><?php echo htmlspecialchars($student['student_id'] ?? 'N/A'); ?></code>
                    <span class="badge badge-<?php echo $student['status'] ?? 'active'; ?>"><?php echo ucfirst($student['status'] ?? 'active'); ?></span>
                    <span class="badge badge-orange">Grade: <?php echo $grade; ?></span>
                    <?php if ($student['gender']): ?>
                        <span class="badge badge-info"><?php echo ucfirst($student['gender']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($student['address']): ?>
                    <p style="color:var(--gray); font-size:12px; margin-top:8px;">📍 <?php echo htmlspecialchars($student['address']); ?></p>
                <?php endif; ?>
            </div>
            <div style="text-align:right;">
                <div style="font-size:12px; color:var(--gray);">Joined: <?php echo date('M d, Y', strtotime($student['created_at'])); ?></div>
                <?php if ($student['date_of_birth']): ?>
                    <div style="font-size:12px; color:var(--gray); margin-top:3px;">DOB: <?php echo date('M d, Y', strtotime($student['date_of_birth'])); ?></div>
                <?php endif; ?>
                <?php if ($student['last_login']): ?>
                    <div style="font-size:12px; color:var(--gray); margin-top:3px;">Last Login: <?php echo timeAgo($student['last_login']); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Circular Charts -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h2>📊 Performance Overview</h2>
        </div>
        <div style="display:flex; justify-content:center; gap:40px; flex-wrap:wrap; padding:10px 0;">

            <!-- Attendance Circle -->
            <div style="text-align:center;">
                <div style="width:130px; height:130px; border-radius:50%; background:conic-gradient(#f97316 <?php echo $attendance_pct; ?>%, #e7e5e4 <?php echo $attendance_pct; ?>%); display:flex; align-items:center; justify-content:center; margin:0 auto 10px; position:relative;">
                    <div style="width:95px; height:95px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                        <div style="font-size:20px; font-weight:700; color:var(--dark);"><?php echo $attendance_pct; ?>%</div>
                        <div style="font-size:10px; color:var(--gray);">Present</div>
                    </div>
                </div>
                <div style="font-size:14px; font-weight:600;">Attendance</div>
                <div style="font-size:12px; color:var(--gray);"><?php echo $present_att; ?>/<?php echo $total_att; ?> days</div>
            </div>

            <!-- Grade Circle -->
            <div style="text-align:center;">
                <div style="width:130px; height:130px; border-radius:50%; background:conic-gradient(#059669 <?php echo $avg_marks; ?>%, #e7e5e4 <?php echo $avg_marks; ?>%); display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                    <div style="width:95px; height:95px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                        <div style="font-size:20px; font-weight:700; color:var(--dark);"><?php echo $avg_marks; ?>%</div>
                        <div style="font-size:10px; color:var(--gray);">Average</div>
                    </div>
                </div>
                <div style="font-size:14px; font-weight:600;">Academic</div>
                <div style="font-size:12px; color:var(--gray);">Grade: <?php echo $grade; ?></div>
            </div>

            <!-- Fee Circle -->
            <div style="text-align:center;">
                <div style="width:130px; height:130px; border-radius:50%; background:conic-gradient(#0284c7 <?php echo $fee_pct; ?>%, #e7e5e4 <?php echo $fee_pct; ?>%); display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                    <div style="width:95px; height:95px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                        <div style="font-size:20px; font-weight:700; color:var(--dark);"><?php echo $fee_pct; ?>%</div>
                        <div style="font-size:10px; color:var(--gray);">Paid</div>
                    </div>
                </div>
                <div style="font-size:14px; font-weight:600;">Fee Status</div>
                <div style="font-size:12px; color:var(--gray);"><?php echo $fee_pct >= 100 ? '✅ Cleared' : '⚠️ Due'; ?></div>
            </div>

            <!-- Attendance Breakdown -->
            <div style="text-align:center;">
                <div style="width:130px; height:130px; border-radius:50%; background:conic-gradient(
                    #059669 <?php echo $total_att > 0 ? ($present_att/$total_att)*100 : 0; ?>%,
                    #dc2626 <?php echo $total_att > 0 ? ($present_att/$total_att)*100 : 0; ?>% <?php echo $total_att > 0 ? (($present_att+$absent_att)/$total_att)*100 : 0; ?>%,
                    #d97706 <?php echo $total_att > 0 ? (($present_att+$absent_att)/$total_att)*100 : 0; ?>% 100%
                ); display:flex; align-items:center; justify-content:center; margin:0 auto 10px;">
                    <div style="width:95px; height:95px; background:white; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-direction:column;">
                        <div style="font-size:20px; font-weight:700; color:var(--dark);"><?php echo $total_att; ?></div>
                        <div style="font-size:10px; color:var(--gray);">Total Days</div>
                    </div>
                </div>
                <div style="font-size:14px; font-weight:600;">Breakdown</div>
                <div style="font-size:11px; color:var(--gray);">
                    <span style="color:#059669;">●</span> <?php echo $present_att; ?> Present
                    <span style="color:#dc2626; margin-left:5px;">●</span> <?php echo $absent_att; ?> Absent
                    <span style="color:#d97706; margin-left:5px;">●</span> <?php echo $late_att; ?> Late
                </div>
            </div>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;" class="responsive-grid">

        <!-- Grades -->
        <div class="card">
            <div class="card-header">
                <h2>📝 Grades</h2>
            </div>
            <?php if (mysqli_num_rows($grades) > 0): ?>
                <?php while ($g = mysqli_fetch_assoc($grades)): ?>
                <div style="margin-bottom:15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <span style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($g['course_name']); ?></span>
                        <span style="font-size:13px; font-weight:700;">
                            <?php echo number_format($g['marks'], 1); ?>%
                            <span class="badge badge-<?php echo match($g['grade']) {
                                'A+', 'A' => 'success',
                                'B'       => 'info',
                                'C', 'D'  => 'warning',
                                default   => 'danger'
                            }; ?>" style="margin-left:5px;"><?php echo $g['grade']; ?></span>
                        </span>
                    </div>
                    <div style="background:var(--border); border-radius:10px; height:8px;">
                        <div style="width:<?php echo $g['marks']; ?>%; background:<?php echo match(true) {
                            $g['marks'] >= 80 => '#059669',
                            $g['marks'] >= 60 => '#0284c7',
                            $g['marks'] >= 50 => '#d97706',
                            default           => '#dc2626'
                        }; ?>; border-radius:10px; height:8px;"></div>
                    </div>
                    <?php if ($g['semester']): ?>
                        <div style="font-size:11px; color:var(--gray); margin-top:3px;"><?php echo htmlspecialchars($g['semester']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No grades yet.</p>
            <?php endif; ?>
        </div>

        <!-- Attendance & Fees -->
        <div>
            <!-- Attendance -->
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header">
                    <h2>📅 Recent Attendance</h2>
                </div>
                <?php if (mysqli_num_rows($attendance) > 0): ?>
                    <?php while ($a = mysqli_fetch_assoc($attendance)): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                        <div style="font-size:13px;"><?php echo date('M d, Y', strtotime($a['date'])); ?></div>
                        <span class="badge badge-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--gray); text-align:center; padding:20px;">No attendance yet.</p>
                <?php endif; ?>
            </div>

            <!-- Fees -->
            <div class="card">
                <div class="card-header">
                    <h2>💰 Fee Records</h2>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px; background:var(--gray-light); border-radius:var(--radius); margin-bottom:15px;">
                    <div style="text-align:center;">
                        <div style="font-weight:700;"><?php echo number_format($total_fees['total'] ?? 0, 0); ?></div>
                        <div style="font-size:11px; color:var(--gray);">Total</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-weight:700; color:var(--success);"><?php echo number_format($total_fees['paid'] ?? 0, 0); ?></div>
                        <div style="font-size:11px; color:var(--gray);">Paid</div>
                    </div>
                    <div style="text-align:center;">
                        <div style="font-weight:700; color:var(--danger);"><?php echo number_format(($total_fees['total'] ?? 0) - ($total_fees['paid'] ?? 0), 0); ?></div>
                        <div style="font-size:11px; color:var(--gray);">Due</div>
                    </div>
                </div>
                <?php if (mysqli_num_rows($fees) > 0): ?>
                    <?php while ($f = mysqli_fetch_assoc($fees)): ?>
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border);">
                        <div>
                            <div style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($f['description'] ?? 'Fee'); ?></div>
                            <div style="font-size:11px; color:var(--gray);"><?php echo number_format($f['amount'], 0); ?> PKR</div>
                        </div>
                        <span class="badge badge-<?php echo $f['status']; ?>"><?php echo ucfirst($f['status']); ?></span>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:var(--gray); text-align:center; padding:20px;">No fee records.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>