<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\Lists;

use Tests\Support\Utils\Lists\EmailNoFsStub;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase {
    public function testGetWordsReturnsBuiltInListWhenExtensionIsNull(): void {
        $words = EmailNoFsStub::getList();

        self::assertIsArray($words);
        self::assertNotEmpty($words);
    }

    public function testGetWordsReturnsOnlyStrings(): void {
        $words = EmailNoFsStub::getList();

        foreach ($words as $word) {
            self::assertIsString($word);
            self::assertNotSame('', $word);
        }
    }
}
