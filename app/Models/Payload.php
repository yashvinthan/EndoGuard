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

class Payload extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event_payload';

    public function getByEventId(int $eventId, int $apiKey): ?string {
        $params = [
            ':event_id' => $eventId,
            ':api_key'  => $apiKey,
        ];

        $query = (
            'SELECT
                event_payload.payload
            FROM
                event
            LEFT JOIN event_payload
            ON (event.payload = event_payload.id)
            WHERE
                event.id = :event_id AND
                event_payload.key = :api_key'
        );

        $result = $this->execQuery($query, $params);

        return count($result) ? $result[0]['payload'] : null;
    }
}
