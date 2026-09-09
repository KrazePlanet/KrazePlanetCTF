<?php
require_once __DIR__.'/../config.php'; require_admin();
$q=trim($_GET['q']??''); $status=$_GET['status']??''; $severity=$_GET['severity']??'';
$where=[];$args=[];
if($q!==''){ $where[]="(case_no LIKE ? OR title LIKE ? OR reporter_email LIKE ? OR target_username LIKE ?)"; $x="%$q%";$args=[$x,$x,$x,$x];}
if(in_array($status,['new','investigating','action_required','resolved','closed'],true)){$where[]="status=?";$args[]=$status;}
if(in_array($severity,['low','medium','high','critical'],true)){$where[]="severity=?";$args[]=$severity;}
$sql="SELECT * FROM reports".($where?" WHERE ".implode(" AND ",$where):"")." ORDER BY FIELD(status,'new','investigating','action_required','resolved','closed'), created_at DESC";
$s=db()->prepare($sql);$s->execute($args);$reports=$s->fetchAll();
$pdo=db();$counts=[];foreach(['new','investigating','action_required','resolved','closed'] as $st){$x=$pdo->prepare("SELECT COUNT(*) c FROM reports WHERE status=?");$x->execute([$st]);$counts[$st]=(int)$x->fetch()['c'];}
$title='Review Queue'; ?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TrustDesk · Review Queue</title><link rel="stylesheet" href="../assets/style.css"></head><body class="admin-shell">
<header class="admin-top"><a class="brand" href="../index.php"><span class="shield">✦</span>TrustDesk</a><div><span>Review console</span><a href="logout.php">Sign out</a></div></header>
<main class="admin-main"><div class="admin-title"><div><span class="eyebrow">TRUST &amp; SAFETY CONSOLE</span><h1>Review queue</h1></div><a class="button dark" href="../report.php">New report ↗</a></div>
<div class="stats"><?php foreach($counts as $k=>$v):?><div><small><?=e(str_replace('_',' ',ucfirst($k)))?></small><strong><?=$v?></strong></div><?php endforeach;?></div>
<form class="filters"><input name="q" value="<?=e($q)?>" placeholder="Search case, title, email, username"><select name="status"><option value="">All statuses</option><?php foreach(['new','investigating','action_required','resolved','closed'] as $v):?><option <?=$status===$v?'selected':''?> value="<?=$v?>"><?=e(ucfirst(str_replace('_',' ',$v)))?></option><?php endforeach;?></select><select name="severity"><option value="">All severity</option><?php foreach(['low','medium','high','critical'] as $v):?><option <?=$severity===$v?'selected':''?> value="<?=$v?>"><?=ucfirst($v)?></option><?php endforeach;?></select><button class="button">Filter</button></form>
<div class="queue"><?php foreach($reports as $r):?><a class="queue-row" href="view.php?id=<?=$r['id']?>"><div class="q-main"><span class="case-id"><?=e($r['case_no'])?></span><h3><?=e($r['title'])?></h3><p><?=e($r['reporter_email']?:'Anonymous report')?> · <?=e(ucfirst($r['report_type']))?></p></div><div class="q-meta"><span class="status status-<?=e($r['status'])?>"><?=e(str_replace('_',' ',$r['status']))?></span><b class="sev-<?=e($r['severity'])?>"><?=e($r['severity'])?></b><small><?=e(date('M j, Y',strtotime($r['created_at'])))?></small></div></a><?php endforeach; if(!$reports):?><div class="empty">No reports match these filters.</div><?php endif;?></div>
</main></body></html>