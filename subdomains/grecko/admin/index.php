<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['grecko_admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle reservation status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    if (in_array($action, ['Confirmed', 'Cancelled', 'Pending'])) {
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$action, $id]);
        header("Location: index.php");
        exit();
    }
}

// Fetch stats
$res_count = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$pending_res = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'Pending'")->fetchColumn();
$messages_count = $pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$subs_count = $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers")->fetchColumn();

// Fetch reservations
$reservations = $pdo->query("SELECT * FROM reservations ORDER BY id DESC")->fetchAll();
// Fetch messages
$messages = $pdo->query("SELECT * FROM contact_messages ORDER BY id DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grecko Restaurant — Management Command Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Montserrat', sans-serif; }
        body { background: #f1f5f9; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; }
        
        header {
            background: #112d4e;
            color: #ffffff;
            padding: 16px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo { font-family: 'Cinzel', serif; font-size: 22px; font-weight: 800; letter-spacing: 2px; }
        .user-panel { display: flex; align-items: center; gap: 16px; }
        .logout-btn { background: #ef4444; color: #fff; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 700; }

        .container { max-width: 1300px; margin: 24px auto; padding: 0 20px; width: 100%; flex-grow: 1; }

        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 30px; }
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .stat-info h4 { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; }
        .stat-info h2 { font-size: 24px; font-weight: 800; color: #112d4e; margin-top: 4px; }

        .panel { background: #ffffff; border-radius: 12px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
        .panel-header h3 { font-family: 'Cinzel', serif; font-size: 18px; font-weight: 700; color: #112d4e; }

        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { text-align: left; padding: 12px; background: #f8fafc; color: #475569; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
        td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }

        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-Pending { background: #fef3c7; color: #b45309; }
        .badge-Confirmed { background: #dcfce7; color: #15803d; }
        .badge-Cancelled { background: #fee2e2; color: #b91c1c; }

        .action-btn { padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: 700; margin-right: 4px; }
        .btn-confirm { background: #dcfce7; color: #15803d; }
        .btn-cancel { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
    <header>
        <div class="logo">GRECKO RESTAURANT</div>
        <div class="user-panel">
            <a href="../index.php" target="_blank" style="color:#cbd5e1; text-decoration:none; font-size:13px;"><i class="fas fa-eye"></i> View Website</a>
            <span><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['grecko_admin_name']) ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="container">
        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon" style="background:#eff6ff; color:#3b82f6;"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h4>Total Bookings</h4>
                    <h2><?= $res_count ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7; color:#f59e0b;"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4>Pending Approval</h4>
                    <h2><?= $pending_res ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#f0fdf4; color:#10b981;"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h4>Customer Inquiries</h4>
                    <h2><?= $messages_count ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#faf5ff; color:#a855f7;"><i class="fas fa-newspaper"></i></div>
                <div class="stat-info">
                    <h4>Newsletter Subscribers</h4>
                    <h2><?= $subs_count ?></h2>
                </div>
            </div>
        </div>

        <!-- Reservations Table -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-table-tennis"></i> Table Reservations</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest Name</th>
                        <th>Date & Party</th>
                        <th>Contact</th>
                        <th>Special Notes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr><td colspan="7">No table reservations recorded.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $r): ?>
                            <tr>
                                <td>#<?= $r['id'] ?></td>
                                <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($r['date'])) ?> (<?= $r['party_size'] ?> Guests)</td>
                                <td><?= htmlspecialchars($r['email']) ?><br><small><?= htmlspecialchars($r['phone']) ?></small></td>
                                <td><small><?= htmlspecialchars($r['message'] ?: 'None') ?></small></td>
                                <td><span class="badge badge-<?= $r['status'] ?>"><?= $r['status'] ?></span></td>
                                <td>
                                    <?php if ($r['status'] !== 'Confirmed'): ?>
                                        <a href="index.php?action=Confirmed&id=<?= $r['id'] ?>" class="action-btn btn-confirm">Confirm</a>
                                    <?php endif; ?>
                                    <?php if ($r['status'] !== 'Cancelled'): ?>
                                        <a href="index.php?action=Cancelled&id=<?= $r['id'] ?>" class="action-btn btn-cancel">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Contact Inquiries -->
        <div class="panel">
            <div class="panel-header">
                <h3><i class="fas fa-comments"></i> Recent Customer Messages</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Name & Email</th>
                        <th>Subject</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="4">No customer messages received.</td></tr>
                    <?php else: ?>
                        <?php foreach ($messages as $m): ?>
                            <tr>
                                <td><?= date('M d, H:i', strtotime($m['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($m['name']) ?></strong><br><small><?= htmlspecialchars($m['email']) ?></small></td>
                                <td><?= htmlspecialchars($m['subject']) ?></td>
                                <td><?= nl2br(htmlspecialchars($m['message'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
