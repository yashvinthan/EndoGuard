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

class FieldAuditTrail extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event_field_audit_trail';

    public function getById(int $trailId, int $apiKey): array {
        $params = [
            ':trail_id' => $trailId,
            ':api_key'  => $apiKey,
        ];

        $query = (
            'SELECT
                event_field_audit.field_id,
                event_field_audit_trail.event_id,
                event_field_audit_trail.field_name,
                event_field_audit_trail.old_value,
                event_field_audit_trail.new_value,
                event_field_audit_trail.parent_id,
                event_field_audit_trail.parent_name
            FROM
                event_field_audit_trail
            LEFT JOIN event_field_audit
            ON (event_field_audit_trail.field_id = event_field_audit.id)
            WHERE
                event_field_audit_trail.id = :trail_id AND
                event_field_audit_trail.key = :api_key'
        );

        $result = $this->execQuery($query, $params);

        return $result[0] ?? [];
    }

    public function getByEventId(int $eventId, int $apiKey): array {
        $params = [
            ':event_id' => $eventId,
            ':api_key'  => $apiKey,
        ];

        $query = (
            'SELECT
                event_field_audit.field_id,
                event_field_audit_trail.field_name,
                event_field_audit_trail.old_value,
                event_field_audit_trail.new_value,
                event_field_audit_trail.parent_id,
                event_field_audit_trail.parent_name
            FROM
                event_field_audit_trail
            LEFT JOIN event_field_audit
            ON (event_field_audit_trail.field_id = event_field_audit.id)
            WHERE
                event_field_audit_trail.event_id = :event_id AND
                event_field_audit_trail.key = :api_key

            ORDER BY event_field_audit_trail.id DESC'
        );

        return $this->execQuery($query, $params);
    }

    public function getByUserId(int $userId, int $apiKey): array {
        $params = [
            ':user_id' => $userId,
            ':api_key'  => $apiKey,
        ];

        $query = (
            'SELECT
                event_field_audit_trail.field_id,
                event_field_audit_trail.field_name,
                event_field_audit_trail.old_value,
                event_field_audit_trail.new_value,
                event_field_audit_trail.parent_id,
                event_field_audit_trail.parent_name
            FROM
                event_field_audit_trail
            WHERE
                event_field_audit_trail.account_id = :user_id AND
                event_field_audit_trail.key = :api_key

            ORDER BY id DESC'
        );

        return $this->execQuery($query, $params);
    }

    public function retentionDeletion(int $weeks, int $apiKey): int {
        // insuring clause
        if ($weeks < 1) {
            return 0;
        }

        $params = [
            ':api_key'  => $apiKey,
            ':weeks'    => $weeks,
            ':week_sec' => \EndoGuard\Utils\Constants::get()->SECONDS_IN_WEEK,
        ];

        $query = (
            'DELETE FROM event_field_audit_trail
            WHERE
                event_field_audit_trail.key = :api_key AND
                (EXTRACT(EPOCH FROM (NOW() - event_field_audit_trail.created)) / :week_sec) >= :weeks'
        );

        return $this->execQuery($query, $params);
    }
}
