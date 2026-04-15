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

class Totals extends Base {
    // execute before risk score!
    public function process(): void {
        $this->addLog('Start totals calculation.');

        $start = time();
        $models = \EndoGuard\Utils\Constants::get()->REST_TOTALS_MODELS;

        $batchSize = \EndoGuard\Utils\Variables::getAccountOperationQueueBatchSize();
        $bottom = false;

        $queueModel = new \EndoGuard\Models\Queue();

        // TODO check multiple batches
        $keys = $queueModel->getNextBatchKeys(\EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE, $batchSize);
        $res = [];

        foreach ($models as $name => $modelClass) {
            $res[$name] = ['cnt' => 0, 's' => 0];
            $timeMark = time();
            $model = new $modelClass();
            foreach ($keys as $key) {
                (new \EndoGuard\Models\SessionStat())->updateStats($key);

                $cnt = $model->updateAllTotals($key);
                $res[$name]['cnt'] += $cnt;
                if (time() - $start > \EndoGuard\Utils\Constants::get()->ACCOUNT_OPERATION_QUEUE_EXECUTE_TIME_SEC) {
                    // TODO: any reason to put the rest keys to queue?
                    $res[$name]['s'] = time() - $timeMark;
                    break 2;
                }
            }
            $res[$name]['s'] = time() - $timeMark;
        }


        $this->addLog(sprintf('Updated %s entities for %s keys and %s models in %s seconds.', array_sum(array_column(array_values($res), 'cnt')), count($keys), count($models), time() - $start));
    }
}
