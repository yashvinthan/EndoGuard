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

namespace EndoGuard\Models\Enrichment;

class Isp extends \EndoGuard\Models\Enrichment\Base {
    protected ?int $asn;
    protected ?string $name;
    protected ?string $description;

    public function __construct() {
    }

    public function init(array $data): void {
        $this->asn          = $data['asn'];
        $this->name         = $data['name'];
        $this->description  = $data['description'];
    }

    public function prepareUpdate(): array {
        $params = $this->queryParams();
        unset($params[':asn']);

        $placeholders = array_keys($params);
        $updateString = $this->updateStringByPlaceholders($placeholders);

        return [$params, $updateString];
    }

    public function updateEntityInDb(int $entityId, int $apiKey): void {
        [$params, $updateString] = $this->prepareUpdate();

        $params[':entity_id'] = $entityId;
        $params[':key'] = $apiKey;

        $query = ("
            UPDATE event_isp
            SET {$updateString}
            WHERE
                event_isp.id = :entity_id AND
                event_isp.key = :key
        ");

        $model = new \EndoGuard\Models\Isp();
        $model->execQuery($query, $params);
    }
}
