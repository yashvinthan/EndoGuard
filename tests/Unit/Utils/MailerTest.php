<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Mailer;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Mailer.
 *
 * Covered (unit-testable without refactor):
 * - Mailer::send() returns a development-mode response when SEND_EMAIL is falsy
 *   (no external IO, no PHPMailer, no sendmail binary checks).
 *
 * Not covered (unstable without refactor):
 * - Mailer::send() when SEND_EMAIL is truthy:
 *   - depends on Variables::getMailPassword() and other static config
 *   - may instantiate PHPMailer and attempt SMTP
 *   - may check filesystem for sendmail and call mail()
 * - sendByMailgun() / sendByNativeMail():
 *   - external IO (SMTP, filesystem, system mail)
 *
 * @todo Refactor:
 * - extract ConfigInterface (F3 wrapper) for SEND_EMAIL / SMTP_DEBUG
 * - extract MailTransportInterface for SMTP/native implementations
 * - inject Variables/Constants readers (avoid static calls)
 * - avoid calling global mail(), file_exists(), is_executable() directly (wrap)
 */
final class MailerTest extends TestCase {
    private \Base $f3;

    /** @var array<string, mixed> */
    private array $f3Backup = [];

    /** @var list<string> */
    private array $f3Keys = [
        'SEND_EMAIL',
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
     * @dataProvider devModeProvider
     */
    public function testSendReturnsDevModeResponse(mixed $sendEmailFlag): void {
        $this->f3->set('SEND_EMAIL', $sendEmailFlag);

        $toName = null;
        $toAddress = 'user@example.com';
        $subject = 'Subject';
        $message = 'Message';

        $result = Mailer::send($toName, $toAddress, $subject, $message);

        $this->assertIsArray($result);

        $expectedSuccess = true;
        $actualSuccess = $result['success'] ?? null;

        $this->assertSame($expectedSuccess, $actualSuccess);

        $expectedMessage = 'Email will not be sent in development mode';
        $actualMessage = $result['message'] ?? null;

        $this->assertSame($expectedMessage, $actualMessage);
    }

    public static function devModeProvider(): array {
        return [
            'missing flag (null)' => [
                'sendEmailFlag' => null,
            ],
            'false boolean' => [
                'sendEmailFlag' => false,
            ],
            'zero int' => [
                'sendEmailFlag' => 0,
            ],
            'empty string' => [
                'sendEmailFlag' => '',
            ],
            'string false' => [
                'sendEmailFlag' => '0',
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
