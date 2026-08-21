<?php
// Lab 1: Basic Command Injection RCE
// Vulnerability: Direct command execution without validation

$message = '';
$command_output = '';
$command = '';

// Handle command execution request
if (isset($_GET['cmd']) && !empty($_GET['cmd'])) {
    $command = $_GET['cmd'];
    
    // Vulnerable: Direct command execution without validation
    try {
        $output = shell_exec($command . ' 2>&1');
        $command_output = $output ?: 'No output';
        $message = '<div class="alert alert-success">Command executed successfully!</div>';
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error executing command: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NexusForge — Server Infrastructure</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-side: #0f1117;
            --bg-main: #13151c;
            --bg-card: #1a1d28;
            --bg-input: #12141e;
            --border: #252936;
            --text: #e2e8f0;
            --text-dim: #8892a4;
            --accent: #f59e0b;
            --accent-dim: #92400e;
            --green: #22c55e;
            --red: #ef4444;
            --blue: #3b82f6;
            --radius: 8px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: var(--bg-main);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .side {
            width: 240px;
            background: var(--bg-side);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
        }
        .side-brand {
            padding: 22px 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 18px;
            font-weight: 700;
            color: var(--accent);
        }
        .side-brand i { font-size: 20px; }
        .side-nav { list-style: none; padding: 12px 0; flex: 1; }
        .side-nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-dim);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
        }
        .side-nav li a:hover {
            color: var(--text);
            background: rgba(255,255,255,0.03);
            border-left-color: var(--accent);
        }
        .side-nav li a.active {
            color: var(--accent);
            background: rgba(245,158,11,0.06);
            border-left-color: var(--accent);
            font-weight: 600;
        }
        .side-nav li a i { width: 16px; text-align: center; }

        .side-status {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            font-size: 12px;
        }
        .side-status .dot {
            display: inline-block;
            width: 8px; height: 8px;
            border-radius: 50%;
            background: var(--green);
            margin-right: 6px;
        }
        .side-status .stat-label { color: var(--text-dim); }
        .side-status .stat-val { color: var(--text); font-weight: 600; }

        /* MAIN */
        .main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* TOPBAR */
        .topbar {
            background: var(--bg-side);
            border-bottom: 1px solid var(--border);
            padding: 12px 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
        }
        .topbar-left .crumb { color: var(--text-dim); }
        .topbar-left .sep { color: #374151; }
        .topbar-left .current { color: var(--text); font-weight: 600; }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .server-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--green);
            background: rgba(34,197,94,0.08);
            padding: 4px 12px;
            border-radius: 20px;
        }
        .server-badge i { font-size: 10px; }

        .avatar-sm {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #000;
        }

        /* BODY */
        .body-area { flex: 1; padding: 32px 28px; overflow-y: auto; }

        .page-hdr { margin-bottom: 28px; }
        .page-hdr h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .page-hdr p { font-size: 13px; color: var(--text-dim); }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        @media (max-width: 1000px) { .two-col { grid-template-columns: 1fr; } }

        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px;
        }
        .panel-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-title i { color: var(--accent); }

        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--text-dim);
            margin-bottom: 5px;
        }
        .field input {
            width: 100%;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 13px;
            color: var(--text);
            font-family: 'Monaco','Menlo','Ubuntu Mono',monospace;
            transition: border-color 0.2s;
        }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 2px rgba(245,158,11,0.12);
            outline: none;
        }
        .field .hint { font-size: 11px; color: var(--text-dim); margin-top: 4px; }

        .btn-cmd {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--accent);
            color: #000;
            border: none;
            padding: 9px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-cmd:hover {
            background: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245,158,11,0.25);
        }

        .btn-preset {
            background: transparent;
            color: var(--text-dim);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 11px;
            font-family: 'Monaco','Menlo','Ubuntu Mono',monospace;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-preset:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .presets {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
        .presets-label {
            font-size: 11px;
            color: var(--text-dim);
            margin-right: 6px;
            line-height: 28px;
        }

        /* TERMINAL OUTPUT */
        .term {
            background: #0a0c10;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .term-head {
            background: var(--bg-side);
            border-bottom: 1px solid var(--border);
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .term-head i { color: var(--green); font-size: 12px; }
        .term-head span { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; color: var(--text-dim); }
        .term-body {
            padding: 20px;
            min-height: 280px;
            font-family: 'Monaco','Menlo','Ubuntu Mono',monospace;
            font-size: 13px;
            line-height: 1.6;
            color: var(--green);
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .term-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
            color: var(--text-dim);
            text-align: center;
        }
        .term-empty i { font-size: 36px; opacity: 0.2; margin-bottom: 10px; }
        .term-empty p { font-size: 13px; }

        .footer-note {
            text-align: center;
            padding: 16px 28px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="side">
        <div class="side-brand">
            <i class="fas fa-cubes"></i>NexusForge
        </div>
        <ul class="side-nav">
            <li><a href="#"><i class="fas fa-th-large"></i>Dashboard</a></li>
            <li><a href="#"><i class="fas fa-server"></i>Servers</a></li>
            <li><a href="#" class="active"><i class="fas fa-terminal"></i>Commands</a></li>
            <li><a href="#"><i class="fas fa-activity"></i>Monitoring</a></li>
            <li><a href="#"><i class="fas fa-file-alt"></i>Logs</a></li>
            <li><a href="#"><i class="fas fa-cog"></i>Settings</a></li>
        </ul>
        <div class="side-status">
            <span class="dot"></span>
            <span class="stat-label">Servers:</span>
            <span class="stat-val">3 Online</span>
            <span style="margin-left:10px;color:var(--text-dim);">Uptime:</span>
            <span class="stat-val">99.9%</span>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="main">
        <header class="topbar">
            <div class="topbar-left">
                <span class="crumb">Infrastructure</span>
                <span class="sep">/</span>
                <span class="current">Remote Command Runner</span>
            </div>
            <div class="topbar-right">
                <span class="server-badge"><i class="fas fa-circle"></i>All Systems Operational</span>
                <div class="avatar-sm">NF</div>
            </div>
        </header>

        <div class="body-area">
            <div class="page-hdr">
                <h1>Remote Command Runner</h1>
                <p>Execute commands on connected infrastructure nodes. Output is streamed in real time from the selected server.</p>
            </div>

            <div class="two-col">
                <!-- LEFT: Form -->
                <div class="panel">
                    <div class="panel-title">
                        <i class="fas fa-sliders-h"></i> Command Configuration
                    </div>

                    <form method="GET">
                        <div class="field">
                            <label for="cmd">Command</label>
                            <input type="text" id="cmd" name="cmd"
                                   value="<?php echo htmlspecialchars($command); ?>"
                                   placeholder="e.g. uptime, whoami, uname -a">
                            <div class="hint">Enter a shell command to execute on the target node</div>
                        </div>

                        <button type="submit" class="btn-cmd">
                            <i class="fas fa-play"></i> Execute
                        </button>

                        <div class="presets">
                            <span class="presets-label">Quick:</span>
                            <button type="button" class="btn-preset" onclick="setCmd('uptime')">uptime</button>
                            <button type="button" class="btn-preset" onclick="setCmd('whoami')">whoami</button>
                            <button type="button" class="btn-preset" onclick="setCmd('uname -a')">uname -a</button>
                            <button type="button" class="btn-preset" onclick="setCmd('df -h')">df -h</button>
                            <button type="button" class="btn-preset" onclick="setCmd('free -m')">free -m</button>
                        </div>
                    </form>
                </div>

                <!-- RIGHT: Output -->
                <div class="term">
                    <div class="term-head">
                        <i class="fas fa-circle"></i>
                        <span>Output — node-01.prod</span>
                    </div>
                    <div class="term-body">
                        <?php if ($command_output): ?>
                            <?php echo htmlspecialchars($command_output); ?>
                        <?php else: ?>
                            <div class="term-empty">
                                <i class="fas fa-terminal"></i>
                                <p>Enter a command above and click <strong>Execute</strong> to see the output here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-note">
            NexusForge v2.4.1 — Server Infrastructure Platform
        </div>
    </div>

    <script>
        function setCmd(val) {
            document.getElementById('cmd').value = val;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
