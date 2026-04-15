<?php

declare(strict_types=1);

namespace Tests\Support\Utils\Lists;

use EndoGuard\Utils\Assets\Lists\FileExtensions;

/**
 * Stub: overrides extension loading (no filesystem).
 */
final class FileExtensionsStubWithExtension extends FileExtensions {
    protected static function getExtension(): ?array {
        return [
            'images' => ['jpg', 'png'],
            'docs' => ['pdf'],
        ];
    }
}
