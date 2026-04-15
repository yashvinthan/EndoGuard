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

namespace EndoGuard\Utils;

class ElapsedDate {
    //https://gist.github.com/fazlurr/473a46d6d2e967119e77b5339dd10bc2
    public static function short(?string $dt): ?string {
        return $dt ? date('d/m/Y H:i:s', strtotime($dt)) : null;
    }

    public static function date(?string $dt): ?string {
        return $dt ? date('d/m/Y', strtotime($dt)) : null;
    }

    public static function long(string $dt): string {
        $ret = [];
        $secs = strtotime($dt);

        $secs = time() - $secs;

        $bit = [
            ' year' => intdiv($secs, 31556926) % 12,
            ' week' => intdiv($secs, 604800) % 52,
            ' day' => intdiv($secs, 86400) % 7,
            ' hour' => intdiv($secs, 3600) % 24,
            ' minute' => intdiv($secs, 60) % 60,
        ];

        foreach ($bit as $k => $v) {
            if ($v > 1) {
                $ret[] = $v . $k . 's';
            }
            if ($v === 1) {
                $ret[] = $v . $k;
            }
        }

        array_splice($ret, count($ret) - 1, 0, 'and');
        $ret[] = 'ago.';

        return join(' ', $ret);
    }
}
