<?php require_once __DIR__.'/bootstrap.php'; db(); $reason=$_GET['reason']??''; $flash=get_flash(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Share feedback — ClientFlow</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="sitebar"><a class="logo" href="index.php"><span>CF</span> ClientFlow</a><nav><a href="index.php">Home</a><a href="feedback.php">Feedback</a><a href="register.php">Create account</a><a class="nav-cta" href="admin/login.php">Admin</a></nav></header>
<main class="feedback-page"><div class="feedback-intro"><div class="checkmark">✓</div><p class="kicker">WE'D LOVE TO HEAR FROM YOU</p>
<h1>How was your experience?</h1>
<p>Your feedback helps us improve the experience. Tell us what worked, what didn't, or what you'd change.</p>
<?php if($reason==='deleted'):?><div class="context">Your account has been permanently deleted. Before you go, we'd appreciate your feedback.</div><?php elseif($reason==='deactivated'):?><div class="context">Your account has been deactivated. Before you go, we'd appreciate your feedback.</div><?php endif;?>
</div>
<?php if($flash):?><div class="notice <?=e($flash[0])?>"><?=e($flash[1])?></div><?php endif;?>
<form class="feedback-form" method="post" action="submit_feedback.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<div class="row"><label>Name<input name="name" maxlength="120" required placeholder="Your name"></label><label>Email<input type="email" name="email" maxlength="190" required placeholder="you@example.com"></label></div>
<label>What can we help with?<select name="category" required><option value="">Choose a topic</option><option>General feedback</option><option>Account experience</option><option>Website feedback</option><option>Security report</option><option>Other</option></select></label>
<label>Your feedback<textarea name="message" maxlength="5000" rows="8" required placeholder="Share your experience..."></textarea></label>
<input class="hp" name="website" tabindex="-1" autocomplete="off">
<button class="submit-btn">Send feedback <span>→</span></button>
</form>
<a class="back-link" href="index.php">← Return to ClientFlow</a></main>
</body></html>
