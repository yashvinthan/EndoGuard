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

namespace EndoGuard\Controllers\Admin\Devices;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getList(int $apiKey): array {
        $result = [];
        $model = new \EndoGuard\Models\Grid\Devices\Grid($apiKey);

        $map = [
            'ipId'          => 'getDevicesByIpId',
            'userId'        => 'getDevicesByUserId',
            'resourceId'    => 'getDevicesByResourceId',
        ];

        $result = $this->idMapIterate($map, $model);

        return $result;
    }

    public function getDeviceDetails(int $id, int $apiKey): array {
        $details = (new \EndoGuard\Models\Device())->getFullDeviceInfoById($id, $apiKey);
        $details['enrichable'] = $this->isEnrichable($apiKey);

        $tsColumns = ['created'];
        \EndoGuard\Utils\Timezones::localizeTimestampsForActiveOperator($tsColumns, $details);

        return $details;
    }

    private function isEnrichable(int $apiKey): bool {
        return (new \EndoGuard\Models\ApiKeys())->attributeIsEnrichable('ua', $apiKey);
    }
}
