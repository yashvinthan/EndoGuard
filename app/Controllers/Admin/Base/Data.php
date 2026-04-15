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

namespace EndoGuard\Controllers\Admin\Base;

abstract class Data extends \EndoGuard\Controllers\Base {
    protected function idMapIterate(array $map, object $model, ?string $default = 'getAll', mixed ...$extra): array {
        $result = [];

        foreach ($map as $param => $method) {
            $id = \EndoGuard\Utils\Conversion::getIntRequestParam($param, true);
            if ($id !== null) {
                $result = $model->$method($id, ...$extra);
            }

            if ($result) {
                break;
            }
        }

        if (!$result && $default !== null) {
            $result = $model->$default(...$extra);
        }

        return $result;
    }

    protected function extractRequestParams(array $params): array {
        $result = [];

        foreach ($params as $key) {
            $result[$key] = \Base::instance()->get('REQUEST.' . $key);
        }

        return $result;
    }
}
