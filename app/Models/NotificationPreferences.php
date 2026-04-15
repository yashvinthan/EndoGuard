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

class NotificationPreferences extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'dshb_operators';

    public function operatorsToNotify(): array {
        $params = [
            ':daily'    => \EndoGuard\Utils\Constants::get()->DAILY_NOTIFICATION_REMINDER,
            ':weekly'   => \EndoGuard\Utils\Constants::get()->WEEKLY_NOTIFICATION_REMINDER,
            ':off'      => \EndoGuard\Utils\Constants::get()->NO_NOTIFICATION_REMINDER,
        ];

        $query = (
            'SELECT
                id,
                email,
                timezone,
                firstname,
                review_queue_cnt
            FROM
                dshb_operators
            WHERE
                unreviewed_items_reminder_freq != :off AND
                review_queue_cnt > 0 AND
                (
                    last_unreviewed_items_reminder IS NULL OR
                    (unreviewed_items_reminder_freq = :daily AND last_unreviewed_items_reminder <= NOW() - \'1 day\'::interval) OR
                    (unreviewed_items_reminder_freq = :weekly AND last_unreviewed_items_reminder <= NOW() - \'7 day\'::interval)
                )'
        );

        return $this->execQuery($query, $params);
    }

    public function updateUnreviewedReminder(int $operatorId): void {
        $params = [
            ':id'   => $operatorId,
        ];

        $query = (
            'UPDATE
                dshb_operators
            SET
                last_unreviewed_items_reminder = NOW()
            WHERE id = :id'
        );

        $this->execQuery($query, $params);
    }
}
