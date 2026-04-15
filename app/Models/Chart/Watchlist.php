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

namespace EndoGuard\Models\Chart;

class Watchlist extends Base {
    private array $userIds = [];
    protected ?string $DB_TABLE_NAME = 'event';

    public function getData(int $apiKey): array {
        $params = [
            ':users_ids' => $this->userIds,
        ];

        $query = (
            "SELECT
                TEXT(date_trunc('day', event.time)) AS day,
                COUNT(event.id) AS event_count
            FROM
                event

            INNER JOIN event_account
            ON (event.account = event_account.id)

            INNER JOIN event_url
            ON (event.url = event_url.id)

            INNER JOIN event_ip
            ON (event.ip = event_ip.id)

            INNER JOIN countries
            ON (event_ip.country = countries.id)

            WHERE
                event.key = :api_key
                %s

            GROUP BY day
            ORDER BY day"
        );
        //$request = $this->f3->get('REQUEST');
        //$dateRange = $this->getDatesRange($request);

        return $this->execQuery($query, $params);
    }

    public function setUsersIds(array $userIds): void {
        $this->userIds = $userIds;
    }
}
