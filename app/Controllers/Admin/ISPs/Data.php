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

namespace EndoGuard\Controllers\Admin\ISPs;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getList(int $apiKey): array {
        $result = [];
        $model = new \EndoGuard\Models\Grid\Isps\Grid($apiKey);

        $map = [
            'userId'        => 'getIspsByUserId',
            'domainId'      => 'getIspsByDomainId',
            'countryId'     => 'getIspsByCountryId',
            'resourceId'    => 'getIspsByResourceId',
            'fieldId'       => 'getIspsByFieldId',
        ];

        $result = $this->idMapIterate($map, $model);

        $ids = array_column($result['data'], 'id');
        if ($ids) {
            $model = new \EndoGuard\Models\Isp();
            $model->updateTotalsByEntityIds($ids, $apiKey);
            $result['data'] = $model->refreshTotals($result['data'], $apiKey);
        }
        return $result;
    }
}
