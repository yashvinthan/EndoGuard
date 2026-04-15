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

namespace Sensor\Model\Config;

class Config implements \JsonSerializable {
    public function __construct(
        public DatabaseConfig $databaseConfig,
        public ?string $enrichmentApiUrl = null,
        #[\SensitiveParameter]
        public ?string $enrichmentApiKey = null,
        public ?string $scoreApiUrl = null,
        public ?string $userAgent = null,
        public bool $debugLog = false,
        public bool $allowEmailPhone = false,
        public int $leakyBucketRps = 5,
        public int $leakyBucketWindow = 5,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array {
        return [
            'databaseConfig' => $this->databaseConfig,
            'enrichmentApiUrl' => $this->enrichmentApiUrl,
            'scoreApiUrl' => $this->scoreApiUrl,
            'debugLog' => $this->debugLog,
        ];
    }
}
