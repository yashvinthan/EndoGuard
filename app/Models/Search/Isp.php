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

namespace EndoGuard\Models\Search;

class Isp extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event_isp';

    public function searchByIsp(string $query, int $apiKey): array {
        $params = [
            ':api_key' => $apiKey,
            ':query' => "%{$query}%",
        ];

        $query = (
            "SELECT
                event_isp.id        AS id,
                'ASN'               AS \"groupName\",
                'isp'               AS \"entityId\",
                event_isp.asn::text AS value

            FROM
                event_isp

            WHERE
                LOWER(event_isp.asn::text) LIKE LOWER(:query) AND
                event_isp.key = :api_key

            LIMIT 25 OFFSET 0"
        );

        return $this->execQuery($query, $params);
    }
}
