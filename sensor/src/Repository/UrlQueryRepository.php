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

namespace Sensor\Repository;

use Sensor\Entity\UrlQueryEntity;
use Sensor\Model\Validated\Timestamp;

class UrlQueryRepository {
    public function __construct(
        private \PDO $pdo,
    ) {
    }

    public function insert(
        UrlQueryEntity $query,
        int $urlId,
    ): int {
        $sql = 'INSERT INTO event_url_query
            (key, url, query, lastseen, created)
        VALUES
            (:key, :url, :query, :lastseen, :created)
        ON CONFLICT (key, url, query) DO UPDATE
        SET
            lastseen = EXCLUDED.lastseen
        RETURNING id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':key', $query->apiKeyId);
        $stmt->bindValue(':url', $urlId);
        $stmt->bindValue(':query', $query->query);
        $stmt->bindValue(':lastseen', $query->lastSeen->format(Timestamp::EVENTFORMAT));
        $stmt->bindValue(':created', $query->lastSeen->format(Timestamp::EVENTFORMAT));

        $stmt->execute();

        /** @var array{id: int} $result */
        $result = $stmt->fetch();

        return $result['id'];
    }
}
