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

namespace EndoGuard\Models\Grid\Emails;

class Ids extends \EndoGuard\Models\Grid\Base\Ids {
    public function getEmailsIdsByUserId(): string {
        return (
            'SELECT DISTINCT
                event_email.id AS itemid
            FROM event_email
            WHERE
                event_email.key = :api_key AND
                event_email.account_id = :account_id'
        );
    }
}
