<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\Lists;

use Tests\Support\Utils\Lists\UrlNoFsStub;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase {
    public function testGetWordsReturnsBuiltInListWhenExtensionIsNull(): void {
        $words = UrlNoFsStub::getList();

        self::assertIsArray($words);
        self::assertNotEmpty($words);
    }

    public function testGetWordsReturnsOnlyStrings(): void {
        $words = UrlNoFsStub::getList();

        foreach ($words as $word) {
            self::assertIsString($word);
            self::assertNotSame('', $word);
        }
    }
}
