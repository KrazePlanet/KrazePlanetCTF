<?php
$username = '';

// Primary: extract from URL path — index.php/account/{username}/messages
$path_info = $_SERVER['PATH_INFO'] ?? '';
if (preg_match('#^/account/(.+)/messages#', $path_info, $m)) {
    $username = $m[1];
} elseif (isset($_GET['username'])) {
    // Fallback: ?username= for convenience
    $username = $_GET['username'];
}

$safe_username = htmlspecialchars($username, ENT_QUOTES);

$messages = [
    [
        'avatar'   => 'S',
        'color'    => '#2ecc71',
        'sender'   => 'SaturnV',
        'preview'  => 'Hey, did you see that new astronomy post on the front page? The one with the nebula shots.',
        'time'     => '2m ago',
        'unread'   => true,
    ],
    [
        'avatar'   => 'M',
        'color'    => '#e74c3c',
        'sender'   => 'MemeKing99',
        'preview'  => 'lmaooo that cat gif you posted is already at 14k points',
        'time'     => '17m ago',
        'unread'   => true,
    ],
    [
        'avatar'   => 'P',
        'color'    => '#9b59b6',
        'sender'   => 'PixelWizard',
        'preview'  => 'I tried your Photoshop technique from the tutorial — worked perfectly. Thanks!',
        'time'     => '1h ago',
        'unread'   => false,
    ],
    [
        'avatar'   => 'D',
        'color'    => '#e67e22',
        'sender'   => 'DankVault',
        'preview'  => 'Did you submit your image to the weekend contest yet? Deadline is Sunday.',
        'time'     => '3h ago',
        'unread'   => false,
    ],
    [
        'avatar'   => 'N',
        'color'    => '#1abc9c',
        'sender'   => 'NightOwlGfx',
        'preview'  => 'Your HDR photo series is incredible. How long does each shot take to process?',
        'time'     => 'Yesterday',
        'unread'   => false,
    ],
];
$unread_count = count(array_filter($messages, fn($m) => $m['unread']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Messages — Imgur</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --imgur-bg:       #1a1a1b;
            --imgur-surface:  #1e1e1f;
            --imgur-card:     #242426;
            --imgur-border:   #343435;
            --imgur-green:    #1bb76e;
            --imgur-green-d:  #159957;
            --imgur-text:     #f2f2f2;
            --imgur-muted:    #888;
            --imgur-red:      #e84040;
        }

        body {
            background: var(--imgur-bg);
            color: var(--imgur-text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            max-width: 480px;
            margin: 0 auto;
        }
        .lab-badge-real {
            background: linear-gradient(90deg, #1bb76e, #159957);
            color: #fff;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.15rem 0.45rem;
            border-radius: 3px;
            white-space: nowrap;
        }

        /* ── Mobile top bar ── */
        .mobile-topbar {
            background: var(--imgur-surface);
            border-bottom: 1px solid var(--imgur-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            height: 52px;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .topbar-back {
            color: var(--imgur-muted);
            font-size: 1.1rem;
            text-decoration: none;
        }
        .topbar-title {
            font-size: 1rem;
            font-weight: 700;
        }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .topbar-icon {
            color: var(--imgur-muted);
            font-size: 1.1rem;
            text-decoration: none;
        }

        /* ── Profile strip ── */
        .profile-strip {
            background: var(--imgur-card);
            border-bottom: 1px solid var(--imgur-border);
            padding: 0.9rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .profile-avatar-lg {
            width: 46px;
            height: 46px;
            background: var(--imgur-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            text-transform: uppercase;
        }
        .profile-info { flex: 1; min-width: 0; }
        .profile-name {
            font-size: 0.95rem;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .profile-sub {
            font-size: 0.75rem;
            color: var(--imgur-muted);
            margin-top: 0.1rem;
        }
        /*
         * VULNERABLE ELEMENT:
         * The $username is echoed raw inside the double-quoted href below.
         * Payload: testcatplzignore"><img src=x onerror=prompt(document.domain)>
         * The " closes the href, > closes the <a> tag, and the <img> fires onerror on load.
         */
        .profile-link {
            color: var(--imgur-green);
            font-size: 0.72rem;
            text-decoration: none;
            margin-top: 0.2rem;
            display: block;
        }

        /* ── Section header ── */
        .section-header {
            padding: 0.6rem 1rem 0.4rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--imgur-muted);
            background: var(--imgur-bg);
            border-bottom: 1px solid var(--imgur-border);
        }

        /* ── Message list ── */
        .message-list { background: var(--imgur-bg); }
        .message-item {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.9rem 1rem;
            border-bottom: 1px solid var(--imgur-border);
            text-decoration: none;
            color: inherit;
            transition: background 0.15s;
        }
        .message-item:hover { background: var(--imgur-card); }
        .msg-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            text-transform: uppercase;
        }
        .msg-body { flex: 1; min-width: 0; }
        .msg-header {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 0.2rem;
        }
        .msg-sender {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--imgur-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 180px;
        }
        .msg-time {
            font-size: 0.7rem;
            color: var(--imgur-muted);
            white-space: nowrap;
            flex-shrink: 0;
        }
        .msg-preview {
            font-size: 0.8rem;
            color: var(--imgur-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .msg-preview.unread { color: var(--imgur-text); font-weight: 500; }
        .unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--imgur-green);
            flex-shrink: 0;
        }

        /* ── Empty / compose area ── */
        .compose-fab {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            width: 52px;
            height: 52px;
            background: var(--imgur-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.3rem;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(27,183,110,0.4);
        }
        .compose-fab:hover { background: var(--imgur-green-d); }

        /* ── Bottom nav ── */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 480px;
            background: var(--imgur-surface);
            border-top: 1px solid var(--imgur-border);
            display: flex;
            justify-content: space-around;
            padding: 0.5rem 0 0.6rem;
        }
        .bottom-nav a {
            color: var(--imgur-muted);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.15rem;
            font-size: 0.6rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            flex: 1;
        }
        .bottom-nav a.active { color: var(--imgur-green); }
        .bottom-nav a i { font-size: 1.2rem; }
        .nav-badge {
            position: relative;
            display: inline-block;
        }
        .nav-badge::after {
            content: '<?php echo $unread_count; ?>';
            position: absolute;
            top: -4px;
            right: -8px;
            background: var(--imgur-red);
            color: #fff;
            font-size: 0.55rem;
            font-weight: 700;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 14px;
            text-align: center;
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

    <!-- Mobile top bar -->
    <div class="mobile-topbar">
        <div class="topbar-left">
            <a href="#" class="topbar-back"><i class="bi bi-chevron-left"></i></a>
            <span class="topbar-title">
                Messages
                <?php if ($unread_count > 0): ?>
                    <span style="background:var(--imgur-red);color:#fff;font-size:0.6rem;padding:0.1rem 0.4rem;border-radius:8px;margin-left:4px;vertical-align:middle;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </span>
        </div>
        <div class="topbar-right">
            <a href="#" class="topbar-icon"><i class="bi bi-search"></i></a>
            <a href="#" class="topbar-icon"><i class="bi bi-gear"></i></a>
        </div>
    </div>

    <!-- Profile strip with VULNERABLE href -->
    <div class="profile-strip">
        <div class="profile-avatar-lg">
            <?php echo $username !== '' ? htmlspecialchars(mb_substr($username, 0, 1)) : 'U'; ?>
        </div>
        <div class="profile-info">
            <div class="profile-name"><?php echo $safe_username !== '' ? $safe_username : 'Guest'; ?></div>
            <div class="profile-sub">Imgur member &middot; 847 posts &middot; 12.3k points</div>
            <!--
                VULNERABLE: $username is echoed raw into this double-quoted href.
                Payload: testcatplzignore"><img src=x onerror=prompt(document.domain)>
            -->
            <a class="profile-link"
               href="/account/<?php echo $username; ?>/profile">
                View profile &rarr;
            </a>
        </div>
    </div>

    <!-- Message list header -->
    <div class="section-header">Conversations</div>

    <!-- Messages -->
    <div class="message-list">
        <?php foreach ($messages as $msg): ?>
        <a href="<?php echo $_SERVER['SCRIPT_NAME']; ?>/account/<?php echo rawurlencode($msg['sender']); ?>/messages" class="message-item">
            <div class="msg-avatar" style="background:<?php echo $msg['color']; ?>">
                <?php echo $msg['avatar']; ?>
            </div>
            <div class="msg-body">
                <div class="msg-header">
                    <span class="msg-sender"><?php echo htmlspecialchars($msg['sender']); ?></span>
                    <span class="msg-time"><?php echo $msg['time']; ?></span>
                </div>
                <div class="msg-preview<?php echo $msg['unread'] ? ' unread' : ''; ?>">
                    <?php echo htmlspecialchars($msg['preview']); ?>
                </div>
            </div>
            <?php if ($msg['unread']): ?>
            <div class="unread-dot"></div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Compose FAB -->
    <a href="#" class="compose-fab"><i class="bi bi-pencil-fill"></i></a>

    <!-- Bottom nav -->
    <nav class="bottom-nav">
        <a href="#"><i class="bi bi-house-fill"></i><span>Home</span></a>
        <a href="#"><i class="bi bi-compass-fill"></i><span>Explore</span></a>
        <a href="#"><i class="bi bi-plus-square-fill"></i><span>Post</span></a>
        <a href="#" class="active">
            <span class="nav-badge"><i class="bi bi-chat-fill"></i></span>
            <span>Messages</span>
        </a>
        <a href="#"><i class="bi bi-person-fill"></i><span>Profile</span></a>
    </nav>

</body>
</html>
