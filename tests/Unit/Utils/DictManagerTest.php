<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\DictManager;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeFilesystem;

/**
 * Unit tests for DictManager.
 *
 * Covered:
 * - loading existing dictionary file
 * - ignoring missing file
 * - ignoring file returning false
 *
 * Notes:
 * - filesystem access is isolated via FakeFilesystem helper
 */
final class DictManagerTest extends TestCase {
    private FakeFilesystem $fileSystem;

    protected function setUp(): void {
        parent::setUp();

        $this->fileSystem = new FakeFilesystem('dict_manager');

        $f3 = \Base::instance();
        $f3->clear('DICT_TEST_KEY');
        $f3->set('LOCALES', $this->fileSystem->getRoot() . '/');
        $f3->set('LANGUAGE', 'en');
    }

    protected function tearDown(): void {
        $this->fileSystem->cleanup();
        parent::tearDown();
    }

    public function testLoadExistingDictionaryFile(): void {
        $this->fileSystem->put(
            'en/Additional/test.php',
            <<<'PHP'
<?php
return [
    'DICT_TEST_KEY' => 'test-value',
];
PHP
        );

        DictManager::load('test');

        $value = \Base::instance()->get('DICT_TEST_KEY');

        $this->assertSame('test-value', $value);
    }

    public function testLoadIgnoresMissingFile(): void {
        DictManager::load('missing');

        $value = \Base::instance()->get('DICT_TEST_KEY');

        $this->assertNull($value);
    }

    public function testLoadIgnoresFileReturningFalse(): void {
        $this->fileSystem->put(
            'en/Additional/invalid.php',
            <<<'PHP'
<?php
return false;
PHP
        );

        DictManager::load('invalid');

        $value = \Base::instance()->get('DICT_TEST_KEY');

        $this->assertNull($value);
    }
}
