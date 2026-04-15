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

namespace EndoGuard\Controllers\Admin\Api;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    protected array $ENRICHED_ATTRIBUTES = [];

    public function __construct() {
        parent::__construct();

        $this->ENRICHED_ATTRIBUTES = array_keys(\EndoGuard\Utils\Constants::get()->ENRICHING_ATTRIBUTES);
    }

    public function proceedPostRequest(): array {
        return match (\EndoGuard\Utils\Conversion::getStringRequestParam('cmd')) {
            'resetKey'          => $this->resetApiKey(),
            'updateApiUsage'    => $this->updateApiUsage(),
            'enrichAll'         => $this->enrichAll(),
            default => []
        };
    }

    public function getUsageStats(int $operatorId): array {
        $model = new \EndoGuard\Models\ApiKeys();
        $apiKeys = $model->getKeys($operatorId);

        $isOwner = true;
        if (!$apiKeys) {
            $coOwnerModel = new \EndoGuard\Models\ApiKeyCoOwner();
            $key = $coOwnerModel->getCoOwnershipKeyId($operatorId);

            if ($key) {
                $isOwner = false;
                $apiKeys[] = $model->getKeyById($key);
            }
        }

        if (!$isOwner) {
            return ['data' => []];
        }

        $resultKeys = [];

        foreach ($apiKeys as $key) {
            $subscriptionStats = [];
            if ($key['token'] !== null) {
                [$code, $response, $error] = $this->getSubscriptionStats($key['token']);
                $subscriptionStats = strlen($error) > 0 || $code > 201 ? [] : $response;
            }

            $remaining = $subscriptionStats['remaining'] ?? null;
            $total = $subscriptionStats['total'] ?? null;
            $used = $remaining !== null && $total !== null ? $total - $remaining : null;

            $resultKeys[] = [
                'id'                        => $key['id'],
                'key'                       => $key['key'],
                'apiToken'                  => $key['token'] ?? null,
                'sub_status'                => $subscriptionStats['status'] ?? null,
                'sub_calls_left'            => $remaining,
                'sub_calls_used'            => $used,
                'sub_calls_limit'           => $total,
                'sub_next_billed'           => $subscriptionStats['next_billed_at'] ?? null,
                'sub_update_url'            => $subscriptionStats['update_url'] ?? null,
                'sub_plan_id'               => $subscriptionStats['current_subscription_plan']['sub_id'] ?? null,
                'sub_plan_api_calls'        => $subscriptionStats['current_subscription_plan']['api_calls'] ?? null,
                //'all_subscription_plans'    => $subscriptionStats['all_subscription_plans'] ?? null,
            ];
        }

        return ['data' => $resultKeys];
    }

    public function getOperatorApiKeysDetails(int $operatorId): array {
        [$isOwner, $apiKeys] = \EndoGuard\Utils\ApiKeys::getOperatorApiKeys($operatorId);

        $resultKeys = [];

        foreach ($apiKeys as $key) {
            $resultKeys[] = [
                'id'                        => $key['id'],
                'key'                       => $key['key'],
                'created_at'                => $key['created_at'],
                'skip_enriching_attributes' => $key['skip_enriching_attributes'],
                'enrichedAttributes'        => $this->getEnrichedAttributes($key['skip_enriching_attributes']),
                'retention_policy'          => $key['retention_policy'],
                'skip_blacklist_sync'       => $key['skip_blacklist_sync'],
                'apiToken'                  => $key['token'],
            ];
        }

        return [$isOwner, $resultKeys];
    }

    private function getSubscriptionStats(string $token): array {
        $response = \EndoGuard\Utils\Network::sendApiRequest(null, '/usage-stats', 'GET', $token);
        $code = $response->code();
        $result = $response->body();

        $statusCode = $code ?? 0;
        $errorMessage = $response->error() ?? '';

        return [$statusCode, $result, $errorMessage];
    }

    public function resetApiKey(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'keyId']);
        // TODO: valid only for owners?
        $errorCode = \EndoGuard\Utils\Validators::validateResetApiKey($params);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $keyId = \EndoGuard\Utils\Conversion::getIntRequestParam('keyId');

            $currentOperator = \EndoGuard\Utils\Routes::getCurrentRequestOperator();
            $operatorId = $currentOperator->id;

            $model = new \EndoGuard\Models\ApiKeys();
            $model->resetKey($keyId, $operatorId);

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminApi_reset_success_message');
        }

        return $pageParams;
    }

    public function enrichAll(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token']);
        $enrichmentKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorEnrichmentKeyString();
        $errorCode = \EndoGuard\Utils\Validators::validateEnrichAll($params, $enrichmentKey);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();

            $model = new \EndoGuard\Models\Users();
            $accountsToEnrich = $model->notCheckedUsers($apiKey);

            (new \EndoGuard\Models\Queue())->addBatchIds($accountsToEnrich, \EndoGuard\Utils\Constants::get()->ENRICHMENT_QUEUE_ACTION_TYPE, $apiKey);

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminApi_manual_enrichment_success_message');
        }

        return $pageParams;
    }

    private function getEnrichedAttributes(string $attributes): array {
        $enrichedAttributes = [];
        $skipAttributes = json_decode($attributes);
        foreach ($this->ENRICHED_ATTRIBUTES as $attribute) {
            $enrichedAttributes[$attribute] = !in_array($attribute, $skipAttributes);
        }

        return $enrichedAttributes;
    }

    public function updateApiUsage(): array {
        $pageParams = [];
        // apiToken, exchangeBlacklist optional
        $params = $this->extractRequestParams(['token', 'keyId', 'enrichedAttributes']);
        $errorCode = \EndoGuard\Utils\Validators::validateUpdateApiUsage($params, $this->ENRICHED_ATTRIBUTES);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $keyId = \EndoGuard\Utils\Conversion::getIntRequestParam('keyId');

            $model = new \EndoGuard\Models\ApiKeys();
            $model->getKeyById($keyId);

            $apiToken = \EndoGuard\Utils\Conversion::getStringRequestParam('apiToken', true);

            if ($apiToken !== null) {
                $apiToken = trim($apiToken);
                [$code, , $error] = $this->getSubscriptionStats($apiToken);
                if (strlen($error) > 0 || $code > 201) {
                    $pageParams['ERROR_CODE'] = \EndoGuard\Utils\ErrorCodes::SUBSCRIPTION_KEY_INVALID_UPDATE;
                    return $pageParams;
                }
                $model->updateInternalToken($apiToken, $keyId);
            }

            $enrichedAttributes = \EndoGuard\Utils\Conversion::getDictionaryRequestParam('enrichedAttributes');
            $skipEnrichingAttr = array_diff($this->ENRICHED_ATTRIBUTES, array_keys($enrichedAttributes));
            $model->updateSkipEnrichingAttributes($skipEnrichingAttr, $keyId);

            $skipBlacklistSync = !\EndoGuard\Utils\Conversion::getStringRequestParam('exchangeBlacklist');
            $model->updateSkipBlacklistSynchronisation($skipBlacklistSync, $keyId);

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminApi_data_enrichment_success_message');
        }

        return $pageParams;
    }

    public function getNotCheckedEntitiesForLoggedUser(): bool {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $controller = new \EndoGuard\Controllers\Admin\Enrichment\Data();

        return $controller->getNotCheckedExists($apiKey);
    }

    public function getScheduledForEnrichment(): bool {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $model = new \EndoGuard\Models\Queue();

        // do not use isInQueue() to prevent true on failed state
        return $model->actionIsInQueueProcessing(\EndoGuard\Utils\Constants::get()->ENRICHMENT_QUEUE_ACTION_TYPE, $apiKey);
    }
}
