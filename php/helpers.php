<?php
ini_set('display_errors', '0');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();
header('Content-Type: application/json');
require __DIR__ . '/db_connect.php';

function out($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function need_staff(): void
{
    if (empty($_SESSION['staff_id']) || empty($_SESSION['staff_role'])) {
        out(['success' => false, 'message' => 'Login required'], 401);
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function need_system_admin(): void
{
    need_staff();
    if ($_SESSION['staff_role'] !== 'system_admin') {
        out(['success' => false, 'message' => 'System administrator access required'], 403);
    }
}

function require_csrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    $stored = (string) ($_SESSION['csrf_token'] ?? '');
    if ($stored === '' || $submitted === '' || !hash_equals($stored, $submitted)) {
        out(['success' => false, 'message' => 'The request could not be verified. Reload and try again.'], 403);
    }
}

function clean($value): string
{
    return trim((string) $value);
}

function active_instance(): array
{
    global $db;
    $instance = $db->query(
        'SELECT county_code, county_name, subcounty_code, subcounty_name
         FROM instanceconfig WHERE instance_id = 1'
    )->fetch_assoc();
    if (!$instance) {
        out(['success' => false, 'message' => 'This installation is not configured for a sub-county.'], 503);
    }
    return $instance;
}
