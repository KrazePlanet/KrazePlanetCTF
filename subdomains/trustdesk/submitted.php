<?php
require 'config.php';
$case=trim($_GET['case']??'');
$stmt=db()->prepare("SELECT * FROM reports WHERE case_no=?"); $stmt->execute([$case]); $r=$stmt->fetch();
if(!$r) { header('Location: report.php'); exit; }
$title='Report received'; require 'partials_header.php';
?>
<main class="success-page"><div class="success-mark">✓</div><span class="eyebrow">REPORT RECEIVED</span><h1>Thank you for speaking up.</h1><p>Your report has been added to our review queue.</p><div class="case-box"><small>YOUR CASE NUMBER</small><strong><?=e($r['case_no'])?></strong><span>Save this number to check your case later.</span></div><a class="button dark" href="track.php?case=<?=urlencode($r['case_no'])?>">View case status →</a><a class="text-link" href="index.php">Return to TrustDesk</a></main>
<?php require 'partials_footer.php'; ?>