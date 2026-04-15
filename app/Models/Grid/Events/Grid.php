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

namespace EndoGuard\Models\Grid\Events;

class Grid extends \EndoGuard\Models\Grid\Base\Grid {
    public function __construct(int $apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->idsModel = new Ids($apiKey);
        $this->queryModel = new Query($apiKey);
    }

    public function getEventsByUserId(int $userId): array {
        $ids = ['userId' => $userId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByIspId(int $ispId): array {
        $ids = ['ispId' => $ispId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByDomainId(int $domainId): array {
        $ids = ['domainId' => $domainId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByDeviceId(int $deviceId): array {
        $ids = ['deviceId' => $deviceId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByResourceId(int $resourceId): array {
        $ids = ['resourceId' => $resourceId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByCountryId(int $countryId): array {
        $ids = ['countryId' => $countryId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByIpId(int $ipId): array {
        $ids = ['ipId' => $ipId];

        return $this->getGrid(null, $ids);
    }

    public function getEventsByFieldId(int $fieldId): array {
        $ids = ['fieldId' => $fieldId];

        return $this->getGrid(null, $ids);
    }

    public function getAll(): array {
        return $this->getGrid();
    }

    protected function calculateCustomParams(array &$result): void {
        \EndoGuard\Utils\Enrichment::calculateIpType($result);
        \EndoGuard\Utils\Enrichment::applyDeviceParams($result);
    }

    protected function convertTimeToUserTimezone(array &$result): void {
        $fields = ['time', 'lastseen', 'session_max_t', 'session_min_t', 'score_updated_at'];

        \EndoGuard\Utils\Timezones::translateTimezones($result, $fields);
    }
}
