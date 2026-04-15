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

namespace EndoGuard\Models\Grid\Ips;

class Grid extends \EndoGuard\Models\Grid\Base\Grid {
    public function __construct(int $apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->idsModel = new Ids($apiKey);
        $this->queryModel = new Query($apiKey);
    }

    public function getIpsByUserId(int $userId): array {
        $params = [':account_id' => $userId];

        return $this->getGrid($this->idsModel->getIpsIdsByUserId(), $params);
    }

    public function getIpsByIspId(int $ispId): array {
        $params = [':isp_id' => $ispId];

        return $this->getGrid($this->idsModel->getIpsIdsByIspId(), $params);
    }

    public function getIpsByDomainId(int $domainId): array {
        $params = [':domain_id' => $domainId];

        return $this->getGrid($this->idsModel->getIpsIdsByDomainId(), $params);
    }

    public function getIpsByCountryId(int $countryId): array {
        $params = [':country_id' => $countryId];

        return $this->getGrid($this->idsModel->getIpsIdsByCountryId(), $params);
    }

    public function getIpsByDeviceId(int $deviceId): array {
        $params = [':device_id' => $deviceId];

        return $this->getGrid($this->idsModel->getIpsIdsByDeviceId(), $params);
    }

    public function getIpsByResourceId(int $resourceId): array {
        $params = [':resource_id' => $resourceId];

        return $this->getGrid($this->idsModel->getIpsIdsByResourceId(), $params);
    }

    public function getIpsByFieldId(int $fieldId): array {
        $params = [':field_id' => $fieldId];

        return $this->getGrid($this->idsModel->getIpsIdsByFieldId(), $params);
    }

    public function getAll(): array {
        return $this->getGrid();
    }

    protected function calculateCustomParams(array &$result): void {
        \EndoGuard\Utils\Enrichment::calculateIpType($result);
    }
}
