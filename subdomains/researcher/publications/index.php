<?php
$active_nav = 'publications';
$page_title = 'Publications & Policy Repository | Northbridge Research Institute';
require_once __DIR__ . '/../includes/header.php';

$filter = $_GET['type'] ?? 'all';
$allowed_filters = ['all', 'report', 'working-paper', 'policy-brief'];
if (!in_array($filter, $allowed_filters, true)) {
    $filter = 'all';
}

$publications = [
    [
        'type' => 'report',
        'type_label' => 'Annual Report',
        'date' => 'December 2025',
        'title' => 'Urban Mobility Trends 2025',
        'abstract' => 'Comprehensive longitudinal review of bus corridor electrification, arterial congestion pricing, and suburban first-mile connectivity across 14 metropolitan regions. Co-authored with Metro Systems Laboratory.',
        'authors' => 'Dr. Elena Morrison, Dr. Henrik Lindqvist',
        'doi' => '10.48550/nri.2025.041',
        'pdf' => '/assets/2025_research_report_pdf.pdf',
        'status' => 'Open Access PDF',
    ],
    [
        'type' => 'policy-brief',
        'type_label' => 'Policy Brief',
        'date' => 'October 2025',
        'title' => 'Grid Hardening Benchmarks for Coastal Municipalities',
        'abstract' => 'Standardized capital-expenditure metrics for undergrounding critical feeder lines and decentralizing emergency microgrids ahead of severe weather events.',
        'authors' => 'Dr. Marcus Thorne',
        'doi' => '10.48550/nri.2025.037',
        'pdf' => null,
        'status' => 'Print Edition / Print-on-Demand',
    ],
    [
        'type' => 'working-paper',
        'type_label' => 'Working Paper',
        'date' => 'August 2025',
        'title' => 'Zero-Knowledge Verification in Municipal Benefit Portals',
        'abstract' => 'Technical evaluation of cryptographic credentialing standards reducing over-collection of resident income records across municipal housing programs.',
        'authors' => 'Prof. Nadia Al-Mansoor',
        'doi' => '10.48550/nri.2025.029',
        'pdf' => null,
        'status' => 'Peer Review Complete (Digital Archive Pending)',
    ],
    [
        'type' => 'working-paper',
        'type_label' => 'Working Paper',
        'date' => 'June 2025',
        'title' => 'Climate Adaptation and Urban Infrastructure: Pre-Conference Synthesis',
        'abstract' => 'Comparative engineering review of permeable pavement retrofits, floodwall sensors, and transit heat-island mitigation across temperate port cities.',
        'authors' => 'Dr. Marcus Thorne, Dr. Elena Morrison',
        'doi' => '10.48550/nri.2025.018',
        'pdf' => null,
        'status' => 'Forthcoming in Q2 2026 Proceedings',
    ],
    [
        'type' => 'report',
        'type_label' => 'Technical Report',
        'date' => 'March 2025',
        'title' => 'Interoperable Procurement Ledgers in Public Works',
        'abstract' => 'Audit of standardized vendor disclosure schemas across six regional transportation authorities, examining bid-rigging detection indicators.',
        'authors' => 'Prof. Nadia Al-Mansoor, Dr. Henrik Lindqvist',
        'doi' => '10.48550/nri.2025.009',
        'pdf' => null,
        'status' => 'Institutional Library Copy Only',
    ],
];

$filtered = array_filter($publications, static function ($pub) use ($filter) {
    return $filter === 'all' || $pub['type'] === $filter;
});
?>

<section class="page-banner">
    <div class="container">
        <h1>Publications &amp; Policy Repository</h1>
        <p>Peer-reviewed flagship reports, policy briefs, and technical working papers published by Northbridge fellows and partner laboratories.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="filter-bar">
            <div class="filter-tabs">
                <a href="/publications/" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">All Publications (<?= count($publications) ?>)</a>
                <a href="/publications/?type=report" class="filter-tab <?= $filter === 'report' ? 'active' : '' ?>">Reports</a>
                <a href="/publications/?type=policy-brief" class="filter-tab <?= $filter === 'policy-brief' ? 'active' : '' ?>">Policy Briefs</a>
                <a href="/publications/?type=working-paper" class="filter-tab <?= $filter === 'working-paper' ? 'active' : '' ?>">Working Papers</a>
            </div>
            <div style="font-size:12.5px;color:var(--muted);">
                Showing <?= count($filtered) ?> <?= count($filtered) === 1 ? 'record' : 'records' ?>
            </div>
        </div>

        <div class="pub-list">
            <?php foreach ($filtered as $pub): ?>
            <article class="pub-row">
                <div class="pub-meta-col">
                    <span class="pub-type"><?= htmlspecialchars($pub['type_label']) ?></span>
                    <div><?= htmlspecialchars($pub['date']) ?></div>
                    <div style="margin-top:4px;font-size:11px;"><?= htmlspecialchars($pub['doi']) ?></div>
                </div>
                <div class="pub-main-col">
                    <h3><?= htmlspecialchars($pub['title']) ?></h3>
                    <p><?= htmlspecialchars($pub['abstract']) ?></p>
                    <div class="pub-authors"><?= htmlspecialchars($pub['authors']) ?> &middot; <?= htmlspecialchars($pub['status']) ?></div>
                </div>
                <div>
                    <?php if ($pub['pdf']): ?>
                        <a href="<?= htmlspecialchars($pub['pdf']) ?>" target="_blank" rel="noopener" class="btn btn-primary">Download PDF</a>
                    <?php else: ?>
                        <a href="/about.php#press" class="btn btn-outline">Request Copy</a>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
