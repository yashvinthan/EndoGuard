<?php
/**
 * Advanced Scenario Seeder for EndoGuard
 * Generates real-world security threats and diverse data
 */

require __DIR__ . '/libs/bcosca/fatfree-core/base.php';

spl_autoload_register(function (string $className): void {
    $libs = [
        'Ruler\\' => '/libs/ruler/ruler/src/',
        'PHPMailer\\PHPMailer\\' => '/libs/phpmailer/phpmailer/src/',
        'EndoGuard\\' => '/app/',
    ];
    foreach ($libs as $namespace => $path) {
        if (str_starts_with($className, $namespace)) {
            require __DIR__ . $path . str_replace([$namespace, '\\'], ['', '/'], $className) . '.php';
            break;
        }
    }
});

$f3 = \Base::instance();
$f3->config('config/config.ini');
if (file_exists('config/local/config.local.ini')) {
    $f3->config('config/local/config.local.ini');
}

// Database setup
$dbUrl = getenv('DATABASE_URL') ?: $f3->get('DATABASE_URL');
if (!$dbUrl) {
    die("DATABASE_URL not set\n");
}

$parts = parse_url($dbUrl);
$dsn = "pgsql:host={$parts['host']};port={$parts['port']};dbname=" . ltrim($parts['path'], '/');
$db = new \DB\SQL($dsn, $parts['user'], $parts['pass']);

echo "Cleaning up database...\n";
$db->exec([
    'TRUNCATE TABLE event CASCADE',
    'TRUNCATE TABLE event_account CASCADE',
    'TRUNCATE TABLE event_ip CASCADE',
    'TRUNCATE TABLE event_country CASCADE',
    'TRUNCATE TABLE event_url CASCADE',
    'TRUNCATE TABLE event_isp CASCADE',
    'TRUNCATE TABLE event_session CASCADE',
    'TRUNCATE TABLE event_device CASCADE',
    'TRUNCATE TABLE event_ua_parsed CASCADE',
    'TRUNCATE TABLE dshb_api CASCADE',
    'TRUNCATE TABLE dshb_rules CASCADE'
]);

$apiKeyId = 1;

// 1. Seed API Key
echo "Seeding API credentials...\n";
$db->exec("INSERT INTO dshb_api (id, key, creator, quote, created_at) VALUES (?, ?, 1, 100000, NOW())", [$apiKeyId, 'ed_live_550e8400-e29b-41d4-a716-446655440000']);

// 2. Seed Rules
echo "Seeding security rules...\n";
$rules = [
    ['BR01', 'Brute Force Detection', 'Triggers when more than 10 failed logins occur from one IP', '{"threshold": 10, "window": "5m"}'],
    ['AT01', 'Account Takeover', 'Triggers when one account uses more than 3 countries in 24h', '{"countries": 3}'],
    ['SC01', 'Scraping Protection', 'Triggers when an IP visits more than 50 unique pages in 1 minute', '{"pages": 50}'],
    ['DC01', 'Data Center Filter', 'Flags traffic originating from cloud providers', '{"action": "flag"}']
];
foreach ($rules as $r) {
    $db->exec("INSERT INTO dshb_rules (uid, name, descr, attributes, updated) VALUES (?, ?, ?, ?, NOW())", [$r[0], $r[1], $r[2], $r[3]]);
}

// 3. Seed Countries
$countries = $db->exec('SELECT id, iso FROM countries WHERE iso IN (\'US\', \'GB\', \'DE\', \'IN\', \'CN\', \'FR\', \'BR\', \'CA\', \'AU\', \'JP\')');
$countryMap = [];
foreach ($countries as $c) {
    $countryMap[$c['iso']] = $c['id'];
}

// Helper to create IDs
function getInsertId($db, $table, $data) {
    $keys = array_keys($data);
    $placeholders = array_fill(0, count($data), '?');
    $sql = "INSERT INTO $table (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $placeholders) . ") RETURNING id";
    $res = $db->exec($sql, array_values($data));
    return $res[0]['id'];
}

