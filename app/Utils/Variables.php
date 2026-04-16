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

namespace EndoGuard\Utils;

class Variables {
    private static function getF3(): \Base {
        return \Base::instance();
    }

    public static function getDB(): ?string {
        return getenv('DATABASE_URL') ?: self::getF3()->get('DATABASE_URL');
    }

    public static function getConfigFile(): string {
        return getenv('CONFIG_FILE') ?: 'local/config.local.ini';
    }

    public static function getHosts(): array {
        $env = getenv('SITE');
        $conf = self::getF3()->get('SITE');

        return $env ? explode(',', $env) : (is_array($conf) ? $conf : [$conf]);
    }

    public static function getHost(): string {
        $httpHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '';

        if ($httpHost) {
            return $httpHost;
        }

        $hosts = self::getHosts();

        return $hosts[0];
    }

    public static function getAdminEmail(): ?string {
        return getenv('ADMIN_EMAIL') ?: self::getF3()->get('ADMIN_EMAIL');
    }

    public static function getMailLogin(): ?string {
        return getenv('MAIL_LOGIN') ?: self::getF3()->get('MAIL_LOGIN');
    }

    public static function getMailPassword(): ?string {
        return getenv('MAIL_PASS') ?: self::getF3()->get('MAIL_PASS');
    }

    public static function getEnrichmentApi(): string {
        return getenv('ENRICHMENT_API') ?: self::getF3()->get('ENRICHMENT_API');
    }

    public static function getPepper(): string {
        return getenv('PEPPER') ?: self::getF3()->get('PEPPER');
    }

    public static function getLogbookLimit(): int {
        $value = getenv('LOGBOOK_LIMIT') ?: self::getF3()->get('LOGBOOK_LIMIT') ?: \EndoGuard\Utils\Constants::get()->LOGBOOK_LIMIT;

        return \EndoGuard\Utils\Conversion::intValCheckEmpty($value, \EndoGuard\Utils\Constants::get()->LOGBOOK_LIMIT);
    }

    public static function getForgotPasswordAllowed(): bool {
        $variable = getenv('ALLOW_FORGOT_PASSWORD') ?: self::getF3()->get('ALLOW_FORGOT_PASSWORD') ?? 'false';

        return \EndoGuard\Utils\Conversion::filterBool($variable) ?? false;
    }

    public static function getEmailPhoneAllowed(): bool {
        $variable = getenv('ALLOW_EMAIL_PHONE') ?: self::getF3()->get('ALLOW_EMAIL_PHONE') ?? 'false';

        return \EndoGuard\Utils\Conversion::filterBool($variable) ?? false;
    }

    public static function getForceHttps(): bool {
        // set 'false' string if FORCE_HTTPS wasn't set due to filter_var() issues
        $variable = getenv('FORCE_HTTPS') ?: self::getF3()->get('FORCE_HTTPS') ?? 'false';

        return \EndoGuard\Utils\Conversion::filterBool($variable) ?? true;
    }

    public static function getHostWithProtocol(): string {
        $host = self::getHost();

        if (!str_starts_with($host, '[') && \EndoGuard\Utils\Conversion::filterIpGetType($host) === 6) {
            $host = '[' . $host . ']';
        }

        return (self::getForceHttps() ? 'https://' : 'http://') . $host;
    }

    public static function getHostWithProtocolAndBase(): string {
        return self::getHostWithProtocol() . self::getF3()->get('BASE');
    }

    public static function getAccountOperationQueueBatchSize(): int {
        return \EndoGuard\Utils\Conversion::intValCheckEmpty(getenv('ACCOUNT_OPERATION_QUEUE_BATCH_SIZE'), \EndoGuard\Utils\Constants::get()->ACCOUNT_OPERATION_QUEUE_BATCH_SIZE);
    }

    public static function getNewEventsBatchSize(): int {
        return \EndoGuard\Utils\Conversion::intValCheckEmpty(getenv('NEW_EVENTS_BATCH_SIZE'), \EndoGuard\Utils\Constants::get()->NEW_EVENTS_BATCH_SIZE);
    }

    public static function getRuleUsersBatchSize(): int {
        return \EndoGuard\Utils\Conversion::intValCheckEmpty(getenv('RULE_USERS_BATCH_SIZE'), \EndoGuard\Utils\Constants::get()->RULE_USERS_BATCH_SIZE);
    }

    public static function getAvailableTimezones(): array {
        return array_intersect_key(self::getF3()->get('timezones'), array_flip(\DateTimeZone::listIdentifiers()));
    }

    public static function completedConfig(): bool {
        return
            (getenv('SITE') || self::getF3()->get('SITE')) &&
            (getenv('PEPPER') || self::getF3()->get('PEPPER')) &&
            (getenv('ENRICHMENT_API') || self::getF3()->get('ENRICHMENT_API')) &&
            (getenv('DATABASE_URL') || self::getF3()->get('DATABASE_URL'));
    }
}
