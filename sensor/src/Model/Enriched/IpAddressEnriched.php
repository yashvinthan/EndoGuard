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

class IpAddressEnriched {
    /**
     * @param string[] $domainsCount
     */
    public function __construct(
        public string $ipAddress,
        public string $countryCode,
        public bool $hosting,
        public bool $vpn,
        public bool $tor,
        public bool $relay,
        public bool $starlink,
        public bool $blocklist,
        public array $domainsCount,
        public string $cidr,
        public ?bool $alertList,
    ) {
    }
}
