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

namespace EndoGuard\Controllers\Admin\ISP;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function checkIfOperatorHasAccess(int $ispId, int $apiKey): bool {
        return (new \EndoGuard\Models\Isp())->checkAccess($ispId, $apiKey);
    }

    public function getFullIspInfoById(int $ispId, int $apiKey): array {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $model = new \EndoGuard\Models\Isp();
        $result = $model->getFullIspInfoById($ispId, $apiKey);
        $result['lastseen'] = \EndoGuard\Utils\ElapsedDate::short($result['lastseen']);

        return $result;
    }

    private function getNumberOfIpsByIspId(int $ispId, int $apiKey): int {
        return (new \EndoGuard\Models\Isp())->getIpCountById($ispId, $apiKey);
    }

    public function getIspDetails(int $ispId, int $apiKey): array {
        $result = [];
        $data = $this->getFullIspInfoById($ispId, $apiKey);

        if (array_key_exists('asn', $data)) {
            $result = [
                'asn'           => $data['asn'],
                'total_fraud'   => $data['total_fraud'],
                'total_visit'   => $data['total_visit'],
                'total_account' => $data['total_account'],
                'total_ip'      => $this->getNumberOfIpsByIspId($ispId, $apiKey),
            ];
        }

        return $result;
    }
}
