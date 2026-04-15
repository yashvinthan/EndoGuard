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

namespace Sensor\Service\Debug;

use Sensor\Service\Logger;

class PdoStatementProxy extends \PDOStatement {
    /** @var array<string,mixed> */
    private array $values = [];
    private ?Logger $logger = null;

    protected function __construct(?Logger $logger) {
        $this->logger = $logger;
    }

    public function bindValue(string|int $param, mixed $value, int $type = \PDO::PARAM_STR): bool {
        $this->values[strval($param)] = $value;

        return parent::bindValue($param, $value, $type);
    }

    public function execute(?array $params = null): bool {
        $this->logger?->logQuery($this->queryString, $this->values);

        return parent::execute($params);
    }
}
