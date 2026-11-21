<?php require __DIR__.'/helpers.php'; need_staff();
$id=(int)($_GET['id']??0);
$st=$db->prepare("SELECT r.report_id,r.reference_code,c.category_name,a.area_name,r.landmark,r.description,r.photo_path,r.current_status,DATE_FORMAT(r.date_submitted,'%d %b %Y %H:%i') AS date_submitted FROM reports r JOIN categories c USING(category_id) JOIN areas a USING(area_id) WHERE r.report_id=?");
$st->bind_param('i',$id); $st->execute(); $rep=$st->get_result()->fetch_assoc();
if (!$rep) out(['success'=>false,'message'=>'Report not found'],404);
$h=$db->prepare("SELECT u.new_status,u.resolution_note,DATE_FORMAT(u.updated_at,'%d %b %H:%i') AS updated_at,s.full_name FROM statusupdates u LEFT JOIN staff s USING(staff_id) WHERE u.report_id=? ORDER BY u.update_id DESC");
$h->bind_param('i',$id); $h->execute();
$rep['history']=$h->get_result()->fetch_all(MYSQLI_ASSOC);
out(['success'=>true,'report'=>$rep,'csrf_token'=>$_SESSION['csrf_token']]);