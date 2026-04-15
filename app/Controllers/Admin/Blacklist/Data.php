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

namespace EndoGuard\Controllers\Admin\Blacklist;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getList(int $apiKey): array {
        $model = new \EndoGuard\Models\Grid\Blacklist\Grid($apiKey);

        return $model->getAll();
    }

    public function removeItemFromBlacklist(int $itemId, string $type, int $apiKey): void {
        $model = null;

        switch ($type) {
            case 'ip':
                $model = new \EndoGuard\Models\Ip();
                break;
            case 'email':
                $model = new \EndoGuard\Models\Email();
                break;
            case 'phone':
                $model = new \EndoGuard\Models\Phone();
                break;
        }

        if ($model) {
            $model->updateFraudFlag([$itemId], false, $apiKey);
        }
    }

    public function setBlacklistUsersCount(bool $cache, int $apiKey): array {
        $operator = \EndoGuard\Utils\Routes::getCurrentRequestOperator();

        if (!$operator) {
            $key = \EndoGuard\Entities\ApiKey::getById($apiKey);
            $operator = \EndoGuard\Entities\Operator::getById($key->creator);
        }

        $takeFromCache = $this->canTakeNumberOfBlacklistUsersFromCache($operator);

        $total = $operator->blacklistUsersCnt;
        if (!$cache || !$takeFromCache) {
            $total = (new \EndoGuard\Models\Dashboard())->getTotalBlockedUsers(null, $apiKey);

            $model = new \EndoGuard\Models\Operator();
            $model->updateBlacklistUsersCnt($total, $operator->id);
        }

        return ['total' => $total];
    }

    private function canTakeNumberOfBlacklistUsersFromCache(\EndoGuard\Entities\Operator $operator): bool {
        $interval = \Base::instance()->get('REVIEWED_QUEUE_CNT_CACHE_TIME');

        return !!\EndoGuard\Utils\DateRange::inIntervalTillNow($operator->reviewQueueUpdatedAt, $interval);
    }
}
