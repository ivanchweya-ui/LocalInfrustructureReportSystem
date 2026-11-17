<?php require __DIR__.'/helpers.php'; need_staff();
$id=(int)($_POST['report_id']??0); $new=clean($_POST['new_status']??''); $note=clean($_POST['note']??'');
$order=['Submitted','Under Review','In Progress','Resolved'];
$st=$db->prepare("SELECT current_status FROM reports WHERE report_id=?"); $st->bind_param('i',$id); $st->execute();
$r=$st->get_result()->fetch_assoc();
if (!$r) out(['success'=>false,'message'=>'Report not found'],404);
$old=$r['current_status'];
if (array_search($new,$order,true) !== array_search($old,$order,true)+1) out(['success'=>false,'message'=>"A report can only move from $old to the next status"],422);
if ($new==='Resolved' && strlen($note)<5) out(['success'=>false,'message'=>'Add a resolution note before resolving'],422);
$db->begin_transaction();
$u=$db->prepare("UPDATE reports SET current_status=? WHERE report_id=?"); $u->bind_param('si',$new,$id); $ok=$u->execute();
$sid=$_SESSION['staff_id'];
$h=$db->prepare("INSERT INTO status_updates (report_id,staff_id,old_status,new_status,resolution_note) VALUES (?,?,?,?,?)");
$h->bind_param('iisss',$id,$sid,$old,$new,$note); $ok=$ok && $h->execute();
if (!$ok) { $db->rollback(); out(['success'=>false,'message'=>'Could not save the update'],500); }
$db->commit();
out(['success'=>true,'message'=>'Status updated']);