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

namespace EndoGuard\Controllers\Api;

abstract class Endpoint {
    public const API_KEY = 'Api-Key';

    protected \Base $f3;

    protected \EndoGuard\Views\Json $response;
    protected string $responseType;
    protected int $error;
    protected int $statusCode;
    protected array $validationErrors = [];
    protected \DateTime $startTime;

    private string $apiKeyString;
    protected int $apiKeyId;
    //protected \EndoGuard\Interfaces\ApiKeyAccessAuthorizationInterface $authorizationModel;

    protected array $body = [];

    protected array|null $data = null;

    public function __construct() {
        $this->f3 = \Base::instance();
        $this->f3->set('ONERROR', function (): void {
            $this->handleInternalServerError();
        });
        \EndoGuard\Utils\Database::initConnect(false);

        $this->response = new \EndoGuard\Views\Json();
        $this->responseType = \EndoGuard\Utils\Constants::get()->SINGLE_RESPONSE_TYPE;
    }

    public function beforeRoute(): void {
        $this->startTime = new \DateTime();
        $this->identify();
        $this->authenticate();
        $this->parseBody();
    }

    public function afterRoute(): void {
        if (isset($this->error)) {
            $errorI18nCode = sprintf('error_%s', $this->error);
            $errorMessage = $this->f3->get($errorI18nCode);
            $this->response->data = [
                'code' => $this->error,
                'message' => $errorMessage,
            ];
            $this->data = null;
        }

        if (!isset($this->error) || !isset($this->statusCode) || (!in_array($this->statusCode, [400, 401, 403]))) {
            $this->saveLogbook();
        }

        if (($this->data !== null)) {
            $this->response->data = $this->data;
        }

        echo $this->response->render();
    }

    protected function identify(): void {
        $headers = $this->f3->get('HEADERS') ?? [];

        if (array_key_exists(self::API_KEY, $headers) && is_string($headers[self::API_KEY])) {
            $this->apiKeyString = $headers[self::API_KEY];

            return;
        }

        $this->setError(400, \EndoGuard\Utils\ErrorCodes::REST_API_KEY_DOES_NOT_EXIST);
    }

    protected function authenticate(): void {
        $model = new \EndoGuard\Models\ApiKeys();
        $apiKeyId = $model->getKeyIdByHash($this->apiKeyString);

        if ($apiKeyId) {
            $this->apiKeyId = $apiKeyId;

            return;
        }

        $this->setError(401, \EndoGuard\Utils\ErrorCodes::REST_API_KEY_IS_NOT_CORRECT);
    }

    /*protected function authorize(string $subjectId): void {
        if (!isset($this->apiKeyId) || isset($this->error)) {
            exit;
        }

        $hasAccess = $this->authorizationModel->checkAccessByExternalId($subjectId, $this->apiKeyId);

        if ($hasAccess) {
            return;
        }

        $this->setError(403, \EndoGuard\Utils\ErrorCodes::REST_API_NOT_AUTHORIZED);
    }*/

    protected function getBodyProp(string $key, string $paramType = 'string'): string|int|array|null {
        $value = $this->body[$key] ?? null;

        if (isset($value)) {
            settype($value, $paramType);
        }

        return $value;
    }

    protected function saveLogbook(): void {
        $model = new \EndoGuard\Models\Logbook();
        $model->add(
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $this->f3->get('PATH'),
            null,
            !isset($this->error) ? \EndoGuard\Utils\Constants::get()->LOGBOOK_ERROR_TYPE_SUCCESS : \EndoGuard\Utils\Constants::get()->LOGBOOK_ERROR_TYPE_CRITICAL_ERROR,
            !isset($this->error) ? null : json_encode(['Undefined error']),
            json_encode($this->body),
            $this->formatStartTime(),
            $this->apiKeyId,
        );
    }

    protected function formatStartTime(): string {
        $milliseconds = intval(intval($this->startTime->format('u')) / 1000);

        return $this->startTime->format('Y-m-d H:i:s') . '.' . sprintf('%03d', $milliseconds);
    }

    protected function setError(int $statusCode, int $errorCode): void {
        $this->f3->status($statusCode);
        $this->statusCode = $statusCode;
        $this->error = $errorCode;
        $this->afterRoute();
        exit;
    }

    private function parseBody(): void {
        $body = $this->f3->get('BODY');
        $this->body = json_decode($body, true) ?? [];
    }

    private function handleInternalServerError(): void {
        $errorData = \EndoGuard\Utils\ErrorHandler::getErrorDetails($this->f3);
        \EndoGuard\Utils\ErrorHandler::saveErrorInformation($this->f3, $errorData);

        $this->setError(500, 500);
    }
}
