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

namespace EndoGuard\Models\Chart;

class UserAgents extends Base {
    protected ?string $DB_TABLE_NAME = 'event_ua_parsed';

    public function getData(int $apiKey): array {
        $data = $this->getFirstLine($apiKey);

        $timestamps = array_column($data, 'ts');
        $line1 = array_column($data, 'bot_count');

        return $this->addEmptyDays([$timestamps, $line1]);
    }

    private function getFirstLine(int $apiKey): array {
        $query = (
            'SELECT
                EXTRACT(EPOCH FROM date_trunc(:resolution, event.time + :offset))::bigint AS ts,
                COUNT(DISTINCT (
                    CASE WHEN event_ua_parsed.modified IS TRUE THEN event.device END)
                ) AS bot_count
            FROM
                event

            INNER JOIN event_device
            ON(event.device = event_device.id)
            INNER JOIN event_ua_parsed
            ON(event_device.user_agent=event_ua_parsed.id)

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
