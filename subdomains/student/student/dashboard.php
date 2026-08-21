<?php
require_once '../config.php';
requireStudent();

$page_title    = "Student Dashboard";
$student_id    = $_SESSION['student_id'];

// Get student data
$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Performance Stats
$attendance_pct = getAttendancePercentage($conn, $student_id);
$avg_marks      = getAverageMarks($conn, $student_id);
$fee_pct        = getFeePercentage($conn, $student_id);
$grade          = calculateGrade($avg_marks);

// Grades per course
$grades = mysqli_query($conn, "
    SELECT g.*, c.name as course_name
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = $student_id
    ORDER BY g.created_at DESC
");

// Recent attendance
$attendance = mysqli_query($conn, "
    SELECT * FROM attendance
    WHERE student_id = $student_id
    ORDER BY date DESC
    LIMIT 10
");

// Fee records
$fees = mysqli_query($conn, "
    SELECT * FROM fees
    WHERE student_id = $student_id
    ORDER BY created_at DESC
");

$total_fees = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) as total, SUM(paid_amount) as paid FROM fees WHERE student_id = $student_id"));
?>

<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> — <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/style.css">
    <style>
        /* Student Sidebar */
        .student-sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1c1917 0%, #292524 100%);
            min-height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            z-index: 99;
            transition: transform 0.3s ease;
            overflow-y: auto;
        }

        .student-main {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
            min-height: 100vh;
        }

        /* Circular Progress */
        .circle-container {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .circle-item {
            text-align: center;
        }

        .circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin: 0 auto 10px;
        }

        .circle-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: conic-gradient(var(--color) var(--pct), #e7e5e4 var(--pct));
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .circle-inner {
            width: 85px;
            height: 85px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 1;
        }

        .circle-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
        }

        .circle-label {
            font-size: 10px;
            color: var(--gray);
        }

        .circle-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--dark);
        }

        .circle-sub {
            font-size: 11px;
            color: var(--gray);
            margin-top: 2px;
        }

        /* Bar Chart */
        .bar-chart {
            padding: 10px 0;
        }

        .bar-item {
            margin-bottom: 15px;
        }

        .bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .bar-track {
            background: var(--border);
            border-radius: 10px;
            height: 10px;
            overflow: hidden;
        }

        .bar-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 1s ease;
        }

        @media (max-width: 768px) {
            .student-sidebar {
                transform: translateX(-100%) !important;
                width: 260px !important;
            }
            .student-sidebar.open {
                transform: translateX(0) !important;
            }
            .student-main {
                margin-left: 0 !important;
                padding: 70px 15px 20px !important;
            }
            .circle-container {
                gap: 15px;
            }
            .circle {
                width: 100px;
                height: 100px;
            }
            .circle-inner {
                width: 70px;
                height: 70px;
            }
        }
    </style>
