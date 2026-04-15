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

namespace EndoGuard\Controllers\Admin\FieldAudits;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminFieldAudits';

    public function getPageParams(): array {
        $pageParams = [
            'SEARCH_PLACEHOLDER'    => $this->f3->get('AdminFieldAuditTrail_search_placeholder'),
            'LOAD_UPLOT'            => true,
            'LOAD_DATATABLE'        => true,
            'LOAD_AUTOCOMPLETE'     => true,
            'HTML_FILE'             => 'admin/fieldAudits.html',
            'JS'                    => 'admin_field_audits.js',
        ];

        return parent::applyPageParams($pageParams);
    }
}
