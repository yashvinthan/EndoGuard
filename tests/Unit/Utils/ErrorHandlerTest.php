<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\ErrorHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\ErrorHandler.
 *
 * Covered (unit-testable without refactor):
 * - ErrorHandler::getErrorDetails() (trace normalization + message formatting + basic payload fields)
 * - ErrorHandler::exceptionErrorHandler() (throws \ErrorException when severity is reported; returns false otherwise)
 *
 * Not covered (recommended to refactor first):
 * - ErrorHandler::saveErrorInformation() (Logger/Database/Routes/Model/Mailer side effects)
 * - ErrorHandler::getOnErrorHandler() (echo/ob_* loops, reroute, view/controller rendering, heavy global side effects)
 * - ErrorHandler::getCronErrorHandler() (calls saveErrorInformation())
 * - ErrorHandler::getAjaxErrorMessage() (protected; best tested after refactor via a pure renderer)
 *
 * @todo Refactor:
 * - extract side-effecting collaborators behind interfaces:
 *   LoggerInterface, MailerInterface, DatabaseProviderInterface, RoutesInterface,
 *   ErrorPageRendererInterface, OutputBufferInterface, ClockInterface.
 * - after that, saveErrorInformation() and getOnErrorHandler() become deterministic and properly unit-testable.
 */
final class ErrorHandlerTest extends TestCase {
    private \Base $f3;

    /**
     * @var array<string, mixed>
     */
    private array $backup = [];

    protected function setUp(): void {
        parent::setUp();

        $this->f3 = \Base::instance();

        $keys = [
            'ERROR.trace',
            'ERROR.code',
            'ERROR.text',
            'POST',
            'GET',
        ];

        foreach ($keys as $key) {
            $this->backup[$key] = $this->f3->get($key);
        }

        $this->backup['IP'] = $this->f3->IP ?? null;
    }

    protected function tearDown(): void {
        foreach ($this->backup as $key => $value) {
            if ($key === 'IP') {
                $this->f3->IP = $value;
                continue;
            }

            $this->f3->set($key, $value);
        }

        parent::tearDown();
    }

    public function testGetErrorDetailsNormalizesTraceAndFormatsMessage(): void {
        $this->f3->IP = '203.0.113.10';

        $code = 500;
        $text = 'Something went wrong';

        // Make 3 lines where the *longest* must be removed (iters > 1).
        $line1 = 'short line';
        $line2 = '<b>keep</b> &gt; &lt; tag';
        $line3 = '<div>This is the longest line and must be removed from trace output</div>';

        $trace = $line1 . PHP_EOL . $line3 . PHP_EOL . $line2;

        $post = ['a' => 'b'];
        $get = ['q' => 'x'];

        $this->f3->set('ERROR.trace', $trace);
        $this->f3->set('ERROR.code', $code);
        $this->f3->set('ERROR.text', $text);
        $this->f3->set('POST', $post);
        $this->f3->set('GET', $get);

        $result = ErrorHandler::getErrorDetails($this->f3);

        $this->assertSame('203.0.113.10', $result['ip']);
        $this->assertSame($code, $result['code']);
        $this->assertSame('ERROR_500, Something went wrong', $result['message']);
        $this->assertSame($post, $result['post']);
        $this->assertSame($get, $result['get']);

        // Trace:
        // - longest line removed
        // - strip_tags applied
        // - &gt; &lt; decoded to > <
        // - joined with <br>
        $this->assertIsString($result['trace']);
        $this->assertStringContainsString('short line', $result['trace']);
        $this->assertStringContainsString('keep > < tag', $result['trace']);
        $this->assertStringNotContainsString('must be removed', $result['trace']);
        $this->assertStringNotContainsString('<b>', $result['trace']);
        $this->assertStringContainsString('<br>', $result['trace']);

        $this->assertIsString($result['date']);
        $this->assertNotSame('', $result['date']);
    }

    public function testGetErrorDetailsDoesNotRemoveTraceWhenSingleLine(): void {
        $this->f3->IP = '127.0.0.1';

        $line = '<i>one</i> &gt; test';
        $this->f3->set('ERROR.trace', $line);
        $this->f3->set('ERROR.code', 404);
        $this->f3->set('ERROR.text', 'Not Found');
        $this->f3->set('POST', []);
        $this->f3->set('GET', []);

        $result = ErrorHandler::getErrorDetails($this->f3);

        // With a single line, nothing should be spliced out.
        $this->assertSame('one > test', $result['trace']);
        $this->assertSame('ERROR_404, Not Found', $result['message']);
    }

    public function testExceptionErrorHandlerThrowsWhenSeverityIsReported(): void {
        $original = error_reporting();

        try {
            $mask = E_USER_WARNING;
            error_reporting($mask);

            $this->expectException(\ErrorException::class);
            $this->expectExceptionMessage('boom');

            $severity = E_USER_WARNING;
            $message = 'boom';
            $file = __FILE__;
            $line = __LINE__;

            ErrorHandler::exceptionErrorHandler($severity, $message, $file, $line);
        } finally {
            error_reporting($original);
        }
    }

    public function testExceptionErrorHandlerReturnsFalseWhenSeverityIsNotReported(): void {
        $original = error_reporting();

        try {
            // Disable all reporting -> handler must return false and NOT throw.
            error_reporting(0);

            $severity = E_USER_WARNING;
            $message = 'ignored';
            $file = __FILE__;
            $line = __LINE__;

            $result = ErrorHandler::exceptionErrorHandler($severity, $message, $file, $line);

            $this->assertFalse($result);
        } finally {
            error_reporting($original);
        }
    }
}
