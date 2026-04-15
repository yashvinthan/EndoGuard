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

namespace EndoGuard\Controllers\Admin\Events;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminEvents';

    public function getPageParams(): array {
        $searchPlacholder = $this->f3->get('AdminEvents_search_placeholder');
        $controller = new Data();
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $rulesController = new \EndoGuard\Controllers\Admin\Rules\Data();

        $pageParams = [
            'SEARCH_PLACEHOLDER'            => $searchPlacholder,
            'LOAD_ACCEPT_LANGUAGE_PARSER'   => true,
            'LOAD_UPLOT'                    => true,
            'LOAD_DATATABLE'                => true,
            'LOAD_CHOICES'                  => true,
            'LOAD_AUTOCOMPLETE'             => true,
            'HTML_FILE'                     => 'admin/events.html',
            'JS'                            => 'admin_events.js',
            'EVENT_TYPES'                   => $controller->getAllEventTypes(),
            'DEVICE_TYPES'                  => $controller->getAllDeviceTypes(),
            'RULES'                         => $rulesController->getAllRulesByApiKey($apiKey),
        ];

        return parent::applyPageParams($pageParams);
    }
}
