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

return [
    'AdminRetentionPolicy_form_title' => 'Data retention',
    'AdminRetentionPolicy_form_title_tooltip' => 'Configure the maximum duration of the recorded information storage.',
    'AdminRetentionPolicy_form_button_save' => 'Update',

    'AdminRetentionPolicy_form_field_policy_label' => 'Retention period',
    'AdminRetentionPolicy_form_field_policy_warning' => 'Caution! Reducing the data retention period will result in the removal of all data belonging to users who haven’t logged in beyond the updated retention period.',
    'AdminRetentionPolicy_changeTimezone_success_message' => 'Data retention period has been changed successfully.',
];
