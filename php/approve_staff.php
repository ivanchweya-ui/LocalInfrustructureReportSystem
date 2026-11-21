<?php require __DIR__ . '/helpers.php';
need_system_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['success' => false, 'message' => 'POST only'], 405);
require_csrf();

$staffId = (int) ($_POST['staff_id'] ?? 0);
$decision = clean($_POST['decision'] ?? '');
if ($staffId < 1 || !in_array($decision, ['approve', 'reject'], true)) {
    out(['success' => false, 'message' => 'Choose a valid account request and decision.'], 422);
}

$status = $decision === 'approve' ? 'approved' : 'rejected';
$statement = $db->prepare(
    "UPDATE staff SET approval_status = ?
     WHERE staff_id = ? AND role = 'subcounty_admin' AND approval_status = 'pending'"
);
$statement->bind_param('si', $status, $staffId);
if (!$statement->execute()) {
    error_log('Staff approval failed: ' . $statement->error);
    out(['success' => false, 'message' => 'Could not update the account request.'], 500);
}
if ($statement->affected_rows !== 1) {
    out(['success' => false, 'message' => 'That pending account request no longer exists.'], 404);
}

out(['success' => true, 'message' => 'Account request ' . $status . '.']);
