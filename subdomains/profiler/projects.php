<?php
require_once __DIR__ . '/includes/profiles_data.php';

$all_profiles = get_all_member_profiles();
$repos = [];
foreach ($all_profiles as $member) {
    foreach ($member['pinned_projects'] as $repo) {
        $repos[] = [
            'owner_id' => $member['id'],
            'owner_user' => $member['username'],
            'repo' => $repo,
        ];
    }
}

usort($repos, static function ($a, $b) {
    return $b['repo']['stars'] <=> $a['repo']['stars'];
});

$active_nav = 'projects';
$page_title = 'Trending Open Source Repositories | Peerstack';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <div class="explore-header">
        <div>
            <h1>Community Repositories</h1>
            <div style="color:var(--fg-muted);font-size:13.5px;">Open-source libraries, storage kernels, and security tools maintained by Peerstack members.</div>
        </div>
        <div style="font-size:12.5px;color:var(--fg-muted);">Sorted by community stars</div>
    </div>

    <div class="pinned-grid" style="margin-bottom:48px;">
        <?php foreach ($repos as $item): $r = $item['repo']; ?>
        <div class="repo-card">
            <div>
                <div class="repo-name">
                    <a href="/?id=<?= urlencode((string) $item['owner_id']) ?>">
                        <?= htmlspecialchars($item['owner_user']) ?> / <?= htmlspecialchars($r['name']) ?>
                    </a>
                </div>
                <p class="repo-desc"><?= htmlspecialchars($r['desc']) ?></p>
            </div>
            <div class="repo-meta">
                <span><span class="lang-dot" style="background:<?= htmlspecialchars($r['lang_color']) ?>;"></span><?= htmlspecialchars($r['lang']) ?></span>
                <span>&#9733; <?= number_format($r['stars']) ?></span>
                <span>Forks <?= number_format($r['forks']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