</head>
<body id="body-root">

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- Student Sidebar -->
<div class="student-sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL; ?>/student/dashboard.php" class="sidebar-logo">
            <span class="logo-icon">🎓</span>
            <span class="logo-text">Edu<span style="color:var(--primary);">Pro</span></span>
        </a>
    </div>

    <!-- Student Info -->
    <div style="padding:15px 20px; border-bottom:1px solid #44403c; display:flex; align-items:center; gap:10px;">
        <?php if (!empty($student['profile_image']) && file_exists(UPLOAD_PATH . $student['profile_image'])): ?>
            <img src="<?php echo UPLOAD_URL . $student['profile_image']; ?>"
                style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--primary); flex-shrink:0;">
        <?php else: ?>
            <div style="width:40px; height:40px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:16px; flex-shrink:0;">
                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        <div>
            <div style="color:white; font-size:13px; font-weight:600;"><?php echo htmlspecialchars($student['name']); ?></div>
            <div style="color:#78716c; font-size:11px;"><?php echo htmlspecialchars($student['student_id']); ?></div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Student Portal</div>
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">🏠</span> My Dashboard
        </a>
        <div class="nav-section">Academic</div>
        <a href="#grades" class="nav-item" onclick="scrollTo('grades')">
            <span class="nav-icon">📝</span> My Grades
        </a>
        <a href="#attendance" class="nav-item" onclick="scrollTo('attendance')">
            <span class="nav-icon">📅</span> My Attendance
        </a>
        <a href="#fees" class="nav-item" onclick="scrollTo('fees')">
            <span class="nav-icon">💰</span> My Fees
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php"
            style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.6); text-decoration:none; padding:10px; border-radius:10px;">
            🚪 Logout
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="student-main">

    <div class="topbar">
        <div class="topbar-left">
            <h1>👋 Welcome, <?php echo htmlspecialchars(explode(' ', $student['name'])[0]); ?>!</h1>
            <p>Here's your academic overview — <?php echo date('l, M d Y'); ?></p>
        </div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <!-- Profile Card -->
    <div class="card" style="margin-bottom:24px;">
        <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
            <?php if (!empty($student['profile_image']) && file_exists(UPLOAD_PATH . $student['profile_image'])): ?>
                <img src="<?php echo UPLOAD_URL . $student['profile_image']; ?>"
                    style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid var(--primary);">
            <?php else: ?>
                <div style="width:80px; height:80px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-size:32px; font-weight:700; flex-shrink:0;">
                    <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div>
                <h2 style="font-size:20px; font-weight:700;"><?php echo htmlspecialchars($student['name']); ?></h2>
                <p style="color:var(--gray); font-size:13px; margin-top:3px;">📧 <?php echo htmlspecialchars($student['email']); ?></p>
                <p style="color:var(--gray); font-size:13px;">📚 <?php echo htmlspecialchars($student['course']); ?></p>
                <div style="display:flex; gap:8px; margin-top:8px; flex-wrap:wrap;">
                    <code style="background:var(--gray-light); padding:3px 10px; border-radius:5px; font-size:12px;"><?php echo htmlspecialchars($student['student_id']); ?></code>
                    <span class="badge badge-<?php echo $student['status'] ?? 'active'; ?>"><?php echo ucfirst($student['status'] ?? 'active'); ?></span>
                    <span class="badge badge-orange">Grade: <?php echo $grade; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Circular Progress Charts -->
    <div class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h2>📊 Performance Overview</h2>
        </div>
        <div class="circle-container">

            <!-- Attendance Circle -->
            <div class="circle-item">
                <div class="circle">
                    <div class="circle-bg" style="--pct:<?php echo $attendance_pct; ?>%; --color:#f97316;"></div>
                    <div class="circle-inner">
                        <div class="circle-value"><?php echo $attendance_pct; ?>%</div>
                        <div class="circle-label">Present</div>
                    </div>
                </div>
                <div class="circle-title">Attendance</div>
                <div class="circle-sub">
                    <?php echo $attendance_pct >= 75 ? '✅ Good' : '⚠️ Low'; ?>
                </div>
            </div>

            <!-- Grades Circle -->
            <div class="circle-item">
                <div class="circle">
                    <div class="circle-bg" style="--pct:<?php echo $avg_marks; ?>%; --color:#059669;"></div>
                    <div class="circle-inner">
                        <div class="circle-value"><?php echo $avg_marks; ?>%</div>
                        <div class="circle-label">Average</div>
                    </div>
                </div>
                <div class="circle-title">Academic</div>
                <div class="circle-sub">Grade: <?php echo $grade; ?></div>
            </div>

            <!-- Fee Circle -->
            <div class="circle-item">
                <div class="circle">
                    <div class="circle-bg" style="--pct:<?php echo $fee_pct; ?>%; --color:#0284c7;"></div>
                    <div class="circle-inner">
                        <div class="circle-value"><?php echo $fee_pct; ?>%</div>
                        <div class="circle-label">Paid</div>
                    </div>
                </div>
                <div class="circle-title">Fee Status</div>
                <div class="circle-sub">
                    <?php echo $fee_pct >= 100 ? '✅ Cleared' : '⚠️ Due'; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Grades Bar Chart -->
    <div id="grades" class="card" style="margin-bottom:24px;">
        <div class="card-header">
            <h2>📝 My Grades</h2>
        </div>
        <?php if (mysqli_num_rows($grades) > 0): ?>
            <div class="bar-chart">
                <?php while ($g = mysqli_fetch_assoc($grades)): ?>
                <div class="bar-item">
                    <div class="bar-label">
                        <span><?php echo htmlspecialchars($g['course_name']); ?> — <?php echo $g['semester'] ?? ''; ?></span>
                        <span style="font-weight:700;">
                            <?php echo number_format($g['marks'], 1); ?>%
                            <span class="badge badge-<?php
                                echo match($g['grade']) {
                                    'A+', 'A' => 'success',
                                    'B'       => 'info',
                                    'C', 'D'  => 'warning',
                                    default   => 'danger'
                                };
                            ?>" style="margin-left:5px;"><?php echo $g['grade']; ?></span>
                        </span>
                    </div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:<?php echo $g['marks']; ?>%; background:<?php
                            echo match(true) {
                                $g['marks'] >= 80 => '#059669',
                                $g['marks'] >= 60 => '#0284c7',
                                $g['marks'] >= 50 => '#d97706',
                                default           => '#dc2626'
                            };
                        ?>;"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="color:var(--gray); text-align:center; padding:20px;">No grades recorded yet.</p>
        <?php endif; ?>
    </div>

    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;" class="responsive-grid">

        <!-- Attendance History -->
        <div id="attendance" class="card">
            <div class="card-header">
                <h2>📅 Recent Attendance</h2>
            </div>
            <?php if (mysqli_num_rows($attendance) > 0): ?>
                <?php while ($a = mysqli_fetch_assoc($attendance)): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border);">
                    <div style="font-size:13px; font-weight:600;">
                        <?php echo date('M d, Y', strtotime($a['date'])); ?>
                    </div>
                    <span class="badge badge-<?php echo $a['status']; ?>">
                        <?php echo ucfirst($a['status']); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No attendance records yet.</p>
            <?php endif; ?>
        </div>

        <!-- Fee Status -->
        <div id="fees" class="card">
            <div class="card-header">
                <h2>💰 My Fees</h2>
            </div>
            <div style="display:flex; justify-content:space-between; padding:15px; background:var(--gray-light); border-radius:var(--radius); margin-bottom:15px;">
                <div style="text-align:center;">
                    <div style="font-size:18px; font-weight:700; color:var(--dark);">
                        <?php echo number_format($total_fees['total'] ?? 0, 0); ?>
                    </div>
                    <div style="font-size:11px; color:var(--gray);">Total (PKR)</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:18px; font-weight:700; color:var(--success);">
                        <?php echo number_format($total_fees['paid'] ?? 0, 0); ?>
                    </div>
                    <div style="font-size:11px; color:var(--gray);">Paid (PKR)</div>
                </div>
                <div style="text-align:center;">
                    <div style="font-size:18px; font-weight:700; color:var(--danger);">
                        <?php echo number_format(($total_fees['total'] ?? 0) - ($total_fees['paid'] ?? 0), 0); ?>
                    </div>
                    <div style="font-size:11px; color:var(--gray);">Due (PKR)</div>
                </div>
            </div>
            <?php if (mysqli_num_rows($fees) > 0): ?>
                <?php while ($f = mysqli_fetch_assoc($fees)): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 0; border-bottom:1px solid var(--border);">
                    <div>
                        <div style="font-size:13px; font-weight:600;"><?php echo htmlspecialchars($f['description'] ?? 'Fee'); ?></div>
                        <div style="font-size:11px; color:var(--gray);">
                            Due: <?php echo $f['due_date'] ? date('M d, Y', strtotime($f['due_date'])) : '—'; ?>
                        </div>
                    </div>
                    <span class="badge badge-<?php echo $f['status']; ?>"><?php echo ucfirst($f['status']); ?></span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color:var(--gray); text-align:center; padding:20px;">No fee records yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>