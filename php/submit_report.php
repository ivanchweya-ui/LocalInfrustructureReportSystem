<?php require __DIR__.'/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['success'=>false,'message'=>'POST only'],405);
$instance=active_instance();
$cat=(int)($_POST['category_id']??0); $area=(int)($_POST['area_id']??0);
$desc=clean($_POST['description']??''); $land=clean($_POST['landmark']??'');
$method=clean($_POST['contact_method']??''); $detail=clean($_POST['contact_detail']??'');
if ($method !== '' && $method !== 'email') $detail = preg_replace('/\s+/','',$detail);
$err=[];
if (!$cat) $err[]='Choose a category';
if (!$area) $err[]='Choose an area';
if (strlen($desc) < 10) $err[]='Description must be at least 10 characters';
if (($method === '') !== ($detail === '')) $err[]='Choose a contact method and enter its contact detail, or leave both blank';
elseif ($method !== '' && !in_array($method,['email','phone','whatsapp'],true)) $err[]='Choose a valid contact method';
elseif ($method==='email' && !filter_var($detail,FILTER_VALIDATE_EMAIL)) $err[]='Enter a valid email address';
elseif ($method!=='' && $method!=='email' && !preg_match('/^(\+?254|0)[17]\d{8}$/',$detail)) $err[]='Enter a valid Kenyan phone number';
$photo=null; $photoDiskPath=null; $f=null;
if (!empty($_FILES['photo']['name'])) {
  $f=$_FILES['photo']; $ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));
  if ($f['error']!==0 || $f['size']>2097152 || !in_array($ext,['jpg','jpeg','png'],true) || !@getimagesize($f['tmp_name'])) $err[]='Photo must be a JPG or PNG under 2MB';
}
if ($err) out(['success'=>false,'message'=>implode('. ',$err)],422);
if ($f) {
  $name=bin2hex(random_bytes(8)).'.'.$ext;
  $photoDiskPath=__DIR__.'/../uploads/'.$name;
  if (!move_uploaded_file($f['tmp_name'],$photoDiskPath)) out(['success'=>false,'message'=>'Could not store the uploaded photo'],500);
  $photo='uploads/'.$name;
}
$code='RF-'.date('Y').'-'.str_pad((string)$instance['subcounty_code'],4,'0',STR_PAD_LEFT).'-'.strtoupper(bin2hex(random_bytes(8)));
$methodValue=$method===''?null:$method;
$detailValue=$detail===''?null:$detail;
if (!$db->begin_transaction()) {
  if ($photoDiskPath && is_file($photoDiskPath) && !unlink($photoDiskPath)) error_log('Could not remove uploaded photo after transaction start failure.');
  error_log('Could not start report transaction: '.$db->error);
  out(['success'=>false,'message'=>'Could not start saving the report'],500);
}
$st=$db->prepare("INSERT INTO reports (reference_code,category_id,area_id,description,landmark,photo_path,contact_method,contact_detail) VALUES (?,?,?,?,?,?,?,?)");
$st->bind_param('siisssss',$code,$cat,$area,$desc,$land,$photo,$methodValue,$detailValue);
if (!$st->execute()) {
  $db->rollback();
  if ($photoDiskPath && is_file($photoDiskPath) && !unlink($photoDiskPath)) error_log('Could not remove uploaded photo after report insert failure.');
  error_log('Report insert failed: '.$st->error);
  out(['success'=>false,'message'=>'Could not save the report'],500);
}
$id=$st->insert_id;
$h=$db->prepare("INSERT INTO statusupdates (report_id,new_status,resolution_note) VALUES (?,'Submitted','Report received')");
$h->bind_param('i',$id);
if (!$h->execute()) {
  $db->rollback();
  if ($photoDiskPath && is_file($photoDiskPath) && !unlink($photoDiskPath)) error_log('Could not remove uploaded photo after status history insert failure.');
  error_log('Initial report history insert failed: '.$h->error);
  out(['success'=>false,'message'=>'Could not save the report history'],500);
}
if (!$db->commit()) {
  error_log('Could not commit report transaction: '.$db->error);
  out(['success'=>false,'message'=>'Could not finish saving the report'],500);
}
out([
  'success'=>true,
  'code'=>$code,
  'message'=>'No email, SMS, or WhatsApp message was sent. Save this reference code to check your report status.',
],201);