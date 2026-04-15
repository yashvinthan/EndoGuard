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

namespace EndoGuard\Crons;

class LogbookRotation extends Base {
    public function process(): void {
        $this->addLog('Start logbook rotation.');

        $model = new \EndoGuard\Models\ApiKeys();
        $keys = $model->getAllApiKeyIds();
        // rotate events for unauthorized requests
        $keys[] = ['id' => null];

        $model = new \EndoGuard\Models\Logbook();
        $cnt = 0;
        foreach ($keys as $key) {
            $cnt += $model->rotateRequests($key['id']);
        }

        $this->addLog(sprintf('Deleted %s events for %s keys in logbook.', $cnt, count($keys)));
    }
}
