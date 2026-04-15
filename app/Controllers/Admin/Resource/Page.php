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

namespace EndoGuard\Controllers\Admin\Resource;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminResource';

    public function getPageParams(): array {
        $dataController = new Data();
        $resourceId = \EndoGuard\Utils\Conversion::getIntUrlParam('resourceId');
        $hasAccess = $dataController->checkIfOperatorHasAccess($resourceId);

        if (!$hasAccess) {
            $this->f3->error(404);
        }

        $resource = $dataController->getResourceById($resourceId);

        $pageTitle = $resource['url'];
        if ($resource['title']) {
            $pageTitle .= sprintf(' (%s)', $resource['title']);
        }

        $pageTitle = $this->getInternalPageTitleWithPostfix($pageTitle);

        $pageParams = [
            'LOAD_DATATABLE'                => true,
            'LOAD_JVECTORMAP'               => true,
            'LOAD_UPLOT'                    => true,
            'LOAD_ACCEPT_LANGUAGE_PARSER'   => true,
            'LOAD_AUTOCOMPLETE'             => true,
            'HTML_FILE'                     => 'admin/resource.html',
            'RESOURCE'                      => $resource,
            'PAGE_TITLE'                    => $pageTitle,
            'JS'                            => 'admin_resource.js',
        ];

        return parent::applyPageParams($pageParams);
    }
}
