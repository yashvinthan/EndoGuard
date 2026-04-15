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

namespace EndoGuard\Utils;

class Network {
    public static function sendApiRequest(?array $data, string $path, string $method, ?string $enrichmentKey): \EndoGuard\Entities\HttpResponse {
        $version = \EndoGuard\Utils\VersionControl::versionString();
        $userAgent = \Base::instance()->get('APP_USER_AGENT');
        $userAgent = ($version && $userAgent) ? $userAgent . '/' . $version : $userAgent;

        $url = \EndoGuard\Utils\Variables::getEnrichmentApi() . $path;

        $headers = [
            'User-Agent: ' . $userAgent,
        ];

        if ($enrichmentKey !== null) {
            $headers[] = 'Authorization: Bearer ' . $enrichmentKey;
        }

        $body = null;
        if ($data !== null) {
            $body = json_encode($data);
            if ($body === false) {
                return \EndoGuard\Entities\HttpResponse::failure(null, 'json_encode_failed', []);
            }
        }

        $headers = \EndoGuard\Utils\Http\HeaderUtils::ensureHeader($headers, 'Content-Type', 'application/json');

        if ($data !== null) {
            $headers[] = 'Content-Type: application/json';
            $data = json_encode($data);
        }

        $request = new \EndoGuard\Entities\HttpRequest($url, $method, $headers, $data);
        $client = \EndoGuard\Utils\Http\HttpClient::default();

        return $client->request($request);
    }
}
