<?php
$configPath = __DIR__ . '/config.local.php';
if (!is_file($configPath)) {
    error_log('Local Infrastructure Reporter is not configured: php/config.local.php is missing.');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'This installation is not configured yet.']);
    exit;
}

$appConfig = require $configPath;
$required = ['db_host', 'db_user', 'db_password', 'db_name'];
if (!is_array($appConfig)) {
    error_log('Local Infrastructure Reporter configuration must return an array.');
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'This installation has an invalid configuration.']);
    exit;
}
foreach ($required as $key) {
    if (!array_key_exists($key, $appConfig)) {
        error_log("Local Infrastructure Reporter configuration is missing '$key'.");
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'This installation has an invalid configuration.']);
        exit;
    }
}

mysqli_report(MYSQLI_REPORT_OFF);
$db = new mysqli($appConfig['db_host'], $appConfig['db_user'], $appConfig['db_password'], $appConfig['db_name']);
if ($db->connect_error) {
    error_log('Local Infrastructure Reporter database connection failed: ' . $db->connect_error);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}
$db->set_charset('utf8mb4');
