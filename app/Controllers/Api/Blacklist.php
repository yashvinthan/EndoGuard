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

namespace EndoGuard\Controllers\Api;

class Blacklist extends Endpoint {
    public function search(): void {
        $value = $this->getBodyProp('value', 'string');

        $model = new \EndoGuard\Models\BlacklistItems();
        $itemFound = $model->searchBlacklistedItem($value, $this->apiKeyId);

        $this->data = [
            'value'         => $value,
            'blacklisted'   => $itemFound,
        ];
    }
}
