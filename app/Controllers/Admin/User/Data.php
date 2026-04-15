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

namespace EndoGuard\Controllers\Admin\User;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function proceedPostRequest(): array {
        return match (\EndoGuard\Utils\Conversion::getStringRequestParam('cmd')) {
            'riskScore'     => $this->recalculateRiskScore(),
            'reenrichment'  => $this->enrichEntity(),
            'delete'        => $this->deleteUser(),
            default => []
        };
    }

    public function recalculateRiskScore(): array {
        $result = [];
        set_error_handler([\EndoGuard\Utils\ErrorHandler::class, 'exceptionErrorHandler']);

        try {
            $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
            $userId = \EndoGuard\Utils\Conversion::getIntRequestParam('accountid');

            [$score, $rules] = $this->getUserScore($userId, $apiKey);
            $result = [
                'SUCCESS_MESSAGE' => $this->f3->get('AdminUser_recalculate_risk_score_success_message'),
                'score' => $score,
                'rules' => $rules,
            ];
        } catch (\ErrorException $e) {
            $result = ['ERROR_CODE' => \EndoGuard\Utils\ErrorCodes::RISK_SCORE_UPDATE_UNKNOWN_ERROR];
        }

        restore_error_handler();

        return $result;
    }

    public function enrichEntity(): array {
        $dataController = new \EndoGuard\Controllers\Admin\Enrichment\Data();
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $enrichmentKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorEnrichmentKeyString();

        $type       = \EndoGuard\Utils\Conversion::getStringRequestParam('type');
        $search     = \EndoGuard\Utils\Conversion::getStringRequestParam('search', true);
        $entityId   = \EndoGuard\Utils\Conversion::getIntRequestParam('entityId', true);

        return $dataController->enrichEntity($type, $search, $entityId, $apiKey, $enrichmentKey);
    }

    public function deleteUser(): void {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();

        if ($apiKey) {
            $model = new \EndoGuard\Models\Queue();
            $accountId = \EndoGuard\Utils\Conversion::getIntRequestParam('accountid');
            $code = \EndoGuard\Utils\ErrorCodes::REST_API_USER_ALREADY_DELETING;

            if (!$model->isInQueue($accountId, \EndoGuard\Utils\Constants::get()->DELETE_USER_QUEUE_ACTION_TYPE, $apiKey)) {
                $code = \EndoGuard\Utils\ErrorCodes::REST_API_USER_ADDED_FOR_DELETION;
                $model->add($accountId, \EndoGuard\Utils\Constants::get()->DELETE_USER_QUEUE_ACTION_TYPE, $apiKey);
            }

            $this->f3->set('SESSION.extra_message_code', $code);
            $this->f3->reroute('/id');
        }
    }

    public function getUserScoreDetails(int $userId, int $apiKey): array {
        $model = new \EndoGuard\Models\User();
        $user = $model->getUserById($userId, $apiKey);

        return [
            'score_details'     => $model->getApplicableRulesByAccountId($userId, $apiKey, true),
            'score_calculated'  => $user !== [] ? $user['score'] !== null : false,
        ];
    }

    public function getUserById(int $accountId, int $apiKey): array {
        $user = (new \EndoGuard\Models\User())->getUserById($accountId, $apiKey);
        $rules = (new \EndoGuard\Models\Rules())->getAll();

        $details = [];
        if ($user['score_details']) {
            $scoreDetails = json_decode($user['score_details'], true);

            foreach ($scoreDetails as $detail) {
                $score = $detail['score'] ?? null;
                $ruleUid = $detail['uid'] ?? null;
                if ($score !== 0 && isset($rules[$ruleUid])) {
                    $item = $rules[$ruleUid];
                    $item['score'] = $score;
                    $details[] = $item;
                }
            }
        }

        usort($details, [\EndoGuard\Utils\Sort::class, 'cmpScore']);

        $user['score_details'] = $details;

        $pageTitle = $user['userid'];
        if ($user['firstname'] !== null && $user['firstname'] !== '') {
            $pageTitle .= sprintf(' (%s)', $user['firstname']);
        }
        if ($user['lastname'] !== null && $user['lastname'] !== '') {
            $pageTitle .= sprintf(' (%s)', $user['lastname']);
        }
        $user['page_title'] = $pageTitle;

        $tsColumns = ['created', 'lastseen', 'score_updated_at', 'latest_decision', 'updated', 'added_to_review'];
        \EndoGuard\Utils\Timezones::localizeTimestampsForActiveOperator($tsColumns, $user);

        return $user;
    }

    public function checkIfOperatorHasAccess(int $userId, int $apiKey): bool {
        return (new \EndoGuard\Models\User())->checkAccess($userId, $apiKey);
    }

    public function checkEnrichmentAvailability(): bool {
        return \EndoGuard\Utils\ApiKeys::getCurrentOperatorEnrichmentKeyString() !== null;
    }

    public function addToWatchlist(int $accountId, int $apiKey): void {
        $model = new \EndoGuard\Models\Watchlist();
        $model->add($accountId, $apiKey);
    }

    public function removeFromWatchlist(int $accountId, int $apiKey): void {
        $model = new \EndoGuard\Models\Watchlist();
        $model->remove($accountId, $apiKey);
    }

    public function addToBlacklistQueue(int $accountId, bool $fraud, bool $cron, bool $cnt, int $apiKey): void {
        $model = new \EndoGuard\Models\Queue();
        $inQueue = $model->isInQueue($accountId, \EndoGuard\Utils\Constants::get()->BLACKLIST_QUEUE_ACTION_TYPE, $apiKey);

        if (!$fraud) {
            $this->setFraudFlag($accountId, false, $apiKey); // Directly remove blacklisted items

            if ($inQueue) {
                $model->removeFromQueue($accountId, \EndoGuard\Utils\Constants::get()->BLACKLIST_QUEUE_ACTION_TYPE, $apiKey); // Cancel queued operation
            }
        }

        if (!$inQueue && $fraud) {
            $model->add($accountId, \EndoGuard\Utils\Constants::get()->BLACKLIST_QUEUE_ACTION_TYPE, $apiKey);
        }

        $model = new \EndoGuard\Models\User();
        $model->updateFraudFlag([$accountId], $apiKey, $fraud);

        if ($cnt) {
            $controller = new \EndoGuard\Controllers\Admin\Blacklist\Data();
            $controller->setBlacklistUsersCount(false, $apiKey);        // do not use cache
            $controller = new \EndoGuard\Controllers\Admin\ReviewQueue\Data();
            $controller->setNotReviewedCount(false, $apiKey);           // do not use cache
        }

        \EndoGuard\Utils\Routes::callExtra('UPDATE_USER_FRAUD_STATUS', $accountId, $fraud, $cron, $apiKey);
    }

    public function addToCalulcateRiskScoreQueue(int $accountId): void {
        $apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();

        $model = new \EndoGuard\Models\Queue();
        $inQueue = $model->isInQueue($accountId, \EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE, $apiKey);

        if (!$inQueue) {
            $model->add($accountId, \EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE, $apiKey);
        }
    }

    /**
     * @param array{accountId: int, key: int}[] $accounts
     */
    public function addBatchToCalulcateRiskScoreQueue(array $accounts): void {
        (new \EndoGuard\Models\Queue())->addBatch($accounts, \EndoGuard\Utils\Constants::get()->RISK_SCORE_QUEUE_ACTION_TYPE);
    }

    public function setReviewedFlag(int $accountId, bool $reviewed, int $apiKey): void {
        $model = new \EndoGuard\Models\User();
        $model->updateReviewedFlag($accountId, $apiKey, $reviewed);
    }

    public function getUserScore(int $accountId, int $apiKey): array {
        $total = 0;
        $rules = [];

        $rulesController = new \EndoGuard\Controllers\Admin\Rules\Data();
        $rulesController->evaluateUser($accountId, $apiKey);

        $model = new \EndoGuard\Models\User();
        $rules = $model->getApplicableRulesByAccountId($accountId, $apiKey);

        $total = $rules[0]['total_score'] ?? 0;
        array_walk($rules, function (&$rule): void {
            unset($rule['total_score']);
        }, $rules);

        return [$total, $rules];
    }

    public function getScheduledForDeletion(int $userId, int $apiKey): array {
        $model = new \EndoGuard\Models\Queue();

        [$scheduled, $status] = $model->isInQueueStatus($userId, \EndoGuard\Utils\Constants::get()->DELETE_USER_QUEUE_ACTION_TYPE, $apiKey);

        return [$scheduled, ($status === \EndoGuard\Utils\Constants::get()->FAILED_QUEUE_STATUS_TYPE) ? \EndoGuard\Utils\ErrorCodes::USER_DELETION_FAILED : null];
    }

    public function getScheduledForBlacklist(int $userId, int $apiKey): array {
        $model = new \EndoGuard\Models\Queue();

        [$scheduled, $status] = $model->isInQueueStatus($userId, \EndoGuard\Utils\Constants::get()->BLACKLIST_QUEUE_ACTION_TYPE, $apiKey);

        return [$scheduled, ($status === \EndoGuard\Utils\Constants::get()->FAILED_QUEUE_STATUS_TYPE) ? \EndoGuard\Utils\ErrorCodes::USER_BLACKLISTING_FAILED : null];
    }

    public function setFraudFlag(int $accountId, bool $fraud, int $apiKey): array {
        $blacklistItemsModel = new \EndoGuard\Models\BlacklistItems();

        $ips = $blacklistItemsModel->getIpsRelatedToAccountWithinOperator($accountId, $apiKey);
        $emails = $blacklistItemsModel->getEmailsRelatedToAccountWithinOperator($accountId, $apiKey);
        $phones = $blacklistItemsModel->getPhonesRelatedToAccountWithinOperator($accountId, $apiKey);

        $relatedIpsIds = array_column($ips, 'id');
        $relatedEmailsIds = array_column($emails, 'id');
        $relatedPhonesIds = array_column($phones, 'id');

        $ips = $blacklistItemsModel->getIpsRelatedToAccountWithinOperator($accountId, $apiKey);
        $relatedIpsIds = array_column($ips, 'id');
        if (count($relatedIpsIds) !== 0) {
            $model = new \EndoGuard\Models\Ip();
            $model->updateFraudFlag($relatedIpsIds, $fraud, $apiKey);
        }

        $emails = $blacklistItemsModel->getEmailsRelatedToAccountWithinOperator($accountId, $apiKey);
        $relatedEmailsIds = array_column($emails, 'id');
        if (count($relatedEmailsIds) !== 0) {
            $model = new \EndoGuard\Models\Email();
            $model->updateFraudFlag($relatedEmailsIds, $fraud, $apiKey);
        }

        $phones = $blacklistItemsModel->getPhonesRelatedToAccountWithinOperator($accountId, $apiKey);
        $relatedPhonesIds = array_column($phones, 'id');
        if (count($relatedPhonesIds) !== 0) {
            $model = new \EndoGuard\Models\Phone();
            $model->updateFraudFlag($relatedPhonesIds, $fraud, $apiKey);
        }

        return array_merge($ips, $emails, $phones);
    }

    public function updateUserStatus(int $score, string $details, bool $cron, int $accountId, int $apiKey): void {
        $addToReview = false;

        $model = new \EndoGuard\Models\ApiKeys();
        $key = $model->getKeyById($apiKey);

        $userModel = new \EndoGuard\Models\User();

        if ($score <= $key['blacklist_threshold']) {
            $this->addToBlacklistQueue($accountId, true, true, false, $apiKey); // automatic blacklist anyway, do not recalculate
        } elseif ($score <= $key['review_queue_threshold']) {
            $user = $userModel->getUserById($accountId, $apiKey);
            $addToReview = $user['added_to_review'] === null && $user['fraud'] === null;

            if (!$cron && $addToReview) {
                $controller = new \EndoGuard\Controllers\Admin\ReviewQueue\Data();
                $controller->setNotReviewedCount(false, $apiKey);           // do not use cache
            }
        }

        \EndoGuard\Utils\Routes::callExtra('UPDATE_USER_STATUS', $score, $details, $addToReview, $cron, $accountId, $apiKey);

        $userModel->updateUserStatus($score, $details, $addToReview, $accountId, $apiKey);
    }
}
