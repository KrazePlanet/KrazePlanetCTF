<?php
$active_nav = 'about';
$page_title = 'About the Institute | Northbridge Research Institute';
require_once __DIR__ . '/includes/header.php';
?>

<section class="page-banner">
    <div class="container">
        <h1>Mission, Governance &amp; Partnerships</h1>
        <p>Founded in 2016, Northbridge Research Institute produces independent, empirical analysis on urban infrastructure, climate resilience, and public-sector digital architecture.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="programs-grid" style="grid-template-columns: 1.4fr 1fr; gap: 40px;">
            <div style="background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:32px;">
                <span class="kicker">Institutional Mandate</span>
                <h2 style="font-family:'Newsreader',Georgia,serif;font-size:26px;font-weight:500;margin:0 0 16px 0;color:var(--navy);">Independent Empirical Research for Civic Decisions</h2>
                <p style="color:var(--slate);margin:0 0 16px 0;">
                    Municipalities and national ministries frequently evaluate major capital commitments without access to comparable, cross-regional operational telemetry. Northbridge bridges that gap by partnering directly with transit authorities, grid operators, and university laboratories to audit real-world system performance before policy frameworks are finalized.
                </p>
                <p style="color:var(--slate);margin:0 0 16px 0;">
                    All published studies undergo double-blind external peer review orchestrated by our Research Programs Office. Embargoed pre-publication working drafts and internal review evaluations remain restricted until formal proceedings release dates.
                </p>
                <p style="color:var(--slate);margin:0;">
                    Northbridge does not accept commissioned advocacy funding from private infrastructure vendors or political committees.
                </p>
            </div>

            <div id="partners" style="background:var(--surface);border:1px solid var(--border);border-radius:4px;padding:32px;">
                <span class="kicker">Academic &amp; Lab Network</span>
                <h3 style="font-family:'Newsreader',Georgia,serif;font-size:22px;font-weight:500;margin:0 0 16px 0;color:var(--navy);">Partner Institutions</h3>
                <ul style="list-style:none;padding:0;margin:0;color:var(--slate);font-size:13.5px;">
                    <li style="padding:12px 0;border-bottom:1px solid var(--border-light);">
                        <strong style="color:var(--navy);display:block;">Westbridge University</strong>
                        Joint chair and urban mobility telemetry fellowship host.
                    </li>
                    <li style="padding:12px 0;border-bottom:1px solid var(--border-light);">
                        <strong style="color:var(--navy);display:block;">Metro Systems Laboratory</strong>
                        Co-investigator on arterial traffic modeling and fleet electrification.
                    </li>
                    <li style="padding:12px 0;border-bottom:1px solid var(--border-light);">
                        <strong style="color:var(--navy);display:block;">Geneva Climate Infrastructure Consortium</strong>
                        European coastal substation resilience benchmarking.
                    </li>
                    <li style="padding:12px 0;">
                        <strong style="color:var(--navy);display:block;">Nordic GovLab</strong>
                        Cryptographic municipal credentialing and open procurement standards.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section section-white" id="press">
    <div class="container">
        <div class="section-header">
            <h2>Media, Data Access &amp; Archival Requests</h2>
        </div>
        <div class="programs-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div style="padding:24px;border:1px solid var(--border);border-radius:4px;background:var(--paper);">
                <h3 style="font-family:'Newsreader',Georgia,serif;font-size:20px;color:var(--navy);margin:0 0 8px 0;">Press &amp; Policy Inquiries</h3>
                <p style="font-size:13.5px;color:var(--slate);margin:0 0 12px 0;">
                    Journalists, legislative staff, and institutional librarians requesting embargoed briefing schedules, speaker availability, or print monographs may contact our Communications Desk.
                </p>
                <div style="font-size:13px;color:var(--navy);font-weight:600;">press@northbridge-institute.test &middot; +1 (617) 555-0192</div>
            </div>
            <div style="padding:24px;border:1px solid var(--border);border-radius:4px;background:var(--paper);">
                <h3 style="font-family:'Newsreader',Georgia,serif;font-size:20px;color:var(--navy);margin:0 0 8px 0;">Research Programs Office</h3>
                <p style="font-size:13.5px;color:var(--slate);margin:0 0 12px 0;">
                    For dataset replication packages, institutional review board (IRB) inquiries, or pre-publication peer review correspondence, please cite the relevant document DOI.
                </p>
                <div style="font-size:13px;color:var(--navy);font-weight:600;">research-office@northbridge-institute.test &middot; Suite 300, Cambridge</div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

