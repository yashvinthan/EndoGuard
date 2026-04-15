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

namespace EndoGuard\Controllers\Pages;

class Logout extends Base {
    public ?string $page = 'Logout';

    public function getPageParams(): array {
        $pageParams = [
            'HTML_FILE'     => 'logout.html',
            'JS'            => 'user_main.js',
        ];

        if ($this->isPostRequest()) {
            $params = $this->extractRequestParams(['token']);

            $errorCode = \EndoGuard\Utils\Access::CSRFTokenValid($params, $this->f3);

            if (!$errorCode) {
                $this->f3->clear('SESSION');
                session_commit();

                $this->f3->reroute('/');
            }

            $pageParams['ERROR_CODE'] = $errorCode;
        }

        return parent::applyPageParams($pageParams);
    }
}
