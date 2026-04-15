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

namespace EndoGuard\Controllers\Admin\Base;

abstract class Navigation extends \EndoGuard\Controllers\Base {
    protected \EndoGuard\Views\Base $response;

    protected ?object $page = null;
    protected ?object $controller = null;
    protected ?\EndoGuard\Entities\Operator $operator = null;
    protected ?int $apiKey = null;
    protected ?int $id = null;

    public function __construct() {
        parent::__construct();

        $this->operator = \EndoGuard\Utils\Routes::getCurrentRequestOperator();
        $this->apiKey = \EndoGuard\Utils\ApiKeys::getCurrentOperatorApiKeyId();
        $this->id = \EndoGuard\Utils\Conversion::getIntRequestParam('id', true);
    }

    public function showIndexPage(): void {
        if (!$this->page) {
            return;
        }

        \EndoGuard\Utils\Routes::redirectIfUnlogged();

        $this->response = new \EndoGuard\Views\Frontend();
        $this->response->data = $this->page->getPageParams();
    }

    public function beforeroute(): void {
        if ($this->operator) {
            \EndoGuard\Utils\Updates::syncUpdates();

            if (!$this->apiKey) {
                $this->f3->reroute('/logout');
            }

            $messages = \EndoGuard\Utils\SystemMessages::get($this->apiKey);

            $this->f3->set('SYSTEM_MESSAGES', $messages);

            if (count($messages)) {
                $doRedirect = $this->shouldRedirectToApiKeys($messages[0]);

                if ($doRedirect) {
                    $this->f3->reroute('/api');
                }
            }
        }
    }

    private function shouldRedirectToApiKeys(array $message): bool {
        $route = $this->f3->get('PARAMS.0');
        $allowedPages = [
            '/api',
            '/settings',
            '/logbook',
        ];

        $allowedPages = array_merge($allowedPages, $this->f3->get('EXTRA_ALLOWED_PAGES') ?? []);

        $isPageAllowed = in_array($route, $allowedPages);

        return !$isPageAllowed && ($message['id'] === \EndoGuard\Utils\ErrorCodes::THERE_ARE_NO_EVENTS_YET);
    }

    public function isPostRequest(): bool {
        return $this->f3->get('VERB') === 'POST';
    }

    /**
     * kick start the View, which creates the response
     * based on our previously set content data.
     * finally echo the response or overwrite this method
     * and do something else with it.
     */
    public function afterroute(): void {
        $shouldPrintSqlToLog = $this->f3->get('PRINT_SQL_LOG_AFTER_EACH_SCRIPT_CALL');

        if ($shouldPrintSqlToLog) {
            $hive = $this->f3->hive();
            $path = $hive['PATH'];

            $log = \EndoGuard\Utils\Database::getDb()->log();
            if ($log) {
                \EndoGuard\Utils\Logger::logSql($path, $log);
            }
        }

        echo $this->response->render();
    }
}
