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

namespace EndoGuard\Utils\Http;

final class CurlTransport implements \EndoGuard\Interfaces\HttpTransportInterface {
    public function isAvailable(): bool {
        return function_exists('curl_init');
    }

    public function request(\EndoGuard\Entities\HttpRequest $request): \EndoGuard\Entities\HttpResponse {
        $ch = curl_init($request->url());
        if ($ch === false) {
            $result = \EndoGuard\Entities\HttpResponse::failure(null, 'curl_init_failed', []);

            return $result;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $request->connectTimeoutSeconds(),
            CURLOPT_TIMEOUT => $request->timeoutSeconds(),
            CURLOPT_HTTPHEADER => $request->headers(),
        ];

        if (!$request->sslVerify()) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        $methodUpper = strtoupper($request->method());

        if ($methodUpper === 'GET') {
            $options[CURLOPT_HTTPGET] = true;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $methodUpper;
        }

        $body = $request->body();
        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);

        $codeValue = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $code = intval($codeValue);

        if (curl_errno($ch)) {
            $error = strval(curl_error($ch));
            curl_close($ch);

            return \EndoGuard\Entities\HttpResponse::failure($code, $error, []);
        }

        curl_close($ch);

        if ($raw === false) {
            $result = \EndoGuard\Entities\HttpResponse::failure($code, 'curl_exec_failed', []);

            return $result;
        }

        $bodyString = strval($raw);
        $result = \EndoGuard\Entities\HttpResponse::success($code, $bodyString, []);

        return $result;
    }
}
