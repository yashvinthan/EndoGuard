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

namespace EndoGuard\Controllers\Admin\Totals;

class Navigation extends \EndoGuard\Controllers\Admin\Base\Navigation {
    public function __construct() {
        parent::__construct();

        $this->controller = new Data();
        $this->page = null;
    }

    public function getTimeFrameTotal(): array {
        $ids        = \EndoGuard\Utils\Conversion::getArrayRequestParam('ids');
        $type       = \EndoGuard\Utils\Conversion::getStringRequestParam('type');
        $startDate  = \EndoGuard\Utils\Conversion::getStringRequestParam('startDate');
        $endDate    = \EndoGuard\Utils\Conversion::getStringRequestParam('endDate');

        return $this->controller->getTimeFrameTotal($ids, $type, $startDate, $endDate, $this->apiKey);
    }
}
