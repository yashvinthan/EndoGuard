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

namespace Sensor\Repository;

use Sensor\Entity\PayloadEntity;
use Sensor\Model\Validated\Timestamp;

class PayloadRepository {
    public function __construct(
        private \PDO $pdo,
    ) {
    }

    public function insert(PayloadEntity $payload): int {
        $sql = 'INSERT INTO event_payload
                (key, created, payload)
            VALUES
                (:key, :created, :payload)
            RETURNING id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':key', $payload->apiKeyId);
        $stmt->bindValue(':created', $payload->lastSeen->format(Timestamp::EVENTFORMAT));
        $stmt->bindValue(':payload', $payload->payload);
        $stmt->execute();

        $result = $stmt->fetch();

        return $result['id'];
    }
}
