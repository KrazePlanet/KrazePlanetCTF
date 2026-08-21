<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch stats
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'Paid'")->fetchColumn();
$pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn();
$preparing_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Preparing'")->fetchColumn();

// Fetch live orders with items
$orders = $pdo->query("
    SELECT o.*, 
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.item_name) SEPARATOR '<br>') AS items_summary
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display System (KDS) & POS Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; }
        
        header {
            background: #1e293b;
            border-bottom: 1px solid #334155;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-icon {
            width: 40px; height: 40px;
            background: #f95724;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
        }
        .brand-text h1 { font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 800; color: #fff; }
        .brand-text span { font-size: 12px; color: #94a3b8; }

        .user-nav { display: flex; align-items: center; gap: 16px; }
        .user-badge {
            background: #334155;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .logout-btn {
            background: #ef4444;
            color: #fff;
            padding: 7px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
        }

        .main-content { max-width: 1400px; margin: 0 auto; width: 100%; padding: 24px; flex-grow: 1; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }
        .stat-info h4 { font-size: 13px; color: #94a3b8; font-weight: 600; }
        .stat-info h2 { font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; margin-top: 4px; }

        /* KDS Orders Board */
        .kds-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .kds-header h3 { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .order-card {
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .order-card.status-Pending { border-top: 4px solid #f59e0b; }
        .order-card.status-Preparing { border-top: 4px solid #3b82f6; }
        .order-card.status-Ready { border-top: 4px solid #10b981; }
        .order-card.status-Delivered { border-top: 4px solid #64748b; opacity: 0.7; }

        .order-head {
            padding: 14px 16px;
            border-bottom: 1px solid #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .order-code { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 800; color: #f95724; }
        .order-table { background: #334155; color: #fff; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 700; }

        .order-body { padding: 16px; flex-grow: 1; }
        .customer-line { font-size: 13px; color: #94a3b8; margin-bottom: 12px; }
        .items-box {
            background: #0f172a;
            border-radius: 10px;
            padding: 12px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 12px;
            color: #e2e8f0;
        }

        .instructions-box {
            font-size: 12px;
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 12px;
        }

        .order-foot {
            padding: 14px 16px;
            border-top: 1px solid #334155;
            background: #182234;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .order-total { font-size: 16px; font-weight: 800; color: #fff; }

        .status-select {
            background: #334155;
            color: #fff;
            border: 1px solid #475569;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            outline: none;
        }
    </style>
</head>
<body>
    <header>
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-utensils"></i></div>
            <div class="brand-text">
                <h1>Buffet Box Kitchen Command</h1>
                <span>Live Kitchen Display & POS Tickets</span>
            </div>
        </div>

        <div class="user-nav">
            <a href="../index.php" target="_blank" style="color:#94a3b8; text-decoration:none; font-size:13px; font-weight:600;">
                <i class="fas fa-external-link-alt"></i> Open Customer Menu
            </a>
            <span class="user-badge"><i class="fas fa-user-chef"></i> <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <div class="main-content">
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(249,87,36,0.2); color:#f95724;"><i class="fas fa-receipt"></i></div>
                <div class="stat-info">
                    <h4>Total Orders</h4>
                    <h2><?= $total_orders ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.2); color:#10b981;"><i class="fas fa-dollar-sign"></i></div>
                <div class="stat-info">
                    <h4>Total Sales</h4>
                    <h2>$<?= number_format($total_revenue, 2) ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.2); color:#f59e0b;"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h4>Pending Tickets</h4>
                    <h2><?= $pending_count ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(59,130,246,0.2); color:#3b82f6;"><i class="fas fa-fire-burner"></i></div>
                <div class="stat-info">
                    <h4>In Kitchen (Prep)</h4>
                    <h2><?= $preparing_count ?></h2>
                </div>
            </div>
        </div>

        <!-- Live Orders Section -->
        <div class="kds-header">
            <h3><i class="fas fa-tv" style="color:#f95724;"></i> Live Kitchen Display Board</h3>
            <button onclick="location.reload()" style="background:#334155; color:#fff; border:none; padding:8px 14px; border-radius:8px; font-weight:700; cursor:pointer;">
                <i class="fas fa-sync-alt"></i> Refresh Board
            </button>
        </div>

        <div class="orders-grid">
            <?php if (empty($orders)): ?>
                <p style="color:#94a3b8;">No active orders yet.</p>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="order-card status-<?= $o['status'] ?>">
                        <div class="order-head">
                            <span class="order-code"><?= htmlspecialchars($o['order_code']) ?></span>
                            <span class="order-table"><?= htmlspecialchars($o['table_number']) ?></span>
                        </div>
                        <div class="order-body">
                            <div class="customer-line">
                                <strong><?= htmlspecialchars($o['customer_name']) ?></strong> • <?= date('h:i A', strtotime($o['created_at'])) ?>
                            </div>
                            
                            <div class="items-box">
                                <?= $o['items_summary'] ?: 'No items listed' ?>
                            </div>

                            <?php if (!empty($o['special_instructions'])): ?>
                                <div class="instructions-box">
                                    <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($o['special_instructions']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="order-foot">
                            <span class="order-total">$<?= number_format($o['total_amount'], 2) ?></span>
                            <select class="status-select" onchange="updateStatus(<?= $o['id'] ?>, this.value)">
                                <option value="Pending" <?= $o['status'] == 'Pending' ? 'selected' : '' ?>>⏳ Pending</option>
                                <option value="Preparing" <?= $o['status'] == 'Preparing' ? 'selected' : '' ?>>🍳 Preparing</option>
                                <option value="Ready" <?= $o['status'] == 'Ready' ? 'selected' : '' ?>>🔔 Ready</option>
                                <option value="Delivered" <?= $o['status'] == 'Delivered' ? 'selected' : '' ?>>✅ Delivered</option>
                                <option value="Cancelled" <?= $o['status'] == 'Cancelled' ? 'selected' : '' ?>>❌ Cancelled</option>
                            </select>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateStatus(orderId, newStatus) {
            fetch('../api/update_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderId, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error updating status: ' + data.message);
                }
            })
            .catch(() => alert('Network error.'));
        }
    </script>
</body>
</html>
