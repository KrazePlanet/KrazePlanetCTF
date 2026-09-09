<?php
require_once __DIR__.'/../config.php'; require_admin(); verify_csrf();
$id=(int)($_POST['id']??0);$pdo=db();$s=$pdo->prepare("SELECT * FROM reports WHERE id=?");$s->execute([$id]);$old=$s->fetch();if(!$old)exit('Case not found.');
if(($_POST['action']??'')==='note'){ $note=trim($_POST['note']??'');if($note!==''){$pdo->prepare("INSERT INTO report_notes(report_id,note) VALUES(?,?)")->execute([$id,$note]);$pdo->prepare("INSERT INTO report_events(report_id,event_text) VALUES(?,?)")->execute([$id,'Internal review note added']);}header("Location:view.php?id=$id");exit;}
$status=$_POST['status']??$old['status'];$severity=$_POST['severity']??$old['severity'];$reply=trim($_POST['admin_response']??'');
$validS=['new','investigating','action_required','resolved','closed'];$validV=['low','medium','high','critical'];if(!in_array($status,$validS,true)||!in_array($severity,$validV,true))exit('Invalid status.');
$pdo->prepare("UPDATE reports SET status=?,severity=?,admin_response=? WHERE id=?")->execute([$status,$severity,$reply?:null,$id]);
if($old['status']!==$status)$pdo->prepare("INSERT INTO report_events(report_id,event_text) VALUES(?,?)")->execute([$id,'Status changed to '.str_replace('_',' ',$status)]);
if($reply!==($old['admin_response']??''))$pdo->prepare("INSERT INTO report_events(report_id,event_text) VALUES(?,?)")->execute([$id,'Customer-facing case update posted']);
flash('Case updated successfully.');header("Location:view.php?id=$id");exit;
?>