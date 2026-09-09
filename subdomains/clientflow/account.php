<?php require_once __DIR__.'/bootstrap.php'; db(); if(!user_logged_in()) redirect('login.php');
$s=db()->prepare("SELECT * FROM users WHERE id=?"); $s->execute([$_SESSION['user_id']]); $u=$s->fetch();
if(!$u){unset($_SESSION['user_id']);redirect('login.php');} $flash=get_flash();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Account settings — ClientFlow</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<header class="sitebar"><a class="logo" href="index.php"><span>CF</span> ClientFlow</a><nav><a href="index.php">Home</a><a href="account.php">Account</a><a href="feedback.php">Feedback</a><a class="nav-cta" href="admin/login.php">Admin</a></nav></header>
<main class="shell"><div class="crumb">Account / Settings</div><div class="title-row"><div><p class="kicker">ACCOUNT SETTINGS</p><h1>Manage your account</h1><p class="sub">Update your account status or share feedback with our team.</p></div></div>
<?php if($flash):?><div class="notice <?=e($flash[0])?>"><?=e($flash[1])?></div><?php endif;?>
<div class="settings-grid">
<section class="surface"><div class="surface-head"><div class="avatar"><?=e(strtoupper(substr($u['name'],0,1)))?></div><div><h2><?=e($u['name'])?></h2><p><?=e($u['email'])?></p></div></div>
<div class="info-list"><div><span>Status</span><b class="pill <?=e($u['status'])?>"><?=e(ucfirst($u['status']))?></b></div><div><span>Member since</span><b><?=e(date('d M Y',strtotime($u['created_at'])))?></b></div></div>
</section>
<section class="surface"><p class="kicker">ACCOUNT ACTIONS</p><h2>Close your account</h2><p class="sub">Choose whether to temporarily deactivate your account or permanently delete it.</p>
<div class="action-stack">
<form method="post" action="deactivate.php"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="action danger-soft" type="submit"><strong>Deactivate account</strong><span>Disable access while keeping your account record.</span></button></form>
<form method="post" action="delete_account.php" onsubmit="return confirm('Delete your account permanently? This cannot be undone.');"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><button class="action danger" type="submit"><strong>Delete account</strong><span>Permanently remove your account and sign you out.</span></button></form>
</div></section>
</div></main></body></html>
