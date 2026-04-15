<?php
require_once 'app/Utils/Database.php';
require_once 'app/Models/BaseSql.php';
require_once 'app/Models/Queue.php';

use EndoGuard\Utils\Database;

Database::initConnect(false);
$db = Database::getDb();

echo "--- Debugging Database ---\n";

// Check API Key
$key = $db->exec("SELECT id, creator, code FROM dshb_api WHERE code = 'test-key-12345'")[0] ?? null;
if ($key) {
    echo "Found API Key: ID=" . $key['id'] . ", Creator=" . $key['creator'] . ", Code=" . $key['code'] . "\n";
} else {
    echo "!!! API Key 'test-key-12345' NOT found !!!\n";
    $allKeys = $db->exec("SELECT id, code FROM dshb_api LIMIT 10");
    echo "Existing keys: " . json_encode($allKeys) . "\n";
}

// Check Events
$eventCount = $db->exec("SELECT COUNT(*) FROM event")[0]['count'] ?? 0;
echo "Total events: $eventCount\n";

// Check Queue
$queueCount = $db->exec("SELECT COUNT(*) FROM queue_account_operation")[0]['count'] ?? 0;
echo "Total queue items: $queueCount\n";

$pendingQueue = $db->exec("SELECT * FROM queue_account_operation WHERE status = 'waiting' LIMIT 5");
echo "Pending queue items (first 5): " . json_encode($pendingQueue) . "\n";

// Check Action Type Enum
try {
    $enums = $db->exec("SELECT enum_range(NULL::queue_account_operation_action)");
    echo "Valid Actions: " . json_encode($enums) . "\n";
} catch (\Exception $e) {
    echo "Error fetching enums: " . $e->getMessage() . "\n";
}
