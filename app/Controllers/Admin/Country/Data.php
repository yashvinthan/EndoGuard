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

namespace EndoGuard\Controllers\Admin\Country;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function checkIfOperatorHasAccess(int $countryId): bool {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $model = new \EndoGuard\Models\Country();

        return $model->checkAccess($countryId, $apiKey);
    }

    public function getCountryById(int $countryId): array {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $model = new \EndoGuard\Models\Country();

        return $model->getCountryById($countryId, $apiKey);
    }
}
