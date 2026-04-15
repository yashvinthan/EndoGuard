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

namespace EndoGuard\Models;

abstract class BaseSql extends \DB\SQL\Mapper {
    protected ?\Base $f3 = null;
    protected int $DB_TABLE_TTL = 0;
    protected ?string $DB_TABLE_NAME = null;
    protected ?array $DB_TABLE_FIELDS = null;

    public function __construct() {
        $this->f3 = \Base::instance();

        if ($this->DB_TABLE_NAME) {
            $database = $this->getDatabaseConnection();
            parent::__construct($database, $this->DB_TABLE_NAME, $this->DB_TABLE_FIELDS, $this->DB_TABLE_TTL);
        }
    }

    protected function getDatabaseConnection(): ?\DB\SQL {
        return \EndoGuard\Utils\Database::getDb();
    }

    public function printLog(): void {
        echo \EndoGuard\Utils\Database::getDb()->log();
    }

    public function getArrayPlaceholders(array $ids, string $postfix = ''): array {
        $params = [];
        $placeHolders = [];

        $postfix = $postfix !== '' ? '_' . $postfix : '';

        foreach ($ids as $i => $id) {
            $key = sprintf(':item_id_%s%s', $i, $postfix);
            $placeHolders[] = $key;
            $params[$key] = $id;
        }

        $placeHolders = implode(', ', $placeHolders);

        return [$params, $placeHolders];
    }

    public function execQuery(string $query, ?array $params): array|int|null {
        return $this->getDatabaseConnection()->exec($query, $params);
    }
}
