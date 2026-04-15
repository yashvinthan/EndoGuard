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

namespace EndoGuard\Models\Context;

abstract class Base extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event';
    protected ?bool $uniqueValues = null;

    abstract protected function getDetails(array $accountIds, int $apiKey): array;

    protected function getRequestParams(array $accountIds, int $apiKey): array {
        [$params, $placeHolders] = $this->getArrayPlaceholders($accountIds);
        $params[':api_key'] = $apiKey;

        return [$params, $placeHolders];
    }

    public function getContext(array $accountIds, int $apiKey): array {
        $unique = $this->uniqueValues;
        $records = $this->getDetails($accountIds, $apiKey);
        $keys = array_keys($records[0] ?? []);
        if (!$keys || !in_array('id', $keys)) {
            return [];
        }

        $groupped = [];

        $userId = 0;

        foreach ($records as $record) {
            $userId = $record['id'];

            if (!isset($groupped[$userId])) {
                $groupped[$userId] = [];
                foreach ($keys as $key) {
                    $groupped[$userId][$key] = [];
                }
            }

            foreach ($keys as $key) {
                if (!$unique || !in_array($record[$key], $groupped[$userId][$key])) {
                    $groupped[$userId][$key][] = $record[$key];
                }
            }
        }

        return $groupped;
    }
}
