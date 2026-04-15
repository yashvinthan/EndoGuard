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

namespace EndoGuard\Controllers\Pages;

class PasswordRecovering extends Base {
    public ?string $page = 'PasswordRecovering';

    public function getPageParams(): array {
        $pageParams = [
            'HTML_FILE' => 'passwordRecovering.html',
        ];

        $errorCode = \EndoGuard\Utils\Validators::validatePasswordRecovering($this->f3->get('PARAMS'));
        $pageParams['SUCCESS_CODE'] = $errorCode;

        if ($this->isPostRequest()) {
            $params = $this->extractRequestParams(['token', 'new-password', 'password-confirmation']);
            $errorCode = \EndoGuard\Utils\Validators::validatePasswordRecoveringPost($params);

            $pageParams['SUCCESS_CODE'] = 0;
            $pageParams['ERROR_CODE'] = $errorCode;

            if (!$errorCode) {
                $forgotPasswordModel = new \EndoGuard\Models\ForgotPassword();
                $operatorId = $forgotPasswordModel->useByRenewKey($this->f3->get('PARAMS.renewKey'));

                $password = \EndoGuard\Utils\Conversion::getStringRequestParam('new-password');

                $model = new \EndoGuard\Models\Operator();
                $model->updatePassword($password, $operatorId);
                $model->activateByOperatorId($operatorId);

                $pageParams['SUCCESS_CODE'] = \EndoGuard\Utils\ErrorCodes::ACCOUNT_ACTIVATED;
            }
        }

        return parent::applyPageParams($pageParams);
    }
}
