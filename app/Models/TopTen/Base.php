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

namespace EndoGuard\Models\TopTen;

class Base extends \EndoGuard\Models\BaseSql {
    public function getQueryParams(int $apiKey, ?array $dateRange): array {
        $queryParams = [':api_key' => $apiKey];

        $endDate = $dateRange['endDate'] ?? null;
        $startDate = $dateRange['startDate'] ?? null;

        if ($startDate && $endDate) {
            $queryParams[':end_time'] = $dateRange['endDate'];
            $queryParams[':start_time'] = $dateRange['startDate'];
        }

        return $queryParams;
    }

    public function getQueryConditions(?array $dateRange): array {
        $conditions = ['event.key = :api_key'];

        $endDate = $dateRange['endDate'] ?? null;
        if ($endDate) {
            $conditions[] = 'event.time >= :start_time';
            $conditions[] = 'event.time <= :end_time';
        }

        return $conditions;
    }
}
