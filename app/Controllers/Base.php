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

namespace EndoGuard\Controllers;

abstract class Base {
    protected \Base $f3;

    public function __construct() {
        $this->f3 = \Base::instance();

        $keepSessionInDb = $this->f3->get('KEEP_SESSION_IN_DB') ?? null;
        if (!\EndoGuard\Utils\Database::initConnect(boolval($keepSessionInDb))) {
            $this->f3->error(404);
        }

        //Determine current user
        \EndoGuard\Utils\Routes::setCurrentRequestOperator();
        \EndoGuard\Utils\Routes::setCurrentRequestApiKey();

        //Set CSRF token
        //$rnd = mt_rand();
        //$this->f3->CSRF = sprintf('%s.%s', $this->f3->SEED, $this->f3->hash($rnd));
    }

    /**
     * @todo This is only used at one place. We should remove or generalise it.
     */
    public function validateCsrfToken(): int|bool {
        $csrf = $this->f3->get('SESSION.csrf');
        $token = \EndoGuard\Utils\Conversion::getStringRequestParam('token');

        if (!isset($token) || $token === '' || !isset($csrf) || $csrf === '' || $token !== $csrf) {
            return \EndoGuard\Utils\ErrorCodes::CSRF_ATTACK_DETECTED;
        }

        return false;
    }
}
