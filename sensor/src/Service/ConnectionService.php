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

class ConnectionService {
    /**
     * Try to close connection with user.
     */
    public function finishRequestForUser(): bool {
        http_response_code(204);

        if (function_exists('fastcgi_finish_request')) {
            return fastcgi_finish_request();
        }

        // Fallback to old method
        header('Connection: close');
        $size = ob_get_length();
        header("Content-Length: $size");
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();

        return true;
    }
}
