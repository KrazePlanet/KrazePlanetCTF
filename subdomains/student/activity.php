<?php
require_once 'config.php';
requireAdmin();
$page_title = "Activity Log";

// Clear logs
if (isset($_POST['clear_logs'])) {
    mysqli_query($conn, "DELETE FROM activity_log");
    header('Location: activity.php?success=cleared');
    exit();
}

// Pagination
$per_page = 15;
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset   = ($page - 1) * $per_page;

$total_rows  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM activity_log"))['count'];
$total_pages = ceil($total_rows / $per_page);
$logs        = mysqli_query($conn, "SELECT * FROM activity_log ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
?>

<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>

<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <h1>📜 Activity Log</h1>
            <p>Track all system activities</p>
        </div>
        <div class="topbar-right">
            <form method="POST" action="activity.php" style="display:inline;">
                <button type="submit" name="clear_logs" class="btn btn-danger"
                    onclick="return confirm('Clear all logs?')">🗑️ Clear Logs</button>
            </form>
            <button class="dark-toggle" onclick="toggleDark()" id="darkBtn">🌙 Dark Mode</button>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">✅ Logs cleared successfully!</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2>📋 System Activity</h2>
            <span style="color:var(--gray); font-size:13px;"><?php echo $total_rows; ?> total records</span>
        </div>

        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Action</th>
                    <th>Date & Time</th>
                    <th>Time Ago</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($logs) > 0): ?>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td>
                            <?php
                            $action = htmlspecialchars($log['action']);
                            if (strpos($log['action'], 'Added') !== false) {
                                echo '<span class="badge badge-success">➕</span> ' . $action;
                            } elseif (strpos($log['action'], 'Deleted') !== false || strpos($log['action'], 'deleted') !== false) {
                                echo '<span class="badge badge-danger">🗑️</span> ' . $action;
                            } elseif (strpos($log['action'], 'Updated') !== false) {
                                echo '<span class="badge badge-warning">✏️</span> ' . $action;
                            } elseif (strpos($log['action'], 'attendance') !== false) {
                                echo '<span class="badge badge-info">📅</span> ' . $action;
                            } elseif (strpos($log['action'], 'fee') !== false || strpos($log['action'], 'Fee') !== false) {
                                echo '<span class="badge badge-orange">💰</span> ' . $action;
                            } else {
                                echo '<span class="badge badge-info">📋</span> ' . $action;
                            }
                            ?>
                        </td>
                        <td style="font-size:13px;"><?php echo $log['created_at']; ?></td>
                        <td style="font-size:12px; color:var(--gray);"><?php echo timeAgo($log['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding:40px; color:var(--gray);">
                            No activity logged yet.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page-1; ?>" class="page-btn">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?php echo $i; ?>"
                    class="page-btn <?php echo $i == $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page+1; ?>" class="page-btn">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>