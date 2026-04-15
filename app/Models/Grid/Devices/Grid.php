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

namespace EndoGuard\Models\Grid\Devices;

class Grid extends \EndoGuard\Models\Grid\Base\Grid {
    public function __construct(int $apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->idsModel = new Ids($apiKey);
        $this->queryModel = new Query($apiKey);
    }

    public function getDevicesByIpId(int $ipId): array {
        $params = [':ip_id' => $ipId];

        return $this->getGrid($this->idsModel->getDevicesIdsByIpId(), $params);
    }

    public function getDevicesByUserId(int $userId): array {
        $params = [':account_id' => $userId];

        return $this->getGrid($this->idsModel->getDevicesIdsByUserId(), $params);
    }

    public function getDevicesByResourceId(int $resourceId): array {
        $params = [':resource_id' => $resourceId];

        return $this->getGrid($this->idsModel->getDevicesIdsByResourceId(), $params);
    }

    public function getAll(): array {
        return $this->getGrid();
    }

    protected function calculateCustomParams(array &$result): void {
        \EndoGuard\Utils\Enrichment::applyDeviceParams($result);
    }

    protected function convertTimeToUserTimezone(array &$result): void {
        $fields = ['created'];

        \EndoGuard\Utils\Timezones::translateTimezones($result, $fields);
    }
}
