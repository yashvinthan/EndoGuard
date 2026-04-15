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

namespace EndoGuard\Controllers\Admin\Enrichment;

class Navigation extends \EndoGuard\Controllers\Admin\Base\Navigation {
    public function __construct() {
        parent::__construct();

        $this->controller = new Data();
        $this->page = null;
    }

    public function enrichEntity(): array {
        $enrichmentKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorEnrichmentKeyString();

        $type       = \EndoGuard\Utils\Conversion::getStringRequestParam('type');
        $search     = \EndoGuard\Utils\Conversion::getStringRequestParam('search', true);
        $entityId   = \EndoGuard\Utils\Conversion::getIntRequestParam('entityId', true);

        return $this->controller->enrichEntity($type, $search, $entityId, $this->apiKey, $enrichmentKey);
    }

    public function getNotCheckedEntitiesCount(): array {
        return $this->apiKey ? $this->controller->getNotCheckedEntitiesCount($this->apiKey) : [];
    }
}
