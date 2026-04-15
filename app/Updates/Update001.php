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

namespace EndoGuard\Updates;

class Update001 extends Base {
    public static string $version = 'v0.9.5';

    public static function apply(\DB\SQL $database): void {
        $queries = [
            'ALTER TABLE dshb_api ADD COLUMN blacklist_threshold INTEGER DEFAULT -1',
            'ALTER TABLE dshb_api ADD COLUMN review_queue_threshold INTEGER DEFAULT 33',
        ];
        foreach ($queries as $sql) {
            $database->exec($sql);
        }
    }
}
