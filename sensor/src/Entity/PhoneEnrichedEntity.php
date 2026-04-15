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

class PhoneEnrichedEntity {
    public function __construct(
        public int $accountId,
        public int $apiKeyId,
        public string $phoneNumber,
        public ?string $hash,
        public int $profiles,
        public ?int $countryId,
        public int $callingCountryCode,
        public string $nationalFormat,
        public bool $invalid,
        public ?string $validationErrors,
        public ?string $carrierName,
        public string $type,
        public ?bool $alertList,
        public bool $fraudDetected,
        public bool $checked,
        public \DateTimeImmutable $lastSeen,
    ) {
    }
}
