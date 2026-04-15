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

namespace EndoGuard\Models\Grid\FieldAuditTrail;

class Ids extends \EndoGuard\Models\Grid\Base\Ids {
    public function getDataIdsByUserId(): string {
        return (
            'SELECT
                event_field_audit_trail.id AS itemid
            FROM event_field_audit_trail
            WHERE
                event_field_audit_trail.account_id = :account_id AND
                event_field_audit_trail.key = :api_key'
        );
    }

    public function getDataIdsByFieldId(): string {
        return (
            'SELECT
                event_field_audit_trail.id AS itemid
            FROM event_field_audit_trail
            WHERE
                event_field_audit_trail.field_id = :field_id AND
                event_field_audit_trail.key = :api_key'
        );
    }

    public function getDataIdsByResourceId(): string {
        return (
            'SELECT
                event_field_audit_trail.id AS itemid
            FROM event_field_audit_trail
            LEFT JOIN event
            ON (event_field_audit_trail.event_id = event.id)
            WHERE
                event.url = :resource_id AND
                event_field_audit_trail.key = :api_key'
        );
    }
}
