<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\DateRange;
use Base;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\DateRange.
 *
 * Covered (unit-testable without refactor):
 * - DateRange::inIntervalTillNow()
 * - DateRange::getDatesRangeByGivenDates()
 * - DateRange::getLatestNDatesRangeFromRequest() (format + rough boundaries)
 * - DateRange::getResolutionFromRequest() (via F3 REQUEST)
 * - DateRange::getDatesRangeFromRequest() (via F3 REQUEST/SESSION)
 *
 * Not covered (recommended to refactor first):
 * - DateRange::isQueueTimeouted()
 *
 * @todo Refactor:
 * - inject a Clock (now()) and a Config/Constants provider (queue timeout seconds),
 *   then isQueueTimeouted() becomes deterministic and properly unit-testable.
 */
final class DateRangeTest extends TestCase {
    private Base $f3;

    protected function setUp(): void {
        parent::setUp();

        $f3 = Base::instance();
        $this->f3 = $f3;

        $this->f3->clear('REQUEST');
        $this->f3->clear('SESSION');
        $this->f3->clear('EXTRA_SECONDS_IN_DAY');
        $this->f3->clear('EXTRA_CHART_RESOLUTION');
    }

    /**
     * @dataProvider inIntervalTillNowProvider
     */
    public function testInIntervalTillNow(?string $time, int $interval, ?bool $expected): void {
        $result = DateRange::inIntervalTillNow($time, $interval);

        $this->assertSame($expected, $result);
    }

    public static function inIntervalTillNowProvider(): array {
        $now = time();

        return [
            'null time -> null' => [null, 60, null],
            'within interval' => [gmdate('Y-m-d H:i:s', $now - 10), 60, true],
            'outside interval' => [gmdate('Y-m-d H:i:s', $now - 120), 60, false],
            'future still counts by abs diff' => [gmdate('Y-m-d H:i:s', $now + 30), 60, true],
            'zero interval always false for non-null time' => [gmdate('Y-m-d H:i:s', $now - 1), 0, false],
        ];
    }

    /**
     * @dataProvider getDatesRangeByGivenDatesProvider
     */
    public function testGetDatesRangeByGivenDates(string $startDate, string $endDate, int $offset, array $expected): void {
        $result = DateRange::getDatesRangeByGivenDates($startDate, $endDate, $offset);

        $this->assertSame($expected, $result);
    }

