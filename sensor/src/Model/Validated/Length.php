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

namespace Sensor\Model\Validated;

class Length extends Base {
    public string $value;

    public function __construct(string $value, string $type, int $limit = 100) {
        parent::__construct($value, $type);

        if (strlen($value) > $limit) {
            $this->value = substr($value, 0, $limit);
        } else {
            // even if empty string!
            $this->value = $value;
        }

        $this->invalid = strlen($value) > $limit;
    }

    public function isEmpty(): bool {
        return $this->value === '';
    }
}
