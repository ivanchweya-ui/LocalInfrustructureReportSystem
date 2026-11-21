<?php require __DIR__ . '/helpers.php';
need_system_admin();

$rows = $db->query(
    "SELECT staff_id, full_name, username, email, created_at
     FROM staff
     WHERE role = 'subcounty_admin' AND approval_status = 'pending'
     ORDER BY created_at, staff_id"
)->fetch_all(MYSQLI_ASSOC);
out(['success' => true, 'requests' => $rows]);
