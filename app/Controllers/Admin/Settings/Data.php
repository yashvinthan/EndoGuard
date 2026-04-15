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

namespace EndoGuard\Controllers\Admin\Settings;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    public function proceedPostRequest(): array {
        return match (\EndoGuard\Utils\Conversion::getStringRequestParam('cmd')) {
            'changeEmail'                   => $this->changeEmail(),
            'changeTimezone'                => $this->changeTimezone(),
            'changePassword'                => $this->changePassword(),
            'closeAccount'                  => $this->closeAccount(),
            'updateNotificationPreferences' => $this->updateNotificationPreferences(),
            'changeRetentionPolicy'         => $this->changeRetentionPolicy(),
            'inviteCoOwner'                 => $this->inviteCoOwner(),
            'removeCoOwner'                 => $this->removeCoOwner(),
            'checkUpdates'                  => $this->checkUpdates(),
            default => []
        };
    }

    public function getSharedApiKeyOperators(int $operatorId): array {
        $model = new \EndoGuard\Models\ApiKeyCoOwner();

        return $model->getSharedApiKeyOperators($operatorId);
    }

    protected function changePassword(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'current-password', 'new-password', 'password-confirmation']);
        $errorCode = \EndoGuard\Utils\Validators::validateChangePassword($params);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $password = \EndoGuard\Utils\Conversion::getStringRequestParam('new-password');
            $operatorId = \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id;

            $model = new \EndoGuard\Models\Operator();
            $model->updatePassword($password, $operatorId);

            // update operator obj
            \EndoGuard\Utils\Routes::setCurrentRequestOperator();

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminSettings_changePassword_success_message');
        }

        return $pageParams;
    }

    protected function changeEmail(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'email']);
        $errorCode = \EndoGuard\Utils\Validators::validateChangeEmail($params);

        if ($errorCode) {
            $pageParams['EMAIL_VALUES'] = $params;
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $operatorId = \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id;
            $email = \EndoGuard\Utils\Conversion::getStringRequestParam('email');

            $model = new \EndoGuard\Models\Operator();
            $model->updateEmail($email, $operatorId);

            // update operator obj
            \EndoGuard\Utils\Routes::setCurrentRequestOperator();

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminSettings_changeEmail_success_message');
        }

        return $pageParams;
    }

    protected function changeTimezone(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'timezone']);
        $errorCode = \EndoGuard\Utils\Validators::validateChangeTimezone($params);

        if ($errorCode) {
            $pageParams['TIME_ZONE_VALUES'] = $params;
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $timezone = \EndoGuard\Utils\Conversion::getStringRequestParam('timezone');
            $operatorId = \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id;

            $model = new \EndoGuard\Models\Operator();
            $model->updateTimezone($timezone, $operatorId);

            // update operator in f3 hive for clock
            \EndoGuard\Utils\Routes::setCurrentRequestOperator();

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminTimezone_changeTimezone_success_message');
        }

        return $pageParams;
    }

    protected function closeAccount(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token']);
        $errorCode = \EndoGuard\Utils\Validators::validateCloseAccount($params);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $operatorId = \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id;
            $model = new \EndoGuard\Models\Operator();
            $model->closeAccount($operatorId);
            $model->removeData($operatorId);

            $this->f3->clear('SESSION');
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            } else {
                session_commit();
            }

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminSettings_closeAccount_success_message');
        }

        return $pageParams;
    }

    protected function checkUpdates(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token']);
        $errorCode = \EndoGuard\Utils\Validators::validateCheckUpdates($params);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $currentVersion = \EndoGuard\Utils\VersionControl::versionString();

            $response = \EndoGuard\Utils\Network::sendApiRequest(null, '/version', 'GET', null);
            $code = $response->code();
            $result = $response->body();

            $statusCode = $code ?? 0;
            $errorMessage = $response->error() ?? '';

            if (strlen($errorMessage) > 0 || $statusCode !== 200 || !is_array($result)) {
                $pageParams['ERROR_CODE'] = \EndoGuard\Utils\ErrorCodes::ENRICHMENT_API_IS_NOT_AVAILABLE;
            } else {
                if (version_compare($currentVersion, $result['version'], '<')) {
                    $pageParams['SUCCESS_MESSAGE'] = sprintf('An update is available. Released date: %s.', $result['release_date']);
                } else {
                    $pageParams['SUCCESS_MESSAGE'] = 'Current version is up to date.';
                }
            }
        }

        return $pageParams;
    }

    protected function updateNotificationPreferences(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'review-reminder-frequency']);
        $errorCode = \EndoGuard\Utils\Validators::validateUpdateNotificationPreferences($params);

        if ($errorCode) {
            $pageParams['PROFILE_VALUES'] = $params;
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $reminder = \EndoGuard\Utils\Conversion::getStringRequestParam('review-reminder-frequency');
            $operatorId = \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id;

            $model = new \EndoGuard\Models\Operator();
            $model->updateNotificationPreferences($reminder, $operatorId);

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminSettings_notificationPreferences_success_message');
        }

        return $pageParams;
    }

    protected function changeRetentionPolicy(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'keyId', 'retention-policy']);
        $errorCode = \EndoGuard\Utils\Validators::validateRetentionPolicy($params);

        if ($errorCode) {
            $pageParams['RETENTION_POLICY_VALUES'] = $params;
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $keyId = \EndoGuard\Utils\Conversion::getIntRequestParam('keyId');
            $retentionPolicy = \EndoGuard\Utils\Conversion::getIntRequestParam('retention-policy');

            $model = new \EndoGuard\Models\ApiKeys();
            $model->updateRetentionPolicy($retentionPolicy, $keyId);
            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminRetentionPolicy_changeTimezone_success_message');
        }

        return $pageParams;
    }

    protected function inviteCoOwner(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'email']);
        $errorCode = \EndoGuard\Utils\Validators::validateInvitingCoOwner($params);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $currentOperator = \EndoGuard\Utils\Routes::getCurrentRequestOperator();
            $currentOperatorId = $currentOperator->id;

            $apiKey = \EndoGuard\Utils\Routes::getCurrentRequestApiKey();

            $params['timezone'] = 'UTC';
            $model = new \EndoGuard\Models\Operator();
            $invitedOperatorId = $model->insertRecord(null, $params['email'], 'UTC');

            $passwordReset = new \EndoGuard\Models\ForgotPassword();
            $renewKey = $passwordReset->insertRecord($invitedOperatorId);

            $this->makeOperatorCoOwner($invitedOperatorId, $apiKey->id);
            $this->sendInvitationEmail($params['email'], $currentOperatorId, $renewKey);

            $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminApi_add_co_owner_success_message');
        }

        return $pageParams;
    }

    protected function removeCoOwner(): array {
        $pageParams = [];
        $params = $this->extractRequestParams(['token', 'operatorId']);
        $errorCode = \EndoGuard\Utils\Validators::validateRemovingCoOwner($params);

        if ($errorCode) {
            $pageParams['ERROR_CODE'] = $errorCode;
        } else {
            $operatorId = \EndoGuard\Utils\Conversion::getIntRequestParam('operatorId');

            $coOwnerModel = new \EndoGuard\Models\ApiKeyCoOwner();
            $keyId = $coOwnerModel->getCoOwnershipKeyId($operatorId);

            $apiKey = \EndoGuard\Utils\Routes::getCurrentSessionApiKey();

            if ($apiKey->id === $keyId && \EndoGuard\Utils\Routes::getCurrentRequestOperator()->id === $apiKey->creator) {
                $coOwnerModel->deleteCoOwnership($operatorId);

                $operatorModel = new \EndoGuard\Models\Operator();
                $operatorModel->deleteAccount($operatorId);

                $pageParams['SUCCESS_MESSAGE'] = $this->f3->get('AdminApi_remove_co_owner_success_message');
            } else {
                $pageParams['ERROR_MESSAGE'] = $this->f3->get('AdminApi_remove_co_owner_error_message');
            }
        }

        return $pageParams;
    }

    protected function makeOperatorCoOwner(int $operatorId, int $apiKey): void {
        $model = new \EndoGuard\Models\ApiKeyCoOwner();
        $model->insertRecord($operatorId, $apiKey);
    }

    protected function sendInvitationEmail(string $email, int $inviterId, string $renewKey): void {
        $toAddress = $email;

        $inviter = \EndoGuard\Entities\Operator::getById($inviterId);

        $site = \EndoGuard\Utils\Variables::getHostWithProtocolAndBase();

        $inviterDisplayName = $inviter->email;
        if ($inviter->firstname && $inviter->lastname) {
            $inviterDisplayName = sprintf('%s %s (%s)', $inviter->firstname, $inviter->lastname, $inviterDisplayName);
        }

        $toName = null;
        //$toAddress = $operator->email;

        $subject = $this->f3->get('AdminApi_invitation_email_subject');
        $message = $this->f3->get('AdminApi_invitation_email_body');

        $renewUrl = sprintf('%s/password-recovering/%s', $site, $renewKey);
        $message = sprintf($message, $inviterDisplayName, $renewUrl);

        \EndoGuard\Utils\Mailer::send($toName, $toAddress, $subject, $message);
    }
}
