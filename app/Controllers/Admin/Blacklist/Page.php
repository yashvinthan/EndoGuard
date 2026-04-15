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

namespace EndoGuard\Controllers\Admin\Blacklist;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminBlacklist';

    public function getPageParams(): array {
        $searchPlacholder = $this->f3->get('AdminBlacklist_search_placeholder');

        $pageParams = [
            'SEARCH_PLACEHOLDER'    => $searchPlacholder,
            'LOAD_UPLOT'            => true,
            'LOAD_DATATABLE'        => true,
            'LOAD_CHOICES'          => true,
            'LOAD_AUTOCOMPLETE'     => true,
            'HTML_FILE'             => 'admin/blacklist.html',
            'JS'                    => 'admin_blacklist.js',
            'ENTITY_TYPES'          => \EndoGuard\Utils\Constants::get()->ENTITY_TYPES,
        ];

        return parent::applyPageParams($pageParams);
    }
}
