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

namespace EndoGuard\Controllers\Admin\UserAgent;

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

    public function checkIfOperatorHasAccess(int $userAgentId, int $apiKey): bool {
        return (new \EndoGuard\Models\UserAgent())->checkAccess($userAgentId, $apiKey);
    }

    public function getUserAgentDetails(int $userAgentId, int $apiKey): array {
        return (new \EndoGuard\Models\UserAgent())->getFullUserAgentInfoById($userAgentId, $apiKey);
    }

    public function isEnrichable(int $apiKey): bool {
        return (new \EndoGuard\Models\ApiKeys())->attributeIsEnrichable('ua', $apiKey);
    }
}
