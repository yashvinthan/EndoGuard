<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\Http;

use EndoGuard\Utils\Http\CurlTransport;
use PHPUnit\Framework\TestCase;

/**
 * @covers \EndoGuard\Utils\Http\CurlTransport
 */
final class CurlTransportTest extends TestCase {
    public function testIsAvailableMatchesFunctionExists(): void {
        $transport = new CurlTransport();

        $expected = function_exists('curl_init');
        $actual = $transport->isAvailable();

        $this->assertSame($expected, $actual);
    }
}
