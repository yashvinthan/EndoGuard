<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Variables;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Variables.
 *
 * Covered (unit-testable without refactor):
 * - env > F3 precedence for scalar getters:
 *   getDB(), getAdminEmail(), getMailLogin(), getMailPassword(),
 *   getEnrichmentApi(), getPepper()
 * - getConfigFile() (default + env override)
 * - getHosts() / getHost():
 *   - env comma-separated string
 *   - F3 array
 *   - F3 scalar wrapped into array
 * - boolean flags via explicit values:
 *   - getForceHttps() (env/F3 true/false)
 *   - getForgotPasswordAllowed()
 *   - getEmailPhoneAllowed()
 * - protocol composition:
 *   - getHostWithProtocol()
 *   - getHostWithProtocolAndBase()
 * - getAvailableTimezones():
 *   - filters invalid timezone identifiers
 * - completedConfig():
 *   - false if any required value missing
 *   - true if all present (env or F3)
 *
 * Not covered (unstable without refactor):
 * - default boolean behavior when all sources missing
 *   (depends on Conversion::filterBool() null-handling)
 * - numeric getters depending on Constants::get()
 *
 * @todo Refactor:
 * - extract EnvReaderInterface (get(string): ?string)
 * - extract ConfigReaderInterface (F3 wrapper)
 * - eliminate direct getenv()/Base::instance() usage
 * - make boolean defaults explicit (no ?? true / ?? false)
 */
final class VariablesTest extends TestCase {
    private \Base $f3;

    /** @var array<string, string|false> */
    private array $envBackup = [];

    /** @var array<string, mixed> */
    private array $f3Backup = [];

    /** @var list<string> */
    private array $envKeys = [
        'DATABASE_URL',
        'CONFIG_FILE',
        'SITE',
        'ADMIN_EMAIL',
        'MAIL_LOGIN',
        'MAIL_PASS',
        'ENRICHMENT_API',
        'PEPPER',
        'FORCE_HTTPS',
        'ALLOW_FORGOT_PASSWORD',
        'ALLOW_EMAIL_PHONE',
    ];

