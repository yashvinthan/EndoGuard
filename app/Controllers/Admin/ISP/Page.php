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

namespace EndoGuard\Controllers\Admin\ISP;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminIsp';

    public function getPageParams(): array {
        $dataController = new Data();
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $ispId = \EndoGuard\Utils\Conversion::getIntUrlParam('ispId');
        $hasAccess = $dataController->checkIfOperatorHasAccess($ispId, $apiKey);

        if (!$hasAccess) {
            $this->f3->error(404);
        }

        $isp = $dataController->getFullIspInfoById($ispId, $apiKey);
        $pageTitle = $this->getInternalPageTitleWithPostfix(strval($isp['asn']));

        $pageParams = [
            'LOAD_DATATABLE'                => true,
            'LOAD_JVECTORMAP'               => true,
            'LOAD_AUTOCOMPLETE'             => true,
            'HTML_FILE'                     => 'admin/isp.html',
            'ISP'                           => $isp,
            'PAGE_TITLE'                    => $pageTitle,
            'LOAD_UPLOT'                    => true,
            'LOAD_ACCEPT_LANGUAGE_PARSER'   => true,
            'JS'                            => 'admin_isp.js',
        ];

        return parent::applyPageParams($pageParams);
    }
}
