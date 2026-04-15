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

namespace EndoGuard\Models\UserDetails;

class Id extends \EndoGuard\Models\BaseSql implements \EndoGuard\Interfaces\ApiKeyAccessAuthorizationInterface {
    protected ?string $DB_TABLE_NAME = 'event_account';

    public function checkAccess(int $subjectId, int $apiKey): bool {
        $params = [
            ':user_id' => $subjectId,
            ':api_key' => $apiKey,
        ];

        $query = (
            'SELECT
                userid

            FROM
                event_account

            WHERE
                event_account.id = :user_id AND
                event_account.key = :api_key'
        );

        $results = $this->execQuery($query, $params);

        return count($results) > 0;
    }

    public function getDetails(int $userId, int $apiKey): array {
        $params = [
            ':user_id' => $userId,
            ':api_key' => $apiKey,
        ];

        $query = (
            'SELECT
                event_account.userid,
                event_account.lastseen,
                event_account.created,
                event_account.firstname,
                event_account.lastname,
                event_account.score,
                event_account.score_details,
                event_account.is_important,
                event_account.fraud,
                event_account.reviewed,
                event_account.latest_decision,
                event_account.added_to_review,

                event_email.email

            FROM
                event_account

            LEFT JOIN event_email
            ON (event_account.lastemail = event_email.id)

            WHERE
                event_account.id = :user_id AND
                event_account.key = :api_key'
        );


        $results = $this->execQuery($query, $params);

        $result = $results[0] ?? [];

        $tsColumns = ['created', 'lastseen', 'score_updated_at', 'latest_decision', 'updated', 'added_to_review'];
        \EndoGuard\Utils\Timezones::localizeTimestampsForActiveOperator($tsColumns, $result);

        return $result;
    }
}
