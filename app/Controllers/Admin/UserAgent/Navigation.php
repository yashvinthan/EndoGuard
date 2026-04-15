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

namespace EndoGuard\Controllers\Admin\UserAgent;

class Navigation extends \EndoGuard\Controllers\Admin\Base\Navigation {
    public function __construct() {
        parent::__construct();

        $this->controller = new Data();
        $this->page = new Page();
    }

    public function getUserAgentDetails(): array {
        $userAgentId = \EndoGuard\Utils\Conversion::getIntRequestParam('userAgentId');
        $hasAccess = $this->controller->checkIfOperatorHasAccess($userAgentId, $this->apiKey);
        if (!$hasAccess) {
            $this->f3->error(404);
        }

        return $this->controller->getUserAgentDetails($userAgentId, $this->apiKey);
    }
}
