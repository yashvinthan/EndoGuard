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

namespace EndoGuard\Controllers\Admin\FieldAudit;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function checkIfOperatorHasAccess(int $fieldId): bool {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $model = new \EndoGuard\Models\FieldAudit();

        return $model->checkAccess($fieldId, $apiKey);
    }

    public function getFieldById(int $fieldId): array {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();

        $model = new \EndoGuard\Models\FieldAudit();
        $result = $model->getFieldById($fieldId, $apiKey);
        $result['lastseen'] = \EndoGuard\Utils\ElapsedDate::short($result['lastseen']);
        $result['created'] = \EndoGuard\Utils\ElapsedDate::short($result['created']);

        return $result;
    }
}
