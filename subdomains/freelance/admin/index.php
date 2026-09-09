<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
$pdo=db();
$total=(int)$pdo->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
$new=(int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
$contacted=(int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='contacted'")->fetchColumn();
$closed=(int)$pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='closed'")->fetchColumn();
$recent=$pdo->query('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 8')->fetchAll();
include __DIR__.'/header.php';
?>
<h1>Dashboard</h1><p class="muted">Welcome back, <?= e($_SESSION['admin_name']) ?>.</p>
<div class="stats"><div><span>Total inquiries</span><strong><?= $total ?></strong></div><div><span>New</span><strong><?= $new ?></strong></div><div><span>Contacted</span><strong><?= $contacted ?></strong></div><div><span>Closed</span><strong><?= $closed ?></strong></div></div>
<div class="admin-card"><div class="admin-card-head"><h2>Recent inquiries</h2><a class="small-btn" href="inquiries.php">View all</a></div>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Service</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
<?php foreach($recent as $r): ?><tr><td><strong><?= e($r['full_name']) ?></strong><small><?= e($r['email']) ?></small></td><td><?= e($r['service']) ?></td><td><span class="status <?= e($r['status']) ?>"><?= e(ucfirst($r['status'])) ?></span></td><td><?= e(date('d M Y',strtotime($r['created_at']))) ?></td><td><a class="small-btn" href="inquiry.php?id=<?= (int)$r['id'] ?>">View</a></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php include __DIR__.'/footer.php'; ?>
