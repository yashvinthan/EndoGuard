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

class Updates {
    private const UPDATES_LIST = [
        \EndoGuard\Updates\Update001::class,
        \EndoGuard\Updates\Update002::class,
        \EndoGuard\Updates\Update003::class,
        \EndoGuard\Updates\Update004::class,
        \EndoGuard\Updates\Update005::class,
        \EndoGuard\Updates\Update006::class,
        \EndoGuard\Updates\Update007::class,
        \EndoGuard\Updates\Update008::class,
    ];

    public static function syncUpdates(): void {
        $f3 = \Base::instance();
        $updates = new \EndoGuard\Models\Updates($f3);
        $applied = $updates->checkDb('core', self::UPDATES_LIST);

        if ($applied) {
            $controller = new \EndoGuard\Controllers\Admin\Rules\Data();
            // update only core rules
            $controller->updateRules(false);
        }

        \EndoGuard\Utils\Routes::callExtra('UPDATES');
    }
}
