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

namespace Sensor\Model\Enriched;

class IpAddressLocalhostEnriched {
    /**
     * @param string[] $domainsCount
     */
    public function __construct(
        public string $ipAddress,
        public string $countryCode = 'N/A',
        public bool $hosting = false,
        public bool $vpn = false,
        public bool $tor = false,
        public bool $relay = false,
        public bool $starlink = false,
        public bool $blocklist = false,
        public ?array $domainsCount = [],
        public ?string $cidr = null,
        public ?bool $alertList = null,
    ) {
    }
}
