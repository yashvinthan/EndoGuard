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

namespace EndoGuard\Models;

class RetentionPolicies extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'dshb_api';

    public function getRetentionKeys(): array {
        $query = (
            'SELECT
                dshb_api.id,
                dshb_api.retention_policy
            FROM
                dshb_api
            WHERE
                dshb_api.retention_policy > 0'
        );

        return $this->execQuery($query, null);
    }
}
