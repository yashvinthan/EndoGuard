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

namespace EndoGuard\Controllers\Admin\Data;

class Data extends \EndoGuard\Controllers\Admin\Base\Data {
    // POST requests
    public function enrichEntity(): array {
        $controller = new \EndoGuard\Controllers\Admin\Enrichment\Navigation();

        return $controller->enrichEntity();
    }

    public function saveRule(): array {
        $controller = new \EndoGuard\Controllers\Admin\Rules\Navigation();

        return $controller->saveRule();
    }

    public function removeFromBlacklist(): array {
        $controller = new \EndoGuard\Controllers\Admin\Blacklist\Navigation();

        return $controller->removeItemFromList();
    }

    public function removeFromWatchlist(): array {
        $controller = new \EndoGuard\Controllers\Admin\Watchlist\Navigation();

        return $controller->removeUserFromList();
    }

    public function manageUser(): array {
        $controller = new \EndoGuard\Controllers\Admin\User\Navigation();

        return $controller->manageUser();
    }

    // GET requests
    public function checkRule(): array {
        $controller = new \EndoGuard\Controllers\Admin\Rules\Navigation();

        return $controller->checkRule();
    }

    public function getTimeFrameTotal(): array {
        $controller = new \EndoGuard\Controllers\Admin\Totals\Navigation();

        return $controller->getTimeFrameTotal();
    }

    public function getCountries(): array {
        $controller = new \EndoGuard\Controllers\Admin\Countries\Navigation();

        return $controller->getList();
    }

    public function getMap(): array {
        $controller = new \EndoGuard\Controllers\Admin\Countries\Navigation();

        return $controller->getMap();
    }

    public function getIps(): array {
        $controller = new \EndoGuard\Controllers\Admin\IPs\Navigation();

        return $controller->getList();
    }

    public function getEvents(): array {
        $controller = new \EndoGuard\Controllers\Admin\Events\Navigation();

        return $controller->getList();
    }

    public function getLogbook(): array {
        $controller = new \EndoGuard\Controllers\Admin\Logbook\Navigation();

        return $controller->getList();
    }

    public function getUsers(): array {
        $controller = new \EndoGuard\Controllers\Admin\Users\Navigation();

        return $controller->getList();
    }

    public function getUserAgents(): array {
        $controller = new \EndoGuard\Controllers\Admin\UserAgents\Navigation();

        return $controller->getList();
    }

    public function getDevices(): array {
        $controller = new \EndoGuard\Controllers\Admin\Devices\Navigation();

        return $controller->getList();
    }

    public function getResources(): array {
        $controller = new \EndoGuard\Controllers\Admin\Resources\Navigation();

        return $controller->getList();
    }

    public function getDashboardStat(): array {
        $controller = new \EndoGuard\Controllers\Admin\Home\Navigation();

        return $controller->getDashboardStat();
    }

    public function getTopTen(): array {
        $controller = new \EndoGuard\Controllers\Admin\Home\Navigation();

        return $controller->getTopTen();
    }

    public function getChart(): array {
        $controller = new \EndoGuard\Controllers\Admin\Home\Navigation();

        return $controller->getChart();
    }

    public function getEventDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Events\Navigation();

        return $controller->getEventDetails();
    }

    public function getFieldEventDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\FieldAuditTrail\Navigation();

        return $controller->getFieldEventDetails();
    }

    public function getLogbookDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Logbook\Navigation();

        return $controller->getLogbookDetails();
    }

    public function getEmailDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Emails\Navigation();

        return $controller->getEmailDetails();
    }

    public function getPhoneDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Phones\Navigation();

        return $controller->getPhoneDetails();
    }

    public function getUserDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\UserDetails\Navigation();

        return $controller->getUserDetails();
    }

    /*public function getUserEnrichmentDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\UserDetails\Navigation();

        return $controller->getUserEnrichmentDetails();
    }*/

    public function getNotCheckedEntitiesCount(): array {
        $controller = new \EndoGuard\Controllers\Admin\Enrichment\Navigation();

        return $controller->getNotCheckedEntitiesCount();
    }

    public function getEmails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Emails\Navigation();

        return $controller->getList();
    }

    public function getPhones(): array {
        $controller = new \EndoGuard\Controllers\Admin\Phones\Navigation();

        return $controller->getList();
    }

    public function getFieldAuditTrail(): array {
        $controller = new \EndoGuard\Controllers\Admin\FieldAuditTrail\Navigation();

        return $controller->getList();
    }

    public function getFieldAudits(): array {
        $controller = new \EndoGuard\Controllers\Admin\FieldAudits\Navigation();

        return $controller->getList();
    }

    public function getUserScoreDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\User\Navigation();

        return $controller->getUserScoreDetails();
    }

    public function getIsps(): array {
        $controller = new \EndoGuard\Controllers\Admin\ISPs\Navigation();

        return $controller->getList();
    }

    public function getDomains(): array {
        $controller = new \EndoGuard\Controllers\Admin\Domains\Navigation();

        return $controller->getList();
    }

    public function getReviewUsersQueue(): array {
        $controller = new \EndoGuard\Controllers\Admin\ReviewQueue\Navigation();

        return $controller->getList();
    }

    public function getReviewUsersQueueCount(): array {
        $controller = new \EndoGuard\Controllers\Admin\ReviewQueue\Navigation();

        return $controller->setNotReviewedCount(false);     // no cache
    }

    public function getBlacklistUsersCount(): array {
        $controller = new \EndoGuard\Controllers\Admin\Blacklist\Navigation();

        return $controller->setBlacklistUsersCount(false);  // no cache
    }

    public function getIspDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\ISP\Navigation();

        return $controller->getIspDetails();
    }

    public function getIpDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\IP\Navigation();

        return $controller->getIpDetails();
    }

    public function getDeviceDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Devices\Navigation();

        return $controller->getDeviceDetails();
    }

    public function getUserAgentDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\UserAgent\Navigation();

        return $controller->getUserAgentDetails();
    }

    public function getDomainDetails(): array {
        $controller = new \EndoGuard\Controllers\Admin\Domain\Navigation();

        return $controller->getDomainDetails();
    }

    public function getSearchResults(): array {
        $controller = new \EndoGuard\Controllers\Admin\Search\Navigation();

        return $controller->getSearchResults();
    }

    public function getBlacklist(): array {
        $controller = new \EndoGuard\Controllers\Admin\Blacklist\Navigation();

        return $controller->getList();
    }

    public function getUsageStats(): array {
        $controller = new \EndoGuard\Controllers\Admin\Api\Navigation();

        return $controller->getUsageStats();
    }

    public function getCurrentTime(): array {
        $controller = new \EndoGuard\Controllers\Admin\Home\Navigation();

        return $controller->getCurrentTime();
    }

    public function getConstants(): array {
        $controller = new \EndoGuard\Controllers\Admin\Home\Navigation();

        return $controller->getConstants();
    }
}
