<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) redirect('inquiries.php');
$id=(int)($_POST['id']??0);
$stmt=db()->prepare('DELETE FROM inquiries WHERE id=:id'); $stmt->execute([':id'=>$id]);
flash('success','Inquiry deleted.');
redirect('inquiries.php');
