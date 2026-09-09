<?php
require 'config.php'; if($_SERVER['REQUEST_METHOD']!=='POST') {header('Location:file.php');exit;} check_csrf();
$name=trim($_POST['claimant_name']??'');$email=trim($_POST['claimant_email']??'');$phone=trim($_POST['phone']??'');$company=trim($_POST['company']??'');$tx=trim($_POST['transaction_ref']??'');$amount=(float)($_POST['amount']??0);$currency=trim($_POST['currency']??'INR');$type=trim($_POST['dispute_type']??'Other');$desc=trim($_POST['description']??'');$resolution=trim($_POST['desired_resolution']??'');$summary=trim($_POST['evidence_summary']??'');
$types=['Unauthorized payment','Duplicate charge','Product not received','Service issue','Not as described','Refund issue','Other']; if(!$name||!filter_var($email,FILTER_VALIDATE_EMAIL)||!$desc||!in_array($type,$types,true))die('Please complete the required fields.');
$no=case_no();$st=db()->prepare("INSERT INTO disputes(case_no,claimant_name,claimant_email,phone,company,transaction_ref,amount,currency,dispute_type,description,desired_resolution,evidence_summary) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
$st->bind_param('ssssssdsssss',$no,$name,$email,$phone,$company,$tx,$amount,$currency,$type,$desc,$resolution,$summary);if(!$st->execute())die('Could not create case.');
$id=$st->insert_id;
$ev=db()->prepare("INSERT INTO dispute_events(dispute_id,event_text,public_note) VALUES(?,?,?)");$event='Case submitted';$note='Your dispute was received and is ready for review.';$ev->bind_param('iss',$id,$event,$note);$ev->execute();
$dir=__DIR__.'/uploads';if(!is_dir($dir))mkdir($dir,0755,true);
if(!empty($_FILES['evidence']['name']) && is_array($_FILES['evidence']['name'])){
  $allowed=['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png'];
  for($i=0;$i<count($_FILES['evidence']['name']);$i++){
    if($_FILES['evidence']['error'][$i]!==UPLOAD_ERR_OK)continue;
    if($_FILES['evidence']['size'][$i]>5*1024*1024)continue;
    $orig=basename($_FILES['evidence']['name'][$i]);$ext=strtolower(pathinfo($orig,PATHINFO_EXTENSION));
    if(!isset($allowed[$ext]))continue;
    $stored=bin2hex(random_bytes(12)).'.'.$ext;
    if(move_uploaded_file($_FILES['evidence']['tmp_name'][$i],$dir.'/'.$stored)){
      $f=$db=db();$x=$f->prepare("INSERT INTO evidence(dispute_id,original_name,stored_name,mime_type,file_size) VALUES(?,?,?,?,?)");$size=(int)$_FILES['evidence']['size'][$i];$mime=$allowed[$ext];$x->bind_param('isssi',$id,$orig,$stored,$mime,$size);$x->execute();
    }
  }
}
header('Location:submitted.php?case='.urlencode($no));exit;