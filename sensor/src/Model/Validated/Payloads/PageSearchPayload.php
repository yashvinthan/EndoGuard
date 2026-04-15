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

namespace Sensor\Model\Validated\Payloads;

class PageSearchPayload extends \Sensor\Model\Validated\BaseArray {
    public function __construct(mixed $value) {
        $this->requiredFields = [
            'field_id',
            'value',
        ];

        $this->optionalFields = [
            'field_name',
        ];

        $this->set = false;
        $this->dump = true;

        parent::__construct($value);
    }
}
