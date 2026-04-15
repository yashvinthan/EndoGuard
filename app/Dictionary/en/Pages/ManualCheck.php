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
    'AdminManualCheck_page_title' => 'Manual check',

    'AdminManualCheck_form_title' => 'Manual check',
    'AdminManualCheck_form_field_type_label' => 'Type',
    'AdminManualCheck_form_types' => [
        'ip' => 'IP',
        'email' => 'Email',
        'domain' => 'Domain',
        'phone' => 'Phone',
    ],
    'AdminManualCheck_form_field_search_query_label' => 'Search query',
    'AdminManualCheck_form_button_search' => 'Search',

    'AdminManualCheck_result_title' => '%s result',

    'AdminManualCheck_key_overwrites' => [
        'ip' => 'IP',
        'email' => 'Email',
        'domain' => 'Domain',
        'phone' => 'Phone',
        'geo_ip' => 'Geo IP',
        'geo_html' => 'Geo HTML',
        'iso_country_code' => 'ISO country code',
        'asn' => 'ASN',
        'tor' => 'TOR',
        'vpn' => 'VPN',
        'mx_record' => 'MX record',
        'domains_count' => 'Domains hosting',
        'cidr' => 'CIDR',
    ],

    'AdminManualCheck_history_title' => 'History',
];
