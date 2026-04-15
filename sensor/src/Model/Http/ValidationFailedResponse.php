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

namespace Sensor\Model\Http;

use Sensor\Exception\ValidationException;

class ValidationFailedResponse extends ErrorResponse {
    public function __construct(
        private ValidationException $exception,
    ) {
        parent::__construct('Validation error', 400);
    }

    public function __toString(): string {
        return sprintf(
            'Validation error: "%s" for key "%s"',
            $this->exception->getMessage(),
            $this->exception->getKey(),
        );
    }
}
