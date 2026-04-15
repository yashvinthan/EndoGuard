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

namespace Sensor\Model\Enriched;

class DomainNotFoundEnriched {
    public function __construct(
        public string $domain,
        public bool $blockdomains,
        public bool $disposableDomains,
        public bool $freeEmailProvider,
        public ?string $creationDate,
        public ?string $expirationDate,
        public ?int $returnCode,
        public bool $disabled,
        public ?string $closestSnapshot,
        public bool $mxRecord,
    ) {
    }
}
