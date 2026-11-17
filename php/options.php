<?php require __DIR__.'/helpers.php';
$c = $db->query("SELECT category_id,category_name FROM categories ORDER BY category_name")->fetch_all(MYSQLI_ASSOC);
$a = $db->query("SELECT area_id,area_name,region FROM areas ORDER BY region,area_name")->fetch_all(MYSQLI_ASSOC);
out(['success'=>true,'categories'=>$c,'areas'=>$a]);