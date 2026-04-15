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

namespace EndoGuard\Utils;

class Routes {
    private static function getF3(): \Base {
        return \Base::instance();
    }

    public static function getCurrentRequestOperator(): ?\EndoGuard\Entities\Operator {
        return self::getF3()->get('CURRENT_USER');
    }

    public static function setCurrentRequestOperator(): void {
        self::getF3()->set('CURRENT_USER', self::getCurrentSessionOperator());
    }

    public static function getCurrentSessionOperator(): ?\EndoGuard\Entities\Operator {
        $loggedInOperatorId = \EndoGuard\Utils\Conversion::intValCheckEmpty(self::getF3()->get('SESSION.active_user_id'));

        return $loggedInOperatorId ? \EndoGuard\Entities\Operator::getById($loggedInOperatorId) : null;
    }

    public static function getCurrentRequestApiKey(): ?\EndoGuard\Entities\ApiKey {
        return self::getF3()->get('CURRENT_KEY');
    }

    public static function setCurrentRequestApiKey(): void {
        self::getF3()->set('CURRENT_KEY', self::getCurrentSessionApiKey());
    }

    public static function getCurrentSessionApiKey(): ?\EndoGuard\Entities\ApiKey {
        $keyId = self::getF3()->get('TEST_API_KEY_ID');

        if (!$keyId) {
            $keyId = \EndoGuard\Utils\Conversion::intValCheckEmpty(self::getF3()->get('SESSION.active_key_id'));
        }

        return $keyId ? \EndoGuard\Entities\ApiKey::getById($keyId) : null;
    }

    public static function redirectIfUnlogged(string $targetPage = '/'): void {
        if (!boolval(self::getCurrentRequestOperator())) {
            self::getF3()->reroute($targetPage);
        }
    }

    public static function redirectIfLogged(): void {
        if (boolval(self::getCurrentRequestOperator())) {
            self::getF3()->reroute('/');
        }
    }

    public static function callExtra(string $method, mixed ...$extra): string|array|null {
        $method = \Base::instance()->get('EXTRA_' . $method);

        return $method && is_callable($method) ? $method(...$extra) : null;
    }
}
