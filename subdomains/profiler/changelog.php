<?php
$active_nav = 'changelog';
$page_title = 'Platform Changelog & Status | Peerstack';
require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="max-width:860px;padding-top:28px;padding-bottom:48px;">
    <h1 style="font-size:24px;margin:0 0 6px 0;">Platform Changelog</h1>
    <p style="color:var(--fg-muted);margin:0 0 28px 0;">Recent engineering updates to member profiles, search indexing, and repository feeds.</p>

    <div class="panel-box">
        <div class="panel-header">
            <strong>v2.14.0 &mdash; Unified Profile Hydration &amp; Heatmap Caching</strong>
            <span>February 2026</span>
        </div>
        <div class="panel-body" style="color:var(--fg-muted);font-size:13.5px;">
            Migrated member overview pages to lightweight client-side JSON hydration to eliminate duplicate server round-trips between desktop tabs and mobile drawers.
        </div>
    </div>

    <div class="panel-box" id="api">
        <div class="panel-header">
            <strong>v2.13.4 &mdash; Contributor Directory Search Improvements</strong>
            <span>January 2026</span>
        </div>
        <div class="panel-body" style="color:var(--fg-muted);font-size:13.5px;">
            Added instant filtering across Backend, Security, Frontend, and ML / Data specializations along with cross-member repository aggregation on the Explore feed.
        </div>
    </div>

    <div class="panel-box" id="privacy">
        <div class="panel-header">
            <strong>Privacy &amp; Account Visibility Controls</strong>
            <span>Policy Update</span>
        </div>
        <div class="panel-body" style="color:var(--fg-muted);font-size:13.5px;">
            Public profile pages display only opt-in bio fields, company affiliation, location, and pinned repositories. Account security, billing metadata, and private contact records are restricted to authenticated account owners.
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

