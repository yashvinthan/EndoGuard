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

class Base {
    public function queryParams(): array {
        $properties = get_object_vars($this);
        $modifiedArray = [];
        foreach ($properties as $key => $value) {
            $modifiedArray[':' . $key] = $value;
        }

        return $modifiedArray;
    }

    public function slimIds(array $ids): array {
        $filtered = array_filter($ids, static function ($value): bool {
            return $value !== null;
        });

        return array_unique($filtered);
    }

    public function updateStringByPlaceholders(array $placeholders): string {
        $transformed = array_map(static function ($item): string {
            $key = ltrim($item, ':');

            return "{$key} = {$item}";
        }, $placeholders);
        return implode(', ', $transformed);
    }

    public function validateDate(string $date, string $format = 'Y-m-d'): bool {
        $datetime = \DateTime::createFromFormat($format, $date);

        return $datetime && $datetime->format($format) === $date;
    }

    public function validateDates(array $dates): bool {
        foreach ($dates as $date) {
            if ($date !== null && !$this->validateDate($date)) {
                return false;
            }
        }

        return true;
    }

    public function validateCidr(string $cidr): bool {
        $parts = explode('/', $cidr);
        if (count($parts) !== 2) {
            return false;
        }

        $ipAddr = $parts[0];
        $netmask = \EndoGuard\Utils\Conversion::intVal($parts[1], -1);

        if ($netmask < 0) {
            return false;
        }

        $ipType = \EndoGuard\Utils\Conversion::filterIpGetType($ipAddr);

        return $ipType === 4 ? $netmask <= 32 : ($ipType === 6 ? $netmask <= 128 : false);
    }
}
