<?php
// ── Database setup ──
$db_host = 'localhost';
$db_name = 'VexiumCTF';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

// Auto-create lab201_users table and seed default accounts
$pdo->exec("
    CREATE TABLE IF NOT EXISTS lab201_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('user','admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");
$seedCheck = $pdo->query("SELECT COUNT(*) FROM lab201_users")->fetchColumn();
if ($seedCheck == 0) {
    $ins = $pdo->prepare("INSERT INTO lab201_users (username, password, role) VALUES (?, ?, ?)");
    $ins->execute(['admin',   password_hash('admin',       PASSWORD_DEFAULT), 'admin']);
    $ins->execute(['support', password_hash('support@123', PASSWORD_DEFAULT), 'user']);
}

// ── AJAX login handler ──
if (isset($_GET['action']) && $_GET['action'] === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body  = json_decode(file_get_contents('php://input'), true);
    $uname = trim($body['UserName'] ?? '');
    $pass  = trim($body['Password'] ?? '');

    $stmt = $pdo->prepare("SELECT password FROM lab201_users WHERE username = ?");
    $stmt->execute([$uname]);
    $row = $stmt->fetch();

    // VULNERABLE: status value is returned in the JSON body but the CLIENT trusts it without
    // any server-side session. An attacker intercepts ANY response and flips false → true.
    if ($row && password_verify($pass, $row['password'])) {
        echo json_encode(['status' => true,  'errorMessage' => '']);
    } else {
        echo json_encode(['status' => false, 'errorMessage' => 'Username and Password does not match.']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPS Admin Portal — Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --ups-brown:    #351C15;
            --ups-brown-l:  #4A2C17;
            --ups-gold:     #FFB500;
            --ups-gold-d:   #D49800;
            --ups-dark:     #1a1a1b;
            --ups-surface:  #242426;
            --ups-border:   #3d3d3f;
            --ups-text:     #f2f2f2;
            --ups-muted:    #888;
            --ups-green:    #2ecc71;
            --ups-red:      #e74c3c;
        }

        body {
            background: var(--ups-dark);
            color: var(--ups-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
        }

        /* ── Lab top bar ── */
        .lab-topbar {
            background: linear-gradient(90deg, #0f172a, #1e1b4b);
            padding: 0.5rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.72rem;
            color: #94a3b8;
            border-bottom: 2px solid var(--ups-gold);
            position: sticky;
            top: 0;
            z-index: 200;
        }
        .lab-topbar a {
            color: #48bb78;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 600;
        }
        .lab-topbar a:hover { color: #68d391; }
        .lab-badge-real {
            background: linear-gradient(90deg, var(--ups-brown), var(--ups-brown-l));
            border: 1px solid var(--ups-gold);
            color: var(--ups-gold);
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.18rem 0.55rem;
            border-radius: 3px;
            white-space: nowrap;
        }

        /* ── UPS site header ── */
        .ups-header {
            background: var(--ups-brown);
            border-bottom: 3px solid var(--ups-gold);
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .ups-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .ups-logo-shield {
            width: 44px;
            height: 52px;
            background: var(--ups-gold);
            clip-path: polygon(50% 0%, 100% 15%, 100% 60%, 50% 100%, 0% 60%, 0% 15%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--ups-brown);
            letter-spacing: -0.02em;
        }
        .ups-logo-text { font-size: 0.75rem; color: rgba(255,255,255,0.7); line-height: 1.4; }
        .ups-logo-text strong { font-size: 1.1rem; color: #fff; display: block; }
        .ups-header-right { font-size: 0.72rem; color: rgba(255,255,255,0.5); text-align: right; }

        /* ── Login page ── */
        #loginPage {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 120px);
        }
        .login-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            background: linear-gradient(135deg, #0f0d0c 0%, #1a1412 50%, #0f0d0c 100%);
        }
        .login-card {
            background: var(--ups-surface);
            border: 1px solid var(--ups-border);
            border-top: 3px solid var(--ups-gold);
            border-radius: 6px;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--ups-gold);
            margin-bottom: 0.3rem;
        }
        .login-header p { font-size: 0.8rem; color: var(--ups-muted); }
        .form-group { margin-bottom: 1.25rem; }
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #aaa;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .form-control {
            width: 100%;
            background: #1a1a1b;
            border: 1px solid var(--ups-border);
            border-radius: 4px;
            color: var(--ups-text);
            padding: 0.7rem 0.9rem;
            font-size: 0.9rem;
            font-family: inherit;
            transition: border-color 0.2s;
            outline: none;
        }
        .form-control:focus { border-color: var(--ups-gold); }
        .btn-login-submit {
            width: 100%;
            background: var(--ups-gold);
            color: var(--ups-brown);
            border: none;
            border-radius: 4px;
            padding: 0.8rem;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        .btn-login-submit:hover { background: var(--ups-gold-d); }
        .btn-login-submit:disabled { opacity: 0.6; cursor: default; }
        .login-error {
            background: rgba(231,76,60,0.12);
            border: 1px solid rgba(231,76,60,0.3);
            color: #e74c3c;
            border-radius: 4px;
            padding: 0.6rem 0.85rem;
            font-size: 0.8rem;
            margin-top: 0.75rem;
            display: none;
        }
        .login-footer-links {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--ups-muted);
        }
        .login-footer-links a { color: var(--ups-gold); text-decoration: none; margin: 0 0.5rem; }


        /* ── Admin panel ── */
        #adminPanel { display: none; }
        .admin-header {
            background: var(--ups-brown);
            border-bottom: 3px solid var(--ups-gold);
            padding: 0 2rem;
            display: flex;
            align-items: center;
            height: 52px;
            gap: 2rem;
        }
        .admin-header-title {
            color: var(--ups-gold);
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.04em;
        }
        .admin-nav { display: flex; gap: 0; flex: 1; }
        .admin-nav a {
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            font-size: 0.78rem;
            padding: 0 1rem;
            height: 52px;
            display: flex;
            align-items: center;
            border-bottom: 3px solid transparent;
            transition: all 0.15s;
        }
        .admin-nav a:hover, .admin-nav a.active {
            color: #fff;
            border-bottom-color: var(--ups-gold);
        }
        .admin-user {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.7);
        }
        .admin-user-dot {
            width: 8px;
            height: 8px;
            background: var(--ups-green);
            border-radius: 50%;
        }
        .admin-body {
            padding: 2rem;
            background: #111;
            min-height: calc(100vh - 175px);
        }
        .admin-bypass-alert {
            background: rgba(46,204,113,0.1);
            border: 1px solid rgba(46,204,113,0.3);
            border-left: 4px solid var(--ups-green);
            border-radius: 6px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.82rem;
            color: #a0f0c0;
        }
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--ups-surface);
            border: 1px solid var(--ups-border);
            border-radius: 6px;
            padding: 1.25rem;
        }
        .stat-card-label { font-size: 0.72rem; color: var(--ups-muted); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.5rem; }
        .stat-card-val { font-size: 1.8rem; font-weight: 700; color: var(--ups-gold); }
        .stat-card-sub { font-size: 0.72rem; color: var(--ups-muted); margin-top: 0.2rem; }
        .admin-section-title {
            font-size: 0.82rem;
            font-weight: 700;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .reports-table {
            background: var(--ups-surface);
            border: 1px solid var(--ups-border);
            border-radius: 6px;
            overflow: hidden;
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            margin-bottom: 2rem;
        }
        .reports-table thead th {
            background: var(--ups-brown);
            color: var(--ups-gold);
            padding: 0.65rem 1rem;
            text-align: left;
            font-weight: 700;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            border-bottom: 2px solid var(--ups-gold);
        }
        .reports-table tbody td {
            padding: 0.7rem 1rem;
            border-bottom: 1px solid var(--ups-border);
            color: var(--ups-text);
        }
        .reports-table tbody tr:hover { background: rgba(255,181,0,0.04); }
        .reports-table tbody tr:last-child td { border-bottom: none; }
        .status-badge {
            padding: 0.15rem 0.5rem;
            border-radius: 3px;
            font-size: 0.68rem;
            font-weight: 700;
        }
        .status-delivered { background: rgba(46,204,113,0.15); color: #2ecc71; }
        .status-transit   { background: rgba(255,181,0,0.15);  color: var(--ups-gold); }
        .status-pending   { background: rgba(52,152,219,0.15); color: #3498db; }

        /* ── Lab info box ── */
        .lab-info-box {
            background: linear-gradient(135deg, #0f172a, #1e1b4b);
            border: 1px solid #334155;
            border-left: 4px solid var(--ups-gold);
            border-radius: 8px;
            padding: 1.1rem 1.25rem;
            margin-top: 2rem;
            color: #e2e8f0;
        }
        .lab-info-box h4 {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--ups-gold);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .lab-info-box p { font-size: 0.82rem; color: #94a3b8; line-height: 1.65; }
        .lab-info-box code {
            background: rgba(255,255,255,0.08);
            padding: 0.1rem 0.35rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            color: #7dd3fc;
        }
        .lab-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem 1.25rem;
            margin-top: 0.65rem;
            padding-top: 0.65rem;
            border-top: 1px solid #1e293b;
        }
        .lab-meta-item { font-size: 0.72rem; color: #64748b; }
        .lab-meta-item strong { color: #94a3b8; }
    </style>
</head>
<body>

    <!-- Lab top bar -->
    <div class="lab-topbar">
        <a href="index.php">
            <i class="bi bi-arrow-left"></i> Back to Labs
        </a>
        <span class="lab-badge-real">HackerOne #1490470 &mdash; UPS VDP &mdash; Auth Bypass &mdash; Real World</span>
    </div>

    <!-- UPS site header -->
    <div class="ups-header">
        <div class="ups-logo">
            <div class="ups-logo-shield">UPS</div>
            <div class="ups-logo-text">
                <strong>UPS</strong>
                Shipment Administration Portal
            </div>
        </div>
        <div class="ups-header-right">
            Internal Use Only<br>
            Authorized Personnel
        </div>
    </div>

    <!-- ════════════════════ LOGIN PAGE ════════════════════ -->
    <div id="loginPage">
        <div class="login-wrap">
            <div class="login-card">
                <div class="login-header">
                    <h2><i class="bi bi-shield-lock-fill"></i> Admin Portal</h2>
                    <p>Sign in with your UPS administrator credentials</p>
                </div>
                <form id="loginForm" onsubmit="submitLogin(event)">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input type="text" id="username" class="form-control" placeholder="Enter username" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" class="form-control" placeholder="Enter password">
                    </div>
                    <button type="submit" class="btn-login-submit" id="loginBtn">
                        <i class="bi bi-box-arrow-in-right"></i> Sign In
                    </button>
                    <div class="login-error" id="loginError"></div>
                </form>
                <div class="login-footer-links">
                    <a href="#">Forgot Password?</a>
                    <a href="#">Help Desk</a>
                    <a href="#">Privacy Policy</a>
                </div>
            </div>
        </div>
    </div>


    <!-- ════════════════════ ADMIN PANEL ════════════════════ -->
    <div id="adminPanel">
        <div class="admin-header">
            <span class="admin-header-title"><i class="bi bi-shield-fill-check"></i> Admin Panel</span>
            <nav class="admin-nav">
                <a href="#" class="active">Dashboard</a>
                <a href="#">Shipments</a>
                <a href="#">Reports</a>
                <a href="#">Process Return</a>
                <a href="#">Users</a>
                <a href="#">Settings</a>
            </nav>
            <div class="admin-user">
                <div class="admin-user-dot"></div>
                Admin (bypassed)
            </div>
        </div>

        <div class="admin-body">

            <div class="admin-bypass-alert">
                <i class="bi bi-check-circle-fill" style="font-size:1.2rem;color:#2ecc71;flex-shrink:0;"></i>
                <div>
                    <strong>Authentication Bypassed!</strong> — You flipped <code style="background:rgba(255,255,255,0.1);padding:0.1rem 0.3rem;border-radius:3px;font-family:monospace;color:#a0f0c0;">"status":false</code> →
                    <code style="background:rgba(255,255,255,0.1);padding:0.1rem 0.3rem;border-radius:3px;font-family:monospace;color:#a0f0c0;">"status":true</code>
                    in the server response. The app trusted the client-side value and granted admin access.
                </div>
            </div>

            <!-- Stats -->
            <div class="admin-stats">
                <div class="stat-card">
                    <div class="stat-card-label"><i class="bi bi-file-earmark-text"></i> Total Reports</div>
                    <div class="stat-card-val">1,066</div>
                    <div class="stat-card-sub">+14 this week</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label"><i class="bi bi-box-seam"></i> Active Shipments</div>
                    <div class="stat-card-val">347</div>
                    <div class="stat-card-sub">In transit</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label"><i class="bi bi-arrow-return-left"></i> Process Returns</div>
                    <div class="stat-card-val">89</div>
                    <div class="stat-card-sub">Pending approval</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-label"><i class="bi bi-people-fill"></i> Customers (PII)</div>
                    <div class="stat-card-val">4,812</div>
                    <div class="stat-card-sub">Records exposed</div>
                </div>
            </div>

            <!-- Shipment reports table -->
            <div class="admin-section-title">
                <i class="bi bi-table"></i> Shipment Reports — PII Data
            </div>
            <table class="reports-table">
                <thead>
                    <tr>
                        <th>Tracking #</th>
                        <th>Pickup Location</th>
                        <th>Return Location</th>
                        <th>Service</th>
                        <th>Process Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code style="color:var(--ups-gold);">1Z999AA10123456784</code></td>
                        <td>142 Green St, Chicago IL 60601</td>
                        <td>UPS Warehouse, Louisville KY</td>
                        <td>UPS Ground</td>
                        <td>2022-02-18</td>
                        <td><span class="status-badge status-delivered">Delivered</span></td>
                        <td><a href="#" style="color:var(--ups-red);font-size:0.72rem;"><i class="bi bi-trash"></i> Delete</a></td>
                    </tr>
                    <tr>
                        <td><code style="color:var(--ups-gold);">1Z999AA10234567895</code></td>
                        <td>87 Oak Ave, Austin TX 78701</td>
                        <td>Sender — 87 Oak Ave</td>
                        <td>UPS 2nd Day Air</td>
                        <td>2022-02-19</td>
                        <td><span class="status-badge status-transit">In Transit</span></td>
                        <td><a href="#" style="color:var(--ups-red);font-size:0.72rem;"><i class="bi bi-trash"></i> Delete</a></td>
                    </tr>
                    <tr>
                        <td><code style="color:var(--ups-gold);">1Z999AA10345678906</code></td>
                        <td>531 River Rd, Miami FL 33101</td>
                        <td>UPS Store #4821, Miami FL</td>
                        <td>UPS Next Day Air</td>
                        <td>2022-02-20</td>
                        <td><span class="status-badge status-pending">Pending</span></td>
                        <td><a href="#" style="color:var(--ups-red);font-size:0.72rem;"><i class="bi bi-trash"></i> Delete</a></td>
                    </tr>
                    <tr>
                        <td><code style="color:var(--ups-gold);">1Z999AA10456789017</code></td>
                        <td>9 Maple Ln, Seattle WA 98101</td>
                        <td>UPS Warehouse, Seattle WA</td>
                        <td>UPS Ground</td>
                        <td>2022-02-21</td>
                        <td><span class="status-badge status-delivered">Delivered</span></td>
                        <td><a href="#" style="color:var(--ups-red);font-size:0.72rem;"><i class="bi bi-trash"></i> Delete</a></td>
                    </tr>
                    <tr>
                        <td><code style="color:var(--ups-gold);">1Z999AA10567890128</code></td>
                        <td>220 Pine St, Boston MA 02101</td>
                        <td>Sender — 220 Pine St</td>
                        <td>UPS Worldwide Express</td>
                        <td>2022-02-22</td>
                        <td><span class="status-badge status-transit">In Transit</span></td>
                        <td><a href="#" style="color:var(--ups-red);font-size:0.72rem;"><i class="bi bi-trash"></i> Delete</a></td>
                    </tr>
                </tbody>
            </table>

            <!-- Lab info box inside admin panel -->
            <div class="lab-info-box">
                <h4><i class="bi bi-bug-fill"></i> Real World Lab — Vulnerability Explained</h4>
                <p>
                    The database contains two accounts: <code>admin / admin</code> and <code>support / support@123</code>.<br><br>
                    The login endpoint returns <code>{"status":true}</code> for correct credentials and
                    <code>{"status":false}</code> for wrong ones. The client-side JavaScript reads this
                    <code>status</code> field to decide whether to grant access —
                    <strong style="color:#fbbf24;">no server-side session is created or validated</strong>.<br><br>
                    Use <strong style="color:#fbbf24;">Burp Suite</strong> to intercept the login response:
                    send any wrong password, intercept the response in Burp Proxy, and change
                    <code>"status":false</code> → <code>"status":true</code>, then forward.
                    The app grants admin access because it blindly trusts the client-received JSON value.<br><br>
                    The real-world impact included access to 1,066 shipment reports containing full PII
                    (tracking numbers, addresses, service details) and the ability to delete records or change the admin password.
                </p>
                <div class="lab-meta-row">
                    <div class="lab-meta-item"><strong>Platform:</strong> HackerOne</div>
                    <div class="lab-meta-item"><strong>Report:</strong> #1490470</div>
                    <div class="lab-meta-item"><strong>Target:</strong> UPS VDP</div>
                    <div class="lab-meta-item"><strong>Severity:</strong> Medium (6.5)</div>
                    <div class="lab-meta-item"><strong>Bounty:</strong> Hidden</div>
                    <div class="lab-meta-item"><strong>Researcher:</strong> 7odamoo</div>
                    <div class="lab-meta-item"><strong>Status:</strong> Resolved (Mar 2022)</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── Login submission ──
    async function submitLogin(e) {
        e.preventDefault();
        const btn    = document.getElementById('loginBtn');
        const errDiv = document.getElementById('loginError');
        errDiv.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Authenticating...';

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        try {
            const resp = await fetch('?action=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ UserName: username, Password: password })
            });
            const data = await resp.json();

            // VULNERABLE: app trusts the client-received JSON status field.
            // No server-side session is created — intercepting this response
            // in Burp Suite and flipping false → true grants admin access.
            if (data.status === true) {
                document.getElementById('loginPage').style.display = 'none';
                document.getElementById('adminPanel').style.display = 'block';
            } else {
                errDiv.textContent = data.errorMessage || 'Authentication failed.';
                errDiv.style.display = 'block';
            }
        } catch (err) {
            errDiv.textContent = 'Network error. Please try again.';
            errDiv.style.display = 'block';
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Sign In';
    }
    </script>
</body>
</html>
