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

class Signup extends Base {
    public ?string $page = 'Signup';

    public function getPageParams(): array {
        $model = new \EndoGuard\Models\Operator();
        if (count($model->getAll())) {
            $this->f3->error(404);
        }

        $pageParams = [
            'HTML_FILE'     => 'signup.html',
            'TIMEZONES'     => \EndoGuard\Utils\Timezones::timezonesList(),
            'RULES_PRESETS' => \EndoGuard\Utils\Constants::get()->RULES_PRESETS,
        ];

        if ($this->isPostRequest()) {
            \EndoGuard\Utils\Updates::syncUpdates();

            $params = $this->extractRequestParams(['token', 'email', 'password', 'timezone', 'rules-preset']);
            $errorCode = \EndoGuard\Utils\Validators::validateSignup($params);

            $pageParams['ERROR_CODE'] = $errorCode;

            if ($errorCode) {
                $pageParams['VALUES'] = $params;
            } else {
                $operatorId = $this->addUser($params);

                $apiKey = $this->addDefaultApiKey($operatorId);
                (new \EndoGuard\Controllers\Admin\Rules\Data())->applyRulesPresetById($params['rules-preset'], $apiKey);

                //$this->sendActivationEmail($operatorId);
                $pageParams['SUCCESS_CODE'] = \EndoGuard\Utils\ErrorCodes::ACCOUNT_CREATED;
            }
        }

        return parent::applyPageParams($pageParams);
    }

    private function addDefaultApiKey(int $operatorId): int {
        $skipEnrichingAttr = json_encode(array_keys(\EndoGuard\Utils\Constants::get()->ENRICHING_ATTRIBUTES));
        $model = new \EndoGuard\Models\ApiKeys();

        return $model->insertRecord($skipEnrichingAttr, true, $operatorId);
    }

    protected function addUser(array $data): int {
        $model = new \EndoGuard\Models\Operator();

        return $model->insertRecord($data['password'], $data['email'], $data['timezone']);
    }

    /*private function sendActivationEmail(int $operatorId): void {
        $operator = \EndoGuard\Entities\Operator::getById($operatorId);
        $url = \EndoGuard\Utils\Variables::getHostWithProtocolAndBase();

        $toName = $operator->firstname;
        $toAddress = $operator->email;
        $activationKey = $operator->activationKey;

        $subject = $this->f3->get('Signup_activation_email_subject');
        $message = $this->f3->get('Signup_activation_email_body');

        $activationUrl = sprintf('%s/account-activation/%s', $url, $activationKey);
        $message = sprintf($message, $activationUrl);

        \EndoGuard\Utils\Mailer::send($toName, $toAddress, $subject, $message);
    }*/
}
