<?php
declare(strict_types=1);

chdir(__DIR__);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/libs/bcosca/fatfree-core/base.php';
    spl_autoload_register(function (string $className): void {
        $libs = [
            'Ruler\\' => '/libs/ruler/ruler/src/',
            'PHPMailer\\PHPMailer\\' => '/libs/phpmailer/phpmailer/src/',
            'EndoGuard\\' => '/app/',
        ];
        foreach ($libs as $namespace => $path) {
            if (str_starts_with($className, $namespace)) {
                $file = __DIR__ . $path . str_replace([$namespace, '\\'], ['', '/'], $className) . '.php';
                if (file_exists($file)) {
                    require $file;
                }
                break;
            }
        }
    });
}

$f3 = \Base::instance();
$f3->config('config/config.ini');

use EndoGuard\Utils\Database;

putenv('DATABASE_URL=postgres://endoguard:endoguard@db:5432/endoguard');
Database::initConnect(false);
$db = Database::getDb();

echo "--- Injecting ADVANCED Demonstration Data (v19) ---\n";

// Get valid API Key ID
$keyResult = $db->exec("SELECT id FROM dshb_api LIMIT 1");
if (!$keyResult) {
    echo "!!! No API Key found in dshb_api table !!!\n";
    exit(1);
}
$apiKeyId = $keyResult[0]['id'];
echo "Using API Key ID: $apiKeyId\n";

// Reset data in correct dependency order
$db->exec("TRUNCATE event CASCADE;");
$db->exec("TRUNCATE event_logbook CASCADE;");
$db->exec("TRUNCATE event_account CASCADE;");
$db->exec("TRUNCATE event_device CASCADE;");
$db->exec("TRUNCATE event_ua_parsed CASCADE;");
$db->exec("TRUNCATE event_ip CASCADE;");
$db->exec("TRUNCATE event_isp CASCADE;");
$db->exec("TRUNCATE event_url CASCADE;");
$db->exec("TRUNCATE event_session CASCADE;");
$db->exec("TRUNCATE queue_account_operation CASCADE;");
$db->exec("TRUNCATE queue_new_events_cursor CASCADE;");

$now = time();
$date = date('Y-m-d H:i:s');
$accounts = [1, 2, 3, 4];

// 1. Create Base Infrastructure
$db->exec("INSERT INTO event_isp (id, key, asn, name, created, updated) VALUES (1, ?, 1234, 'Cyber Link', ?, ?)", [$apiKeyId, $date, $date]);
$db->exec("INSERT INTO event_isp (id, key, asn, name, created, updated) VALUES (2, ?, 5678, 'Neon ISP', ?, ?)", [$apiKeyId, $date, $date]);
$db->exec("INSERT INTO event_ua_parsed (id, key, device, browser_name, ua, created) VALUES (1, ?, 'Desktop', 'Chrome', 'Mozilla/5.0...', ?)", [$apiKeyId, $date]);

// 2. Create Base Entities
foreach ($accounts as $uid) {
    $db->exec("INSERT INTO event_account (id, key, userid, created, lastseen, lastip, updated) VALUES (?, ?, ?, ?, ?, '192.168.1.1', ?)", [
        $uid, $apiKeyId, "demo_user_$uid", $date, $date, $date
    ]);
    
    // Sessions
    $db->exec("INSERT INTO event_session (id, key, account_id, created, updated, lastseen) VALUES (?, ?, ?, ?, ?, ?)", [
        100 + $uid, $apiKeyId, $uid, $date, $date, $date
    ]);
}

