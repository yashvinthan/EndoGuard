<?php
/**
 * Demo Data Seeder for EndoGuard - Massive Dataset
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
    'TRUNCATE TABLE event_ua_parsed CASCADE',
    'TRUNCATE TABLE event_field_audit CASCADE',
    'TRUNCATE TABLE event_field_audit_trail CASCADE'
]);

$apiKey = 1;

echo "Seeding massive diverse dataset...\n";

// Countries (From Screenshot)
$countries = $db->exec('SELECT id, iso FROM countries WHERE iso IN (\'AR\', \'AU\', \'AT\', \'BH\', \'BE\', \'BO\', \'BR\', \'BG\', \'CA\', \'CL\', \'CN\', \'CR\', \'CI\', \'CZ\', \'EG\', \'FR\', \'DE\', \'GH\')');
$countryMap = [];
foreach ($countries as $c) {
    $countryMap[$c['iso']] = $c['id'];
}

// ISPs (From Screenshot)
$ispData = [
    ['Dnic As 00749', 749], ['Amazon 02', 16509], ['Chinanet Backbone No.31,Jin Rong Street', 4134],
    ['Att Internet4', 7018], ['Comcast 7922', 7922], ['Microsoft Corp Msn As Block', 8075],
    ['China169 Backbone China Unicom China169 Backbone', 4837], ['Kixs As Kr Korea Telecom', 4766],
    ['Uunet', 701], ['Dtag Deutsche Telekom Ag', 3320], ['Gigainfra Softbank Corp.', 17676],
    ['Ocn Ntt Docomo Business,Inc.', 4713], ['Dnic Asblk 00721 00726', 721], ['Level3', 3356],
    ['Cogent 174', 174], ['Asn Ibsnaz Telecom Italia S.P.A.', 3269], 
    ['Chinamobile Cn China Mobile Communications Group...', 9808], ['Local Area Network', 0],
    ['As3215 Orange S.A.', 3215], ['Apple Engineering', 714], ['N/A', 0]
];
$ispIds = [];
foreach ($ispData as $isp) {
    $res = $db->exec("INSERT INTO event_isp (name, asn, key, updated) VALUES (?, ?, ?, NOW()) RETURNING id", [$isp[0], $isp[1], $apiKey]);
    $ispIds[] = $res[0]['id'];
}

// IPs
$ips = [];
for ($i = 0; $i < 300; $i++) {
    // Generate IPs matching the screenshots (172.x, 149.x, etc.)
    $firstOctets = [172, 149, 216, 50, 104, 180, 213, 88, 147, 218, 66, 75, 133, 198, 20, 22, 183, 223, 59];
    $ip = $firstOctets[array_rand($firstOctets)] . "." . rand(1, 255) . "." . rand(1, 255) . "." . rand(1, 255);
    
    // Pick country randomly
    $countryIso = array_keys($countryMap)[array_rand(array_keys($countryMap))];
    $ispId = $ispIds[array_rand($ispIds)];
    
    $ipMins = rand(0, 24 * 60);
    $ipTime = date('Y-m-d H:i:s', strtotime("-$ipMins minutes"));

    // Biased towards residential vs blacklisted like the screenshots
    $isBlacklisted = ($i % 4 == 0);

    $res = $db->exec(
        "INSERT INTO event_ip (ip, key, country, isp, data_center, vpn, tor, lastseen, fraud_detected, updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id",
        [$ip, $apiKey, $countryMap[$countryIso], $ispId, ($i % 5 == 0), ($i % 10 == 0), ($i % 20 == 0), $ipTime, $isBlacklisted, $ipTime]
    );
    $ips[] = $res[0]['id'];
    
    $db->exec(
        "INSERT INTO event_country (key, country, total_visit, total_ip, lastseen, updated) 
         VALUES (?, ?, 1, 1, ?, ?)
         ON CONFLICT (country, key) DO UPDATE SET total_visit = event_country.total_visit + 1, updated = ?",
        [$apiKey, $countryMap[$countryIso], $ipTime, $ipTime, $ipTime]
    );
}

// Resources (URLs from Screenshot)
$resourcesData = [
    '/form/location', '/form/contact', '/list/tag/category', '/tags/tag/categories', 
    '/blog/explore/blog', '/tag/category/main', '/tags/category/blog', '/category/list/search', 
    '/tags/categories/posts', '/explore/list/list', '/wp-content/posts/tags', '/categories/category/search', 
    '/posts/tags/app', '/blog/main/list', '/categories/posts/app', '/main/tag/search', 
    '/tag/main/tags', '/main/app/app', '/tag/posts/blog', '/blog/search/list'
];
$resourceIds = [];
foreach ($resourcesData as $res) {
    // Some are 404/500 based on the screenshot
    $ins = $db->exec("INSERT INTO event_url (url, title, key, lastseen, updated) VALUES (?, ?, ?, NOW(), NOW()) RETURNING id", [$res, ucfirst(trim($res, '/')) ?: 'Home', $apiKey]);
    $resourceIds[] = $ins[0]['id'];
}

// UA Parsed
$uaSpecs = [
    ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0', 'Chrome', 'Windows'],
    ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1', 'Safari', 'Mac'],
    ['Mozilla/5.0 (Linux; Android 13; SM-S901B) Chrome/119.0', 'Chrome Mobile', 'Android'],
    ['Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)', 'Safari Mobile', 'iOS'],
    ['Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X)', 'Safari Mobile', 'iPadOS'],
    ['Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:109.0)', 'Firefox', 'GNU/Linux']
];
$uaIds = [];
foreach ($uaSpecs as $spec) {
    $uaRes = $db->exec(
        "INSERT INTO event_ua_parsed (ua, browser_name, os_name, key) VALUES (?, ?, ?, ?) RETURNING id",
        [$spec[0], $spec[1], $spec[2], $apiKey]
    );
    $uaIds[] = $uaRes[0]['id'];
}

// Users
$firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'Daniel', 'Olivia', 'James', 'Isabella', 'Ann', 'Abigail', 'Melinda', 'Mark', 'Dennis', 'Christopher', 'Jean', 'Tonya', 'Anne', 'Fred', 'Fernando', 'Steven', 'Travis', 'Jamie', 'Sarah'];
$lastNames = ['Doe', 'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Townsend', 'Petersen', 'Willis', 'Nichols', 'Perez', 'Lopez', 'Gray', 'Wolf', 'Jackson', 'Sims', 'Colon', 'Roberts', 'Jenkins', 'Salinas', 'Tanner', 'Thomas'];
$domains = ['goblog.info', 'coolbook.com', 'proguide.net', 'thestore.me', 'coolweb.io', 'gettech.club', 'easytech.net', 'topguide.me', 'mystore.online', 'topapp.io', 'myguide.info', 'theapp.info', 'yourshop.club', 'yoursite.biz', 'protech.club'];

$userIds = [];
$deviceIds = [];
$sessionIds = [];

for ($i = 0; $i < 310; $i++) {
    $fName = $firstNames[array_rand($firstNames)];
    $lName = $lastNames[array_rand($lastNames)];
    // Randomly assign usernames and emails matching the screenshots
    $usernameBase = strtolower($fName) . rand(1,99);
    if ($i % 3 == 0) {
        $email = "info@" . $domains[array_rand($domains)];
    } else {
        $email = $usernameBase . '@' . $domains[array_rand($domains)];
    }
    
    // UserID format from screenshot: a1a704adf10c
    $userId = "a1a" . bin2hex(random_bytes(4)) . dechex(rand(16, 255));
    
    $signupMins = rand(0, 48 * 60);
    $signupTime = date('Y-m-d H:i:s', strtotime("-$signupMins minutes"));
    
    // Random trust scores
    $score = rand(0, 100);
    if ($i % 4 == 0) $score = rand(0, 30); // Force some low scores (red flags)
    
    $isImportant = rand(0, 100) > 90;
    $fraud = rand(0, 100) > 80;

    $res = $db->exec(
        "INSERT INTO event_account (userid, fullname, key, is_important, fraud, score, created, lastseen, updated, total_visit) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1) RETURNING id",
        [$userId, "$fName $lName", $apiKey, $isImportant, $fraud, $score, $signupTime, $signupTime, $signupTime]
    );
    $uId = $res[0]['id'];
    $userIds[] = $uId;

    $uaId = $uaIds[array_rand($uaIds)];
    $db->exec(
        "INSERT INTO event_device (account_id, key, user_agent, updated, lastseen, created) VALUES (?, ?, ?, ?, ?, ?) RETURNING id",
        [$uId, $apiKey, $uaId, $signupTime, $signupTime, $signupTime]
    );
    $devIdRow = $db->exec("SELECT id FROM event_device WHERE account_id = ? AND key = ?", [$uId, $apiKey]);
    $deviceIds[$uId] = $devIdRow[0]['id'];

    $sessionId = rand(10000000, 99999999);
    $db->exec(
        "INSERT INTO event_session (id, key, account_id, lastseen, created, updated) VALUES (?, ?, ?, ?, ?, ?)",
        [$sessionId, $apiKey, $uId, $signupTime, $signupTime, $signupTime]
    );
    $sessionIds[$uId] = $sessionId;
}

// Fields (From Screenshot)
$fieldsData = [
    ['city', 'City'],
    ['country', 'Country'],
    ['preferred_contact_method', 'Preferred contact method']
];
$fieldIds = [];
foreach ($fieldsData as $fd) {
    $res = $db->exec("INSERT INTO event_field_audit (field_id, field_name, key, lastseen, updated) VALUES (?, ?, ?, NOW(), NOW()) RETURNING id", [$fd[0], $fd[1], $apiKey]);
    $fieldIds[] = $res[0]['id'];
}

echo "Generating activity timeline...\n";
$httpCodes = [200, 200, 200, 200, 200, 404, 500, 302, 403, 401];
for ($i = 0; $i < 5000; $i++) {
    $uId = $userIds[array_rand($userIds)];
    $ipId = $ips[array_rand($ips)];
    $resId = $resourceIds[array_rand($resourceIds)];
    $dId = $deviceIds[$uId];
    $sId = $sessionIds[$uId];
    
    // In screenshots, some URLs return 404 or 500
    if ($i % 30 == 0) $code = 404;
    elseif ($i % 50 == 0) $code = 500;
    else $code = 200;

    $method = rand(1, 4);

    // 80% of events in the last 24 hours
    if ($i < 4000) {
        $eventMins = rand(0, 24 * 60);
    } else {
        $eventMins = rand(24 * 60, 7 * 24 * 60);
    }
    $time = date('Y-m-d H:i:s', strtotime("-$eventMins minutes"));
    
    // In EndoGuard, Event Types map to integers. We use rand(1, 15) to hit things like Page View, Field Edit, Registration, Page Error
    $type = rand(1, 15);
    
    $eventRes = $db->exec(
        "INSERT INTO event (key, account, ip, url, device, session_id, time, type, http_code, http_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id",
        [$apiKey, $uId, $ipId, $resId, $dId, $sId, $time, $type, $code, $method]
    );
    $eventId = $eventRes[0]['id'];
    
    // If it's a Field Edit, record the audit trail
    if ($i % 2 == 0) {
        $fId = $fieldIds[array_rand($fieldIds)];
        $db->exec(
            "INSERT INTO event_field_audit_trail (account_id, key, field_id, event_id, old_value, new_value, time) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$uId, $apiKey, $fId, $eventId, 'Old Value', 'New Value', $time]
        );
    }
}

echo "Successfully seeded massive robust demo data for API ID: $apiKey\n";

