<?php
declare(strict_types=1);
chdir(__DIR__);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/libs/bcosca/fatfree-core/base.php';
    spl_autoload_register(function (string $className): void {
        $libs = ['EndoGuard\\' => '/app/'];
        foreach ($libs as $namespace => $path) {
            if (str_starts_with($className, $namespace)) {
                $file = __DIR__ . $path . str_replace([$namespace, '\\'], ['', '/'], $className) . '.php';
                if (file_exists($file)) require $file;
                break;
            }
        }
    });
}
use EndoGuard\Utils\Database;
putenv('DATABASE_URL=postgres://endoguard:endoguard@db:5432/endoguard');
Database::initConnect(false);
$db = Database::getDb();

$table = $argv[1] ?? 'event_account';
echo "--- Inspecting Table: $table ---\n";
try {
    $cols = $db->exec("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?", [$table]);
    echo json_encode($cols, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
