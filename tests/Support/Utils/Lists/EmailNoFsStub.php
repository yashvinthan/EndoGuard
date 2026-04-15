<?php

declare(strict_types=1);

namespace Tests\Support\Utils\Lists;

use EndoGuard\Utils\Assets\Lists\Email;

/**
 * Stub: disables filesystem access.
 */
final class EmailNoFsStub extends Email {
    protected static function getExtension(): ?array {
        return null;
    }
}
