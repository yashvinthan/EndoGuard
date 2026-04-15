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

namespace EndoGuard\Controllers\Admin\Phones;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getList(int $apiKey): array {
        $result = [];
        $model = new \EndoGuard\Models\Grid\Phones\Grid($apiKey);

        $map = [
            'userId' => 'getPhonesByUserId',
        ];

        $result = $this->idMapIterate($map, $model, null);

        $ids = array_column($result['data'], 'id');
        if ($ids) {
            $model = new \EndoGuard\Models\Phone();
            $model->updateTotalsByEntityIds($ids, $apiKey);
            $result['data'] = $model->refreshTotals($result['data'], $apiKey);
        }

        return $result;
    }

    public function getPhoneDetails(int $id, int $apiKey): array {
        $details = (new \EndoGuard\Models\Phone())->getPhoneDetails($id, $apiKey);
        $details['enrichable'] = $this->isEnrichable($apiKey);

        $tsColumns = ['created', 'lastseen'];
        \EndoGuard\Utils\Timezones::localizeTimestampsForActiveOperator($tsColumns, $details);

        return $details;
    }

    private function isEnrichable(int $apiKey): bool {
        return (new \EndoGuard\Models\ApiKeys())->attributeIsEnrichable('phone', $apiKey);
    }
}
