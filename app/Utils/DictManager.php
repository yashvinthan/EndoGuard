<?php

/**
 * EndoGuard ~ Embedded & Internal security framework
 * Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.online)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) EndoGuard Security Sàrl (https://www.endoguard.online)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.endoguard.online endoguard(tm)
 */

declare(strict_types=1);

namespace EndoGuard\Utils;

class DictManager {
    public static function load(string $file): void {
        $f3 = \Base::instance();

        $locale = $f3->get('LOCALES');
        $language = $f3->get('LANGUAGE');

        $path = sprintf('%s%s/Additional/%s.php', $locale, $language, $file);

        $isFileExists = file_exists($path);

        if ($isFileExists) {
            $values = include $path;

            if ($values !== false) {
                foreach ($values as $key => $value) {
                    $f3->set($key, $value);
                }
            }
        }
    }
}
