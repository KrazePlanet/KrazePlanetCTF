<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
$pdo=db(); $q=trim($_GET['q']??''); $status=$_GET['status']??'';
$sql='SELECT * FROM inquiries WHERE 1=1'; $params=[];
if($q!==''){ $sql.=' AND (full_name LIKE :q OR email LIKE :q OR service LIKE :q)'; $params[':q']="%$q%"; }
if(in_array($status,['new','read','contacted','closed'],true)){ $sql.=' AND status=:status'; $params[':status']=$status; }
$sql.=' ORDER BY created_at DESC';
$stmt=$pdo->prepare($sql); $stmt->execute($params); $rows=$stmt->fetchAll();
include __DIR__.'/header.php';
?>
<div class="page-head"><div><h1>Project inquiries</h1><p class="muted">Manage leads submitted through your website.</p></div></div>
<form class="filters" method="get"><input name="q" value="<?= e($q) ?>" placeholder="Search name, email or service"><select name="status"><option value="">All statuses</option><?php foreach(['new','read','contacted','closed'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select><button class="small-btn" type="submit">Filter</button></form>
<div class="admin-card"><div class="table-wrap"><table><thead><tr><th>Client</th><th>Service</th><th>Budget</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="6" class="empty">No inquiries found.</td></tr><?php endif; ?>
<?php foreach($rows as $r): ?><tr><td><strong><?= e($r['full_name']) ?></strong><small><?= e($r['email']) ?></small></td><td><?= e($r['service']) ?></td><td><?= e($r['budget'] ?: '—') ?></td><td><span class="status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= e(date('d M Y, H:i',strtotime($r['created_at']))) ?></td><td><a class="small-btn" href="inquiry.php?id=<?= (int)$r['id'] ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__.'/footer.php'; ?>
