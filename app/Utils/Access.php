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

class Access {
    public static function cleanHost(): void {
        $f3 = \Base::instance();

        $host = \EndoGuard\Utils\Variables::getHostWithProtocol();
        $host = strtolower(parse_url($host, PHP_URL_HOST));

        $f3->set('HOST', $host);

        return;
    }

    public static function CSRFTokenValid(array $params, \Base $f3): int|false {
        $token = $params['token'] ?? null;
        $csrf = $f3->get('SESSION.csrf');

        if (!isset($token) || $token === '' || !isset($csrf) || $csrf === '' || $token !== $csrf) {
            return \EndoGuard\Utils\ErrorCodes::CSRF_ATTACK_DETECTED;
        }

        return false;
    }

    public static function checkApiKeyAccess(int $keyId, int $operatorId): bool {
        $model = new \EndoGuard\Models\ApiKeys();
        $keyExists = $model->existsByKeyAndOperatorId($keyId, $operatorId);

        if ($keyExists) {
            return true;
        }

        $coOwnerModel = new \EndoGuard\Models\ApiKeyCoOwner();
        $key = $coOwnerModel->getCoOwnershipKeyId($operatorId);

        return boolval($key);
    }

    public static function checkCurrentOperatorApiKeyAccess(int $keyId): bool {
        $operatorId = self::getCurrentOperatorId();

        return $operatorId && self::checkApiKeyAccess($keyId, $operatorId);
    }

    public static function getCurrentOperatorId(): ?int {
        return \EndoGuard\Utils\Routes::getCurrentRequestOperator()?->id;
    }

    public static function getCurrentOperatorApiKeyId(): ?int {
        return \EndoGuard\Utils\Routes::getCurrentRequestApiKey()?->id;
    }

    public static function hashPassword(string $password): string {
        $pepper = \EndoGuard\Utils\Variables::getPepper();
        $pepperedPassword = hash_hmac('sha256', $password, $pepper);

        return password_hash($pepperedPassword, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $unverified, string $password): bool {
        $pepper = \EndoGuard\Utils\Variables::getPepper();
        $pepperedPassword = hash_hmac('sha256', $unverified, $pepper);

        return password_verify($pepperedPassword, $password);
    }

    public static function saltHash(string $string): string {
        $iterations = 1000;
        $salt = \Base::instance()->get('SALT');

        return hash_pbkdf2('sha256', $string, $salt, $iterations, 32);
    }

    public static function pseudoRandString(int $length = 32): string {
        $bytes = random_bytes(intdiv($length, 2));

        return bin2hex($bytes);
    }
}
