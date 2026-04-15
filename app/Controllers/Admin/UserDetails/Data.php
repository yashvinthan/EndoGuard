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

namespace EndoGuard\Controllers\Admin\UserDetails;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getUserDetails(int $userId, int $apiKey): array {
        (new \EndoGuard\Models\User())->updateTotalsByAccountIds([$userId], $apiKey);

        $model          = new \EndoGuard\Models\UserDetails\Id();
        $userDetails    = $model->getDetails($userId, $apiKey);

        $model          = new \EndoGuard\Models\UserDetails\Ip();
        $ipDetails      = $model->getDetails($userId, $apiKey);

        $model          = new \EndoGuard\Models\UserDetails\Total();
        $totalDetails   = $model->getDetails($userId, $apiKey);

        $model          = new \EndoGuard\Models\UserDetails\Behaviour();
        $offset         = \EndoGuard\Utils\Timezones::getCurrentOperatorOffset();

        $dateRange      = \EndoGuard\Utils\Timezones::getCurDayRange($offset);
        $dayDetails     = $model->getDayDetails($userId, $dateRange, $apiKey);

        $dateRange      = \EndoGuard\Utils\Timezones::getWeekAgoDayRange($offset);
        $weekDetails    = $model->getDayDetails($userId, $dateRange, $apiKey);

        return [
            'userDetails'   => $userDetails,
            'ipDetails'     => $ipDetails,
            'totalDetails'  => $totalDetails,
            'dayDetails'    => $dayDetails,
            'weekDetails'   => $weekDetails,
        ];
    }

    public function checkIfOperatorHasAccess(int $userId, int $apiKey): bool {
        $model = new \EndoGuard\Models\UserDetails\Id();

        return $model->checkAccess($userId, $apiKey);
    }
}
