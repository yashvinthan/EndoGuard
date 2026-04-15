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

namespace EndoGuard\Utils\Http;

class HttpClient {
    /** @var array<int, \EndoGuard\Interfaces\HttpTransportInterface> */
    private array $transports;

    /**
     * @param array<int, \EndoGuard\Interfaces\HttpTransportInterface> $transports
     */
    public function __construct(array $transports) {
        $this->transports = $transports;
    }

    public static function default(): self {
        $transports = [
            new \EndoGuard\Utils\Http\CurlTransport(),
            new \EndoGuard\Utils\Http\StreamTransport(),
        ];

        return new self($transports);
    }

    public function request(\EndoGuard\Entities\HttpRequest $request): \EndoGuard\Entities\HttpResponse {
        foreach ($this->transports as $transport) {
            if ($transport->isAvailable()) {
                return $transport->request($request);
            }
        }

        return \EndoGuard\Entities\HttpResponse::failure(null, 'no_transport_available', []);
    }
}
