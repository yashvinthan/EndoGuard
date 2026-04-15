<?php

/**
 * EndoGuard ~ Embedded & Internal security framework
 * Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.io)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.io)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.endoguard.io endoguard(tm)
 */

declare(strict_types=1);

namespace EndoGuard\Utils\Assets;

class ContextClass extends Base {
    protected static function getDirectory(): string {
        return dirname(__DIR__, 3) . '/assets/rules/custom';
    }

    protected static function getClassFilename(string $filename): string {
        return self::getDirectory() . '/' . $filename;
    }

    protected static function getNamespace(): string {
        return '\\EndoGuard\\Rules\\Custom';
    }

    public static function getContextObj(): ?\EndoGuard\Assets\Context {
        $obj = null;

        $filename   = self::getClassFilename('Context.php');
        $cls        = self::getNamespace() . '\\Context';

        try {
            self::validateClass($filename, $cls);
            $obj = new $cls();
        } catch (\Throwable $e) {
            self::log('Context validation failed with error ' . $e->getMessage());
        }

        return $obj;
    }
}