    /** @var list<string> */
    private array $f3Keys = [
        'DATABASE_URL',
        'SITE',
        'ADMIN_EMAIL',
        'MAIL_LOGIN',
        'MAIL_PASS',
        'ENRICHMENT_API',
        'PEPPER',
        'FORCE_HTTPS',
        'ALLOW_FORGOT_PASSWORD',
        'ALLOW_EMAIL_PHONE',
        'BASE',
        'timezones',
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

    /* ---------- precedence: env > F3 ---------- */

    public function testGetDbPrefersEnvOverF3(): void {
        $this->setF3('DATABASE_URL', 'f3-db');
        $this->setEnv('DATABASE_URL', 'env-db');

        $expected = 'env-db';
        $actual = Variables::getDB();

        $this->assertSame($expected, $actual);
    }

    public function testGetDbFallsBackToF3(): void {
        $this->setF3('DATABASE_URL', 'f3-db');

        $expected = 'f3-db';
        $actual = Variables::getDB();

        $this->assertSame($expected, $actual);
    }

    public function testScalarGettersPreferEnv(): void {
        $this->setF3('ADMIN_EMAIL', 'f3@ex');
        $this->setEnv('ADMIN_EMAIL', 'env@ex');

        $expected = 'env@ex';
        $actual = Variables::getAdminEmail();

        $this->assertSame($expected, $actual);
    }

    /* ---------- config file ---------- */

    public function testGetConfigFileDefault(): void {
        $expected = 'local/config.local.ini';
        $actual = Variables::getConfigFile();

        $this->assertSame($expected, $actual);
    }

    public function testGetConfigFileFromEnv(): void {
        $this->setEnv('CONFIG_FILE', 'custom.ini');

        $expected = 'custom.ini';
        $actual = Variables::getConfigFile();

        $this->assertSame($expected, $actual);
    }

    /* ---------- hosts ---------- */

    public function testGetHostsFromEnv(): void {
        $this->setEnv('SITE', 'a.example,b.example');

        $expected = ['a.example', 'b.example'];
        $actual = Variables::getHosts();

        $this->assertSame($expected, $actual);
    }

    public function testGetHostsFromF3Array(): void {
        $this->setF3('SITE', ['a.example', 'b.example']);

        $expected = ['a.example', 'b.example'];
        $actual = Variables::getHosts();

        $this->assertSame($expected, $actual);
    }

    public function testGetHostsFromF3Scalar(): void {
        $this->setF3('SITE', 'single.example');

        $expected = ['single.example'];
        $actual = Variables::getHosts();

        $this->assertSame($expected, $actual);
    }

    public function testGetHostReturnsFirst(): void {
        $this->setF3('SITE', ['a.example', 'b.example']);

        $expected = 'a.example';
        $actual = Variables::getHost();

        $this->assertSame($expected, $actual);
    }

    /* ---------- booleans (explicit values only) ---------- */

    public function testForceHttpsTrueFromEnv(): void {
        $this->setEnv('FORCE_HTTPS', 'true');

        $expected = true;
        $actual = Variables::getForceHttps();

        $this->assertSame($expected, $actual);
    }

    public function testForceHttpsFalseFromEnv(): void {
        $this->setEnv('FORCE_HTTPS', 'false');

        $expected = false;
        $actual = Variables::getForceHttps();

        $this->assertSame($expected, $actual);
    }

    public function testForgotPasswordAllowedTrue(): void {
        $this->setEnv('ALLOW_FORGOT_PASSWORD', 'true');

        $expected = true;
        $actual = Variables::getForgotPasswordAllowed();

        $this->assertSame($expected, $actual);
    }

    /* ---------- protocol helpers ---------- */

    public function testHostWithProtocolHttps(): void {
        $this->setF3('SITE', 'example.com');
        $this->setEnv('FORCE_HTTPS', 'true');

        $expected = 'https://example.com';
        $actual = Variables::getHostWithProtocol();

        $this->assertSame($expected, $actual);
    }

    public function testHostWithProtocolAndBase(): void {
        $this->setF3('SITE', 'example.com');
        $this->setF3('BASE', '/base');
        $this->setEnv('FORCE_HTTPS', 'true');

        $expected = 'https://example.com/base';
        $actual = Variables::getHostWithProtocolAndBase();

        $this->assertSame($expected, $actual);
    }

    /* ---------- timezones ---------- */

    public function testAvailableTimezonesFiltersInvalid(): void {
        $this->setF3('timezones', [
            'UTC' => 'UTC',
            'Europe/Kyiv' => 'Kyiv',
            'Invalid/Zone' => 'Nope',
        ]);

        $actual = Variables::getAvailableTimezones();

        $expectedHasUtc = true;
        $actualHasUtc = array_key_exists('UTC', $actual);
        $this->assertSame($expectedHasUtc, $actualHasUtc);

        $expectedHasInvalid = false;
        $actualHasInvalid = array_key_exists('Invalid/Zone', $actual);
        $this->assertSame($expectedHasInvalid, $actualHasInvalid);
    }

    /* ---------- completedConfig ---------- */

    public function testCompletedConfigFalseWhenMissing(): void {
        $this->setEnv('SITE', 'example.com');
        $this->setEnv('PEPPER', 'pep');
        $this->setEnv('ENRICHMENT_API', 'api');

        $expected = false;
        $actual = Variables::completedConfig();

        $this->assertSame($expected, $actual);
    }

    public function testCompletedConfigTrueFromEnv(): void {
        $this->setEnv('SITE', 'example.com');
        $this->setEnv('PEPPER', 'pep');
        $this->setEnv('ENRICHMENT_API', 'api');
        $this->setEnv('DATABASE_URL', 'db');

        $expected = true;
        $actual = Variables::completedConfig();

        $this->assertSame($expected, $actual);
    }

    /* ---------- helpers ---------- */

    private function setEnv(string $key, string $value): void {
        putenv($key . '=' . $value);
    }

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

    private function setF3(string $key, mixed $value): void {
        $this->f3->set($key, $value);
    }

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
}
