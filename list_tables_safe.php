<?php
declare(strict_types=1);

chdir(__DIR__);
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/libs/bcosca/fatfree-core/base.php';
    spl_autoload_register(function (string $className): void {
        $libs = [
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

echo "--- LISTING TABLES ---\n";
$tables = $db->exec("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
foreach ($tables as $t) {
    echo $t['table_name'] . "\n";
}
