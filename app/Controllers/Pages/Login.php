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

class Login extends Base {
    public ?string $page = 'Login';

    public function getPageParams(): array {
        if (!\EndoGuard\Utils\Variables::completedConfig()) {
            $this->f3->error(422);
        }

        $pageParams = [
            'HTML_FILE'             => 'login.html',
            'JS'                    => 'user_main.js',
            'ALLOW_FORGOT_PASSWORD' => \EndoGuard\Utils\Variables::getForgotPasswordAllowed(),
        ];

        if (!$this->isPostRequest()) {
            return parent::applyPageParams($pageParams);
        }

        $params = $this->extractRequestParams(['token', 'email', 'password']);
        $errorCode = \EndoGuard\Utils\Validators::validateLogin($params);

        $pageParams['VALUES'] = $params;
        $pageParams['ERROR_CODE'] = $errorCode;

        if ($errorCode) {
            return parent::applyPageParams($pageParams);
        }

        \EndoGuard\Utils\Updates::syncUpdates();

        $email      = \EndoGuard\Utils\Conversion::getStringRequestParam('email');
        $password   = \EndoGuard\Utils\Conversion::getStringRequestParam('password');

        $model = new \EndoGuard\Models\Operator();
        $operatorId = $model->getActivatedByEmail($email);

        if ($operatorId && $model->verifyPassword($password, $operatorId)) {
            $this->f3->set('SESSION.active_user_id', $operatorId);

            $this->f3->set('SESSION.active_key_id', \EndoGuard\Utils\ApiKeys::getFirstKeyByOperatorId($operatorId));

            // blacklist first because it uses review_queue_updated_at for cache check
            $controller = new \EndoGuard\Controllers\Admin\Blacklist\Navigation();
            $controller->setBlacklistUsersCount(true);      // use cache

            $controller = new \EndoGuard\Controllers\Admin\ReviewQueue\Navigation();
            $controller->setNotReviewedCount(true);         // use cache

            $pageParams['VALUES'] = \EndoGuard\Utils\Routes::callExtra('LOGIN', $params) ?? $params;
            $this->f3->reroute('/');
        } else {
            $pageParams['VALUES'] = \EndoGuard\Utils\Routes::callExtra('LOGIN_FAIL', $params) ?? $params;
            $pageParams['ERROR_CODE'] = \EndoGuard\Utils\ErrorCodes::EMAIL_OR_PASSWORD_IS_NOT_CORRECT;
        }

        return parent::applyPageParams($pageParams);
    }
}
