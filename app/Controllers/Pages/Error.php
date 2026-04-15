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

namespace EndoGuard\Controllers\Pages;

class Error extends Base {
    public function getPageParams(array $errorData): array {
        $pageTitle = $this->getInternalPageTitleWithPostfix(strval($errorData['code']));

        return [
            'HTML_FILE' => 'error.html',
            'ERROR_DATA' => $errorData,
            'PAGE_TITLE' => $pageTitle,
        ];
    }
}
