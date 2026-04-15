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

namespace EndoGuard\Controllers\Admin\Totals;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function getTimeFrameTotal(array $ids, string $type, string $startDate, string $endDate, int $apiKey): array {
        $processErrorMessage = ['ERROR_CODE' => \EndoGuard\Utils\ErrorCodes::TOTALS_INVALID_TYPE];

        if (!in_array($type, ['ip', 'isp', 'domain', 'country', 'resource', 'field', 'userAgent'])) {
            return $processErrorMessage;
        }

        $model = null;

        switch ($type) {
            case 'ip':
                $model = new \EndoGuard\Models\Ip();
                break;
            case 'isp':
                $model = new \EndoGuard\Models\Isp();
                break;
            case 'domain':
                $model = new \EndoGuard\Models\Domain();
                break;
            case 'country':
                $model = new \EndoGuard\Models\Country();
                break;
            case 'resource':
                $model = new \EndoGuard\Models\Resource();
                break;
            case 'field':
                $model = new \EndoGuard\Models\FieldAudit();
                break;
            case 'userAgent':
                $model = new \EndoGuard\Models\UserAgent();
                break;
        }

        $totals = $model->getTimeFrameTotal($ids, $startDate, $endDate, $apiKey);

        return [
            'SUCCESS_MESSAGE'   => $this->f3->get('AdminTotals_success_message'),
            'totals'            => $totals,
        ];
    }
}
