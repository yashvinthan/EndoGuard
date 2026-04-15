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

namespace EndoGuard\Controllers\Admin\Domain;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function proceedPostRequest(): array {
        return match (\EndoGuard\Utils\Conversion::getStringRequestParam('cmd')) {
            'reenrichment' => $this->enrichEntity(),
            default => []
        };
    }

    public function enrichEntity(): array {
        $dataController = new \EndoGuard\Controllers\Admin\Enrichment\Data();
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $enrichmentKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorEnrichmentKeyString();

        $type       = \EndoGuard\Utils\Conversion::getStringRequestParam('type');
        $search     = \EndoGuard\Utils\Conversion::getStringRequestParam('search', true);
        $entityId   = \EndoGuard\Utils\Conversion::getIntRequestParam('entityId', true);

        return $dataController->enrichEntity($type, $search, $entityId, $apiKey, $enrichmentKey);
    }

    public function checkIfOperatorHasAccess(int $domainId, int $apiKey): bool {
        return (new \EndoGuard\Models\Domain())->checkAccess($domainId, $apiKey);
    }

    public function getDomainDetails(int $domainId, int $apiKey): array {
        $result = (new \EndoGuard\Models\Domain())->getFullDomainInfoById($domainId, $apiKey);

        $tsColumns = ['lastseen'];
        \EndoGuard\Utils\Timezones::localizeTimestampsForActiveOperator($tsColumns, $result);

        return $result;
    }

    public function isEnrichable(int $apiKey): bool {
        return (new \EndoGuard\Models\ApiKeys())->attributeIsEnrichable('domain', $apiKey);
    }
}
