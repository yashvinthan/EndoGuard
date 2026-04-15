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

namespace EndoGuard\Controllers\Admin\ReviewQueue;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getList(int $apiKey): array {
        $model = new \EndoGuard\Models\Grid\ReviewQueue\Grid($apiKey);

        return $model->getAll();
    }

    public function setNotReviewedCount(bool $cache, int $apiKey): array {
        $operator = \EndoGuard\Utils\Routes::getCurrentRequestOperator();

        if (!$operator) {
            $key = \EndoGuard\Entities\ApiKey::getById($apiKey);
            $operator = \EndoGuard\Entities\Operator::getById($key->creator);
        }

        $takeFromCache = $this->canTakeNumberOfNotReviewedUsersFromCache($operator);

        $total = $operator->reviewQueueCnt;
        if (!$cache || !$takeFromCache) {
            $total = (new \EndoGuard\Models\ReviewQueue())->getCount($apiKey);

            $model = new \EndoGuard\Models\Operator();
            $model->updateReviewedQueueCnt($total, $operator->id);
        }

        return ['total' => $total];
    }

    private function canTakeNumberOfNotReviewedUsersFromCache(\EndoGuard\Entities\Operator $operator): bool {
        $interval = \Base::instance()->get('REVIEWED_QUEUE_CNT_CACHE_TIME');

        return !!\EndoGuard\Utils\DateRange::inIntervalTillNow($operator->reviewQueueUpdatedAt, $interval);
    }
}
