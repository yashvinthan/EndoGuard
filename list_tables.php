<?php
require 'app/Utils/Database.php';
EndoGuard\Utils\Database::initConnect(false);
$db = EndoGuard\Utils\Database::getDb();
$tables = $db->exec("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
foreach ($tables as $t) {
    echo $t['table_name'] . PHP_EOL;
}
