<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\ApiKeys;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\ApiKeys.
 *
 * Covered (unit-testable without refactor):
 * - ApiKeys::getCurrentOperatorApiKeyId() returns null when current operator is missing
 * - ApiKeys::getCurrentOperatorApiKeyString() returns null when current operator is missing
 * - ApiKeys::getCurrentOperatorEnrichmentKeyString() returns null when current operator is missing
 *
 * Not covered (unstable without refactor):
 * - ApiKeys::getOperatorApiKeys() (new Models inside; DB / storage required)
 * - ApiKeys::getCurrentOperatorApiKeyObject() positive branches:
 *   - depends on Routes::getCurrentRequestOperator() (static global)
 *   - depends on Models\ApiKeys / ApiKeyCoOwner (new Models inside)
 *   - "TEST_API_KEY_ID" branch depends on getKeyById() and storage
 *
 * @todo Refactor:
 * - extract CurrentOperatorProviderInterface (instead of static Routes::getCurrentRequestOperator())
 * - inject ApiKeysModelInterface / ApiKeyCoOwnerModelInterface (instead of new Models)
 * - avoid calling getKeyById() multiple times in TEST_API_KEY_ID branch (fetch once)
 */
final class ApiKeysTest extends TestCase {
    private \Base $f3;

    /** @var array<string, mixed> */
    private array $f3Backup = [];

    /** @var list<string> */
    private array $f3Keys = [
        'TEST_API_KEY_ID',
    ];

    protected function setUp(): void {
        parent::setUp();

        $this->f3 = \Base::instance();

        $this->backupF3();
        $this->clearF3();
    }

    protected function tearDown(): void {
        $this->restoreF3();

        parent::tearDown();
    }

    /**
     * @dataProvider nullWhenNoCurrentOperatorProvider
     */
    public function testReturnsNullWhenNoCurrentOperator(string $method): void {
        $this->f3->clear('TEST_API_KEY_ID');

        $actual = ApiKeys::$method();

        $this->assertNull($actual);
    }

    public static function nullWhenNoCurrentOperatorProvider(): array {
        return [
            'getCurrentOperatorApiKeyId' => [
                'method' => 'getCurrentOperatorApiKeyId',
            ],
            'getCurrentOperatorApiKeyString' => [
                'method' => 'getCurrentOperatorApiKeyString',
            ],
            'getCurrentOperatorEnrichmentKeyString' => [
                'method' => 'getCurrentOperatorEnrichmentKeyString',
            ],
        ];
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
