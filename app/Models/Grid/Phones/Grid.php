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

namespace EndoGuard\Models\Grid\Phones;

class Grid extends \EndoGuard\Models\Grid\Base\Grid {
    public function __construct(int $apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->idsModel = new Ids($apiKey);
        $this->queryModel = new Query($apiKey);
    }

    public function getPhonesByUserId(int $userId): array {
        $params = [':account_id' => $userId];

        return $this->getGrid($this->idsModel->getPhonesIdsByUserId(), $params);
    }

    protected function convertTimeToUserTimezone(array &$result): void {
        $fields = ['lastseen'];

        \EndoGuard\Utils\Timezones::translateTimezones($result, $fields);
    }
}
