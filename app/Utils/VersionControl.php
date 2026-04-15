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

namespace EndoGuard\Utils;

class VersionControl {
    public const VERSION_MAJOR = 0;
    public const VERSION_MINOR = 9;
    public const VERSION_REVISION = 12;

    public static function versionString(): string {
        return sprintf('%d.%d.%d', self::VERSION_MAJOR, self::VERSION_MINOR, self::VERSION_REVISION);
    }

    public static function fullVersionString(): string {
        return sprintf('v%d.%d.%d', self::VERSION_MAJOR, self::VERSION_MINOR, self::VERSION_REVISION);
    }
}
