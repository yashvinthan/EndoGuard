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

namespace EndoGuard\Controllers\Admin\FieldAuditTrail;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getList(int $apiKey): array {
        $result = [];
        $model = new \EndoGuard\Models\Grid\FieldAuditTrail\Grid($apiKey);

        $map = [
            'userId'        => 'getDataByUserId',
            'resourceId'    => 'getDataByResourceId',
            'fieldId'       => 'getDataByFieldId',
        ];

        $result = $this->idMapIterate($map, $model);

        $ids = array_column($result['data'], 'field_audit_id');
        if ($ids) {
            $model = new \EndoGuard\Models\FieldAudit();
            $model->updateTotalsByEntityIds($ids, $apiKey);
            $result['data'] = $model->refreshTotals($result['data'], $apiKey);
        }

        return $result;
    }

    public function getFieldEventDetails(int $id, int $apiKey): array {
        $result = [];
        $model = new \EndoGuard\Models\FieldAuditTrail();
        $trailResult = $model->getById($id, $apiKey);

        if ($trailResult) {
            $eventId = $trailResult['event_id'];
            $controller = new \EndoGuard\Controllers\Admin\Events\Data();
            $result = $controller->getEventDetails($eventId, $apiKey);

            if ($result) {
                $result = $controller->extendPayload($result, $apiKey);
            }
        }

        return $result;
    }
}
