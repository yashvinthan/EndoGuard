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

namespace EndoGuard\Models\TopTen;

class CountriesByUsers extends Base {
    protected ?string $DB_TABLE_NAME = 'event';

    public function getList(int $apiKey, ?array $dateRange): array {
        $params = $this->getQueryParams($apiKey, $dateRange);

        $queryConditions = $this->getQueryConditions($dateRange);
        $queryConditions = join(' AND ', $queryConditions);

        $query = (
            "SELECT
                countries.id                  AS country_id,
                countries.iso                 AS country_iso,
                countries.value               AS full_country,
                COUNT(DISTINCT event.account) AS value

            FROM
                event

            INNER JOIN event_ip
            ON (event.ip = event_ip.id)

            INNER JOIN countries
            ON (event_ip.country = countries.id)

            WHERE
                {$queryConditions}

            GROUP BY
                countries.id

            ORDER BY
                value DESC

            LIMIT 10 OFFSET 0"
        );

        return $this->execQuery($query, $params);
    }
}
