<?php
$title='Submit a Report';
require 'partials_header.php';
$type = $_GET['type'] ?? 'content';
$types = ['content'=>'Content','user'=>'User','profile'=>'Profile','comment'=>'Comment','listing'=>'Listing','seller'=>'Seller','review'=>'Review','spam'=>'Spam','abuse'=>'Abuse','other'=>'Other'];
?>
<main class="report-layout">
  <aside class="report-side"><span class="eyebrow">SECURE REPORTING</span><h1>Tell us what you saw.</h1><p>Give enough detail for a reviewer to understand the situation. You can report anonymously.</p><div class="side-rule"></div><p class="mini"><b>Good reports include</b><br>What happened, where it happened, who was involved and useful evidence.</p></aside>
  <section class="form-panel">
    <div class="form-title"><span class="eyebrow">NEW CASE</span><h2>Submit a trust &amp; safety report</h2><p>Fields marked * are required.</p></div>
    <form action="submit_report.php" method="post">
      <input type="hidden" name="csrf" value="<?= csrf() ?>">
      <div class="field-row"><label>Report type *<select name="report_type" required><?php foreach($types as $k=>$v): ?><option value="<?=e($k)?>" <?=($type===$k?'selected':'')?>><?=e($v)?></option><?php endforeach; ?></select></label><label>Severity *<select name="severity"><option>low</option><option selected>medium</option><option>high</option><option>critical</option></select></label></div>
      <label>Report title *<input name="title" maxlength="220" required placeholder="Briefly describe the issue"></label>
      <div class="field-row"><label>Reported URL<input name="target_url" maxlength="500" placeholder="https://example.com/..."></label><label>Username / profile<input name="target_username" maxlength="120" placeholder="@username"></label></div>
      <label>What happened? *<textarea name="description" rows="7" required placeholder="Describe the behavior, content or incident in as much useful detail as possible."></textarea></label>
      <label>Evidence &amp; context<textarea name="evidence" rows="4" placeholder="Links, timestamps, relevant messages, screenshots you can reference, or other context."></textarea></label>
      <div class="anonymous-box"><label class="check"><input type="checkbox" name="anonymous" value="1" id="anon"> Submit anonymously</label><small>Anonymous reports cannot receive direct follow-up.</small></div>
      <div id="contactFields"><div class="field-row"><label>Your name<input name="reporter_name" maxlength="120"></label><label>Your email<input type="email" name="reporter_email" maxlength="190" placeholder="you@example.com"></label></div></div>
      <button class="button dark wide" type="submit">Submit report <span>→</span></button>
    </form>
  </section>
</main>
<script>
document.getElementById('anon').addEventListener('change', e => {
 document.getElementById('contactFields').style.display=e.target.checked?'none':'block';
});
</script>
<?php require 'partials_footer.php'; ?>