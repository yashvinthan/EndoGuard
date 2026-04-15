<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Sort;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Sort.
 *
 * Covered (unit-testable without refactor):
 * - Sort::cmpTimestamp() (numeric timestamp delta)
 * - Sort::cmpScore() (descending score comparison via spaceship)
 * - Sort::cmpRule() (validated desc, missing false-before-true, uid asc)
 *
 * Notes:
 * - tests assert comparator sign (-/0/+) rather than exact values for robustness
 * - cmpTimestamp returns a numeric difference (not spaceship), so we assert sign as well
 */
final class SortTest extends TestCase {
    /**
     * @dataProvider cmpTimestampProvider
     */
    public function testCmpTimestampReturnsExpectedSign(array $left, array $right, int $expectedSign): void {
        $result = Sort::cmpTimestamp($left, $right);

        $sign = $this->sign($result);

        $this->assertSame($expectedSign, $sign);
    }

    public static function cmpTimestampProvider(): array {
        return [
            'left smaller ts => negative' => [
                ['ts' => 10],
                ['ts' => 20],
                -1,
            ],
            'equal ts => zero' => [
                ['ts' => 10],
                ['ts' => 10],
                0,
            ],
            'left larger ts => positive' => [
                ['ts' => 30],
                ['ts' => 20],
                1,
            ],
        ];
    }

    /**
     * @dataProvider cmpScoreProvider
     */
    public function testCmpScoreReturnsExpectedSign(array $left, array $right, int $expectedSign): void {
        $result = Sort::cmpScore($left, $right);

        $sign = $this->sign($result);

        $this->assertSame($expectedSign, $sign);
    }

    public static function cmpScoreProvider(): array {
        return [
            'left higher score => negative (desc order)' => [
                ['score' => 100],
                ['score' => 50],
                -1,
            ],
            'equal score => zero' => [
                ['score' => 10],
                ['score' => 10],
                0,
            ],
            'left lower score => positive (desc order)' => [
                ['score' => 5],
                ['score' => 10],
                1,
            ],
        ];
    }

    public function testCmpRuleSortsByValidatedDescFirst(): void {
        $left = [
            'validated' => 0,
            'missing' => false,
            'uid' => 'A01',
        ];

        $right = [
            'validated' => 1,
            'missing' => false,
            'uid' => 'A01',
        ];

        $result = Sort::cmpRule($left, $right);

        $sign = $this->sign($result);

        $this->assertSame(1, $sign, 'validated=1 must come before validated=0');
    }

    public function testCmpRuleSortsByMissingFalseBeforeTrueWhenValidatedEqual(): void {
        $left = [
            'validated' => 1,
            'missing' => false,
            'uid' => 'A01',
        ];

        $right = [
            'validated' => 1,
            'missing' => true,
            'uid' => 'A01',
        ];

        $result = Sort::cmpRule($left, $right);

        $sign = $this->sign($result);

        $this->assertSame(-1, $sign, 'missing=false must come before missing=true');
    }

    public function testCmpRuleSortsByUidAscWhenValidatedAndMissingEqual(): void {
        $left = [
            'validated' => 1,
            'missing' => false,
            'uid' => 'A01',
        ];

        $right = [
            'validated' => 1,
            'missing' => false,
            'uid' => 'B01',
        ];

        $result = Sort::cmpRule($left, $right);

        $sign = $this->sign($result);

        $this->assertSame(-1, $sign, 'uid must be sorted ascending');
    }

    public function testCmpRuleReturnsZeroWhenAllKeysEqual(): void {
        $left = [
            'validated' => 1,
            'missing' => false,
            'uid' => 'A01',
        ];

        $right = [
            'validated' => 1,
            'missing' => false,
            'uid' => 'A01',
        ];

        $result = Sort::cmpRule($left, $right);

        $this->assertSame(0, $result);
    }

    private function sign(int $value): int {
        if ($value === 0) {
            return 0;
        }

        $result = ($value < 0) ? -1 : 1;
        return $result;
    }
}
