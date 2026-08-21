<?php
$current = basename($_SERVER['PHP_SELF']);
?>

<!-- Hamburger -->
<button class="hamburger" onclick="toggleSidebar()">
    <span></span><span></span><span></span>
</button>

<!-- Overlay -->
<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL; ?>/dashboard.php" class="sidebar-logo">
            <span class="logo-icon">🎓</span>
            <span class="logo-text">Edu<span style="color:var(--primary);">Pro</span></span>
        </a>
    </div>

    <!-- Admin Info -->
    <div style="padding:15px 20px; border-bottom:1px solid var(--dark-3); display:flex; align-items:center; gap:10px;">
        <div style="width:38px; height:38px; border-radius:50%; background:var(--primary); display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:15px; flex-shrink:0;">
            <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
        </div>
        <div>
            <div style="color:white; font-size:13px; font-weight:600;"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
            <div style="color:var(--gray); font-size:11px;">Administrator</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <!-- Main -->
        <div class="nav-section">Main</div>
        <a href="<?php echo APP_URL; ?>/dashboard.php" class="nav-item <?php echo $current == 'dashboard.php' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>

        <!-- Students -->
        <div class="nav-section">Students</div>
        <a href="<?php echo APP_URL; ?>/students.php" class="nav-item <?php echo $current == 'students.php' ? 'active' : ''; ?>">
            <span class="nav-icon">👨‍🎓</span> All Students
        </a>
        <a href="<?php echo APP_URL; ?>/add.php" class="nav-item <?php echo $current == 'add.php' ? 'active' : ''; ?>">
            <span class="nav-icon">➕</span> Add Student
        </a>

        <!-- Academic -->
        <div class="nav-section">Academic</div>
        <a href="<?php echo APP_URL; ?>/courses.php" class="nav-item <?php echo $current == 'courses.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📚</span> Courses
        </a>
        <a href="<?php echo APP_URL; ?>/grades.php" class="nav-item <?php echo $current == 'grades.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📝</span> Grades
        </a>
        <a href="<?php echo APP_URL; ?>/attendance.php" class="nav-item <?php echo $current == 'attendance.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📅</span> Attendance
        </a>

        <!-- Administration -->
        <div class="nav-section">Administration</div>
        <a href="<?php echo APP_URL; ?>/fees.php" class="nav-item <?php echo $current == 'fees.php' ? 'active' : ''; ?>">
            <span class="nav-icon">💰</span> Fees
        </a>
        <a href="<?php echo APP_URL; ?>/activity.php" class="nav-item <?php echo $current == 'activity.php' ? 'active' : ''; ?>">
            <span class="nav-icon">📜</span> Activity Log
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo APP_URL; ?>/logout.php"
            style="display:flex; align-items:center; gap:10px; color:rgba(255,255,255,0.6); text-decoration:none; padding:10px; border-radius:var(--radius); transition:all 0.2s;">
            🚪 Logout
        </a>
    </div>
</div>