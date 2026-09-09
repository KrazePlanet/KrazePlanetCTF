<?php require_once __DIR__ . '/bootstrap.php'; db(); if (user_logged_in()) redirect('account.php'); $flash=get_flash(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Create Account — <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="assets/style.css"></head>
<body class="center-page">
<div class="auth-card"><a class="brand" href="index.php"><span class="brand-mark">&lt;/&gt;</span> ClientFlow</a><h1>Create an Account</h1><p class="muted">Create a demo account to test the account lifecycle workflow.</p>
<?php if($flash): ?><div class="flash <?=e($flash[0])?>"><?=e($flash[1])?></div><?php endif; ?>
<form method="post" action="register_submit.php">
<input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<label>Full name<input name="name" required maxlength="120"></label>
<label>Email<input type="email" name="email" required maxlength="190"></label>
<label>Password<input type="password" name="password" required minlength="8"></label>
<button class="btn primary full" type="submit">Create Account</button>
</form><p class="auth-foot">Already have an account? <a href="login.php">Sign in</a></p><a href="index.php" class="back">← Back to website</a></div>
</body></html>
