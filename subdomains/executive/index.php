<?php
// SoundVault Records -- Artist Database

$server   = "localhost";
$username = "root";
$password = "";
$database = "KrazePlanet_DB";

$conn = mysqli_connect($server, $username, $password);
if (!$conn) { die("DB connection failed"); }
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database");
mysqli_select_db($conn, $database);

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS lab1606_artists ("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
    . "name VARCHAR(255) NOT NULL,"
    . "genre VARCHAR(100) NOT NULL,"
    . "album_count INT NOT NULL DEFAULT 0,"
    . "biography TEXT,"
    . "label VARCHAR(255) NOT NULL DEFAULT 'SoundVault Records'"
. ")");

$check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM lab1606_artists");
$row = mysqli_fetch_assoc($check);
if ($row['cnt'] == 0) {
    mysqli_query($conn, "INSERT INTO lab1606_artists (name, genre, album_count, biography, label) VALUES"
        . "('Aurora Veil', 'Synth-Pop / Dream Pop', 4, 'Aurora Veil emerged from the Oslo electronic scene in 2018, blending ethereal vocals with analog synthesizers. Her debut album \"Neon Dusk\" reached #3 on the Scandinavian Electronic Charts.', 'SoundVault Records'),"
        . "('The Rust Brothers', 'Blues Rock / Southern Rock', 7, 'Hailing from Nashville, this four-piece band brings raw delta blues energy fused with modern rock. Their signature sound features slide guitar and soulful harmonies.', 'Highline Music Group'),"
        . "('Luna Mariposa', 'Latin Pop / Reggaeton', 3, 'Mexican-Colombian artist who broke streaming records with her single \"Fuego Lento\" (127M Spotify streams). Known for genre-blending productions and high-energy live performances.', 'SoundVault Records'),"
        . "('Static Aura', 'Industrial / Darkwave', 5, 'Berlin-based electronic project pushing the boundaries of industrial soundscapes. Their live shows are immersive experiences with synchronized laser installations.', 'Neon Fringe Records'),"
        . "('Cedar & Smoke', 'Indie Folk / Americana', 2, 'Portland duo known for haunting vocal harmonies and minimalist acoustic arrangements. Their sophomore album \"Ghost Towns\" was featured on NPRs Top 50 of 2025.', 'SoundVault Records'),"
        . "('DJ Kargo', 'Electronic / House', 12, 'Amsterdam-based DJ and producer with residencies at clubs across Europe. Known for genre-defying sets that blend house, breakbeat, and world music influences.', 'SoundVault Records'),"
        . "('Violet Storm', 'Alternative Rock / Grunge', 6, 'Seattle trio carrying the torch of 90s grunge with a modern edge. Their sophomore album won the 2024 Independent Music Award for Best Rock Album.', 'Highline Music Group')");
}

$param_key   = "";
$param_value = "";
$artist_data = null;
$error_msg   = "";

