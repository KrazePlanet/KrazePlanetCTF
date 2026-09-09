<?php
require __DIR__.'/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: submit.php'); exit; }
check_csrf();
if (!empty($_POST['website'])) { header('Location: submit.php?error=Submission+could+not+be+processed'); exit; }
$required=['title','description','steps','expected_behavior','actual_behavior'];
foreach($required as $f){ if(trim($_POST[$f]??'')===''){ header('Location: submit.php?error=Please+complete+all+required+fields'); exit; } }
$title=trim($_POST['title']); $description=trim($_POST['description']); $steps=trim($_POST['steps']); $expected=trim($_POST['expected_behavior']); $actual=trim($_POST['actual_behavior']);
$severity=$_POST['severity']??'medium'; if(!in_array($severity,['low','medium','high','critical'],true))$severity='medium';
$name=trim($_POST['reporter_name']??''); $email=trim($_POST['reporter_email']??''); $url=trim($_POST['page_url']??''); $browser=trim($_POST['browser_device']??'');
if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL)){header('Location: submit.php?error=Please+enter+a+valid+email');exit;}
if($url!=='' && !filter_var($url,FILTER_VALIDATE_URL)){header('Location: submit.php?error=Please+enter+a+valid+URL');exit;}
$attachment=null;
if(!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error']===UPLOAD_ERR_OK){
  if($_FILES['attachment']['size']>5*1024*1024){header('Location: submit.php?error=Screenshot+must+be+under+5MB');exit;}
  $mime=(new finfo(FILEINFO_MIME_TYPE))->file($_FILES['attachment']['tmp_name']); $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if(!isset($allowed[$mime])){header('Location: submit.php?error=Only+PNG%2C+JPG+or+WEBP+screenshots+are+allowed');exit;}
  $attachment='uploads/'.bin2hex(random_bytes(12)).'.'.$allowed[$mime]; move_uploaded_file($_FILES['attachment']['tmp_name'],__DIR__.'/'.$attachment);
}
$stmt=db()->prepare('INSERT INTO bug_reports(title,description,steps,expected_behavior,actual_behavior,severity,reporter_name,reporter_email,page_url,browser_device,attachment) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
$stmt->execute([$title,$description,$steps,$expected,$actual,$severity,$name?:null,$email?:null,$url?:null,$browser?:null,$attachment]);
flash('Your bug report was submitted successfully.'); header('Location: submit.php');
