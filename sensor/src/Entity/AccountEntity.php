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

class AccountEntity {
    public function __construct(
        public int $apiKeyId,
        public string $userName,
        public string $lastIp,
        public ?string $fullName,
        public ?string $firstName,
        public ?string $lastName,
        public \DateTimeImmutable $lastSeen,
        public ?\DateTimeImmutable $userCreated,
    ) {
    }
}
