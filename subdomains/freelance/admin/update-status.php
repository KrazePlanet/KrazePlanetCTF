<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) redirect('inquiries.php');
$id=(int)($_POST['id']??0); $status=$_POST['status']??'';
if(in_array($status,['new','read','contacted','closed'],true)){
  $stmt=db()->prepare('UPDATE inquiries SET status=:status WHERE id=:id'); $stmt->execute([':status'=>$status,':id'=>$id]);
}
redirect('inquiry.php?id='.$id);
