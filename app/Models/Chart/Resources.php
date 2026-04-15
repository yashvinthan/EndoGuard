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

class Resources extends Base {
    protected ?string $DB_TABLE_NAME = 'event';

    public function getData(int $apiKey): array {
        $data = $this->getFirstLine($apiKey);

        $timestamps = array_column($data, 'ts');
        $line1      = array_column($data, 'count_200');
        $line2      = array_column($data, 'count_404');
        $line3      = array_column($data, 'count_500');

        return $this->addEmptyDays([$timestamps, $line1, $line2, $line3]);
    }

    private function getFirstLine(int $apiKey): array {
        $query = (
            'SELECT
                EXTRACT(EPOCH FROM date_trunc(:resolution, event.time + :offset))::bigint AS ts,
                COUNT(DISTINCT event.url) AS url_count,

                COUNT(DISTINCT (
                    CASE WHEN event.http_code = 200 OR event.http_code IS NULL THEN event.url END)
                ) AS count_200,

                COUNT(DISTINCT (
                    CASE WHEN event.http_code = 404 THEN event.url END)
                ) AS count_404,

                COUNT(DISTINCT (
                    CASE WHEN event.http_code IN(403, 500) THEN event.url END)
                ) AS count_500

            FROM
                event

            LEFT JOIN event_account
            ON event.account = event_account.id

            WHERE
                event.key = :api_key AND
                event.time >= :start_time AND
                event.time <= :end_time

            GROUP BY ts
            ORDER BY ts'
        );

        return $this->execute($query, $apiKey);
    }
}
