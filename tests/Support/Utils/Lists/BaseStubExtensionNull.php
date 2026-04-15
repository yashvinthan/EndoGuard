<?php

declare(strict_types=1);

namespace Tests\Support\Utils\Lists;

use EndoGuard\Utils\Assets\Lists\Base;

/**
 * Concrete stub for testing Base logic (no filesystem).
 */
final class BaseStubExtensionNull extends Base {
    protected static array $list = ['fallback-1', 'fallback-2'];

    protected static function getExtension(): ?array {
        return null;
    }
}
