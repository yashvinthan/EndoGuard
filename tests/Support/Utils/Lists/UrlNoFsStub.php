<?php

declare(strict_types=1);

namespace Tests\Support\Utils\Lists;

use EndoGuard\Utils\Assets\Lists\Url;

/**
 * Stub: disables filesystem access.
 */
final class UrlNoFsStub extends Url {
    protected static function getExtension(): ?array {
        return null;
    }
}
