<?php
/**
 * Demo Data Seeder for EndoGuard - Fixed for Charts
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

echo "Clearing existing demo data...\n";
$db->exec([
    'TRUNCATE TABLE event CASCADE',
    'TRUNCATE TABLE event_account CASCADE',
    'TRUNCATE TABLE event_ip CASCADE',
    'TRUNCATE TABLE event_country CASCADE',
    'TRUNCATE TABLE event_url CASCADE',
    'TRUNCATE TABLE event_isp CASCADE',
    'TRUNCATE TABLE event_session CASCADE',
    'TRUNCATE TABLE event_device CASCADE',
    'TRUNCATE TABLE event_ua_parsed CASCADE'
]);

$apiKey = 1;

echo "Seeding diverse data...\n";

// 1. Seed Countries (Linking ISOs to IDs)
$countries = $db->exec('SELECT id, iso FROM countries WHERE iso IN (\'US\', \'GB\', \'DE\', \'IN\', \'CN\', \'FR\', \'BR\', \'CA\', \'AU\', \'JP\')');
$countryMap = [];
foreach ($countries as $c) {
    $countryMap[$c['iso']] = $c['id'];
}

// 2. Seed ISPs
$ispData = [
    ['Google Cloud', '8075'],
    ['Amazon Data Services', '16509'],
    ['DigitalOcean', '14061'],
    ['Comcast Cable', '7922'],
    ['British Telecommunications', '2856'],
    ['Deutsche Telekom', '3320'],
    ['Reliance JioInfo', '55836'],
    ['China Telecom', '4134'],
];

$ispIds = [];
foreach ($ispData as $isp) {
    $res = $db->exec("INSERT INTO event_isp (name, asn, key, updated) VALUES (?, ?, ?, NOW()) RETURNING id", [$isp[0], $isp[1], $apiKey]);
    $ispIds[] = $res[0]['id'];
}

// 3. Seed IPs
$ips = [];
for ($i = 0; $i < 20; $i++) {
    $ip = "104.26.1." . (10 + $i);
    if ($i > 10) $ip = "8.8.8." . $i;
    $countryIso = array_keys($countryMap)[$i % count($countryMap)];
    $ispId = $ispIds[$i % count($ispIds)];
    
    // Spread IP seen dates
    $ipMins = rand(0, 7 * 24 * 60);
    $ipTime = date('Y-m-d H:i:s', strtotime("-$ipMins minutes"));

    $res = $db->exec(
        "INSERT INTO event_ip (ip, key, country, isp, data_center, vpn, tor, lastseen, fraud_detected, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$ip, $apiKey, $countryMap[$countryIso], $ispId, ($i % 3 == 0), ($i % 5 == 0), ($i % 7 == 0), $ipTime, ($i % 5 == 0), $ipTime]
    );
    
    // Get the ID of the inserted IP
    $ipIdRow = $db->exec("SELECT id FROM event_ip WHERE ip = ? AND key = ?", [$ip, $apiKey]);
    $ips[] = $ipIdRow[0]['id'];
    
    // Also update event_country counts
    $db->exec(
        "INSERT INTO event_country (key, country, total_visit, total_ip, lastseen, updated) 
         VALUES (?, ?, 1, 1, ?, ?)
         ON CONFLICT (country, key) DO UPDATE SET total_visit = event_country.total_visit + 1, updated = ?",
        [$apiKey, $countryMap[$countryIso], $ipTime, $ipTime, $ipTime]
    );
}

// 4. Seed Resources
$resourcesData = [
    ['/login', 'Login'],
    ['/dashboard', 'Dashboard'],
    ['/api/v1/user', 'User API'],
    ['/settings', 'Settings'],
    ['/admin/users', 'Users Management'],
    ['/billing', 'Billing']
];
$resourceIds = [];
foreach ($resourcesData as $res) {
    $ins = $db->exec("INSERT INTO event_url (url, title, key, lastseen, updated) VALUES (?, ?, ?, NOW(), NOW()) RETURNING id", [$res[0], $res[1], $apiKey]);
    $resourceIds[] = $ins[0]['id'];
}

// 5. Seed UA Parsed
$uaSpecs = [
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0', 'Chrome', 'Windows 10'],
    ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15', 'Safari', 'macOS'],
    ['Mozilla/5.0 (Linux; Android 13; SM-S901B) Chrome/119.0.0.0 Mobile', 'Chrome Mobile', 'Android 13']
];
$uaIds = [];
foreach ($uaSpecs as $spec) {
    $uaRes = $db->exec(
        "INSERT INTO event_ua_parsed (ua, browser_name, os_name, key) VALUES (?, ?, ?, ?) RETURNING id",
        [$spec[0], $spec[1], $spec[2], $apiKey]
    );
    $uaIds[] = $uaRes[0]['id'];
}

// 6. Seed Users (event_account)
$users = [
    ['john.doe', 'John Doe', 'john@example.com', false, null],
    ['jane.smith', 'Jane Smith', 'jane@test.org', true, true],
    ['suspicious.user', 'Suspicious Activity', 'anon@proxy.com', false, null],
    ['admin.tester', 'Admin Tester', 'admin@internal.net', true, false],
    ['dev.null', 'Dev Null', 'null@blackhole.com', false, null],
];

$userIds = [];
$deviceIds = [];
$sessionIds = [];

foreach ($users as $index => $u) {
    // Spread user signup dates over 7 days
    $signupMins = rand(0, 7 * 24 * 60);
    $signupTime = date('Y-m-d H:i:s', strtotime("-$signupMins minutes"));
    $score = rand(0, 100);

    $res = $db->exec(
        "INSERT INTO event_account (userid, fullname, key, is_important, fraud, score, created, lastseen, updated, total_visit) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1) RETURNING id",
        [$u[0], $u[1], $apiKey, $u[3], $u[4], $score, $signupTime, $signupTime, $signupTime]
    );
    $uId = $res[0]['id'];
    $userIds[] = $uId;

    // 7. Seed Device for user
    $uaId = $uaIds[$index % count($uaIds)];
    $devRes = $db->exec(
        "INSERT INTO event_device (account_id, key, user_agent, updated, lastseen, created) VALUES (?, ?, ?, ?, ?, ?)",
        [$uId, $apiKey, $uaId, $signupTime, $signupTime, $signupTime]
    );
    // Get device ID
    $devIdRow = $db->exec("SELECT id FROM event_device WHERE account_id = ? AND key = ?", [$uId, $apiKey]);
    $deviceIds[$uId] = $devIdRow[0]['id'];

    // 8. Seed Session for user
    $sessionId = rand(10000000, 99999999);
    $db->exec(
        "INSERT INTO event_session (id, key, account_id, lastseen, created, updated) VALUES (?, ?, ?, ?, ?, ?)",
        [$sessionId, $apiKey, $uId, $signupTime, $signupTime, $signupTime]
    );
    $sessionIds[$uId] = $sessionId;
}

// 9. Seed Events (Spread over time)
echo "Generating activity timeline...\n";
$httpCodes = [200, 200, 200, 200, 200, 404, 500, 302, 403];
for ($i = 0; $i < 200; $i++) {
    $uId = $userIds[array_rand($userIds)];
    $ipId = $ips[array_rand($ips)];
    $resId = $resourceIds[array_rand($resourceIds)];
    $dId = $deviceIds[$uId];
    $sId = $sessionIds[$uId];
    $code = $httpCodes[array_rand($httpCodes)];
    $method = rand(1, 11);

    // Random time in last 7 days
    $eventMins = rand(0, 7 * 24 * 60);
    $time = date('Y-m-d H:i:s', strtotime("-$eventMins minutes"));
    
    $db->exec(
        "INSERT INTO event (key, account, ip, url, device, session_id, time, type, http_code, http_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$apiKey, $uId, $ipId, $resId, $dId, $sId, $time, rand(1, 13), $code, $method]
    );
}

echo "Successfully seeded robust demo data for API ID: $apiKey\n";
