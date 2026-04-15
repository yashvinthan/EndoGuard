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

namespace EndoGuard\Utils;

class ApiKeys {
    public static function getCurrentOperatorApiKeyId(): ?int {
        $key = \EndoGuard\Utils\Routes::getCurrentRequestApiKey();

        return $key ? $key->id : null;
    }

    public static function getCurrentOperatorApiKeyString(): ?string {
        $key = \EndoGuard\Utils\Routes::getCurrentRequestApiKey();

        return $key ? $key->key : null;
    }

    public static function getCurrentOperatorEnrichmentKeyString(): ?string {
        $key = \EndoGuard\Utils\Routes::getCurrentRequestApiKey();

        return $key ? $key->token : null;
    }

    public static function getOperatorApiKeys(int $operatorId): array {
        $model = new \EndoGuard\Models\ApiKeys();
        $apiKeys = $model->getKeys($operatorId);

        $isOwner = true;
        if (!$apiKeys) {
            $coOwnerModel = new \EndoGuard\Models\ApiKeyCoOwner();
            $keyId = $coOwnerModel->getCoOwnershipKeyId($operatorId);

            if ($keyId) {
                $isOwner = false;
                $apiKeys[] = $model->getKeyById($keyId);
            }
        }

        return [$isOwner, $apiKeys];
    }

    public static function getFirstKeyByOperatorId(int $operatorId): ?int {
        $model = new \EndoGuard\Models\ApiKeys();
        $apiKeys = $model->getKeys($operatorId);

        if (!$apiKeys) {
            $coOwnerModel = new \EndoGuard\Models\ApiKeyCoOwner();
            $keyId = $coOwnerModel->getCoOwnershipKeyId($operatorId);

            if ($keyId) {
                $apiKeys[] = $model->getKeyById($keyId);
            }
        }

        return $apiKeys[0]['id'] ?? null;
    }
}
