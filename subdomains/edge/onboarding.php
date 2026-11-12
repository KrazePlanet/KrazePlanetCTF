<?php
$active_nav = 'onboarding';
$page_title = 'Verification & KYB Compliance | KestrelEdge';
require_once __DIR__ . '/includes/header.php';
?>

<section class="subpage-header">
    <div class="container">
        <span class="pill-tag">Trust &amp; Identity Engine</span>
        <h1>Automated KYC, Liveness &amp; Beneficial Ownership Screening</h1>
        <p>Reduce merchant onboarding friction while satisfying global AML/CTF evidentiary standards across North America, Europe, and APAC.</p>
    </div>
</section>

<section class="section">
    <div class="container split-feature">
        <div>
            <img src="/images/marvols-woman-10301273_1920.jpg" alt="Identity verification portrait capture" class="feature-photo" style="object-position:center 25%;">
        </div>
        <div>
            <span class="kicker">Multi-Layer Document Inspection</span>
            <h2>Verify Passports, National IDs &amp; Corporate Registries</h2>
            <p>
                Applicants submit encrypted document captures through our drop-in verification SDK. Every submission undergoes tamper detection, MRZ checksum verification, and live biometric matching prior to settlement activation.
            </p>
            <ul class="check-list">
                <li>Supports 210+ national ID, driver license, and passport templates</li>
                <li>Automated card-bin and proof-of-account ownership validation</li>
                <li>Encrypted cold-storage archiving strictly isolated from public edge nodes</li>
            </ul>
            <a href="/company.php#security" class="btn btn-ghost">Review Security Controls &rarr;</a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