// --- SCENARIO 1: Brute Force ---
echo "Generating Scenario: Brute Force Attack...\n";
$bfIpId = getInsertId($db, 'event_ip', ['ip' => '192.168.1.50', 'key' => $apiKeyId, 'country' => $countryMap['US'], 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);
$bfUserId = getInsertId($db, 'event_account', ['userid' => 'victim_user', 'fullname' => 'Victim User', 'key' => $apiKeyId, 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s'), 'score' => 85, 'fraud' => true]);
$loginUrlId = getInsertId($db, 'event_url', ['url' => '/login', 'title' => 'Login Page', 'key' => $apiKeyId, 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);
$uaId = getInsertId($db, 'event_ua_parsed', ['ua' => 'Python-urllib/3.10', 'browser_name' => 'Python', 'os_name' => 'Linux', 'key' => $apiKeyId]);
$devId = getInsertId($db, 'event_device', ['account_id' => $bfUserId, 'key' => $apiKeyId, 'user_agent' => $uaId, 'updated' => date('Y-m-d H:i:s'), 'lastseen' => date('Y-m-d H:i:s'), 'created' => date('Y-m-d H:i:s')]);

for ($i = 0; $i < 40; $i++) {
    $time = date('Y-m-d H:i:s', strtotime("-$i minutes"));
    $db->exec("INSERT INTO event (key, account, ip, url, device, session_id, time, type, http_code, http_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
    [$apiKeyId, $bfUserId, $bfIpId, $loginUrlId, $devId, rand(1000, 9999), $time, 1, 401, 2]);
}

// --- SCENARIO 2: Account Takeover (Multiple Countries) ---
echo "Generating Scenario: Account Takeover...\n";
$atoUserId = getInsertId($db, 'event_account', ['userid' => 'global_traveler', 'fullname' => 'Compromised User', 'key' => $apiKeyId, 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s'), 'score' => 92, 'is_important' => true]);
$isos = ['FR', 'BR', 'JP', 'CN', 'DE'];
foreach ($isos as $idx => $iso) {
    $ip = "187.20." . rand(1, 254) . "." . rand(1, 254);
    $ipId = getInsertId($db, 'event_ip', ['ip' => $ip, 'key' => $apiKeyId, 'country' => $countryMap[$iso], 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);
    $time = date('Y-m-d H:i:s', strtotime("-" . ($idx * 2) . " hours"));
    $db->exec("INSERT INTO event (key, account, ip, url, device, session_id, time, type, http_code, http_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
    [$apiKeyId, $atoUserId, $ipId, $loginUrlId, $devId, rand(1000, 9999), $time, 1, 200, 2]);
}

// --- SCENARIO 3: Data Scraping ---
echo "Generating Scenario: Data Scraping...\n";
$scrapIpId = getInsertId($db, 'event_ip', ['ip' => '45.12.33.1', 'key' => $apiKeyId, 'country' => $countryMap['CN'], 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s'), 'vpn' => true]);
for ($i = 0; $i < 60; $i++) {
    $url = "/product/" . rand(1000, 5000);
    $urlId = getInsertId($db, 'event_url', ['url' => $url, 'title' => 'Product Details', 'key' => $apiKeyId, 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);
    $time = date('Y-m-d H:i:s', strtotime("-5 seconds"));
    $db->exec("INSERT INTO event (key, account, ip, url, device, session_id, time, type, http_code, http_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
    [$apiKeyId, $bfUserId, $scrapIpId, $urlId, $devId, rand(1000, 9999), $time, 1, 200, 1]);
}

// --- NORMAL DATA ---
echo "Seeding general activity...\n";
$normalUserId = getInsertId($db, 'event_account', ['userid' => 'happy_customer', 'fullname' => 'Regular User', 'key' => $apiKeyId, 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s'), 'score' => 5]);
$normalIpId = getInsertId($db, 'event_ip', ['ip' => '1.1.1.1', 'key' => $apiKeyId, 'country' => $countryMap['AU'], 'lastseen' => date('Y-m-d H:i:s'), 'updated' => date('Y-m-d H:i:s')]);
for ($i = 0; $i < 50; $i++) {
    $time = date('Y-m-d H:i:s', strtotime("-" . rand(1, 10000) . " minutes"));
    $db->exec("INSERT INTO event (key, account, ip, url, device, session_id, time, type, http_code, http_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 
    [$apiKeyId, $normalUserId, $normalIpId, $loginUrlId, $devId, rand(1000, 9999), $time, 1, 200, 1]);
}

echo "\nDone! Diverse scenarios seeded for API ID: $apiKeyId\n";
echo "Visit http://103.194.228.99/ to see the results.\n";
