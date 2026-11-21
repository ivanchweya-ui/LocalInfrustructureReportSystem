<?php require __DIR__ . '/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['success' => false, 'message' => 'POST only'], 405);

$fullName = clean($_POST['full_name'] ?? '');
$username = clean($_POST['username'] ?? '');
$email = clean($_POST['email'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirmation = (string) ($_POST['password_confirmation'] ?? '');
$errors = [];

if (strlen($fullName) < 2 || strlen($fullName) > 100) $errors[] = 'Enter your full name';
if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) $errors[] = 'Username must be 3-50 characters using letters, numbers, dots, underscores, or hyphens';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) $errors[] = 'Enter a valid email address';
if (strlen($password) < 12) $errors[] = 'Password must be at least 12 characters';
if ($password !== $confirmation) $errors[] = 'Passwords do not match';
if ($errors) out(['success' => false, 'message' => implode('. ', $errors)], 422);

$instance = $db->query('SELECT instance_id, subcounty_code FROM instanceconfig WHERE instance_id = 1')->fetch_assoc();
if (!$instance) out(['success' => false, 'message' => 'This installation is not configured for a sub-county.'], 503);

$hash = password_hash($password, PASSWORD_DEFAULT);
$statement = $db->prepare(
    "INSERT INTO staff (full_name, username, password_hash, email, subcounty_code, role, approval_status)
     VALUES (?, ?, ?, ?, ?, 'subcounty_admin', 'pending')"
);
$subcountyCode = (int) $instance['subcounty_code'];
$statement->bind_param('ssssi', $fullName, $username, $hash, $email, $subcountyCode);
if (!$statement->execute()) {
    if ($statement->errno === 1062) out(['success' => false, 'message' => 'That username is already registered.'], 409);
    error_log('Staff signup failed: ' . $statement->error);
    out(['success' => false, 'message' => 'Could not submit the account request. Please try again later.'], 500);
}

out([
    'success' => true,
    'message' => 'Your account request was submitted. You can log in after a system administrator approves it.',
], 201);
