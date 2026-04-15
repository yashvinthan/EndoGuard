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

class EnrichedData {
    public function __construct(
        public EmailEnriched|EmailEnrichFailed|null $email,
        public DomainEnriched|DomainNotFoundEnriched|DomainEnrichFailed|null $domain,
        public IpAddressEnriched|IpAddressLocalhostEnriched|IpAddressEnrichFailed|null $ip,
        public IspEnriched|IspEnrichedEmpty|IspEnrichedLocalhost|null $isp, // TODO: move to IP
        public PhoneEnriched|PhoneInvalidEnriched|PhoneEnrichFailed|null $phone,
        public bool $reached,
    ) {
    }
}
