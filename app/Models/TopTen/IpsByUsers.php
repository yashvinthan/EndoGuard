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

namespace EndoGuard\Models\TopTen;

class IpsByUsers extends Base {
    protected ?string $DB_TABLE_NAME = 'event';

    public function getList(int $apiKey, ?array $dateRange): array {
        $params = $this->getQueryParams($apiKey, $dateRange);

        $queryConditions = $this->getQueryConditions($dateRange);
        $queryConditions[] = 'event_ip.shared > 1';
        $queryConditions = join(' AND ', $queryConditions);

        $query = (
            "SELECT
                MAX(event_ip.ip)        AS ip,
                MAX(event_ip.id)        AS ipid,
                MAX(event_ip.shared)    AS value,
                MAX(event_isp.name)     AS isp_name,

                MAX(countries.value)    AS full_country,
                MAX(countries.id)       AS country_id,
                MAX(countries.iso)      AS country_iso

            FROM
                event

            INNER JOIN event_ip
            ON (event.ip = event_ip.id)

            INNER JOIN countries
            ON (event_ip.country = countries.id)

            INNER JOIN event_isp
            ON (event_ip.isp = event_isp.id)

            WHERE
                {$queryConditions}

            GROUP BY
                event_ip.ip

            ORDER BY
                value DESC

            LIMIT 10 OFFSET 0"
        );

        return $this->execQuery($query, $params);
    }
}
