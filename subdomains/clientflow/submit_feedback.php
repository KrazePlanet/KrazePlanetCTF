<?php
require_once __DIR__.'/bootstrap.php'; db();
if($_SERVER['REQUEST_METHOD']!=='POST') redirect('feedback.php'); check_csrf();
if(trim($_POST['website']??'')!=='') redirect('feedback.php');
$name=trim($_POST['name']??'');$email=strtolower(trim($_POST['email']??''));$category=trim($_POST['category']??'');$message=trim($_POST['message']??'');
$allowed=['General feedback','Account experience','Website feedback','Security report','Other'];
if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($category,$allowed,true)||$message===''){flash('error','Please complete all feedback fields.');redirect('feedback.php');}
$userId=$_SESSION['user_id']??null;$s=db()->prepare("INSERT INTO feedback(name,email,category,message,user_id) VALUES(?,?,?,?,?)");$s->execute([$name,$email,$category,$message,$userId]);
flash('success','Thank you. Your feedback has been received.');redirect('feedback.php');
