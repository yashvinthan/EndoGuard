<?php

declare(strict_types=1);

namespace Tests\Support\Utils\Lists;

use EndoGuard\Utils\Assets\Lists\Constants;

/**
 * Stub: disables filesystem access.
 */
final class ConstantsNoFsStub extends Constants {
    protected static function getExtension(): ?array {
        return null;
    }
}
