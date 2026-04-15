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

namespace EndoGuard\Models\Grid\Base;

class Ids extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event';

    private ?int $apiKey = null;

    public function __construct(int $apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
    }

    public function execute(string $query, array $params): array {
        $params[':api_key'] = $this->apiKey;

        $data = $this->execQuery($query, $params);
        $results = array_column($data, 'itemid');

        return count($results) ? $results : [-1];
    }
}
