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

namespace EndoGuard\Models\Grid\Emails;

class Grid extends \EndoGuard\Models\Grid\Base\Grid {
    public function __construct(int $apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->idsModel = new Ids($apiKey);
        $this->queryModel = new Query($apiKey);
    }

    public function getEmailsByUserId(int $userId): array {
        $params = [':account_id' => $userId];

        return $this->getGrid($this->idsModel->getEmailsIdsByUserId(), $params);
    }

    protected function calculateCustomParams(array &$result): void {
        \EndoGuard\Utils\Enrichment::calculateEmailReputation($result);
    }

    protected function convertTimeToUserTimezone(array &$result): void {
        $fields = ['lastseen'];

        \EndoGuard\Utils\Timezones::translateTimezones($result, $fields);
    }
}
