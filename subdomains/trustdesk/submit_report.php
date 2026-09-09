<?php
require 'config.php';
verify_csrf();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: report.php'); exit; }
$type = trim($_POST['report_type'] ?? 'other');
$severity = trim($_POST['severity'] ?? 'medium');
$allowedTypes=['content','user','profile','comment','listing','seller','review','spam','abuse','other'];
$allowedSev=['low','medium','high','critical'];
if (!in_array($type,$allowedTypes,true) || !in_array($severity,$allowedSev,true)) exit('Invalid report.');
$title=trim($_POST['title']??''); $description=trim($_POST['description']??'');
if ($title==='' || $description==='') exit('Please complete the required fields.');
$anon=!empty($_POST['anonymous'])?1:0;
$name=$anon?null:trim($_POST['reporter_name']??'');
$email=$anon?null:trim($_POST['reporter_email']??'');
if ($email!==null && $email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)) exit('Invalid email.');
$pdo=db(); $no=case_no();
$stmt=$pdo->prepare("INSERT INTO reports(case_no,reporter_name,reporter_email,anonymous,report_type,target_url,target_username,title,description,evidence,severity) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([$no,$name?:null,$email?:null,$anon,$type,trim($_POST['target_url']??'')?:null,trim($_POST['target_username']??'')?:null,$title,$description,trim($_POST['evidence']??'')?:null,$severity]);
$id=(int)$pdo->lastInsertId();
$ev=$pdo->prepare("INSERT INTO report_events(report_id,event_text) VALUES(?,?)"); $ev->execute([$id,'Report submitted and added to the review queue']);
header('Location: submitted.php?case='.urlencode($no)); exit;
?>