<?php require __DIR__.'/helpers.php'; need_staff(); $instance=active_instance();
$where=[]; $types=''; $vals=[];
foreach (['category'=>['r.category_id','i'],'area'=>['r.area_id','i'],'status'=>['r.current_status','s']] as $k=>[$col,$t]) {
  $v=clean($_GET[$k]??'');
  if ($v!=='') { $where[]="$col=?"; $types.=$t; $vals[]=($t==='i')?(int)$v:$v; }
}
$sql="SELECT r.report_id,r.reference_code,c.category_name,a.area_name,r.current_status,DATE_FORMAT(r.date_submitted,'%d %b %Y') AS date_submitted FROM reports r JOIN categories c USING(category_id) JOIN areas a USING(area_id)".($where?' WHERE '.implode(' AND ',$where):'')." ORDER BY r.date_submitted DESC, r.report_id DESC";
$st=$db->prepare($sql); if ($vals) $st->bind_param($types,...$vals); $st->execute();
$rows=$st->get_result()->fetch_all(MYSQLI_ASSOC);
$sum=$db->query("SELECT COUNT(*) total, SUM(current_status='Submitted') submitted, SUM(current_status='Under Review') review, SUM(current_status='In Progress') progress, SUM(current_status='Resolved') resolved FROM reports")->fetch_assoc();
out(['success'=>true,'staff'=>$_SESSION['staff_name'],'csrf_token'=>$_SESSION['csrf_token'],'instance'=>$instance,'summary'=>$sum,'reports'=>$rows]);