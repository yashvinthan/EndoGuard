# Appendix A – Source Code

*The following sections contain the full source code for the critical modules of the EndoGuard application, covering core routing, authentication (login), API base logic, algorithm evaluation (rules), and blacklist management.*

## A.1 Core Initialization (`index.php`)

```php
<?php

/**
 * EndoGuard ~ Embedded & Internal Cybersecurity Framework
 * Copyright (c) EndoGuard Security (https://www.endoguard.online)
 *
 * Licensed under GNU Affero General Public License version 3 of the or any later version.
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) EndoGuard Security (https://www.endoguard.online)
 * @license       https://opensource.org/licenses/AGPL-3.0 AGPL License
 * @link          https://www.endoguard.online EndoGuard(tm)
 */

declare(strict_types=1);

session_name('ENDOGUARDSESSION');

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

chdir(dirname(__FILE__));

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} else {
    require __DIR__ . '/libs/bcosca/fatfree-core/base.php';

    // PSR-4 autoloader
    spl_autoload_register(function (string $className): void {
        $libs = [
            'Ruler\\' => '/libs/ruler/ruler/src/',
            'PHPMailer\\PHPMailer\\' => '/libs/phpmailer/phpmailer/src/',
            'EndoGuard\\' => '/app/',
        ];

        foreach ($libs as $namespace => $path) {
            if (str_starts_with($className, $namespace)) {
                require __DIR__ . $path . str_replace([$namespace, '\\'], ['', '/'], $className) . '.php';
                break;
            }
        }
    });
}

$f3 = \Base::instance();

//Load configuration file with all project variables
$f3->config('config/config.ini');

//Load specific configuration only for local development
$localConfigFile = \EndoGuard\Utils\Variables::getConfigFile();
$localConfigFile = sprintf('config/%s', $localConfigFile);

//Load local configuration file
if (file_exists($localConfigFile)) {
    $f3->config($localConfigFile);
}

//Use custom onError function
$f3->set('ONERROR', \EndoGuard\Utils\ErrorHandler::getOnErrorHandler());

if (\EndoGuard\Utils\Variables::getForceHttps() || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
    ini_set('session.cookie_secure', '1');
}

if (!\EndoGuard\Utils\Variables::completedConfig()) {
    if (is_file("./install/index.php")) {
        if (($f3->get('PATH') === '/' || $f3->get('PATH') === '/index.php')) {
            $f3->reroute('./install/index.php');
        } else {
            header('HTTP/1.1 404 Page Not Found');
            echo 'Error ' . \EndoGuard\Utils\ErrorCodes::INCOMPLETE_CONFIG . ' Configuration is missing. Please visit /install/ to continue.';
            exit(0);
        }
    } else {
        header('HTTP/1.1 404 Page Not Found');
        echo 'Error ' . \EndoGuard\Utils\ErrorCodes::INCOMPLETE_CONFIG . ' Configuration and install/index.php are missing.';
        exit(0);
    }
}

//Load routes configuration
$f3->config('config/routes.ini');
$f3->config('config/apiEndpoints.ini');

//Override F3 host
\EndoGuard\Utils\Access::cleanHost();

if (\EndoGuard\Utils\Variables::getDB()) {
    //Load dictionary file
    $f3->set('LOCALES', 'app/Dictionary/');
    $f3->set('LANGUAGE', 'en');

    $constants = \EndoGuard\Utils\Constants::get();
    $cron = \EndoGuard\Controllers\Cron::instance();

    $f3->set('CONSTANTS', $constants);
}

$f3->run();
```

## A.2 Authentication & Login Module (`app/Controllers/Pages/Login.php`)

```php
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
```

## A.3 Custom API Endpoint Abstract (`app/Controllers/Api/Endpoint.php`)

```php
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
```

## A.4 Algorithm Implementation: Security Rules (`app/Models/Rules.php`)

