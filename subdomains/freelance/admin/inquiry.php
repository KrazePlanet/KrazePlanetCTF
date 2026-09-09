<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
$pdo=db(); $id=(int)($_GET['id']??0);
$stmt=$pdo->prepare('SELECT * FROM inquiries WHERE id=:id'); $stmt->execute([':id'=>$id]); $r=$stmt->fetch();
if(!$r){ flash('error','Inquiry not found.'); redirect('inquiries.php'); }
include __DIR__.'/header.php';
?>
<div class="page-head"><div><h1>Inquiry #<?= (int)$r['id'] ?></h1><p class="muted"><?= e(date('d M Y, H:i',strtotime($r['created_at']))) ?></p></div><a class="small-btn" href="inquiries.php">← All inquiries</a></div>
<div class="detail-grid"><div class="admin-card"><h2>Client details</h2><div class="detail-list"><div><span>Name</span><strong><?= e($r['full_name']) ?></strong></div><div><span>Email</span><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a></div><div><span>Phone</span><?php if($r['phone']): ?><a href="tel:<?= e($r['phone']) ?>"><?= e($r['phone']) ?></a><?php else: ?>—<?php endif; ?></div><div><span>Service</span><strong><?= e($r['service']) ?></strong></div><div><span>Budget</span><?= e($r['budget'] ?: '—') ?></div><div><span>Timeline</span><?= e($r['timeline'] ?: '—') ?></div></div></div>
<div class="admin-card"><h2>Message</h2><p class="message-box"><?= nl2br(e($r['message'])) ?></p><h3>Update status</h3><div class="status-actions"><?php foreach(['new','read','contacted','closed'] as $s): ?><form method="post" action="update-status.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="<?= $s ?>"><button class="status-btn <?= $s ?>" type="submit"><?= ucfirst($s) ?></button></form><?php endforeach; ?></div></div></div>
<form method="post" action="delete.php" class="delete-form" onsubmit="return confirm('Delete this inquiry permanently?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="danger-btn">Delete inquiry</button></form>
<?php include __DIR__.'/footer.php'; ?>
