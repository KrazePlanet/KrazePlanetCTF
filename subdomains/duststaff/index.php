<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['dust_user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['dust_user_id'];
$stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$current_user = $stmt_u->fetch(PDO::FETCH_ASSOC);

if (!$current_user) {
    header("Location: logout.php");
    exit;
}

// Fetch user space
$stmt_sp = $pdo->prepare("SELECT * FROM spaces WHERE user_id = ? ORDER BY id ASC LIMIT 1");
$stmt_sp->execute([$user_id]);
$space = $stmt_sp->fetch(PDO::FETCH_ASSOC);
$space_id = (int)($space['id'] ?? 0);

// Fetch all folders for this space
$stmt_f = $pdo->prepare("SELECT * FROM folders WHERE user_id = ? AND space_id = ? ORDER BY id ASC");
$stmt_f->execute([$user_id, $space_id]);
$folders = $stmt_f->fetchAll(PDO::FETCH_ASSOC);
$total_folders = count($folders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Folders — Knowledge — Dust</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --dust-bg: #0b0d11;
            --dust-sidebar: #101318;
            --dust-card: #14171d;
            --dust-border: #20242c;
            --dust-hover: #191c22;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--dust-bg);
            color: #f3f4f6;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .dust-layout {
            display: flex;
            min-height: 100vh;
        }

        /* 1:1 Dust Sidebar matching Screenshot */
        .dust-sidebar {
            width: 240px;
            background-color: var(--dust-sidebar);
            border-right: 1px solid var(--dust-border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            padding: 16px 0;
        }

        .sidebar-brand {
            padding: 0 18px 16px 18px;
            font-size: 20px;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #64748b;
            padding: 14px 18px 6px 18px;
            letter-spacing: 0.5px;
        }

        .sidebar-link {
            padding: 8px 18px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.15s;
        }

        .sidebar-link:hover, .sidebar-link.active {
            color: #ffffff;
            background-color: var(--dust-hover);
        }

        .sidebar-link.active {
            font-weight: 600;
            color: #3b82f6;
        }

        /* Main Workspace Content Area */
        .dust-main {
            flex-grow: 1;
            padding: 24px 36px;
            background-color: var(--dust-bg);
        }

        .dust-breadcrumb {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dust-breadcrumb a {
            color: #94a3b8;
            text-decoration: none;
        }

        .dust-table-card {
            background-color: var(--dust-card);
            border: 1px solid var(--dust-border);
            border-radius: 8px;
            overflow: hidden;
        }

        .dust-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }

        .dust-table th {
            background-color: #101318;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 12px 18px;
            border-bottom: 1px solid var(--dust-border);
        }

        .dust-table td {
            padding: 14px 18px;
            font-size: 13.5px;
            border-bottom: 1px solid var(--dust-border);
            color: #e2e8f0;
            vertical-align: middle;
        }

        .dust-table tr:hover {
            background-color: var(--dust-hover);
        }

        .btn-add-folder {
            background-color: #ffffff;
            color: #000000;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 6px;
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.15s;
        }

        .btn-add-folder:hover {
            opacity: 0.9;
        }

        .avatar-circle {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: #10b981;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        /* 1:1 Error Toast matching Screenshot F4275950 */
        .toast-error-container {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
            display: none;
        }

        .toast-error-card {
            background: #181111;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.6);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            max-width: 380px;
        }

        /* 1:1 Slide-Over Drawer matching Screenshot */
        .drawer-overlay {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            width: 420px;
            background-color: #101318;
            border-left: 1px solid var(--dust-border);
            z-index: 1040;
            box-shadow: -10px 0 30px rgba(0,0,0,0.5);
            padding: 24px;
            display: none;
            flex-direction: column;
        }

        .drawer-overlay.open {
            display: flex;
        }

        .form-control-dark {
            background-color: #0b0d11;
            border: 1px solid var(--dust-border);
            color: #ffffff;
            font-size: 13.5px;
            border-radius: 6px;
        }

        .form-control-dark:focus {
            background-color: #0b0d11;
            border-color: #3b82f6;
            color: #ffffff;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.25);
        }
    </style>
</head>
<body>

    <div class="dust-layout">
        
        <!-- Sidebar Navigation matching Dust Screenshot -->
        <div class="dust-sidebar">
            <div class="sidebar-brand">
                <i class="bi bi-clouds-fill text-primary"></i> dust
            </div>

            <a href="#" class="sidebar-link"><i class="bi bi-chat-dots"></i> Chat</a>
            <a href="index.php" class="sidebar-link active"><i class="bi bi-book"></i> Knowledge</a>

            <div class="nav-section-title">Open Spaces</div>
            <a href="#" class="sidebar-link"><i class="bi bi-building"></i> Company Space</a>

            <div class="nav-section-title">Restricted Spaces</div>
            <a href="index.php" class="sidebar-link active"><i class="bi bi-lock-fill"></i> Engineering Space</a>
            <div class="ps-3">
                <a href="#" class="sidebar-link small text-secondary"><i class="bi bi-database"></i> Connected Data</a>
                <a href="index.php" class="sidebar-link small active"><i class="bi bi-folder-fill text-warning"></i> Folders</a>
                <a href="#" class="sidebar-link small text-secondary"><i class="bi bi-globe"></i> Websites</a>
                <a href="#" class="sidebar-link small text-secondary"><i class="bi bi-grid"></i> Apps</a>
            </div>

            <div class="mt-auto p-3 border-top border-secondary border-opacity-25 text-secondary small">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-truncate" style="max-width: 140px;"><?= htmlspecialchars($current_user['email']) ?></span>
                    <a href="logout.php" class="text-danger text-decoration-none small fw-bold">Logout</a>
                </div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="dust-main">
            
            <div class="dust-breadcrumb">
                <i class="bi bi-lock-fill"></i>
                <a href="index.php">Engineering Space</a> &rsaquo;
                <span class="text-white">Folders</span>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-3">
                    <input type="text" class="form-control form-control-dark" placeholder="Search folders..." style="width: 260px;">
                    <span class="badge bg-dark border border-secondary text-secondary font-monospace" id="folder-badge">
                        <?= $total_folders ?> / 10 folders
                    </span>
                    <?php if ($total_folders > 10): ?>
                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">
                            Limit Bypassed (<?= $total_folders ?> Folders)
                        </span>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn-add-folder" onclick="openDrawer()">
                    <i class="bi bi-plus-lg"></i> Add folder
                </button>
            </div>

            <!-- Folders Table Card matching Screenshot -->
            <div class="dust-table-card">
                <table class="dust-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Name</th>
                            <th style="width: 25%;">Used By</th>
                            <th style="width: 20%;">Managed By</th>
                            <th style="width: 10%; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="folders-tbody">
                        <?php if (empty($folders)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No folders created in this space yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($folders as $f): ?>
                                <tr id="row-<?= $f['id'] ?>">
                                    <td class="fw-semibold">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-folder-fill text-secondary"></i>
                                            <span><?= htmlspecialchars($f['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-secondary small">
                                        <i class="bi bi-cloud me-1"></i> 0
                                    </td>
                                    <td>
                                        <div class="avatar-circle" title="<?= htmlspecialchars($current_user['email']) ?>">
                                            <?= strtoupper(substr($current_user['email'], 0, 2)) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: right;">
                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="deleteFolder(<?= $f['id'] ?>, '<?= htmlspecialchars(addslashes($f['name'])) ?>')" title="Delete folder">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-secondary small mt-3" id="items-count-text">
                <?= $total_folders ?> items
            </div>

        </div>

    </div>

    <!-- 1:1 Create Folder Drawer matching Screenshot -->
    <div class="drawer-overlay" id="folderDrawer">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0 text-white">Create Folder</h5>
            <button type="button" class="btn-close btn-close-white" onclick="closeDrawer()"></button>
        </div>

        <form id="createFolderForm" onsubmit="submitCreateFolder(event)">
            <input type="hidden" name="space_id" value="<?= $space_id ?>">

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Name</label>
                <input type="text" name="name" id="folderNameInput" class="form-control form-control-dark" placeholder="e.g. Documentation" required autofocus>
                <div class="text-secondary small mt-1" style="font-size:11px;"><i class="bi bi-info-circle me-1"></i> Folder name must be unique</div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-light mb-1">Description</label>
                <textarea name="description" id="folderDescInput" class="form-control form-control-dark" rows="3" placeholder="Optional description..."></textarea>
            </div>

            <div class="mt-auto d-flex justify-content-end gap-2 pt-4 border-top border-secondary border-opacity-25">
                <button type="button" class="btn btn-sm btn-outline-secondary text-light" onclick="closeDrawer()">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary px-3 fw-bold">Save</button>
            </div>
        </form>
    </div>

    <!-- 1:1 Error Toast matching Screenshot F4275950 -->
    <div class="toast-error-container" id="errorToast">
        <div class="toast-error-card">
            <i class="bi bi-x-circle-fill text-danger fs-4"></i>
            <div>
                <strong class="d-block text-white" style="font-size:13px;">Error creating Folder</strong>
                <span class="small" style="font-size:12px;" id="errorToastMsg">Error: Your plan does not allow you to create more data sources.</span>
            </div>
        </div>
    </div>

    <script>
    function openDrawer() {
        document.getElementById('folderDrawer').classList.add('open');
        document.getElementById('folderNameInput').focus();
    }

    function closeDrawer() {
        document.getElementById('folderDrawer').classList.remove('open');
        document.getElementById('createFolderForm').reset();
    }

    function showErrorToast(msg) {
        const toast = document.getElementById('errorToast');
        document.getElementById('errorToastMsg').innerText = msg;
        toast.style.display = 'block';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 5000);
    }

    function submitCreateFolder(e) {
        e.preventDefault();
        const name = document.getElementById('folderNameInput').value;
        const desc = document.getElementById('folderDescInput').value;

        fetch('api/folders.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                name: name,
                description: desc,
                space_id: <?= $space_id ?>
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeDrawer();
                window.location.reload();
            } else {
                showErrorToast(data.error || 'Failed to create folder.');
            }
        })
        .catch(err => {
            showErrorToast('Network error while creating folder.');
        });
    }

    function deleteFolder(id, name) {
        if (!confirm('Are you sure you want to delete folder "' + name + '"?')) return;
        
        fetch('api/folders.php?action=delete&id=' + id, {
            method: 'POST'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
    </script>
</body>
</html>
