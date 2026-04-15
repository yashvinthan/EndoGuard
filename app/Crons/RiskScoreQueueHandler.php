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

namespace EndoGuard\Crons;

class RiskScoreQueueHandler extends BaseQueue {
    private \EndoGuard\Controllers\Admin\Rules\Data $rulesController;

    public function __construct() {
        $this->rulesController = new \EndoGuard\Controllers\Admin\Rules\Data();
        $this->rulesController->buildEvaluationModels();
    }

    public function process(): void {
        $batchSize = \EndoGuard\Utils\Variables::getAccountOperationQueueBatchSize();
        $queueModel = new \EndoGuard\Models\Queue();
        $keys = $queueModel->getNextBatchKeys(\EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE, $batchSize);

        parent::baseProcess(\EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE);

        $blacklist = new \EndoGuard\Controllers\Admin\Blacklist\Data();
        $reviewQueue = new \EndoGuard\Controllers\Admin\ReviewQueue\Data();

        foreach ($keys as $key) {
            $blacklist->setBlacklistUsersCount(false, $key);
            $reviewQueue->setNotReviewedCount(false, $key);
        }
    }

    protected function processItem(array $item): void {
        $this->rulesController->evaluateUser($item['event_account'], $item['key'], true);
    }
}
