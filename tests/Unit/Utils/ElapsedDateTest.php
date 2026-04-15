<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\ElapsedDate;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\ElapsedDate.
 *
 * Covered (unit-testable without refactor):
 * - short(): formats non-null input as "d/m/Y H:i:s"; returns null for null
 * - date(): formats non-null input as "d/m/Y"; returns null for null
 *
 * Partially covered (weak assertions; time-dependent):
 * - long(): uses time() internally, so only invariant properties are asserted:
 *   - contains "ago."
 *   - contains "and"
 *   - does not return empty string
 *   - includes at least one time unit token for sufficiently old timestamps
 *
 * Not covered (recommended to refactor first):
 * - exact unit breakdown in long() (years/weeks/days/hours/minutes/seconds)
 *
 * @todo Refactor:
 * - extract ClockInterface (nowTimestamp(): int) and pass it to long()
 * - remove float arithmetic in long() (use integer divisions and remainders)
 * - define exact output grammar (Oxford comma, "and" placement, pluralization rules)
 */
final class ElapsedDateTest extends TestCase {
    public function testShortReturnsNullForNull(): void {
        $timestampStr = null;

        $expected = null;
        $actual = ElapsedDate::short($timestampStr);

        $this->assertSame($expected, $actual);
    }

    public function testDateReturnsNullForNull(): void {
        $timestampStr = null;

        $expected = null;
        $actual = ElapsedDate::date($timestampStr);

        $this->assertSame($expected, $actual);
    }

    public function testShortFormatsDatetime(): void {
        $timestampStr = '2020-01-02 03:04:05';

        $expected = '02/01/2020 03:04:05';
        $actual = ElapsedDate::short($timestampStr);

        $this->assertSame($expected, $actual);
    }

    public function testDateFormatsDateOnly(): void {
        $timestampStr = '2020-01-02 03:04:05';

        $expected = '02/01/2020';
        $actual = ElapsedDate::date($timestampStr);

        $this->assertSame($expected, $actual);
    }

    public function testLongContainsAgoAndIsNotEmptyForOldEnoughTimestamp(): void {
        $secondsInTwoDays = 2 * 24 * 60 * 60;
        $timestamp = time() - $secondsInTwoDays;

        $timestampStr = date('Y-m-d H:i:s', $timestamp);

        $actual = ElapsedDate::long($timestampStr);

        $this->assertNotSame('', $actual);
        $this->assertTrue(str_contains($actual, 'ago.'));
    }

    public function testLongIncludesAtLeastOneTimeUnitForOldTimestamp(): void {
        $secondsInTwoDays = 2 * 24 * 60 * 60;
        $timestamp = time() - $secondsInTwoDays;

        $timestampStr = date('Y-m-d H:i:s', $timestamp);

        $actual = ElapsedDate::long($timestampStr);

        $hasAnyUnit =
            str_contains($actual, ' year') || str_contains($actual, ' years') ||
            str_contains($actual, ' week') || str_contains($actual, ' weeks') ||
            str_contains($actual, ' day') || str_contains($actual, ' days') ||
            str_contains($actual, ' hour') || str_contains($actual, ' hours') ||
            str_contains($actual, ' minute') || str_contains($actual, ' minutes');

        $this->assertTrue($hasAnyUnit);
    }

    public function testLongIncludesMinuteTokenWhenAtLeastOneMinuteOld(): void {
        $seconds = 61;
        $timestamp = time() - $seconds;

        $timestampStr = date('Y-m-d H:i:s', $timestamp);

        $actual = ElapsedDate::long($timestampStr);

        $this->assertTrue(str_contains($actual, 'ago.'));

        $hasMinute =
            str_contains($actual, ' minute') ||
            str_contains($actual, ' minutes');

        $this->assertTrue($hasMinute);
    }
}