    public static function getDatesRangeByGivenDatesProvider(): array {
        return [
            'zero offset' => [
                'startDate' => '2024-01-01 00:00:00',
                'endDate' => '2024-01-31 23:59:59',
                'offset' => 0,
                'expected' => [
                    'endDate' => '2024-01-31 23:59:59',
                    'startDate' => '2024-01-01 00:00:00',
                ],
            ],
            'positive offset 1h' => [
                'startDate' => '2024-01-01 12:00:00',
                'endDate' => '2024-01-02 12:00:00',
                'offset' => 3600,
                'expected' => [
                    'endDate' => '2024-01-02 13:00:00',
                    'startDate' => '2024-01-01 13:00:00',
                ],
            ],
            'negative offset 1h' => [
                'startDate' => '2024-01-01 12:00:00',
                'endDate' => '2024-01-02 12:00:00',
                'offset' => -3600,
                'expected' => [
                    'endDate' => '2024-01-02 11:00:00',
                    'startDate' => '2024-01-01 11:00:00',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getResolutionFromRequestProvider
     */
    public function testGetResolutionFromRequest(?string $requestValue, array $chartResolution, string $expected): void {
        // Provide CHART_RESOLUTION through EXTRA override so Constants::get() sees it.
        // Constants::get('CHART_RESOLUTION') will merge/override with EXTRA_CHART_RESOLUTION.
        $this->f3->set('EXTRA_CHART_RESOLUTION', $chartResolution);

        if ($requestValue === null) {
            $this->f3->clear('REQUEST.resolution');
        } else {
            $this->f3->set('REQUEST.resolution', $requestValue);
        }

        $result = DateRange::getResolutionFromRequest();

        $this->assertSame($expected, $result);
    }

    public static function getResolutionFromRequestProvider(): array {
        $chartResolution = [
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
        ];

        return [
            'missing request -> day' => [null, $chartResolution, 'day'],
            'valid request hour -> hour' => ['hour', $chartResolution, 'hour'],
            'invalid request -> day' => ['week', $chartResolution, 'day'],
        ];
    }

    /**
     * @dataProvider getDatesRangeFromRequestProvider
     */
    public function testGetDatesRangeFromRequest(
        ?string $dateFrom,
        ?string $dateTo,
        ?int $keepDates,
        int $offset,
        ?array $expectedDates,
        ?string $expectedSessionStart,
        ?string $expectedSessionEnd
    ): void {
        if ($dateFrom !== null) {
            $this->f3->set('REQUEST.dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $this->f3->set('REQUEST.dateTo', $dateTo);
        }

        if ($keepDates !== null) {
            $this->f3->set('REQUEST.keepDates', $keepDates);
        }

        $result = DateRange::getDatesRangeFromRequest($offset);

        $this->assertSame($expectedDates, $result);

        $sessionStart = $this->f3->get('SESSION.filterStartDate');
        $sessionEnd = $this->f3->get('SESSION.filterEndDate');

        $this->assertSame($expectedSessionStart, $sessionStart);
        $this->assertSame($expectedSessionEnd, $sessionEnd);
    }

    public static function getDatesRangeFromRequestProvider(): array {
        return [
            'missing both dates -> null, no session set' => [
                'dateFrom' => null,
                'dateTo' => null,
                'keepDates' => null,
                'offset' => 0,
                'expectedDates' => null,
                'expectedSessionStart' => null,
                'expectedSessionEnd' => null,
            ],
            'dates provided, keepDates=1 -> session set' => [
                'dateFrom' => '2024-01-01 12:00:00',
                'dateTo' => '2024-01-02 12:00:00',
                'keepDates' => 1,
                'offset' => 3600,
                'expectedDates' => [
                    'endDate' => '2024-01-02 13:00:00',
                    'startDate' => '2024-01-01 13:00:00',
                ],
                'expectedSessionStart' => '2024-01-01 13:00:00',
                'expectedSessionEnd' => '2024-01-02 13:00:00',
            ],
            'dates provided, keepDates=0 -> session cleared to nulls' => [
                'dateFrom' => '2024-01-01 12:00:00',
                'dateTo' => '2024-01-02 12:00:00',
                'keepDates' => 0,
                'offset' => 0,
                'expectedDates' => [
                    'endDate' => '2024-01-02 12:00:00',
                    'startDate' => '2024-01-01 12:00:00',
                ],
                'expectedSessionStart' => null,
                'expectedSessionEnd' => null,
            ],
        ];
    }

    public function testGetLatestNDatesRangeFromRequestReturnsValidFormat(): void {
        // Provide SECONDS_IN_DAY through EXTRA override.
        $this->f3->set('EXTRA_SECONDS_IN_DAY', 86400);

        $days = 7;

        $result = DateRange::getLatestNDatesRangeFromRequest($days, 0);

        $this->assertArrayHasKey('startDate', $result);
        $this->assertArrayHasKey('endDate', $result);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} 00:00:01$/', $result['startDate']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} 23:59:59$/', $result['endDate']);

        $startTs = strtotime($result['startDate']);
        $endTs = strtotime($result['endDate']);

        $this->assertNotFalse($startTs);
        $this->assertNotFalse($endTs);
        $this->assertLessThan($endTs, $startTs, 'startDate must be earlier than endDate');

        // Calendar day distance must match $days exactly.
        $startDay = new \DateTimeImmutable(substr($result['startDate'], 0, 10));
        $endDay = new \DateTimeImmutable(substr($result['endDate'], 0, 10));

        $diffDays = $endDay->diff($startDay)->days;

        $this->assertSame($days, $diffDays);
    }
}
