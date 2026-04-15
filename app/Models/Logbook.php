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

namespace EndoGuard\Models;

class Logbook extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event_logbook';

    public function getLastSucceededEvent(int $apiKey): array {
        $params = [
            ':api_key'          => $apiKey,
            ':endpoint'         => '/sensor/',
            ':success'          => \EndoGuard\Utils\Constants::get()->LOGBOOK_ERROR_TYPE_SUCCESS,
            ':validation_error' => \EndoGuard\Utils\Constants::get()->LOGBOOK_ERROR_TYPE_VALIDATION_ERROR,
        ];

        $query = (
            'SELECT
                event_logbook.event,
                event_logbook.ended     AS lastseen

            FROM
                event_logbook

            WHERE
                event_logbook.key = :api_key AND
                (
                    event_logbook.error_type = :success  OR
                    event_logbook.error_type = :validation_error
                ) AND
                event_logbook.endpoint = :endpoint
            ORDER BY event_logbook.ended DESC
            LIMIT 1'
        );

        $results = $this->execQuery($query, $params);

        return $results[0] ?? [];
    }

    public function getLogbookDetails(int $id, int $apiKey): array {
        $params = [
            ':api_key' => $apiKey,
            ':id' => $id,
        ];

        $query = (
            'SELECT
                event_logbook.id,
                event_logbook.ip,
                event_logbook.raw,
                event_logbook.started,
                event_logbook.endpoint,
                event_logbook.error_text,
                event_logbook.error_type,
                event_error_type.name           AS error_name,
                event_error_type.value          AS error_value

            FROM
                event_logbook

            LEFT JOIN event_error_type
            ON (event_logbook.error_type = event_error_type.id)

            WHERE
                event_logbook.id = :id AND
                event_logbook.key = :api_key
            LIMIT 1'
        );

        $results = $this->execQuery($query, $params);

        return $results[0] ?? [];
    }

    public function add(
        string $ip,
        string $endpoint,
        ?int $event,
        int $errorType,
        ?string $errorText,
        string $raw,
        string $started,
        int $apiKey
    ): void {
        $params = [
            ':ip'           => $ip,
            ':endpoint'     => $endpoint,
            ':event'        => $event,
            ':error_type'   => $errorType,
            ':error_text'   => $errorText,
            ':raw'          => $raw,
            ':started'      => $started,
            ':key'          => $apiKey,
        ];

        $query = (
            'INSERT INTO event_logbook
                (endpoint, key, ip, event, error_type, error_text, raw, started)
            VALUES
                (:endpoint, :key, :ip, :event, :error_type, :error_text, :raw, :started)'
        );

        $this->execQuery($query, $params);
    }

    public function rotateRequests(?int $apiKey): int {
        $params = [
            ':key'      => $apiKey,
            ':limit'    => \EndoGuard\Utils\Variables::getLogbookLimit(),
        ];

        $query = (
            'SELECT
                id
            FROM event_logbook
            WHERE key = :key
            ORDER BY id DESC
            LIMIT 1 OFFSET :limit'
        );

        $result = $this->execQuery($query, $params);

        if (!count($result)) {
            return 0;
        }

        $params = [
            ':id' => $result[0]['id'],
            ':key' => $apiKey,
        ];

        $query = (
            'DELETE FROM event_logbook
            WHERE
                id < :id AND
                key = :key'
        );

        return $this->execQuery($query, $params);
    }
}
