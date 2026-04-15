<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use Tests\Support\Utils\Assets\PreparedEqualsRule;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Assets\Rule.
 *
 * Covered (unit-testable without refactor):
 * - execute():
 *   - builds \Ruler\Context from params (via prepareParams())
 *   - uses defineCondition() as RuleBuilder::create() input
 *   - returns evaluation result
 * - updateParams():
 *   - updates params used by subsequent execute()
 *
 * Not covered (recommended to refactor first):
 * - uid generation details (end(explode(...)) may raise a PHP 8+ warning)
 *
 * @todo Refactor:
 * - fix uid generation (avoid end(explode(...)) warning; use basename(str_replace('\\', '/', static::class)))
 * - type properties (rb/context/params/condition/uid) explicitly
 * - expose context building for testing without relying on Ruler internals
 */
final class RuleTest extends TestCase {
    private $prevErrorHandler = null;

    protected function setUp(): void {
        parent::setUp();

        // Suppress PHP 8+ warning from end(explode(...)) in Rule::__construct().
        $this->prevErrorHandler = set_error_handler(
            function (int $errno, string $errstr): bool {
                $isReferenceWarning = str_contains($errstr, 'Only variables should be passed by reference');
                if ($isReferenceWarning) {
                    return true;
                }

                return false;
            }
        );
    }

    protected function tearDown(): void {
        if ($this->prevErrorHandler !== null) {
            restore_error_handler();
            $this->prevErrorHandler = null;
        }

        parent::tearDown();
    }

    public function testExecuteReturnsTrueWhenConditionMatchesPreparedContext(): void {
        $expectedPrepared = 10;

        $params = [
            'raw' => $expectedPrepared,
        ];

        $rule = new PreparedEqualsRule($params, $expectedPrepared);

        $expected = true;
        $actual = $rule->execute();

        $this->assertSame($expected, $actual);
    }

    public function testExecuteReturnsFalseWhenConditionDoesNotMatch(): void {
        $params = [
            'raw' => 10,
        ];

        $expectedPrepared = 11;

        $rule = new PreparedEqualsRule($params, $expectedPrepared);

        $expected = false;
        $actual = $rule->execute();

        $this->assertSame($expected, $actual);
    }

    public function testUpdateParamsAffectsNextExecute(): void {
        $rule = new PreparedEqualsRule(['raw' => 1], 2);

        $expectedFirst = false;
        $actualFirst = $rule->execute();
        $this->assertSame($expectedFirst, $actualFirst);

        $rule->updateParams(['raw' => 2]);

        $expectedSecond = true;
        $actualSecond = $rule->execute();
        $this->assertSame($expectedSecond, $actualSecond);
    }
}
