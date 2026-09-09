<?php
require_once __DIR__ . '/../bootstrap.php'; db(); require_admin();
$counts=[]; foreach(['users','feedback'] as $t){$counts[$t]=(int)db()->query("SELECT COUNT(*) c FROM $t")->fetch()['c'];}
$new=(int)db()->query("SELECT COUNT(*) c FROM feedback WHERE status='new'")->fetch()['c'];
$q=trim($_GET['q']??''); $status=$_GET['status']??'';
$sql="SELECT f.*, u.status user_status FROM feedback f LEFT JOIN users u ON u.id=f.user_id WHERE 1";$p=[];
if($q!==''){ $sql.=" AND (f.name LIKE ? OR f.email LIKE ? OR f.category LIKE ? OR f.message LIKE ?)";$like="%$q%";$p=[$like,$like,$like,$like];}
if(in_array($status,['new','reviewed','resolved'],true)){ $sql.=" AND f.status=?";$p[]=$status;}
$sql.=" ORDER BY f.created_at DESC LIMIT 100";$s=db()->prepare($sql);$s->execute($p);$rows=$s->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Dashboard — <?=e(SITE_NAME)?></title><link rel="stylesheet" href="../assets/style.css"></head>
<body><header class="nav"><a class="brand" href="../index.php"><span class="brand-mark">&lt;/&gt;</span> ClientFlow</a><nav><a href="../index.php">Website</a><a href="dashboard.php">Dashboard</a><a href="users.php">Accounts</a><a href="logout.php">Logout</a></nav></header>
<main class="dashboard-wrap"><div class="page-title"><span class="eyebrow">ADMIN CONSOLE</span><h1>Security Awareness Dashboard</h1><p>Review every account and feedback submission captured by the site.</p></div>
<div class="stats"><div class="stat"><span>Total feedback</span><strong><?=$counts['feedback']?></strong></div><div class="stat"><span>New feedback</span><strong><?=$new?></strong></div><div class="stat"><span>Accounts</span><strong><?=$counts['users']?></strong></div></div>
<section class="panel"><div class="table-head"><div><h2>Feedback submissions</h2><p class="muted">Latest 100 submissions.</p></div><form class="filters"><input name="q" value="<?=e($q)?>" placeholder="Search name, email, message..."><select name="status"><option value="">All statuses</option><?php foreach(['new','reviewed','resolved'] as $x):?><option <?=$status===$x?'selected':''?>><?=$x?></option><?php endforeach;?></select><button class="btn small">Filter</button></form></div>
<div class="table-scroll"><table><thead><tr><th>When</th><th>Sender</th><th>Category</th><th>Message</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach($rows as $r): ?><tr><td><?=e(date('d M Y, H:i',strtotime($r['created_at'])))?></td><td><strong><?=e($r['name'])?></strong><small><?=e($r['email'])?></small></td><td><?=e($r['category'])?></td><td class="message-cell"><?=e($r['message'])?></td><td><span class="status <?=e($r['status'])?>"><?=e($r['status'])?></span></td><td><a class="text-link" href="feedback.php?id=<?=$r['id']?>">View</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="6" class="empty">No feedback submissions found.</td></tr><?php endif; ?>
</tbody></table></div></section></main></body></html>