// IPs
$db->exec("INSERT INTO event_ip (id, key, ip, country, isp, updated, created, lastseen) VALUES (1, ?, '192.168.1.1', 71, 1, ?, ?, ?)", [$apiKeyId, $date, $date, $date]);
$db->exec("INSERT INTO event_ip (id, key, ip, country, isp, updated, created, lastseen) VALUES (2, ?, '10.0.0.1', 77, 1, ?, ?, ?)", [$apiKeyId, $date, $date, $date]);
$db->exec("INSERT INTO event_ip (id, key, ip, country, isp, updated, created, lastseen) VALUES (3, ?, '172.16.0.1', 113, 2, ?, ?, ?)", [$apiKeyId, $date, $date, $date]);
$db->exec("INSERT INTO event_ip (id, key, ip, country, isp, updated, created, lastseen) VALUES (4, ?, '8.8.8.8', 215, 2, ?, ?, ?)", [$apiKeyId, $date, $date, $date]);
$db->exec("INSERT INTO event_ip (id, key, ip, country, isp, updated, created, lastseen) VALUES (5, ?, '1.1.1.1', 215, 2, ?, ?, ?)", [$apiKeyId, $date, $date, $date]);

// URLs
$db->exec("INSERT INTO event_url (id, key, url, updated, created) VALUES (1, ?, '/demo/page/1', ?, ?)", [$apiKeyId, $date, $date]);
$db->exec("INSERT INTO event_url (id, key, url, updated, created) VALUES (2, ?, '/demo/page/2', ?, ?)", [$apiKeyId, $date, $date]);
$db->exec("INSERT INTO event_url (id, key, url, updated, created) VALUES (3, ?, '/demo/page/3', ?, ?)", [$apiKeyId, $date, $date]);

// Devices
$db->exec("INSERT INTO event_device (id, key, account_id, user_agent, updated, created, lastseen) VALUES (1, ?, 1, 1, ?, ?, ?)", [$apiKeyId, $date, $date, $date]);

// 3. Shared IP Events (User 1, 2, 3 all on IP 1)
$eventId = 1;
foreach ([1, 2, 3] as $uid) {
    for ($i = 0; $i < 10; $i++) {
        $db->exec("INSERT INTO event (id, key, type, account, ip, url, device, session_id, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
            $eventId, $apiKeyId, 1, $uid, 1, rand(1,3), 1, 100+$uid, date('Y-m-d H:i:s', $now - rand(0, 600))
        ]);
        
        // Logbook entry for dashboard readiness
        $stmt = $db->prepare('INSERT INTO event_logbook (key, ip, event, endpoint, raw, error_type, started, ended) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([
            $apiKeyId,
            '192.168.1.1',
            $eventId,
            '/sensor/', 
            json_encode(['test' => true]),
            0, 
        ]);
        $eventId++;
    }
}

// 4. Multiple IP Events (User 4 on IPs 1, 2, 3, 4, 5)
foreach ([1, 2, 3, 4, 5] as $ipId) {
    $db->exec("INSERT INTO event (id, key, type, account, ip, url, device, session_id, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", [
        $eventId, $apiKeyId, 3, 4, $ipId, 1, 1, 104, date('Y-m-d H:i:s', $now - rand(0, 600))
    ]);

    $stmt = $db->prepare('INSERT INTO event_logbook (key, ip, event, endpoint, raw, error_type, started, ended) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())');
    $stmt->execute([
        $apiKeyId,
        '10.0.0.1',
        $eventId,
        '/sensor/', 
        json_encode(['test' => true]),
        0, 
    ]);
    $eventId++;
}

// 5. Trace Log for verification
$db->exec("INSERT INTO event_logbook (key, event, started, ended, error_type, endpoint) VALUES (?, 1, ?, ?, 0, '/sensor/')", [$apiKeyId, $date, $date]);

// 6. Queue aggregation tasks
echo "Queueing aggregation tasks...\n";
foreach ($accounts as $accountId) {
    $db->exec(
        "INSERT INTO queue_account_operation (event_account, action, key, status, updated) 
         VALUES (?, 'calculate_risk_score', ?, 'waiting', ?)",
        [$accountId, $apiKeyId, $date]
    );
}

// Ensure the operator last_event_time is recent enough to bypass the /api redirect!
$db->exec("UPDATE dshb_operators SET last_event_time = NOW() WHERE id = 1");

echo "Successfully injected advanced demo data (v19).\n";
