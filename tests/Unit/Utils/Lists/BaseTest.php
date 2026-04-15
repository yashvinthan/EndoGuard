<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\Lists;

use Tests\Support\Utils\Lists\BaseStubExtensionNull;
use PHPUnit\Framework\TestCase;

final class BaseTest extends TestCase {
    public function testGetWordsReturnsBuiltInWordsWhenExtensionIsNull(): void {
        $actual = BaseStubExtensionNull::getList();

        $expected = ['fallback-1', 'fallback-2'];

        self::assertSame($expected, $actual);
    }
}
