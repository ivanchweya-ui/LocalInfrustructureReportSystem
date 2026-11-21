<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Run this installer from the command line.\n");
}

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function runSqlFile(mysqli $db, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        fail("Could not read SQL file: $path");
    }

    if (!$db->multi_query($sql)) {
        fail("Could not run SQL file $path: " . $db->error);
    }

    do {
        if ($result = $db->store_result()) {
            $result->free();
        }
        if ($db->errno) {
            fail("Could not run SQL file $path: " . $db->error);
        }
    } while ($db->more_results() && $db->next_result());
}

function ensureStaffColumn(mysqli $db, string $column, string $definition): void
{
    $check = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $table = 'staff';
    $check->bind_param('ss', $table, $column);
    $check->execute();
    $check->bind_result($exists);
    $check->fetch();
    $check->close();

    if ((int) $exists === 0) {
        $db->query("ALTER TABLE staff ADD COLUMN `$column` $definition");
    }
}

function findSubcounty(string $path, int $countyCode, int $subcountyCode): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        fail("Could not read the national sub-county directory: $path");
    }

    $header = fgetcsv($handle);
    if (!is_array($header)) {
        fclose($handle);
        fail('The national sub-county directory is empty.');
    }
    $indexes = array_flip($header);
    foreach (['COUNTY CODE', 'COUNTY NAME', 'SUB-COUNTY CODE', 'SUB-COUNTY NAME'] as $required) {
        if (!array_key_exists($required, $indexes)) {
            fclose($handle);
            fail("The national sub-county directory is missing the '$required' column.");
        }
    }

    while (($row = fgetcsv($handle)) !== false) {
        if ((int) ($row[$indexes['COUNTY CODE']] ?? 0) === $countyCode
            && (int) ($row[$indexes['SUB-COUNTY CODE']] ?? 0) === $subcountyCode) {
            fclose($handle);
            return [
                'county_name' => trim($row[$indexes['COUNTY NAME']]),
                'subcounty_name' => trim($row[$indexes['SUB-COUNTY NAME']]),
            ];
        }
    }

    fclose($handle);
    fail("County code $countyCode and sub-county code $subcountyCode were not found in the reference directory.");
}

function readAreas(string $path, int $countyCode, int $subcountyCode): array
{
    if (!is_file($path)) {
        fail("Create $path from areas.local.example.csv using the county-verified area/ward list for this sub-county.");
    }

    $handle = fopen($path, 'rb');
    if ($handle === false) {
        fail("Could not read area file: $path");
    }

    $header = fgetcsv($handle);
    $expected = ['countycode', 'subcountycode', 'areaname', 'region', 'location'];
    if ($header !== $expected) {
        fclose($handle);
        fail('The local area file must have these columns in order: ' . implode(',', $expected));
    }

    $areas = [];
    $seen = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) !== count($expected)) {
            fclose($handle);
            fail('The local area file contains a malformed row.');
        }
        if ((int) $row[0] !== $countyCode || (int) $row[1] !== $subcountyCode) {
            fclose($handle);
            fail('Every area row must match the configured county and sub-county codes.');
        }

        [, , $areaName, $region, $location] = array_map('trim', $row);
        if ($areaName === '' || $region === '' || $location === '') {
            fclose($handle);
            fail('Area name, region, and location must not be empty.');
        }
        $key = strtolower($areaName . "\0" . $region);
        if (isset($seen[$key])) {
            fclose($handle);
            fail("The local area file contains a duplicate area: $areaName.");
        }
        $seen[$key] = true;
        $areas[] = ['area_name' => $areaName, 'region' => $region, 'location' => $location];
    }
    fclose($handle);

    if (!$areas) {
        fail('The local area file must contain at least one county-verified area or ward.');
    }
    return $areas;
}

function importAreas(mysqli $db, array $areas, int $subcountyCode): void
{
    $insert = $db->prepare(
        'INSERT INTO areas (subcounty_code, area_name, region, location) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE location = VALUES(location)'
    );
    foreach ($areas as $area) {
        $insert->bind_param('isss', $subcountyCode, $area['area_name'], $area['region'], $area['location']);
        $insert->execute();
    }
    $insert->close();
}

if (PHP_VERSION_ID < 70400) {
    fail('PHP 7.4 or newer is required.');
}

$configPath = __DIR__ . '/../php/config.local.php';
if (!is_file($configPath)) {
    fail('Copy php/config.local.example.php to php/config.local.php and configure this installation first.');
}
$config = require $configPath;
if (!is_array($config)) {
    fail('php/config.local.php must return a configuration array.');
}

$requiredConfig = ['db_host', 'db_user', 'db_password', 'db_name', 'county_code', 'subcounty_code'];
foreach ($requiredConfig as $key) {
    if (!array_key_exists($key, $config)) {
        fail("php/config.local.php is missing '$key'.");
    }
}
if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $config['db_name'])) {
    fail('The configured database name may contain only letters, numbers, and underscores.');
}

