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

namespace EndoGuard\Utils\Assets\Lists;

abstract class Base {
    protected static string $extensionFile = '';
    protected static array $list = [];
    protected static string $path = '/assets/lists/';

    protected static function getExtension(): ?array {
        $filename = dirname(__DIR__, 4) . static::$path . static::$extensionFile;

        if (file_exists($filename) && is_readable($filename)) {
            $data = include $filename;

            if (is_array($data)) {
                return $data;
            }
        }

        return null;
    }

    public static function getList(): array {
        return static::getExtension() ?? static::$list;
    }
}
