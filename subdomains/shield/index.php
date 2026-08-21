<?php
// Lab 1622 — Aliyun WAF Bypass: cat Blocked, tac/head/nl/rev/tail Bypass
// Real-world finding: Aliyun WAF blocks `cat` but allows alternative file-reading commands
// Payload: cat /etc/hosts → BLOCKED; tac /etc/hosts → BYPASS 🧙‍♂️
// Reference: https://www.alibabacloud.com/help/en/waf/

$cmd = $_GET['cmd'] ?? '';
$output = '';
$waf_blocked = false;
$error_msg = '';

// WAF detection: block commands starting with "cat " (word boundary)
if ($cmd !== '' && preg_match('/\bcat\s+/', $cmd)) {
    $waf_blocked = true;
} elseif ($cmd !== '') {
    // Execute the command for real
    try {
        $raw = shell_exec($cmd . ' 2>&1');
        $output = $raw !== null ? $raw : 'No output returned.';
    } catch (Exception $e) {
        $output = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aliyun CloudShell — ECS Command Center</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --ali-orange: #FF6A00;
            --ali-orange-light: #FF8C38;
            --ali-orange-dark: #E05D00;
            --ali-blue: #1650E0;
            --ali-blue-light: #2D6BFF;
            --ali-blue-dark: #0F3DA8;
            --bg-side: #0b1420;
            --bg-main: #101827;
            --bg-card: #192132;
            --bg-input: #0d1522;
            --border: #243049;
            --text: #E8EDF5;
            --text-dim: #7B93B0;
            --text-muted: #4B6589;
            --green: #22C55E;
            --green-bg: rgba(34,197,94,0.08);
            --red: #EF4444;
            --red-bg: rgba(239,68,68,0.1);
            --yellow: #F59E0B;
            --yellow-bg: rgba(245,158,11,0.1);
            --radius: 8px;
            --radius-lg: 12px;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: var(--bg-main);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .side {
            width: 240px;
            background: var(--bg-side);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 50;
        }
        .side-brand {
            padding: 20px 18px 22px;
            border-bottom: 1px solid var(--border);
        }
        .side-brand .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .side-brand .logo-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--ali-orange), var(--ali-orange-dark));
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: #fff;
        }
        .side-brand .logo-text {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }
        .side-brand .logo-text span {
            color: var(--ali-orange);
        }
        .side-brand .logo-sub {
            font-size: 10px;
            color: var(--text-muted);
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .side-nav { list-style: none; padding: 8px 0; flex: 1; }
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
            transition: all 0.15s;
        }
        .side-nav li a i { width: 16px; text-align: center; font-size: 14px; }
        .side-nav li a:hover {
            color: var(--text);
            background: rgba(255,255,255,0.03);
            border-left-color: var(--text-muted);
        }
        .side-nav li a.active {
            color: var(--ali-orange);
            background: rgba(255,106,0,0.06);
            border-left-color: var(--ali-orange);
            font-weight: 600;
        }
        .side-section-label {
            padding: 16px 20px 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-muted);
        }
        .side-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            color: var(--text-dim);
        }
        .side-footer .region {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        .side-footer .region i { color: var(--green); font-size: 8px; }
        .side-footer .reg-name { color: var(--text); font-weight: 500; }

        /* ── MAIN ── */
        .main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

        /* Topbar */
        .topbar {
            background: var(--bg-side);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 56px;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .topbar-left .crumb {
            font-size: 13px;
            color: var(--text-dim);
        }
        .topbar-left .crumb-sep { color: var(--text-muted); font-size: 12px; }
        .topbar-left .crumb-current { color: var(--text); font-weight: 600; font-size: 13px; }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .status-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--green);
            background: var(--green-bg);
            padding: 4px 14px;
            border-radius: 20px;
        }
        .status-badge i { font-size: 8px; }
        .status-badge.waf-alert {
            color: var(--yellow);
            background: var(--yellow-bg);
        }
        .avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ali-orange), var(--ali-blue));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }

        /* Body */
        .body-area { flex: 1; padding: 28px; overflow-y: auto; }

        .page-hdr { margin-bottom: 24px; }
        .page-hdr h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-hdr h1 i { color: var(--ali-orange); }
        .page-hdr p { font-size: 13px; color: var(--text-dim); margin-top: 4px; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start; }
        @media (max-width: 1000px) { .two-col { grid-template-columns: 1fr; } }

        .panel {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
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
        .panel-title i { color: var(--ali-orange); }

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
            font-family: 'Monaco','Menlo','Ubuntu Mono', monospace;
            transition: border-color 0.2s;
        }
        .field input:focus {
            border-color: var(--ali-orange);
            box-shadow: 0 0 0 3px rgba(255,106,0,0.12);
            outline: none;
        }
        .field .hint { font-size: 11px; color: var(--text-dim); margin-top: 4px; }

        .btn-exec {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--ali-orange), var(--ali-orange-dark));
            color: #fff;
            border: none;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-exec:hover {
            background: linear-gradient(135deg, var(--ali-orange-dark), #CC5200);
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(255,106,0,0.3);
        }
        .btn-exec:active { transform: translateY(0); }

        .btn-preset {
            background: transparent;
            color: var(--text-dim);
            border: 1px solid var(--border);
            border-radius: 4px;
            padding: 4px 10px;
            font-size: 11px;
            font-family: 'Monaco','Menlo','Ubuntu Mono', monospace;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-preset:hover {
            border-color: var(--ali-orange);
            color: var(--ali-orange);
            background: rgba(255,106,0,0.05);
        }
        .btn-preset.blocked {
            color: var(--red);
            border-color: rgba(239,68,68,0.3);
        }
        .btn-preset.blocked:hover {
            border-color: var(--red);
            background: var(--red-bg);
        }
        .btn-preset.bypass {
            color: var(--green);
            border-color: rgba(34,197,94,0.3);
        }
        .btn-preset.bypass:hover {
            border-color: var(--green);
            background: var(--green-bg);
        }

        .presets { margin-top: 16px; }
        .presets-group {
            margin-bottom: 10px;
        }
        .presets-group-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .presets-btns {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* Terminal */
        .term {
            background: #070b14;
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
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
        .term-head i { font-size: 12px; }
        .term-head .dot-green { color: var(--green); }
        .term-head span.label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: var(--text-dim);
        }
        .term-head .instance-id {
            font-size: 11px;
            color: var(--text-muted);
            margin-left: auto;
        }
        .term-body {
            padding: 20px;
            min-height: 300px;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'Monaco','Menlo','Ubuntu Mono', monospace;
            font-size: 13px;
            line-height: 1.7;
            color: var(--green);
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .term-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            color: var(--text-dim);
            text-align: center;
        }
        .term-empty i { font-size: 36px; opacity: 0.15; margin-bottom: 12px; color: var(--ali-orange); }
        .term-empty p { font-size: 13px; }
        .term-empty .hint { font-size: 11px; color: var(--text-muted); margin-top: 6px; }

        /* WAF Banner */
        .waf-banner {
            background: var(--red-bg);
            border: 1px solid rgba(239,68,68,0.25);
            border-left: 4px solid var(--red);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
        }
        .waf-banner i { font-size: 20px; color: var(--red); margin-top: 1px; }
        .waf-banner-content h4 {
            font-size: 14px;
            font-weight: 700;
            color: var(--red);
            margin-bottom: 4px;
        }
        .waf-banner-content p {
            font-size: 12px;
            color: #FCA5A5;
            line-height: 1.5;
        }
        .waf-banner-content code {
            background: rgba(255,255,255,0.08);
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 12px;
            color: #FCA5A5;
        }

        .waf-full {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 300px;
            text-align: center;
            padding: 40px;
        }
        .waf-full i {
            font-size: 48px;
            color: var(--red);
            margin-bottom: 16px;
            opacity: 0.7;
        }
        .waf-full h3 {
            font-size: 18px;
            color: var(--red);
            margin-bottom: 8px;
        }
        .waf-full p {
            font-size: 13px;
            color: var(--text-dim);
            max-width: 400px;
            line-height: 1.6;
        }
        .waf-full .waf-rule {
            margin-top: 16px;
            background: rgba(239,68,68,0.06);
            border: 1px solid rgba(239,68,68,0.15);
            border-radius: 6px;
            padding: 12px 20px;
            font-size: 12px;
        }
        .waf-full .waf-rule code {
            background: rgba(255,255,255,0.06);
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 13px;
            color: #FCA5A5;
        }

        /* Info footer */
        .info-footer {
            margin-top: 24px;
            padding: 20px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
        }
        .info-footer h5 {
            font-size: 13px;
            font-weight: 700;
            color: var(--ali-orange);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-footer p {
            font-size: 12px;
            line-height: 1.7;
            color: var(--text-dim);
        }
        .info-footer code {
            background: rgba(255,255,255,0.06);
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 12px;
            color: var(--yellow);
        }
        .info-footer .bypass-table {
            margin-top: 12px;
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .info-footer .bypass-table th {
            text-align: left;
            padding: 6px 10px;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.3px;
        }
        .info-footer .bypass-table td {
            padding: 6px 10px;
            border-bottom: 1px solid rgba(36,48,73,0.4);
            color: var(--text-dim);
            font-family: 'Monaco','Menlo','Ubuntu Mono', monospace;
            font-size: 12px;
        }
        .info-footer .bypass-table .badge-blocked {
            color: var(--red);
            font-weight: 600;
        }
        .info-footer .bypass-table .badge-bypass {
            color: var(--green);
            font-weight: 600;
        }

        .footer-note {
            text-align: center;
            padding: 14px 28px;
            border-top: 1px solid var(--border);
            font-size: 11px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <!-- ═══ SIDEBAR ═══ -->
    <aside class="side">
        <div class="side-brand">
            <div class="logo">
                <div class="logo-icon">A</div>
                <div>
                    <div class="logo-text">Aliyun<span>Cloud</span></div>
                    <div class="logo-sub">Alibaba Cloud Console</div>
                </div>
            </div>
        </div>
        <div class="side-section-label">Compute</div>
        <ul class="side-nav">
            <li><a href="#"><i class="fas fa-th-large"></i>ECS Dashboard</a></li>
            <li><a href="#"><i class="fas fa-server"></i>Instances</a></li>
            <li><a href="#" class="active"><i class="fas fa-terminal"></i>Cloud Shell</a></li>
            <li><a href="#"><i class="fas fa-image"></i>Images</a></li>
            <li><a href="#"><i class="fas fa-network-wired"></i>Security Groups</a></li>
        </ul>
        <div class="side-section-label">Management</div>
        <ul class="side-nav">
            <li><a href="#"><i class="fas fa-shield-alt"></i>WAF & Security</a></li>
            <li><a href="#"><i class="fas fa-chart-bar"></i>CloudMonitor</a></li>
            <li><a href="#"><i class="fas fa-file-alt"></i>Operation Logs</a></li>
        </ul>
        <div class="side-footer">
            <div class="region"><i class="fas fa-circle"></i> <span class="reg-name">Singapore</span> (ap-southeast-1)</div>
            <div>UID: 176452*****0128</div>
        </div>
    </aside>

    <!-- ═══ MAIN ═══ -->
    <div class="main">
        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <span class="crumb"><i class="fas fa-home"></i></span>
                <span class="crumb-sep">/</span>
                <span class="crumb">ECS</span>
                <span class="crumb-sep">/</span>
                <span class="crumb-current">Cloud Shell</span>
            </div>
            <div class="topbar-right">
                <?php if ($waf_blocked): ?>
                    <span class="status-badge waf-alert"><i class="fas fa-shield-halved"></i> WAF BLOCKED</span>
                <?php else: ?>
                    <span class="status-badge"><i class="fas fa-circle"></i> All Systems Operational</span>
                <?php endif; ?>
                <div class="avatar">AK</div>
            </div>
        </header>

        <div class="body-area">
            <div class="page-hdr">
                <h1><i class="fas fa-terminal"></i> Cloud Shell</h1>
                <p>Execute commands on ECS instance <strong>i-2zeb5k1p7m</strong> (production-web-01). WAF rules are active.</p>
            </div>

            <!-- WAF Block Banner (shown when cat is detected) -->
            <?php if ($waf_blocked): ?>
            <div class="waf-banner">
                <i class="fas fa-shield-halved"></i>
                <div class="waf-banner-content">
                    <h4>🚫 Aliyun WAF Rule #1024 — Command Blocked</h4>
                    <p>
                        The <code>cat</code> command triggered WAF Rule #1024 (Command Injection / File Read).
                        This rule blocks <code>cat</code> because it is commonly used in LFI/RCE attacks.
                        <br><br>
                        <strong>🧙‍♂️ Bypass tip:</strong> Try alternative commands like <code>tac</code>, <code>head</code>,
                        <code>tail</code>, <code>nl</code>, <code>rev</code>, or <code>more</code> to read files
                        without triggering the WAF.
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <div class="two-col">
                <!-- LEFT: Command Form -->
                <div class="panel">
                    <div class="panel-title">
                        <i class="fas fa-sliders-h"></i> Command Configuration
                    </div>

                    <form method="GET">
                        <div class="field">
                            <label for="cmd">Command</label>
                            <input type="text" id="cmd" name="cmd"
                                   value="<?php echo htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8'); ?>"
                                   placeholder="e.g. tac /etc/hosts">
                            <div class="hint">Enter a command to execute on the instance via Cloud Shell</div>
                        </div>

                        <button type="submit" class="btn-exec">
                            <i class="fas fa-play"></i> Execute
                        </button>

                        <div class="presets">
                            <div class="presets-group">
                                <div class="presets-group-label"><i class="fas fa-ban" style="color:var(--red);"></i> Blocked by WAF (cat)</div>
                                <div class="presets-btns">
                                    <button type="button" class="btn-preset blocked" onclick="setCmd('cat /etc/hosts')">cat /etc/hosts</button>
                                    <button type="button" class="btn-preset blocked" onclick="setCmd('cat /etc/passwd')">cat /etc/passwd</button>
                                </div>
                            </div>
                            <div class="presets-group">
                                <div class="presets-group-label"><i class="fas fa-check-circle" style="color:var(--green);"></i> WAF Bypass 🧙‍♂️</div>
                                <div class="presets-btns">
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('tac /etc/hosts')">tac /etc/hosts</button>
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('tac /etc/passwd')">tac /etc/passwd</button>
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('head -n 10 /etc/passwd')">head -n 10 /etc/passwd</button>
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('tail -n 20 /etc/hosts')">tail -n 20 /etc/hosts</button>
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('nl /etc/hosts')">nl /etc/hosts</button>
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('rev /etc/hosts')">rev /etc/hosts</button>
                                    <button type="button" class="btn-preset bypass" onclick="setCmd('more /etc/passwd')">more /etc/passwd</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- RIGHT: Terminal Output -->
                <div class="term">
                    <div class="term-head">
                        <i class="fas fa-circle dot-green"></i>
                        <span class="label">Terminal Output</span>
                        <span class="instance-id">i-2zeb5k1p7m</span>
                    </div>
                    <div class="term-body">
                        <?php if ($waf_blocked): ?>
                            <div class="waf-full">
                                <i class="fas fa-shield-halved"></i>
                                <h3>🚫 Blocked by Aliyun WAF</h3>
                                <p>
                                    The command <code style="color:#FCA5A5;"><?php echo htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8'); ?></code>
                                    was blocked by WAF Rule #1024. Aliyun WAF detects <code>cat</code> as a
                                    file read attempt commonly used in LFI/RCE attacks.
                                </p>
                                <div class="waf-rule">
                                    <strong>Rule ID:</strong> 1024 &nbsp;|&nbsp;
                                    <strong>Action:</strong> BLOCK &nbsp;|&nbsp;
                                    <strong>Matched:</strong> <code>cat</code> command pattern<br>
                                    <span style="color:var(--yellow);">🧙‍♂️ Try tac, head, tail, nl, or rev instead!</span>
                                </div>
                            </div>
                        <?php elseif ($cmd === ''): ?>
                            <div class="term-empty">
                                <i class="fas fa-terminal"></i>
                                <p>Enter a command and click <strong>Execute</strong></p>
                                <p class="hint">Try: <code>tac /etc/hosts</code> or <code>head -n 5 /etc/passwd</code></p>
                            </div>
                        <?php else: ?>
                            <span style="color:var(--text-dim);"># Running: <?php echo htmlspecialchars($cmd, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span style="color:var(--text-dim);"> on i-2zeb5k1p7m (production-web-01)</span>
                            <span style="color:var(--text-muted);"> at <?php echo date('Y-m-d H:i:s'); ?></span>
                            <span>—</span>
                            <?php echo htmlspecialchars($output, ENT_QUOTES, 'UTF-8'); ?>
                            <span style="color:var(--text-dim); margin-top:12px; display:block;">
                                ————————————————
                                Command completed with exit code 0
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Vulnerability Info -->
            <div class="info-footer">
                <h5><i class="fas fa-graduation-cap"></i> 🧙‍♂️ Aliyun WAF Bypass — Real-World Finding</h5>
                <p>
                    <strong>The Finding:</strong> Aliyun WAF (Web Application Firewall) blocks the <code>cat</code> command
                    because it's a common vector for Local File Inclusion (LFI) and Remote Code Execution (RCE) attacks.
                    However, the WAF rule only checks for the literal <code>cat</code> keyword — it doesn't account for
                    alternative file-reading commands available on Linux/Unix systems.
                </p>
                <p>
                    <strong>🧙‍♂️ The Bypass:</strong> Commands like <code>tac</code> (reverse of cat),
                    <code>head</code>, <code>tail</code>, <code>nl</code> (number lines), <code>rev</code>
                    (reverse characters), and <code>more</code>/
                    <code>less</code> can all read file contents but are <strong>not</strong> blocked by the WAF ruleset.
                </p>
                <table class="bypass-table">
                    <tr>
                        <th>Command</th>
                        <th>Result</th>
                        <th>Notes</th>
                    </tr>
                    <tr>
                        <td>cat /etc/hosts</td>
                        <td><span class="badge-blocked">🚫 BLOCKED</span></td>
                        <td>Triggers WAF Rule #1024</td>
                    </tr>
                    <tr>
                        <td>tac /etc/hosts</td>
                        <td><span class="badge-bypass">✅ BYPASS</span></td>
                        <td>Prints file in reverse line order</td>
                    </tr>
                    <tr>
                        <td>head /etc/passwd</td>
                        <td><span class="badge-bypass">✅ BYPASS</span></td>
                        <td>Prints first 10 lines by default</td>
                    </tr>
                    <tr>
                        <td>tail /etc/hosts</td>
                        <td><span class="badge-bypass">✅ BYPASS</span></td>
                        <td>Prints last 10 lines by default</td>
                    </tr>
                    <tr>
                        <td>nl /etc/passwd</td>
                        <td><span class="badge-bypass">✅ BYPASS</span></td>
                        <td>Prints line numbers before file content</td>
                    </tr>
                    <tr>
                        <td>rev /etc/hosts</td>
                        <td><span class="badge-bypass">✅ BYPASS</span></td>
                        <td>Reverses each line's characters</td>
                    </tr>
                    <tr>
                        <td>more /etc/passwd</td>
                        <td><span class="badge-bypass">✅ BYPASS</span></td>
                        <td>Pager — prints file one screen at a time</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="footer-note">
            Aliyun Cloud Shell &bull; ECS Instance i-2zeb5k1p7m &bull; Singapore (ap-southeast-1)
        </div>
    </div>

    <script>
    function setCmd(cmd) {
        document.getElementById('cmd').value = cmd;
        document.querySelector('form').submit();
    }
    </script>
</body>
</html>
