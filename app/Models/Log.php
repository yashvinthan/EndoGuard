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

class Log extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'dshb_logs';

    public function insertRecord(array $data): void {
        $params = [
            ':text' => json_encode($data),
        ];

        $query = (
            'INSERT INTO dshb_logs (text) VALUES (:text)'
        );

        $this->execQuery($query, $params);
    }
}