$countyCode = (int) $config['county_code'];
$subcountyCode = (int) $config['subcounty_code'];
$subcounty = findSubcounty(__DIR__ . '/kenya_subcounties.csv', $countyCode, $subcountyCode);
$areas = readAreas(__DIR__ . '/areas.local.csv', $countyCode, $subcountyCode);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli($config['db_host'], $config['db_user'], $config['db_password'], $config['db_name']);
    if ($db->connect_error) {
        fail('Could not connect to the configured database. Create it and grant the configured user access first.');
    }
    $db->set_charset('utf8mb4');

    runSqlFile($db, __DIR__ . '/schema.sql');
    ensureStaffColumn($db, 'role', "ENUM('system_admin','subcounty_admin') NOT NULL DEFAULT 'subcounty_admin'");
    ensureStaffColumn($db, 'approval_status', "ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    ensureStaffColumn($db, 'created_at', 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    $db->query('ALTER TABLE reports MODIFY reference_code VARCHAR(32) NOT NULL');
    $db->query("ALTER TABLE reports MODIFY contact_method ENUM('email','phone','whatsapp') NULL");
    $db->query('ALTER TABLE reports MODIFY contact_detail VARCHAR(100) NULL');
    runSqlFile($db, __DIR__ . '/seed.sql');

    $countyName = $subcounty['county_name'];
    $subcountyName = $subcounty['subcounty_name'];
    $county = $db->prepare('INSERT IGNORE INTO counties (county_code, county_name) VALUES (?, ?)');
    $county->bind_param('is', $countyCode, $countyName);
    $county->execute();
    $county->close();
    $subcountyRow = $db->prepare('INSERT IGNORE INTO subcounties (subcounty_code, county_code, subcounty_name) VALUES (?, ?, ?)');
    $subcountyRow->bind_param('iis', $subcountyCode, $countyCode, $subcountyName);
    $subcountyRow->execute();
    $subcountyRow->close();

    $existing = $db->query('SELECT county_code, county_name, subcounty_code, subcounty_name FROM instanceconfig WHERE instance_id = 1')->fetch_assoc();
    if ($existing) {
        if ((int) $existing['county_code'] !== $countyCode
            || (int) $existing['subcounty_code'] !== $subcountyCode
            || $existing['county_name'] !== $subcounty['county_name']
            || $existing['subcounty_name'] !== $subcounty['subcounty_name']) {
            fail('This database is already assigned to another sub-county. Use a separate database for each instance.');
        }
    } else {
        $legacy = $db->query(
            'SELECT
                (SELECT COUNT(*) FROM reports) AS reports,
                (SELECT COUNT(*) FROM areas) AS areas,
                (SELECT COUNT(*) FROM staff) AS staff'
        )->fetch_assoc();
        if ((int) $legacy['reports'] > 0 || (int) $legacy['areas'] > 0 || (int) $legacy['staff'] > 0) {
            fail('This legacy database already has reports, areas, or staff without an instance assignment. Back it up and use a new dedicated database; this installer will not relabel or delete existing data.');
        }
        $instance = $db->prepare(
            'INSERT INTO instanceconfig (instance_id, county_code, county_name, subcounty_code, subcounty_name)
             VALUES (1, ?, ?, ?, ?)'
        );
        $instance->bind_param('isis', $countyCode, $countyName, $subcountyCode, $subcountyName);
        $instance->execute();
        $instance->close();
    }

    importAreas($db, $areas, $subcountyCode);

    $adminCount = (int) $db->query("SELECT COUNT(*) AS total FROM staff WHERE role = 'system_admin'")->fetch_assoc()['total'];
    if ($adminCount === 0) {
        $username = trim((string) getenv('SYSTEM_ADMIN_USERNAME'));
        $password = (string) getenv('SYSTEM_ADMIN_PASSWORD');
        $fullName = trim((string) (getenv('SYSTEM_ADMIN_NAME') ?: 'System Administrator'));
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            fail('Set SYSTEM_ADMIN_USERNAME to a 3-50 character username using letters, numbers, dots, underscores, or hyphens.');
        }
        if (strlen($password) < 16) {
            fail('Set SYSTEM_ADMIN_PASSWORD to a password of at least 16 characters before creating the first system administrator.');
        }
        if (strlen($fullName) < 2 || strlen($fullName) > 100) {
            fail('SYSTEM_ADMIN_NAME must be between 2 and 100 characters.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $admin = $db->prepare(
            "INSERT INTO staff (full_name, username, password_hash, subcounty_code, role, approval_status)
             VALUES (?, ?, ?, ?, 'system_admin', 'approved')"
        );
        $admin->bind_param('sssi', $fullName, $username, $hash, $subcountyCode);
        $admin->execute();
        $admin->close();
        echo "Created the initial system administrator '$username'." . PHP_EOL;
    }

    echo "Installation ready for {$subcounty['subcounty_name']}, {$subcounty['county_name']}." . PHP_EOL;
    echo "This installer does not drop tables or create sample reports." . PHP_EOL;
} catch (mysqli_sql_exception $e) {
    fail('Installation failed: ' . $e->getMessage());
}
