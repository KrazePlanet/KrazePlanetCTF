<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['omise_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['omise_user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit;
}

// Fetch team members belonging to this user
$stmt_mem = $pdo->prepare("SELECT * FROM memberships WHERE owner_id = ? ORDER BY id ASC");
$stmt_mem->execute([$user_id]);
$members = $stmt_mem->fetchAll(PDO::FETCH_ASSOC);

// Handle delete / revoke member
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM memberships WHERE id = ? AND owner_id = ? AND email != ?")
        ->execute([$del_id, $user_id, $current_user['email']]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team &amp; Access Control — Omise Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --omise-blue: #1a56db;
            --omise-dark: #0f172a;
            --omise-sidebar: #1e293b;
            --omise-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .dashboard-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .omise-sidebar {
            width: 260px;
            background-color: #0f172a;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .brand-header {
            padding: 20px;
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            border-bottom: 1px solid #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-tag {
            background: #1a56db;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .nav-item-omise {
            padding: 10px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.15s;
        }

        .nav-item-omise:hover, .nav-item-omise.active {
            background-color: #1e293b;
            color: #ffffff;
        }

        .nav-item-omise.active {
            border-left: 3px solid #1a56db;
        }

        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 30px 40px;
        }

        .card-custom {
            background: #ffffff;
            border: 1px solid var(--omise-border);
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .btn-omise {
            background-color: #1a56db;
            color: #ffffff;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            transition: background 0.15s;
        }

        .btn-omise:hover {
            background-color: #1e429f;
            color: #ffffff;
        }

        .role-badge {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .status-pill-pending {
            background: #fef3c7;
            color: #92400e;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .status-pill-active {
            background: #dcfce7;
            color: #166534;
            font-weight: 700;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 12px;
        }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        
        <!-- Sidebar -->
        <div class="omise-sidebar">
            <div class="brand-header">
                omise <span class="brand-tag">Live</span>
            </div>

            <div class="p-3 text-secondary small text-uppercase fw-bold" style="font-size: 11px;">Navigation</div>
            <a href="#" class="nav-item-omise"><i class="bi bi-graph-up"></i> Overview</a>
            <a href="#" class="nav-item-omise"><i class="bi bi-credit-card"></i> Charges</a>
            <a href="#" class="nav-item-omise"><i class="bi bi-link-45deg"></i> Payment Links</a>
            <a href="#" class="nav-item-omise"><i class="bi bi-code-slash"></i> API Keys</a>
            <a href="index.php" class="nav-item-omise active"><i class="bi bi-people-fill"></i> Team &amp; Access</a>
            <a href="#" class="nav-item-omise"><i class="bi bi-gear"></i> Settings</a>

            <div class="mt-auto p-3 border-top border-secondary border-opacity-25 text-secondary small">
                <strong class="text-white"><?= htmlspecialchars($current_user['company_name']) ?></strong><br>
                <span class="font-monospace text-muted" style="font-size: 11px;"><?= htmlspecialchars($current_user['account_id']) ?></span>
                <div class="mt-2 pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
                    <span class="text-truncate text-secondary" style="max-width: 140px; font-size:12px;"><?= htmlspecialchars($current_user['email']) ?></span>
                    <a href="logout.php" class="text-danger text-decoration-none small fw-bold">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">
            
            <?php if (isset($_GET['invited']) && $_GET['invited'] == '1'): ?>
                <div class="alert alert-success alert-dismissible fade show py-3 px-4 rounded border-0 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert" style="background:#ecfdf5; color:#065f46; border-left: 4px solid #10b981 !important;">
                    <i class="bi bi-check-circle-fill fs-4 text-success"></i>
                    <div>
                        <strong>Invitation Sent!</strong><br>
                        <span class="small">An official invitation email has been dispatched via SMTP with the selected permissions.</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === 'already_invited'): ?>
                <div class="alert alert-warning alert-dismissible fade show py-3 px-4 rounded border-0 shadow-sm d-flex align-items-center gap-3 mb-4" role="alert" style="background:#fffbeb; color:#92400e; border-left: 4px solid #f59e0b !important;">
                    <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                    <div>
                        <strong>Member Already Exists</strong><br>
                        <span class="small">This user has already been invited or is an active member of this team.</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Team Members</h3>
                    <p class="text-secondary small mb-0">Manage authorized users, administrative permissions, and technical API access for <strong><?= htmlspecialchars($current_user['company_name']) ?></strong>.</p>
                </div>
                <button type="button" class="btn btn-omise" data-bs-toggle="modal" data-bs-target="#inviteModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Invite Member
                </button>
            </div>

            <!-- Members Table Card -->
            <div class="card-custom">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-secondary small text-uppercase">
                        <tr>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th>Invited On</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar bg-light text-dark fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px; font-size:12px; border:1px solid #e2e8f0;">
                                            <?= strtoupper(substr($m['email'], 0, 2)) ?>
                                        </div>
                                        <span><?= htmlspecialchars($m['email']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <?php if ($m['is_admin']): ?>
                                            <span class="role-badge"><i class="bi bi-shield-check me-1 text-primary"></i> Admin</span>
                                        <?php endif; ?>
                                        <?php if ($m['is_technical']): ?>
                                            <span class="role-badge"><i class="bi bi-code-slash me-1 text-success"></i> Technical</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($m['status'] === 'active'): ?>
                                        <span class="status-pill-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-pill-pending">Pending Invitation</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-secondary small font-monospace">
                                    <?= date('Y-m-d H:i', strtotime($m['created_at'])) ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($m['email'] !== $current_user['email']): ?>
                                        <a href="index.php?action=delete&id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" title="Revoke Invitation" onclick="return confirm('Revoke invitation for <?= htmlspecialchars($m['email']) ?>?');">
                                            <i class="bi bi-trash"></i> Revoke
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Account Owner</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- Invite Member Modal (1:1 matching HackerOne POST /team/memberships) -->
    <div class="modal fade" id="inviteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Invite a team member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form method="POST" action="team/memberships.php">
                    <div class="modal-body py-4">
                        <input type="hidden" name="authenticity_token" value="c39a81f08e92a1789b91c7849e7">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark">Email address</label>
                            <input type="email" name="email" class="form-control" placeholder="colleague@company.com" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-dark mb-2">Assign Permissions</label>
                            <input type="hidden" name="membership[admin]" value="0">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="membership[admin]" value="1" id="role_admin">
                                <label class="form-check-label text-dark small" for="role_admin">
                                    <strong>Administrator</strong> — Full access to settings, transfers, and team members.
                                </label>
                            </div>

                            <input type="hidden" name="membership[technical]" value="0">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="membership[technical]" value="1" id="role_tech" checked>
                                <label class="form-check-label text-dark small" for="role_tech">
                                    <strong>Technical</strong> — Manage webhooks, API keys, and test sandbox transactions.
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="commit" value="Send invitation" class="btn btn-omise">
                            Send Invitation &rarr;
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
