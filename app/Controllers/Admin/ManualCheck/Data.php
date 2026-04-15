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

namespace EndoGuard\Controllers\Admin\ManualCheck;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function proceedPostRequest(): array {
        return $this->performSearch();
    }

    public function performSearch(): array {
        $params = $this->extractRequestParams(['token', 'search', 'type']);

        $pageParams = [
            'SEARCH_VALUES' => $params,
        ];

        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $enrichmentKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorEnrichmentKeyString();
        $errorCode = \EndoGuard\Utils\Validators::validateSearch($params, $enrichmentKey);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;

            return $pageParams;
        }

        $type   = \EndoGuard\Utils\Conversion::getStringRequestParam('type');
        $search = \EndoGuard\Utils\Conversion::getStringRequestParam('search');

        $controller = new \EndoGuard\Controllers\Admin\Enrichment\Data();
        $result = $controller->enrichEntity($type, $search, null, $apiKey, $enrichmentKey);

        if (isset($result['ERROR_CODE'])) {
            $pageParams['ERROR_CODE'] = $result['ERROR_CODE'];

            return $pageParams;
        }

        $operatorId = \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id;
        $this->saveSearch($search, $type, $operatorId);

        // TODO: return alert_list back in next release
        if (array_key_exists('alert_list', $result[$type])) {
            unset($result[$type]['alert_list']);
        }

        if ($type === 'phone') {
            unset($result[$type]['valid']);
            unset($result[$type]['validation_error']);
        }

        if ($type === 'email') {
            unset($result[$type]['data_breaches']);
        }

        $pageParams['RESULT'] = [$type => $result[$type]];

        return $pageParams;
    }

    private function saveSearch(string $query, string $type, int $operatorId): void {
        $history = new \EndoGuard\Models\ManualCheckHistory();
        $history->insertRecord($query, $type, $operatorId);
    }

    public function getSearchHistory(int $operatorId): ?array {
        $model = new \EndoGuard\Models\ManualCheckHistory();

        return $model->getLastByOperatorId($operatorId);
    }
}
