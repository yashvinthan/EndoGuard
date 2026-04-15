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

namespace EndoGuard\Controllers\Pages;

class ForgotPassword extends Base {
    public ?string $page = 'ForgotPassword';

    public function getPageParams(): array {
        if (!\EndoGuard\Utils\Variables::getForgotPasswordAllowed()) {
            return [];
        }

        $pageParams = [
            'HTML_FILE' => 'forgotPassword.html',
        ];

        if ($this->isPostRequest()) {
            $params = $this->extractRequestParams(['token', 'email']);
            $errorCode = \EndoGuard\Utils\Validators::validateForgotPassword($params);

            if (!$errorCode) {
                $email = \EndoGuard\Utils\Conversion::getStringRequestParam('email');
                $model = new \EndoGuard\Models\Operator();
                $operatorId = $model->getActivatedByEmail($email);

                if ($operatorId) {
                    // Create forgot password record.
                    $forgotPasswordModel = new \EndoGuard\Models\ForgotPassword();
                    $renewKey = $forgotPasswordModel->insertRecord($operatorId);

                    // Send forgot password email.
                    $this->sendPasswordRenewEmail($operatorId, $renewKey);
                }

                // Random sleep between 0.5 and 1 second to prevent timing attacks.
                usleep(rand(500000, 1000000));

                // Always report back that the email was sent.
                $pageParams['SUCCESS_CODE'] = \EndoGuard\Utils\ErrorCodes::RENEW_KEY_CREATED;
            }

            $pageParams['VALUES'] = $params;
            $pageParams['ERROR_CODE'] = $errorCode;
        }

        return parent::applyPageParams($pageParams);
    }

    private function sendPasswordRenewEmail(int $operatorId, string $renewKey): void {
        $url = \EndoGuard\Utils\Variables::getHostWithProtocolAndBase();

        $operator = \EndoGuard\Entities\Operator::getById($operatorId);

        $toName = $operator->firstname;
        $toAddress = $operator->email;

        $subject = $this->f3->get('ForgotPassowrd_renew_password_subject');
        $message = $this->f3->get('ForgotPassowrd_renew_password_body');

        $renewUrl = sprintf('%s/password-recovering/%s', $url, $renewKey);
        $message = sprintf($message, $renewUrl);

        \EndoGuard\Utils\Mailer::send($toName, $toAddress, $subject, $message);
    }
}
