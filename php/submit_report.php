<?php require __DIR__.'/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['success'=>false,'message'=>'POST only'],405);
$cat=(int)($_POST['category_id']??0); $area=(int)($_POST['area_id']??0);
$desc=clean($_POST['description']??''); $land=clean($_POST['landmark']??'');
$method=clean($_POST['contact_method']??''); $detail=clean($_POST['contact_detail']??'');
if ($method !== 'email') $detail = preg_replace('/\s+/','',$detail);
$err=[];
if (!$cat) $err[]='Choose a category';
if (!$area) $err[]='Choose an area';
if (strlen($desc) < 10) $err[]='Description must be at least 10 characters';
if (!in_array($method,['email','phone','whatsapp'],true)) $err[]='Choose how to receive your code';
elseif ($method==='email' && !filter_var($detail,FILTER_VALIDATE_EMAIL)) $err[]='Enter a valid email address';
elseif ($method!=='email' && !preg_match('/^(\+?254|0)[17]\d{8}$/',$detail)) $err[]='Enter a valid Kenyan phone number';
$photo=null; $f=null;
if (!empty($_FILES['photo']['name'])) {
  $f=$_FILES['photo']; $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
  if ($f['error']!==0 || $f['size']>2097152 || !in_array($ext,['jpg','jpeg','png'],true) || !@getimagesize($f['tmp_name'])) $err[]='Photo must be a JPG or PNG under 2MB';
}
if ($err) out(['success'=>false,'message'=>implode('. ',$err)],422);
if ($f) { $name=bin2hex(random_bytes(8)).'.'.$ext; move_uploaded_file($f['tmp_name'],__DIR__.'/../uploads/'.$name); $photo='uploads/'.$name; }
$code='RF-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(3)));
$st=$db->prepare("INSERT INTO reports (reference_code,category_id,area_id,description,landmark,photo_path,contact_method,contact_detail) VALUES (?,?,?,?,?,?,?,?)");
$st->bind_param('siisssss',$code,$cat,$area,$desc,$land,$photo,$method,$detail);
if (!$st->execute()) out(['success'=>false,'message'=>'Could not save the report'],500);
$id=$st->insert_id;
$h=$db->prepare("INSERT INTO status_updates (report_id,new_status,resolution_note) VALUES (?,'Submitted','Report received')");
$h->bind_param('i',$id); $h->execute();
// Code delivery: the message is queued in logs/notifications.log.
// To send for real, replace this line with an email/SMS/WhatsApp provider call.
file_put_contents(__DIR__.'/../logs/notifications.log',date('Y-m-d H:i:s')." | $method | $detail | $code\n",FILE_APPEND);
out(['success'=>true,'code'=>$code,'message'=>"A message with your code is queued for delivery by $method to $detail."]);