<?php

declare(strict_types=1);

namespace Tests\Unit\Utils\Lists;

use Tests\Support\Utils\Lists\UserAgentNoFsStub;
use PHPUnit\Framework\TestCase;

final class UserAgentTest extends TestCase {
    public function testGetWordsReturnsBuiltInListWhenExtensionIsNull(): void {
        $words = UserAgentNoFsStub::getList();

        self::assertIsArray($words);
        self::assertNotEmpty($words);
    }

    public function testGetWordsReturnsOnlyNonEmptyStrings(): void {
        $words = UserAgentNoFsStub::getList();

        foreach ($words as $word) {
            self::assertIsString($word);
            self::assertNotSame('', $word);
        }
    }
}