if (!empty($_GET)) {
    $keys = array_keys($_GET);
    $param_key   = $keys[0];
    $param_value = $_GET[$param_key];
    $sql = "SELECT id, name, genre, album_count, biography, label FROM lab1606_artists WHERE $param_key = '" . mysqli_real_escape_string($conn, $param_value) . "'";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        $artist_data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $artist_data[] = $row;
        }
    } else {
        $error_msg = mysqli_error($conn);
        $artist_data = array();
    }
} else {
    $result = mysqli_query($conn, "SELECT id, name, genre, album_count, biography, label FROM lab1606_artists ORDER BY album_count DESC");
    $artist_data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $artist_data[] = $row;
    }
}
$icons = array('purple','orange','teal','pink','blue','green','red');
$fas = array('purple'=>'fa-guitar','orange'=>'fa-drum','teal'=>'fa-headphones','pink'=>'fa-microphone','blue'=>'fa-record-vinyl','green'=>'fa-waveform','red'=>'fa-compact-disc');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundVault A&amp;R — Executive Portfolio Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:"Inter",-apple-system,BlinkMacSystemFont,sans-serif;background:#0f1117;color:#e2e8f0;min-height:100vh;}
        a{text-decoration:none;}
        /* ── Navbar ── */
        .navbar-dash{background:linear-gradient(90deg,#0a0c11,#1a1c26);border-bottom:1px solid #2a2d3a;padding:0.7rem 0;position:sticky;top:0;z-index:100;}
        .navbar-dash .container{display:flex;align-items:center;justify-content:space-between;}
        .navbar-dash .brand{display:flex;align-items:center;gap:0.65rem;color:#fff;font-size:1.15rem;font-weight:700;letter-spacing:-0.3px;}
        .navbar-dash .brand i{color:#f59e0b;font-size:1.25rem;}
        .navbar-dash .brand span{color:#f59e0b;}
        .navbar-dash .nav-links{display:flex;align-items:center;gap:1.5rem;list-style:none;margin:0;padding:0;}
        .navbar-dash .nav-links a{color:#71717a;font-size:0.82rem;font-weight:500;transition:color .15s;}
        .navbar-dash .nav-links a:hover,.navbar-dash .nav-links a.active{color:#e2e8f0;}
        .navbar-dash .nav-cta{background:linear-gradient(135deg,#b45309,#f59e0b);color:#0f1117;border:none;padding:0.4rem 1.1rem;border-radius:5px;font-size:0.78rem;font-weight:700;cursor:pointer;}
        /* ── Page Header ── */
        .page-header{padding:2rem 0 1.25rem;}
        .page-header h1{font-family:"Playfair Display",serif;font-size:1.85rem;font-weight:700;margin:0;letter-spacing:-0.3px;color:#f5f5f0;}
        .page-header h1 i{color:#f59e0b;margin-right:0.5rem;}
        .page-header p{color:#71717a;font-size:0.85rem;margin:0.2rem 0 0;}
        /* ── Stats Bar ── */
        .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;padding:1.25rem 0;}
        .stat-card{background:#14161e;border:1px solid #1e2030;border-radius:10px;padding:1rem 1.25rem;}
        .stat-card .stat-label{font-size:0.68rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;color:#52525b;}
        .stat-card .stat-value{font-size:1.65rem;font-weight:700;color:#f5f5f0;margin-top:0.15rem;line-height:1.2;}
        .stat-card .stat-value .sub{font-size:0.75rem;font-weight:500;color:#71717a;}
        .stat-card .stat-icon{float:right;color:#f59e0b;font-size:1.4rem;opacity:0.5;}
        .stat-card.highlight{border-color:#f59e0b33;background:#1a1c28;}
        /* ── Toolbar ── */
        .toolbar{display:flex;align-items:center;justify-content:space-between;padding:0.75rem 0;gap:1rem;border-top:1px solid #1e2030;}
        .toolbar .count{font-size:0.82rem;color:#71717a;}
        .toolbar .count strong{color:#e2e8f0;}
        .toolbar .filter-group{display:flex;gap:0.4rem;}
        .toolbar .filter-btn{background:#14161e;border:1px solid #1e2030;color:#a1a1aa;padding:0.35rem 0.8rem;border-radius:5px;font-size:0.78rem;font-weight:500;cursor:pointer;}
        .toolbar .filter-btn:hover,.toolbar .filter-btn.active{background:#1e2030;border-color:#f59e0b;color:#f5f5f0;}
        /* ── Roster Table ── */
        .roster-table{width:100%;border-collapse:collapse;margin:0.5rem 0 1.5rem;}
        .roster-table thead th{padding:0.7rem 1rem;font-size:0.7rem;font-weight:600;text-transform:uppercase;letter-spacing:0.6px;color:#52525b;border-bottom:2px solid #f59e0b33;text-align:left;}
        .roster-table tbody tr{border-bottom:1px solid #1a1c26;transition:background .12s;}
        .roster-table tbody tr:hover{background:#14161e;}
        .roster-table tbody td{padding:0.8rem 1rem;font-size:0.85rem;vertical-align:middle;}
        .roster-table .artist-cell{display:flex;align-items:center;gap:0.75rem;}
        .roster-table .artist-cell .tbl-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:0.85rem;flex-shrink:0;}
        .roster-table .artist-cell .tbl-icon.amber{background:linear-gradient(135deg,#78350f,#b45309);color:#fbbf24;}
        .roster-table .artist-cell .tbl-icon.gold{background:linear-gradient(135deg,#5b3a0a,#8b5e0b);color:#fcd34d;}
        .roster-table .artist-cell .tbl-icon.copper{background:linear-gradient(135deg,#5b2d0a,#8b4e0b);color:#fdba74;}
        .roster-table .artist-cell .tbl-icon.bronze{background:linear-gradient(135deg,#4a2a0a,#7a4a0b);color:#fca5a5;}
        .roster-table .artist-cell .tbl-icon.steel{background:linear-gradient(135deg,#1e293b,#334155);color:#94a3b8;}
        .roster-table .artist-cell .tbl-icon.slate{background:linear-gradient(135deg,#1e1e2a,#2a2a3a);color:#a1a1aa;}
        .roster-table .artist-cell .tbl-icon.warm{background:linear-gradient(135deg,#5b2d0a,#8b6e0b);color:#fde68a;}
        .roster-table .genre-tag{display:inline-block;padding:0.15rem 0.5rem;border-radius:4px;font-size:0.7rem;font-weight:500;background:#1e2030;color:#f59e0b;}
        .roster-table .album-bar{display:flex;align-items:center;gap:0.5rem;}
        .roster-table .album-bar .bar-track{flex:1;max-width:80px;height:4px;background:#1e2030;border-radius:3px;overflow:hidden;}
        .roster-table .album-bar .bar-track .bar-fill{height:100%;background:linear-gradient(90deg,#b45309,#f59e0b);border-radius:3px;}
        .roster-table .album-bar .bar-num{font-size:0.8rem;font-weight:600;color:#e2e8f0;min-width:1.5rem;}
        .roster-table .label-badge{font-size:0.72rem;color:#71717a;font-weight:500;}
        .roster-table .view-link{color:#f59e0b;font-size:0.78rem;font-weight:600;opacity:0;transition:opacity .15s;}
        .roster-table tbody tr:hover .view-link{opacity:1;}
        /* ── Detail ── */
        .detail-section{padding:1.5rem 0;}
        .detail-section .back-link{display:inline-flex;align-items:center;gap:0.35rem;color:#71717a;font-size:0.82rem;font-weight:500;margin-bottom:1.25rem;}
        .detail-section .back-link:hover{color:#f59e0b;}
        .detail-section .portfolio-card{background:#14161e;border:1px solid #1e2030;border-radius:12px;padding:2rem;}
        .detail-section .portfolio-card .pf-header{display:flex;align-items:center;gap:1.25rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid #1e2030;}
        .detail-section .portfolio-card .pf-icon{width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;}
        .detail-section .portfolio-card .pf-meta h2{font-family:"Playfair Display",serif;font-size:1.4rem;font-weight:700;margin:0;color:#f5f5f0;}
        .detail-section .portfolio-card .pf-meta .pf-position{font-size:0.75rem;color:#f59e0b;font-weight:600;margin-top:0.1rem;}
        .detail-section .portfolio-card .pf-meta .genre-tag{display:inline-block;margin-top:0.2rem;padding:0.15rem 0.55rem;border-radius:4px;font-size:0.72rem;font-weight:500;background:#1e2030;color:#f59e0b;}
        .detail-section .portfolio-card .pf-body{font-size:0.88rem;color:#a1a1aa;line-height:1.7;margin-bottom:1.5rem;}
        .detail-section .portfolio-card .pf-stats{display:flex;gap:2.5rem;padding-top:1.25rem;border-top:1px solid #1e2030;}
        .detail-section .portfolio-card .pf-stats .pf-stat-item{text-align:center;}
        .detail-section .portfolio-card .pf-stats .pf-stat-item .pf-stat-val{font-size:1.2rem;font-weight:700;color:#e2e8f0;}
        .detail-section .portfolio-card .pf-stats .pf-stat-item .pf-stat-lbl{font-size:0.7rem;color:#52525b;font-weight:500;margin-top:0.1rem;}
        .detail-section .portfolio-card .pf-stats .pf-stat-item .pf-stat-val.gold{color:#f59e0b;}
        .error-banner{background:#1e1410;border:1px solid #3a2a1a;border-left:4px solid #f59e0b;border-radius:8px;padding:0.7rem 1rem;margin:1rem 0;font-size:0.78rem;color:#fdba74;font-family:"Courier New",monospace;}
        .error-banner .sql-hint{display:block;margin-top:0.35rem;font-size:0.72rem;color:#71717a;font-style:italic;}
        /* ── Empty State ── */
        .empty-state{text-align:center;padding:4rem 0;color:#52525b;}
        .empty-state i{font-size:2.2rem;margin-bottom:0.75rem;display:block;color:#1e2030;}
        .empty-state h3{font-weight:600;color:#71717a;font-size:1.1rem;}
        .empty-state p{font-size:0.82rem;}
        .empty-state .empty-btn{display:inline-block;margin-top:1rem;background:linear-gradient(135deg,#b45309,#f59e0b);color:#0f1117;padding:0.45rem 1.1rem;border-radius:5px;font-size:0.82rem;font-weight:700;}
        /* ── Footer ── */
        .footer{border-top:1px solid #1a1c26;padding:1.25rem 0;margin-top:2rem;}
        .footer .footer-inner{display:flex;align-items:center;justify-content:space-between;}
        .footer .footer-inner .copyright{font-size:0.75rem;color:#52525b;}
        .footer .footer-inner .socials{display:flex;gap:0.9rem;}
        .footer .footer-inner .socials a{color:#52525b;font-size:0.9rem;transition:color .15s;}
        .footer .footer-inner .socials a:hover{color:#f59e0b;}
        /* ── Responsive ── */
        @media(max-width:768px){.stats-row{grid-template-columns:repeat(2,1fr);}.navbar-dash .nav-links{display:none;}.toolbar{flex-direction:column;align-items:flex-start;}.roster-table thead{display:none;}.roster-table tbody,.roster-table tbody tr{display:block;}.roster-table tbody tr{padding:0.75rem 0;}.roster-table tbody td{display:flex;justify-content:space-between;padding:0.35rem 0;border:none;}.roster-table tbody td::before{content:attr(data-label);font-weight:600;color:#52525b;font-size:0.7rem;text-transform:uppercase;}.roster-table .view-link{opacity:1;}.detail-section .portfolio-card .pf-stats{flex-wrap:wrap;gap:1.25rem;}}
    </style>
</head>
<body>
<nav class="navbar-dash">
    <div class="container">
        <a href="index.php" class="brand"><i class="fas fa-chart-line"></i> Sound<span>Vault</span> <span style="font-size:0.75rem;font-weight:600;color:#71717a;letter-spacing:0.5px;">A&amp;R</span></a>
        <ul class="nav-links">
            <li><a href="index.php" class="active"><i class="fas fa-users me-1"></i> Artists</a></li>
            <li><a href="#"><i class="fas fa-chart-pie me-1"></i> Reports</a></li>
            <li><a href="#"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a></li>
            <li><button class="nav-cta"><i class="fas fa-file-alt me-1"></i> Executive Summary</button></li>
        </ul>
    </div>
</nav>
<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-chart-pie"></i> Artist &amp; Label Portfolio</h1>
        <p>Executive overview of signed talent and catalog performance.</p>
    </div>

    <?php
    // Compute stats
    $total_artists = count($artist_data);
    $total_albums = 0;
    $labels = array();
    $genre_counts = array();
    foreach ($artist_data as $a) {
        $total_albums += (int)$a["album_count"];
        $labels[] = $a["label"];
        $g = $a["genre"];
        $genre_counts[$g] = ($genre_counts[$g] ?? 0) + 1;
    }
    $unique_labels = count(array_unique($labels));
    $top_genre = $genre_counts ? array_keys($genre_counts, max($genre_counts))[0] : "N/A";
    $max_albums = max(array_column($artist_data, "album_count")) ?: 1;
    ?>

    <div class="stats-row">
        <div class="stat-card highlight">
            <i class="fas fa-users stat-icon"></i>
            <div class="stat-label">Total Artists</div>
            <div class="stat-value"><?php echo $total_artists; ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-compact-disc stat-icon"></i>
            <div class="stat-label">Total Albums</div>
            <div class="stat-value"><?php echo $total_albums; ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-building stat-icon"></i>
            <div class="stat-label">Signed Labels</div>
            <div class="stat-value"><?php echo $unique_labels; ?></div>
        </div>
        <div class="stat-card">
            <i class="fas fa-tag stat-icon"></i>
            <div class="stat-label">Top Genre</div>
            <div class="stat-value" style="font-size:1.1rem;margin-top:0.3rem;"><?php echo htmlspecialchars($top_genre); ?></div>
        </div>
    </div>

    <div class="toolbar">
        <div class="count">
            <i class="fas fa-list-ul me-1" style="color:#f59e0b;"></i>
            Showing <strong><?php echo count($artist_data); ?></strong> artist<?php echo count($artist_data) !== 1 ? "s" : ""; ?>
            <?php if (!empty($_GET)): ?><span style="color:#52525b;"> &mdash; filtered</span><?php endif; ?>
        </div>
        <div class="filter-group">
            <a href="index.php" class="filter-btn <?php echo empty($_GET) ? "active" : ""; ?>"><i class="fas fa-th-list me-1"></i> All</a>
            <a href="index.php?genre=Synth-Pop" class="filter-btn"><i class="fas fa-waveform me-1"></i> Synth-Pop</a>
            <a href="index.php?genre=Blues%20Rock" class="filter-btn"><i class="fas fa-guitar me-1"></i> Blues</a>
            <a href="index.php?genre=Electronic" class="filter-btn"><i class="fas fa-drum me-1"></i> Electronic</a>
        </div>
    </div>

    <?php if ($error_msg): ?>
    <div class="error-banner"><i class="fas fa-exclamation-triangle me-2"></i> Database query failed: <?php echo htmlspecialchars($error_msg); ?>
        <span class="sql-hint">Try injecting via parameter name. Example: <code>?genre'=1</code> or <code>?1=1</code></span></div>
    <?php endif; ?>

    <?php if ($artist_data && count($artist_data) === 1 && !empty($_GET)): ?>
    <?php $a = $artist_data[0]; $icon_idx = $a["id"] % count($icons); $tbl_colors = array("amber","gold","copper","bronze","steel","slate","warm"); ?>
    <div class="detail-section">
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to portfolio</a>
        <div class="portfolio-card">
            <div class="pf-header">
                <div class="pf-icon <?php echo $icons[$icon_idx]; ?>"><i class="fas <?php echo $fas[$icons[$icon_idx]]; ?>"></i></div>
                <div class="pf-meta">
                    <h2><?php echo htmlspecialchars($a["name"]); ?></h2>
                    <div class="pf-position">Roster #<?php echo (int)$a["id"]; ?></div>
                    <span class="genre-tag"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($a["genre"]); ?></span>
                </div>
            </div>
            <div class="pf-body"><?php echo htmlspecialchars($a["biography"]); ?></div>
            <div class="pf-stats">
                <div class="pf-stat-item"><div class="pf-stat-val"><?php echo (int)$a["album_count"]; ?></div><div class="pf-stat-lbl">Albums</div></div>
                <div class="pf-stat-item"><div class="pf-stat-val"><?php echo (int)$a["album_count"] * 8; ?> <span class="sub" style="font-size:0.7rem;color:#71717a;">tracks</span></div><div class="pf-stat-lbl">Est. Catalog</div></div>
                <div class="pf-stat-item"><div class="pf-stat-val gold"><?php echo htmlspecialchars($a["label"]); ?></div><div class="pf-stat-lbl">Signed Label</div></div>
                <div class="pf-stat-item"><div class="pf-stat-val" style="font-size:1rem;">Rank #<?php echo (int)$a["id"]; ?> of <?php echo $total_artists; ?></div><div class="pf-stat-lbl">Roster Position</div></div>
            </div>
        </div>
    </div>
    <?php elseif ($artist_data && count($artist_data) > 0): ?>
    <?php $tbl_colors = array("amber","gold","copper","bronze","steel","slate","warm"); ?>
    <table class="roster-table">
        <thead>
            <tr>
                <th>Artist</th>
                <th>Genre</th>
                <th>Albums</th>
                <th>Label</th>
                <th style="width:50px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($artist_data as $a): $idx = $a["id"] % count($tbl_colors); ?>
            <tr>
                <td data-label="Artist">
                    <div class="artist-cell">
                        <div class="tbl-icon <?php echo $tbl_colors[$idx]; ?>"><i class="fas <?php echo $fas[$icons[$a["id"] % count($icons)]]; ?>"></i></div>
                        <span style="font-weight:600;"><?php echo htmlspecialchars($a["name"]); ?></span>
                    </div>
                </td>
                <td data-label="Genre"><span class="genre-tag"><?php echo htmlspecialchars($a["genre"]); ?></span></td>
                <td data-label="Albums">
                    <div class="album-bar">
                        <div class="bar-track"><div class="bar-fill" style="width:<?php echo ((int)$a["album_count"] / $max_albums) * 100; ?>%;"></div></div>
                        <span class="bar-num"><?php echo (int)$a["album_count"]; ?></span>
                    </div>
                </td>
                <td data-label="Label"><span class="label-badge"><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($a["label"]); ?></span></td>
                <td><a href="index.php?id=<?php echo (int)$a["id"]; ?>" class="view-link">View <i class="fas fa-arrow-right ms-1" style="font-size:0.65rem;"></i></a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif (!$artist_data || count($artist_data) === 0): ?>
    <div class="empty-state">
        <i class="fas fa-chart-line"></i>
        <h3>No artists found</h3>
        <p>Try different filter criteria or adjust the parameter payload.</p>
        <a href="index.php" class="empty-btn"><i class="fas fa-th-list me-1"></i> View full portfolio</a>
    </div>
    <?php endif; ?>
</div>
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <div class="copyright">&copy; 2026 SoundVault Records — A&amp;R Division. All rights reserved.</div>
            <div class="socials">
                <a href="#"><i class="fab fa-spotify"></i></a>
                <a href="#"><i class="fab fa-apple"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>
