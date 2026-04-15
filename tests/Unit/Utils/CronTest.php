<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Cron;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Cron.
 *
 * Covered (unit-testable without refactor):
 * - Cron::getHashes()
 * - Cron::printLogs()
 *
 * @todo Refactor (requires refactor / DI to become unit-testable):
 * - Cron::checkTimezone()
 * - Cron::sendBlacklistReportPostRequest()
 * - Cron::sendUnreviewedItemsReminderEmail()
 */
final class CronTest extends TestCase {
    /**
     * @dataProvider getHashesProvider
     */
    public function testGetHashes(
        array $items,
        string $userEmail,
        array $expectedTypes,
        array $expectedRawValues
    ): void {
        $result = Cron::getHashes($items, $userEmail);

        $expectedCount = count($items);
        $this->assertCount($expectedCount, $result);

        $expectedUserHash = hash('sha256', $userEmail);

        $iters = count($result);

        for ($i = 0; $i < $iters; ++$i) {
            $row = $result[$i];

            $this->assertSame($expectedTypes[$i], $row['type']);
            $this->assertSame(hash('sha256', $expectedRawValues[$i]), $row['value']);
            $this->assertSame($expectedUserHash, $row['id']);
        }

        if ($expectedCount > 1) {
            $firstId = $result[0]['id'];

            for ($i = 1; $i < $expectedCount; ++$i) {
                $this->assertSame($firstId, $result[$i]['id']);
            }
        }
    }

    public static function getHashesProvider(): array {
        return [
            'empty items' => [
                'items' => [],
                'userEmail' => 'user@example.com',
                'expectedTypes' => [],
                'expectedRawValues' => [],
            ],
            'single item' => [
                'items' => [
                    ['type' => 'email', 'value' => 'test@example.com'],
                ],
                'userEmail' => 'user@example.com',
                'expectedTypes' => ['email'],
                'expectedRawValues' => ['test@example.com'],
            ],
            'multiple items' => [
                'items' => [
                    ['type' => 'email', 'value' => 'test@example.com'],
                    ['type' => 'ip', 'value' => '192.168.1.1'],
                    ['type' => 'phone', 'value' => '+1234567890'],
                ],
                'userEmail' => 'user@example.com',
                'expectedTypes' => ['email', 'ip', 'phone'],
                'expectedRawValues' => ['test@example.com', '192.168.1.1', '+1234567890'],
            ],
            'same value produces same hash' => [
                'items' => [
                    ['type' => 'email', 'value' => 'same@example.com'],
                    ['type' => 'email', 'value' => 'same@example.com'],
                ],
                'userEmail' => 'user@example.com',
                'expectedTypes' => ['email', 'email'],
                'expectedRawValues' => ['same@example.com', 'same@example.com'],
            ],
        ];
    }

    /**
     * @dataProvider printLogsProvider
     */
    public function testPrintLogs(array $logs, string $expectedOutput): void {
        ob_start();
        Cron::printLogs($logs);
        $output = ob_get_clean();

        $this->assertSame($expectedOutput, $output);
    }

    public static function printLogsProvider(): array {
        return [
            'empty array' => [
                'logs' => [],
                'expectedOutput' => '',
            ],
            'single log' => [
                'logs' => ['Test log message'],
                'expectedOutput' => 'Test log message',
            ],
            'multiple logs concatenated' => [
                'logs' => ['Log 1', 'Log 2', 'Log 3'],
                'expectedOutput' => 'Log 1Log 2Log 3',
            ],
            'preserves new lines' => [
                'logs' => ['Line1' . PHP_EOL, 'Line2' . PHP_EOL],
                'expectedOutput' => 'Line1' . PHP_EOL . 'Line2' . PHP_EOL,
            ],
        ];
    }
}
