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

namespace Sensor\Model\Validated\Payloads;

class EmailChangePayload extends \Sensor\Model\Validated\BaseArray {
    public function __construct(mixed $value) {
        $this->requiredFields = [
            'new_value',
        ];

        $this->optionalFields = [
            'old_value',
        ];

        $this->set = false;
        $this->dump = true;

        parent::__construct($value);
    }
}
