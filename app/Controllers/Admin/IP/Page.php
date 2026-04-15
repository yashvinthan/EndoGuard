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

namespace EndoGuard\Controllers\Admin\IP;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminIp';

    public function getPageParams(): array {
        $dataController = new Data();
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $ipId = \EndoGuard\Utils\Conversion::getIntUrlParam('ipId');
        $hasAccess = $dataController->checkIfOperatorHasAccess($ipId, $apiKey);

        if (!$hasAccess) {
            $this->f3->error(404);
        }

        $ipAddr = $dataController->getFullIpInfoById($ipId, $apiKey);
        $pageTitle = $this->getInternalPageTitleWithPostfix($ipAddr['ip']);
        $isEnrichable = $dataController->isEnrichable($apiKey);

        $pageParams = [
            'LOAD_DATATABLE'                => true,
            'LOAD_AUTOCOMPLETE'             => true,
            'HTML_FILE'                     => 'admin/ip.html',
            'PAGE_TITLE'                    => $pageTitle,
            'IP'                            => $ipAddr,
            'LOAD_UPLOT'                    => true,
            'LOAD_ACCEPT_LANGUAGE_PARSER'   => true,
            'JS'                            => 'admin_ip.js',
            'IS_ENRICHABLE'                 => $isEnrichable,
        ];

        if ($this->isPostRequest()) {
            $operationResponse = $dataController->proceedPostRequest();

            $pageParams = array_merge($pageParams, $operationResponse);
            // recall ip data
            $pageParams['IP'] = $dataController->getFullIpInfoById($ipId, $apiKey);
        }

        return parent::applyPageParams($pageParams);
    }
}
