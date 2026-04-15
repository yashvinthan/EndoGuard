<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Database;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for EndoGuard\Utils\Database.
 *
 * Covered (unit-testable without refactor):
 * - initConnect():
 *   - returns true when APP_DATABASE already set (no connection attempt)
 *   - returns false when no DSN provided by Variables::getDB() (env+F3 empty)
 * - getDbConnect() (private) via Reflection:
 *   - throws InvalidArgumentException on invalid DSN format
 *
 * Not covered (recommended to refactor first):
 * - real connection establishment (new \DB\SQL) and session wiring (new \DB\SQL\Session)
 * - error handling branch calling $f3->error(503) (framework-dependent side effect)
 *
 * @todo Refactor:
 * - extract ConfigInterface (wrap Base::instance()->get/set/clear)
 * - extract VariablesReaderInterface (getDatabaseUrl(): ?string)
 * - extract DbConnectorInterface (connect(string $dsn): \DB\SQL)
 * - extract SessionFactoryInterface (create(\DB\SQL $db): void)
 * - make getDbConnect() non-private and test via pure input/output contract (no Reflection)
 */
final class DatabaseTest extends TestCase {
    private \Base $f3;

    /** @var array<string, mixed> */
    private array $f3Backup = [];

    /** @var array<string, string|false> */
    private array $envBackup = [];

    /** @var list<string> */
    private array $f3Keys = [
        'APP_DATABASE',
        'DATABASE_URL',
    ];

    /** @var list<string> */
    private array $envKeys = [
        'DATABASE_URL',
    ];

    protected function setUp(): void {
        parent::setUp();

        $this->f3 = \Base::instance();

        $this->backupEnv();
        $this->clearEnv();

        $this->backupF3();
        $this->clearF3();
    }

    protected function tearDown(): void {
        $this->restoreEnv();
        $this->restoreF3();

        parent::tearDown();
    }

    public function testInitConnectReturnsTrueWhenDbAlreadySet(): void {
        $fakeDb = $this->makeDbSqlWithoutConstructor();
        Database::setDb($fakeDb);

        $keepSession = false;
        $actual = Database::initConnect($keepSession);

        $expected = true;
        $this->assertSame($expected, $actual);
    }

    public function testInitConnectReturnsFalseWhenNoDsnProvided(): void {
        // Ensure Variables::getDB() returns null:
        // - env DATABASE_URL is cleared in setUp()
        // - F3 DATABASE_URL is cleared in setUp()

        $keepSession = false;
        $actual = Database::initConnect($keepSession);

        $expected = false;
        $this->assertSame($expected, $actual);
    }

    public function testGetDbConnectThrowsOnInvalidDsnFormat(): void {
        $url = 'not-a-dsn';

        $method = $this->getPrivateStaticMethod(Database::class, 'getDbConnect');

        $expectedException = \InvalidArgumentException::class;
        $this->expectException($expectedException);

        $method->invoke(null, $url);
    }

    public function testGetDbConnectThrowsOnMissingParts(): void {
        // parse_url() returns array, but required parts are missing.
        // Example: scheme present, host missing, user/pass missing, path missing.
        $url = 'pgsql://';

        $method = $this->getPrivateStaticMethod(Database::class, 'getDbConnect');

        $expectedException = \InvalidArgumentException::class;
        $this->expectException($expectedException);

        $method->invoke(null, $url);
    }

    /* ================= helpers: env ================= */

    private function backupEnv(): void {
        foreach ($this->envKeys as $key) {
            $this->envBackup[$key] = getenv($key);
        }
    }

    private function clearEnv(): void {
        foreach ($this->envKeys as $key) {
            putenv($key);
        }
    }

    private function restoreEnv(): void {
        foreach ($this->envBackup as $key => $value) {
            if ($value === false) {
                putenv($key);
                continue;
            }

            putenv($key . '=' . $value);
        }
    }

    /* ================= helpers: F3 ================= */

    private function backupF3(): void {
        foreach ($this->f3Keys as $key) {
            if ($this->f3->exists($key)) {
                $this->f3Backup[$key] = $this->f3->get($key);
            }
        }
    }

    private function clearF3(): void {
        foreach ($this->f3Keys as $key) {
            $this->f3->clear($key);
        }
    }

    private function restoreF3(): void {
        foreach ($this->f3Keys as $key) {
            $this->f3->clear($key);
        }

        foreach ($this->f3Backup as $key => $value) {
            $this->f3->set($key, $value);
        }
    }

    /* ================= helpers: reflection / fakes ================= */

    private function makeDbSqlWithoutConstructor(): \DB\SQL {
        $class = new ReflectionClass(\DB\SQL::class);
        $instance = $class->newInstanceWithoutConstructor();

        /** @var \DB\SQL $database */
        $database = $instance;
        return $database;
    }

    private function getPrivateStaticMethod(string $className, string $methodName): ReflectionMethod {
        $class = new ReflectionClass($className);

        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
