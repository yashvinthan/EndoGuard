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

namespace EndoGuard\Controllers\Admin\ReviewQueue;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminReviewQueue';

    public function getPageParams(): array {
        $searchPlacholder = $this->f3->get('AdminReviewQueue_search_placeholder');
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $rulesController = new \EndoGuard\Controllers\Admin\Rules\Data();

        $pageParams = [
            'SEARCH_PLACEHOLDER'    => $searchPlacholder,
            'LOAD_UPLOT'            => true,
            'LOAD_DATATABLE'        => true,
            'LOAD_CHOICES'          => true,
            'LOAD_AUTOCOMPLETE'     => true,
            'HTML_FILE'             => 'admin/reviewQueue.html',
            'JS'                    => 'admin_review_queue.js',
            'RULES'                 => $rulesController->getAllRulesByApiKey($apiKey),
        ];

        return parent::applyPageParams($pageParams);
    }
}
