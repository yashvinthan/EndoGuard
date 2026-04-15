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

namespace EndoGuard\Controllers\Admin\Country;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminCountry';

    public function getPageParams(): array {
        $dataController = new Data();
        $countryId = \EndoGuard\Utils\Conversion::getIntUrlParam('countryId');

        $hasAccess = $dataController->checkIfOperatorHasAccess($countryId);

        if (!$hasAccess) {
            $this->f3->error(404);
        }

        $country = $dataController->getCountryById($countryId);
        $pageTitle = $this->getInternalPageTitleWithPostfix($country['value']);

        $pageParams = [
            'LOAD_DATATABLE'                => true,
            'LOAD_UPLOT'                    => true,
            'LOAD_AUTOCOMPLETE'             => true,
            'LOAD_ACCEPT_LANGUAGE_PARSER'   => true,
            'HTML_FILE'                     => 'admin/country.html',
            'COUNTRY'                       => $country,
            'PAGE_TITLE'                    => $pageTitle,
            'JS'                            => 'admin_country.js',
        ];

        return parent::applyPageParams($pageParams);
    }
}
