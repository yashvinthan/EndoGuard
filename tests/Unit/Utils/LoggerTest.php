<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Logger.
 *
 * Covered (unit-testable without refactor):
 * - Logger::logCronLine() (pure formatting)
 *
 * Not covered (recommended to refactor first):
 * - Logger::log() (hard dependency on F3 \Log + filesystem write side effect)
 * - Logger::logSql() (hard dependency on F3 \Log + filesystem write side effect)
 *
 * @todo Refactor:
 * - extract side-effecting collaborator behind an interface:
 *   LogWriterInterface (or LogFactoryInterface) with write(string $message): void
 * - then Logger::log() / logSql() become deterministic and properly unit-testable without filesystem.
 */
final class LoggerTest extends TestCase {
    /**
     * @dataProvider cronLineProvider
     */
    public function testLogCronLineFormatsAsExpected(string $message, string $cronName, string $expected): void {
        $result = Logger::logCronLine($message, $cronName);

        $this->assertSame($expected, $result);
    }

    public static function cronLineProvider(): array {
        return [
            'simple' => [
                'Started',
                'cronA',
                '[cronA] Started' . PHP_EOL,
            ],
            'message with spaces' => [
                'Hello world',
                'job',
                '[job] Hello world' . PHP_EOL,
            ],
            'message with punctuation' => [
                'Done!',
                'cronB',
                '[cronB] Done!' . PHP_EOL,
            ],
            'empty message' => [
                '',
                'cronC',
                '[cronC] ' . PHP_EOL,
            ],
        ];
    }
}