```php
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

namespace EndoGuard\Models;

class Rules extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'dshb_rules';

    public function getAll(): array {
        $query = (
            'SELECT
                dshb_rules.uid,
                dshb_rules.validated,
                dshb_rules.name,
                dshb_rules.descr,
                dshb_rules.attributes,
                dshb_rules.missing

            FROM
                dshb_rules'
        );

        return $this->execQuery($query, null);
    }

    public function addRule(string $uid, string $name, string $descr, array $attr, bool $validated): void {
        $params = [
            ':validated'    => $validated,
            ':uid'          => $uid,
            ':name'         => $name,
            ':descr'        => $descr,
            ':attributes'   => json_encode($attr),
        ];

        $query = (
            'INSERT INTO dshb_rules (uid, name, descr, validated, attributes)
            VALUES (:uid, :name, :descr, :validated, :attributes)
            ON CONFLICT (uid) DO UPDATE
            SET name = EXCLUDED.name, descr = EXCLUDED.descr, validated = EXCLUDED.validated,
            attributes = EXCLUDED.attributes, updated = now(), missing = null'
        );

        $this->execQuery($query, $params);
    }

    public function setInvalidByUid(string $uid): void {
        $params = [
            ':uid'   => $uid,
        ];

        $query = (
            'UPDATE dshb_rules
            SET validated = false, updated = now()
            WHERE dshb_rules.uid = :uid'
        );

        $this->execQuery($query, $params);
    }

    public function setMissingByUid(string $uid): void {
        $params = [
            ':uid'   => $uid,
        ];

        $query = (
            'UPDATE dshb_rules
            SET missing = true, updated = now()
            WHERE dshb_rules.uid = :uid'
        );

        $this->execQuery($query, $params);
    }

    public function deleteByUid(string $uid): void {
        $params = [
            ':uid'   => $uid,
        ];

        $query = (
            'DELETE FROM dshb_rules WHERE uid = :uid'
        );

        $this->execQuery($query, $params);
    }
}
```

## A.5 API Implementation: Blacklist (`app/Controllers/Api/Blacklist.php`)

```php
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

namespace EndoGuard\Controllers\Api;

class Blacklist extends Endpoint {
    public function search(): void {
        $value = $this->getBodyProp('value', 'string');

        $model = new \EndoGuard\Models\BlacklistItems();
        $itemFound = $model->searchBlacklistedItem($value, $this->apiKeyId);

        $this->data = [
            'value'         => $value,
            'blacklisted'   => $itemFound,
        ];
    }
}
```

# Appendix B – Screenshots of Output / UI

*(Insert application interface screenshots here)*

1. **Dashboard Home Screen**
   *Description: The main dashboard showing real-time security alerts and system metrics.*
   *(Image placeholder)*

2. **Login and Authentication Flow**
   *Description: Secure login portal for administrative access.*
   *(Image placeholder)*

3. **Database Overview**
   *Description: Interface for inspecting active database configurations and schemas.*
   *(Image placeholder)*

# Appendix C – Database Details

*(Insert ER diagrams, table structures, and sample data here)*

### ER Diagram
*(Image placeholder for Entity-Relationship Diagram)*

### Table Structures

**`users` Table**
| Column | Type | Description |
| :--- | :--- | :--- |
| `id` | INT (PK) | Unique user identifier |
| `username` | VARCHAR(50) | User's login name |
| `password_hash` | VARCHAR(255) | Hashed password |
| `role` | VARCHAR(20) | Admin or standard user |

### Sample Data
*(Insert sample rows from critical tables to demonstrate data structure)*

# Appendix D – Test Cases

| Test Case ID | Module | Description | Input | Expected Output | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| TC-01 | Authentication | Admin Login | Valid credentials | Redirect to Dashboard | Pass |
| TC-02 | Authentication | Invalid Login | Incorrect password | Error: "Invalid credentials" | Pass |
| TC-03 | Database | Fetch Table Data | Table name 'users' | Returns JSON schema for 'users' | Pass |
| TC-04 | Core UI | Responsive Layout | Resize window to mobile width | UI collapses to hamburger menu | Pass |

# Appendix E – Extra Diagrams

*(Insert flowcharts and architecture diagrams here)*

1. **System Architecture Diagram**
   *Description: High-level overview of the backend services, database, and client interactions.*
   *(Image placeholder)*

2. **Data Flow Diagram (DFD)**
   *Description: Step-by-step data processing flow from client request to database retrieval.*
   *(Image placeholder)*

# Appendix F – Questionnaires / Survey

*(If applicable, include specific user feedback survey questions used to evaluate the UI/UX redesign).*

1. How intuitive did you find the new "Deep Obsidian" dashboard layout? (1-5)
2. Were you able to locate the database inspection tools without assistance? (Yes/No)
3. Any additional comments or feature requests? (Open text)

# Appendix G – User Manual

### Prerequisites
- Docker and Docker Compose installed on the host machine.
- Minimum 2GB RAM allocated to Docker.

### Steps to Run the Project
1. Open a terminal and navigate to the project directory:
   ```bash
   cd /path/to/EndoGuard
   ```
2. Build and start the containers using Docker Compose:
   ```bash
   docker-compose up -d --build
   ```
3. Once running, open a web browser and navigate to the application:
   ```
   http://localhost:8585
   ```

### How to Use the System
1. **Login**: Enter the provided administrative credentials.
2. **Dashboard**: Monitor the high-level metrics automatically displayed upon login.
3. **Database Inspection**: Use the sidebar navigation to access `inspect_table.php` and view current database health and schemas.
4. **Stopping the App**: To stop the running containers, execute `docker-compose down` in the terminal.
