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

namespace EndoGuard\Controllers\Admin\User;

class Page extends \EndoGuard\Controllers\Admin\Base\Page {
    public ?string $page = 'AdminUser';

    public function getPageParams(): array {
        $dataController = new Data();
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $userId = \EndoGuard\Utils\Conversion::getIntUrlParam('userId');
        $hasAccess = $dataController->checkIfOperatorHasAccess($userId, $apiKey);

        if (!$hasAccess) {
            $this->f3->error(404);
        }

        [$scheduledForDeletion, $errorCode] = $dataController->getScheduledForDeletion($userId, $apiKey);
        $user = $dataController->getUserById($userId, $apiKey);

        $pageTitle      = $this->getInternalPageTitleWithPostfix($user['page_title']);
        $enrichmentOn   = $dataController->checkEnrichmentAvailability();

        $pageParams = [
            'LOAD_DATATABLE'                => true,
            'LOAD_JVECTORMAP'               => true,
            'LOAD_ACCEPT_LANGUAGE_PARSER'   => true,
            'HTML_FILE'                     => 'admin/user.html',
            'LOAD_UPLOT'                    => true,
            'LOAD_AUTOCOMPLETE'             => true,
            'USER'                          => $user,
            'SCHEDULED_FOR_DELETION'        => $scheduledForDeletion,
            'PAGE_TITLE'                    => $pageTitle,
            'ENRICHMENT'                    => $enrichmentOn,
            'JS'                            => 'admin_user.js',
            'ERROR_CODE'                    => $errorCode,
            'SEARCH_PLACEHOLDER'            => $this->f3->get('AdminFieldAuditTrail_search_placeholder'),
        ];

        if ($this->isPostRequest()) {
            $operationResponse = $dataController->proceedPostRequest();

            $pageParams = array_merge($pageParams, $operationResponse);
            // recall user data
            $pageParams['USER'] = $dataController->getUserById($userId, $apiKey);
        }

        [$scheduledForBlacklist, $errorCode] = $dataController->getScheduledForBlacklist($userId, $apiKey);
        if ($scheduledForBlacklist) {
            $this->f3->set('SESSION.extra_message_code', $errorCode ?? \EndoGuard\Utils\ErrorCodes::USER_BLACKLISTING_QUEUED);
        }

        return parent::applyPageParams($pageParams);
    }
}
