<?php
require 'config.php';
$case=trim($_GET['case']??''); $r=null; $events=[]; $error=null;
if ($case!=='') { $s=db()->prepare("SELECT * FROM reports WHERE case_no=?"); $s->execute([$case]); $r=$s->fetch(); if($r){$s=db()->prepare("SELECT * FROM report_events WHERE report_id=? ORDER BY created_at ASC");$s->execute([$r['id']]);$events=$s->fetchAll();} }
$title='Track a Case'; require 'partials_header.php';
?>
<main class="track-page">
  <div class="page-intro center"><span class="eyebrow">CASE LOOKUP</span><h1>Track your report.</h1><p>Enter your TrustDesk case number to see the latest customer-facing update.</p></div>
  <form class="lookup" method="get"><input name="case" value="<?=e($case)?>" placeholder="TD-XXXXXXXX" required><button class="button dark">Search →</button></form>
<?php if($case && !$r): ?><div class="alert">We couldn't find that case number. Check the number and try again.</div><?php endif; ?>
<?php if($r): ?>
  <section class="case-detail">
    <div class="case-head"><div><span class="eyebrow">CASE <?=e($r['case_no'])?></span><h2><?=e($r['title'])?></h2></div><span class="status status-<?=e($r['status'])?>"><?=e(str_replace('_',' ',$r['status']))?></span></div>
    <div class="detail-grid"><div><small>REPORT TYPE</small><b><?=e(ucfirst($r['report_type']))?></b></div><div><small>SEVERITY</small><b><?=e(ucfirst($r['severity']))?></b></div><div><small>SUBMITTED</small><b><?=e(date('M j, Y',strtotime($r['created_at'])))?></b></div></div>
    <div class="case-body"><h3>Case update</h3><p><?=nl2br(e($r['admin_response'] ?: 'Your report is in our review queue. We will update this page when there is a customer-facing change.'))?></p></div>
    <div class="event-list"><?php foreach($events as $ev): ?><div><span></span><p><b><?=e($ev['event_text'])?></b><small><?=e(date('M j, Y · H:i',strtotime($ev['created_at'])))?></small></p></div><?php endforeach; ?></div>
  </section>
<?php endif; ?>
</main>
<?php require 'partials_footer.php'; ?>