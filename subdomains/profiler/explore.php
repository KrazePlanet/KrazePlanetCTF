<?php
require_once __DIR__ . '/includes/profiles_data.php';

$all_profiles = get_all_member_profiles();
$categories = ['All', 'Backend', 'Security', 'Frontend', 'ML / Data'];

$cat = isset($_GET['category']) ? trim((string) $_GET['category']) : 'All';
if (!in_array($cat, $categories, true)) {
    $cat = 'All';
}
$search_q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$results = [];
foreach ($all_profiles as $raw) {
    $pub = get_public_member_summary($raw);
    if ($cat !== 'All' && $pub['role_category'] !== $cat) {
        continue;
    }
    if ($search_q !== '') {
        $hay = strtolower($pub['name'] . ' ' . $pub['username'] . ' ' . $pub['headline'] . ' ' . implode(' ', $pub['skills']));
        if (strpos($hay, strtolower($search_q)) === false) {
            continue;
        }
    }
    $results[] = $pub;
}

$active_nav = 'explore';
$page_title = 'Explore Engineers & Maintainers | Peerstack';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <div class="explore-header">
        <div>
            <h1>Explore Engineers &amp; Maintainers</h1>
            <div style="color:var(--fg-muted);font-size:13.5px;">Browse public developer profiles, open-source maintainers, and systems specialists.</div>
        </div>
        <div class="filter-pills">
            <?php foreach ($categories as $c): ?>
                <?php
                $qs = [];
                if ($c !== 'All') $qs['category'] = $c;
                if ($search_q !== '') $qs['q'] = $search_q;
                $url = '/explore.php' . (count($qs) ? '?' . http_build_query($qs) : '');
                ?>
                <a href="<?= htmlspecialchars($url) ?>" class="filter-pill <?= $cat === $c ? 'active' : '' ?>"><?= htmlspecialchars($c) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (count($results) === 0): ?>
        <div class="panel-box">
            <div class="panel-body" style="text-align:center;color:var(--fg-muted);">
                No profiles matched your query. <a href="/explore.php" style="color:var(--accent);font-weight:600;">Clear filters</a>
            </div>
        </div>
    <?php else: ?>
        <div class="member-list">
            <?php foreach ($results as $m): ?>
            <div class="member-row">
                <div class="member-row-left">
                    <img src="<?= htmlspecialchars($m['avatar_url']) ?>" alt="<?= htmlspecialchars($m['name']) ?>" class="member-row-avatar">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <a href="/?id=<?= urlencode((string) $m['id']) ?>" style="font-size:15px;font-weight:600;color:var(--accent);">
                                <?= htmlspecialchars($m['name']) ?>
                            </a>
                            <span style="color:var(--fg-subtle);font-size:13px;">@<?= htmlspecialchars($m['username']) ?></span>
                            <span class="skill-tag" style="font-size:11px;"><?= htmlspecialchars($m['role_category']) ?></span>
                        </div>
                        <div style="font-size:13px;color:var(--fg);margin-top:2px;"><?= htmlspecialchars($m['headline']) ?> &middot; <?= htmlspecialchars($m['location']) ?></div>
                        <div style="font-size:12.5px;color:var(--fg-muted);margin-top:4px;"><?= htmlspecialchars($m['bio']) ?></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:12px;color:var(--fg-muted);"><?= number_format($m['followers']) ?> followers</span>
                    <a href="/?id=<?= urlencode((string) $m['id']) ?>" class="btn">View Profile</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

