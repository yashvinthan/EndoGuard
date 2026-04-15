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

namespace Sensor\Entity;

class LogbookEntity {
    public const ERROR_TYPE_SUCCESS = 0;
    public const ERROR_TYPE_VALIDATION_ERROR = 1;
    public const ERROR_TYPE_CRITICAL_VALIDATION_ERROR = 2;
    public const ERROR_TYPE_CRITICAL_ERROR = 3;
    public const ERROR_TYPE_RATE_LIMIT_EXCEEDED = 4;

    public function __construct(
        public int $apiKeyId,
        public string $ip,
        public ?int $eventId,
        public int $errorType,
        public ?string $errorText,
        public string $raw,
        public ?string $started,
    ) {
    }
}
