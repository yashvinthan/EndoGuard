<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Timezones;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Timezones.
 *
 * Covered (unit-testable without refactor):
 * - Timezones::getTimezone() (valid/invalid fallback behavior)
 * - Timezones::getUtcTimezone() (returns UTC)
 * - Timezones::localizeTimestamp() (timezone conversion with fixed input)
 * - Timezones::addOffset() (adds seconds; preserves millisecond suffix when requested)
 *
 * Partially covered (weak assertions; time-dependent):
 * - range helpers (only invariants: format, ordering, and day-boundary properties)
 *
 * Not covered (recommended to refactor first):
 * - methods depending on Routes::getCurrentRequestOperator() (active-operator timezone)
 * - timezonesList() (depends on Variables::getAvailableTimezones() + now)
 *
 * @todo Refactor:
 * - extract ClockInterface (nowTimestamp(): int) for deterministic ranges
 * - extract ActiveOperatorProviderInterface for localize*ForActiveOperator methods
 * - extract TimezonesCatalogInterface for timezonesList()
 */
final class TimeZonesTest extends TestCase {
    public function testGetUtcTimezoneReturnsUtc(): void {
        $timezone = Timezones::getUtcTimezone();

        $this->assertSame('UTC', $timezone->getName());
    }

    public function testGetTimezoneReturnsDefaultWhenInvalid(): void {
        $timezone = Timezones::getTimezone('Not/A_Timezone', 'UTC');

        $this->assertSame('UTC', $timezone->getName());
    }

    public function testGetTimezoneReturnsProvidedWhenValid(): void {
        $timezone = Timezones::getTimezone('Europe/Kyiv', 'UTC');

        $this->assertSame('Europe/Kyiv', $timezone->getName());
    }

    public function testLocalizeTimestampConvertsFromUtcToFixedTimezoneWithoutMilliseconds(): void {
        $utc = new \DateTimeZone('UTC');
        $until = new \DateTimeZone('Europe/Kyiv');

        $input = '2020-01-01 00:00:00';
        $result = Timezones::localizeTimestamp($input, $utc, $until, false);

        // We don't hardcode offset (DST rules vary by date), we compute expected via DateTime.
        $dtObj = \DateTime::createFromFormat(Timezones::FORMAT, $input, $utc);
        $dtObj->setTimezone($until);

        $expected = $dtObj->format(Timezones::FORMAT);

        $this->assertSame($expected, $result);
    }

    public function testLocalizeTimestampKeepsMicrosecondsWhenRequested(): void {
        $utc = new \DateTimeZone('UTC');
        $until = new \DateTimeZone('UTC');

        $input = '2020-01-01 00:00:00.123456';
        $result = Timezones::localizeTimestamp($input, $utc, $until, true);

        $this->assertSame($input, $result);
    }

    public function testAddOffsetAddsSecondsWithoutMilliseconds(): void {
        $input = '2020-01-01 00:00:00';
        $offset = 60;

        $result = Timezones::addOffset($input, $offset, false);

        $this->assertSame('2020-01-01 00:01:00', $result);
    }

    public function testAddOffsetPreservesMillisecondSuffixWhenPresent(): void {
        $input = '2020-01-01 00:00:00.123456';
        $offset = 1;

        $result = Timezones::addOffset($input, $offset, true);

        $this->assertSame('2020-01-01 00:00:01.123456', $result);
    }

    public function testAddOffsetFallsBackWhenMillisecondsRequestedButMissingInInput(): void {
        $input = '2020-01-01 00:00:00';
        $offset = 1;

        $result = Timezones::addOffset($input, $offset, true);

        // When input has no ".", function disables millisecond mode and returns plain FORMAT.
        $this->assertSame('2020-01-01 00:00:01', $result);
    }

    public function testCurDayRangeHasValidFormatAndOrdering(): void {
        $range = Timezones::getCurDayRange(0);

        $startDate = (string) $range['startDate'];
        $endDate = (string) $range['endDate'];

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $startDate);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $endDate);

        $this->assertStringEndsWith('00:00:00', $startDate);

        $timezoneName = date_default_timezone_get();
        $timezone = new \DateTimeZone($timezoneName);

        $start = \DateTimeImmutable::createFromFormat(Timezones::FORMAT, $startDate, $timezone);
        $end = \DateTimeImmutable::createFromFormat(Timezones::FORMAT, $endDate, $timezone);

        $this->assertInstanceOf(\DateTimeImmutable::class, $start);
        $this->assertInstanceOf(\DateTimeImmutable::class, $end);

        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();

        $this->assertLessThanOrEqual($endTs, $startTs);
    }
}
