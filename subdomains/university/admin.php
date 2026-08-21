<?php
session_start();
require_once "database.php";

/* ================= SESSION TIMEOUT ================= */
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

/* ================= AUTH ================= */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/* ================= CONTACT ACTIONS ================= */
if (isset($_GET['hide_contact'])) {
    $conn->query("UPDATE contact SET is_hidden=1 WHERE id=" . (int)$_GET['hide_contact']);
}
if (isset($_GET['unhide_contact'])) {
    $conn->query("UPDATE contact SET is_hidden=0 WHERE id=" . (int)$_GET['unhide_contact']);
}
if (isset($_GET['delete_contact'])) {
    $conn->query("UPDATE contact SET is_deleted=1 WHERE id=" . (int)$_GET['delete_contact']);
}

/* ================= USER ACTIONS ================= */
if (isset($_GET['disable_user'])) {
    $conn->query("UPDATE users SET is_disabled=1 WHERE id=" . (int)$_GET['disable_user']);
}
if (isset($_GET['enable_user'])) {
    $conn->query("UPDATE users SET is_disabled=0 WHERE id=" . (int)$_GET['enable_user']);
}
if (isset($_GET['delete_user'])) {
    $conn->query("UPDATE users SET is_deleted=1 WHERE id=" . (int)$_GET['delete_user']);
}

/* ================= CONTACT PAGINATION + SEARCH ================= */
$c_limit = 5;
$c_page = max(1, (int)($_GET['c_page'] ?? 1));
$c_start = ($c_page - 1) * $c_limit;
$c_search = trim($_GET['c_search'] ?? '');

$c_where = "is_deleted=0";
if ($c_search !== '') {
    $safe = $conn->real_escape_string($c_search);
    $c_where .= " AND (name LIKE '%$safe%' OR email LIKE '%$safe%')";
}

$contacts = $conn->query(
    "SELECT * FROM contact WHERE $c_where ORDER BY id DESC LIMIT $c_start,$c_limit"
);

$c_total = $conn->query(
    "SELECT COUNT(*) FROM contact WHERE $c_where"
)->fetch_row()[0];
$c_pages = ceil($c_total / $c_limit);

/* ================= USER PAGINATION + SEARCH ================= */
$u_limit = 5;
$u_page = max(1, (int)($_GET['u_page'] ?? 1));
$u_start = ($u_page - 1) * $u_limit;
$u_search = trim($_GET['u_search'] ?? '');

$u_where = "is_deleted=0";
if ($u_search !== '') {
    $safe = $conn->real_escape_string($u_search);
    $u_where .= " AND (full_name LIKE '%$safe%' OR email LIKE '%$safe%')";
}

$users = $conn->query(
    "SELECT * FROM users WHERE $u_where ORDER BY id DESC LIMIT $u_start,$u_limit"
);

$u_total = $conn->query(
    "SELECT COUNT(*) FROM users WHERE $u_where"
)->fetch_row()[0];
$u_pages = ceil($u_total / $u_limit);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .dashboard-header {
            min-height: 30vh;
            background: linear-gradient(rgba(8, 23, 56, .85), rgba(8, 23, 56, .85)), url("assets/images/bs.jpg") center/cover;
            color: #fff;
            text-align: center;
            padding-top: 60px
        }

        .logout-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 22px;
            background: #e74c3c;
            color: #fff;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none
        }

        .dashboard {
            width: 90%;
            margin: -60px auto 80px
        }

        .card {
            background: #fff;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, .15);
            margin-bottom: 40px
        }

        .search-box {
            margin: 15px 0
        }

        .search-box input {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #ccc
        }

        .search-box button {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-weight: 600
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 0.9rem;
        }

        th {
            background: #f4f6fb;
        }

        .pagination {
            text-align: center;
            margin-top: 15px
        }

        .pagination a {
            padding: 8px 14px;
            background: #f1f1f1;
            border-radius: 8px;
            margin: 0 4px;
            font-weight: 600;
            text-decoration: none
        }

        .pagination a.active {
            background: var(--primary);
            color: #fff
        }

        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            color: #fff;
            font-size: .8rem;
            text-decoration: none
        }

        .hide {
            background: #f39c12
        }

        .unhide {
            background: #27ae60
        }

        .delete {
            background: #c0392b
        }

        .disable {
            background: #e67e22
        }

        .enable {
            background: #2ecc71
        }
    </style>
</head>

<body>

    <section class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['admin_id']) ?></p>
        <a href="admin_logout.php" class="logout-btn">Logout</a>
    </section>

    <div class="dashboard">

        <!-- CONTACT CARD -->
        <div class="card">
            <h2>Contact Messages</h2>

            <form method="get" class="search-box">
                <input type="text" name="c_search" value="<?= htmlspecialchars($c_search) ?>" placeholder="Search contact">
                <button type="submit">Search</button>
            </form>

            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($c = $contacts->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['email']) ?></td>
                        <td><?= htmlspecialchars($c['message']) ?></td>
                        <td><?= $c['is_hidden'] ? 'Hidden' : 'Visible' ?></td>
                        <td>
                            <?= !$c['is_hidden'] ? "<a class='action-btn hide' href='?hide_contact={$c['id']}'>Hide</a>" : "<a class='action-btn unhide' href='?unhide_contact={$c['id']}'>Unhide</a>" ?>
                            <a class="action-btn delete" href="?delete_contact=<?= $c['id'] ?>">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $c_pages; $i++): ?>
                    <a class="<?= $i == $c_page ? 'active' : '' ?>" href="?c_page=<?= $i ?>&c_search=<?= urlencode($c_search) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- USERS CARD -->
        <div class="card">
            <h2>Registered Users</h2>

            <form method="get" class="search-box">
                <input type="text" name="u_search" value="<?= htmlspecialchars($u_search) ?>" placeholder="Search user">
                <button type="submit">Search</button>
            </form>

            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php while ($u = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= $u['is_disabled'] ? 'Disabled' : 'Active' ?></td>
                        <td>
                            <?= !$u['is_disabled'] ? "<a class='action-btn disable' href='?disable_user={$u['id']}'>Disable</a>" : "<a class='action-btn enable' href='?enable_user={$u['id']}'>Enable</a>" ?>
                            <a class="action-btn delete" href="?delete_user=<?= $u['id'] ?>">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $u_pages; $i++): ?>
                    <a class="<?= $i == $u_page ? 'active' : '' ?>" href="?u_page=<?= $i ?>&u_search=<?= urlencode($u_search) ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

    </div>

    <section class="footer">
        <h4>© <?= date("Y") ?> World's Biggest University</h4>
        <p>Empowering students through education, innovation, and excellence.</p>
        <p>All Rights Reserved.</p>
        <p>Designed & Developed by <strong>Modasiya Jaydip</strong></p>
    </section>

</body>

</html>