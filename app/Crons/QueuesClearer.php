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

namespace EndoGuard\Crons;

class QueuesClearer extends Base {
    public const DATETIME_FORMAT = 'Y-m-d H:i:s.u';

    public function process(): void {
        $days = \EndoGuard\Utils\Constants::get()->ACCOUNT_OPERATION_QUEUE_CLEAR_COMPLETED_AFTER_DAYS;
        $before = (new \DateTime(strval($days) . ' days ago'))->format(self::DATETIME_FORMAT);

        $queues = [
            \EndoGuard\Utils\Constants::get()->BLACKLIST_QUEUE_ACTION_TYPE,
            \EndoGuard\Utils\Constants::get()->DELETE_USER_QUEUE_ACTION_TYPE,
            \EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE,
        ];

        $cnt = 0;

        $model = new \EndoGuard\Models\Queue();

        // delete completed records
        foreach ($queues as $queue) {
            $cnt += $model->clearQueue($queue, $before);
        }

        $this->addLog(sprintf('Cleared %s completed items.', $cnt));
    }
}
