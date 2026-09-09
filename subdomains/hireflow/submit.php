<?php
require 'config.php'; if($_SERVER['REQUEST_METHOD']!=='POST') {header('Location: jobs.php');exit;} verify_csrf();
$job=(int)($_POST['job_id']??0); $fields=['first_name','last_name','email','phone','location','linkedin','portfolio','experience','notice_period','cover_letter']; foreach($fields as $f) $$f=trim($_POST[$f]??'');
$st=db()->prepare("SELECT id FROM jobs WHERE id=? AND status='open'"); $st->bind_param('i',$job); $st->execute(); if(!stmt_one($st)) die('This role is no longer accepting applications.');
if(!$first_name||!$last_name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$cover_letter) die('Please complete all required fields.');
if(empty($_FILES['resume']['name'])||$_FILES['resume']['error']!==UPLOAD_ERR_OK) die('A resume is required.');
if($_FILES['resume']['size']>5*1024*1024) die('Resume exceeds 5 MB.');
$allowed=['pdf'=>'application/pdf','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$ext=strtolower(pathinfo($_FILES['resume']['name'],PATHINFO_EXTENSION)); if(!isset($allowed[$ext])) die('Only PDF, DOC and DOCX files are accepted.');
$dir=__DIR__.'/uploads'; if(!is_dir($dir)) mkdir($dir,0755,true); $safe=bin2hex(random_bytes(10)).'.'.$ext; if(!move_uploaded_file($_FILES['resume']['tmp_name'],$dir.'/'.$safe)) die('Could not save resume.');
$no=app_no(); $stmt=db()->prepare("INSERT INTO candidates (application_no,job_id,first_name,last_name,email,phone,location,linkedin,portfolio,resume_path,cover_letter,experience,notice_period) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->bind_param('sisssssssssss',$no,$job,$first_name,$last_name,$email,$phone,$location,$linkedin,$portfolio,$safe,$cover_letter,$experience,$notice_period); $stmt->execute(); $cid=$stmt->insert_id;
$event=db()->prepare("INSERT INTO candidate_events(candidate_id,event_text) VALUES(?,?)"); $event->bind_param('is',$cid,$ev); $ev='Application submitted'; $event->execute();
header('Location: submitted.php?ref='.urlencode($no)); exit;