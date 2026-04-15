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

namespace Sensor\Service;

class Constants {
    public const PAGE_VIEW_EVENT_TYPE_ID = 1;
    public const PAGE_EDIT_EVENT_TYPE_ID = 2;
    public const PAGE_DELETE_EVENT_TYPE_ID = 3;
    public const PAGE_SEARCH_EVENT_TYPE_ID = 4;
    public const ACCOUNT_LOGIN_EVENT_TYPE_ID = 5;
    public const ACCOUNT_LOGOUT_EVENT_TYPE_ID = 6;
    public const ACCOUNT_LOGIN_FAIL_EVENT_TYPE_ID = 7;
    public const ACCOUNT_REGISTRATION_EVENT_TYPE_ID = 8;
    public const ACCOUNT_EMAIL_CHANGE_EVENT_TYPE_ID = 9;
    public const ACCOUNT_PASSWORD_CHANGE_EVENT_TYPE_ID = 10;
    public const ACCOUNT_EDIT_EVENT_TYPE_ID = 11;
    public const PAGE_ERROR_EVENT_TYPE_ID = 12;
    public const FIELD_EDIT_EVENT_TYPE_ID = 13;
}
