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

namespace EndoGuard\Models\Chart;

abstract class BaseEventsCount extends \EndoGuard\Models\BaseSql {
    protected ?string $DB_TABLE_NAME = 'event';

    protected array $alertTypesParams;
    protected array $editTypesParams;
    protected array $normalTypesParams;

    protected string $alertFlatIds;
    protected string $editFlatIds;
    protected string $normalFlatIds;

    public function __construct() {
        parent::__construct();

        [$this->alertTypesParams, $this->alertFlatIds]      = $this->getArrayPlaceholders(\EndoGuard\Utils\Constants::get()->ALERT_EVENT_TYPES, 'alert');
        [$this->editTypesParams, $this->editFlatIds]        = $this->getArrayPlaceholders(\EndoGuard\Utils\Constants::get()->EDITING_EVENT_TYPES, 'edit');
        [$this->normalTypesParams, $this->normalFlatIds]    = $this->getArrayPlaceholders(\EndoGuard\Utils\Constants::get()->NORMAL_EVENT_TYPES, 'normal');
    }

    abstract public function getCounts(int $apiKey): array;

    public function getData(int $apiKey): array {
        $itemsByDate = [];
        $items = $this->getCounts($apiKey);

        foreach ($items as $item) {
            $itemsByDate[$item['ts']] = [
                $item['event_normal_type_count'],
                $item['event_editing_type_count'],
                $item['event_alert_type_count'],
            ];
        }
        // use offset shift because $startTs/$endTs compared with shifted ['ts']
        $offset = \EndoGuard\Utils\Timezones::getCurrentOperatorOffset();
        $datesRange = \EndoGuard\Utils\DateRange::getLatestNDatesRangeFromRequest(180, $offset);
        $endTs = strtotime($datesRange['endDate']);
        $startTs = strtotime($datesRange['startDate']);
        $step = \EndoGuard\Utils\Constants::get()->CHART_RESOLUTION[\EndoGuard\Utils\DateRange::getResolutionFromRequest()];

        $endTs = $endTs - ($endTs % $step);
        $startTs = $startTs - ($startTs % $step);

        while ($endTs >= $startTs) {
            if (!isset($itemsByDate[$startTs])) {
                $itemsByDate[$startTs] = [null, null, null];
            }

            $startTs += $step;
        }

        ksort($itemsByDate);

        $timestamps = [];
        $line1 = [];
        $line2 = [];
        $line3 = [];

        foreach ($itemsByDate as $key => $value) {
            $timestamps[] = $key;
            $line1[] = $value[0];
            $line2[] = $value[1];
            $line3[] = $value[2];
        }

        return [$timestamps, $line1, $line2, $line3];
    }

    protected function executeOnRangeById(string $query, int $apiKey): array {
        // do not use offset because :start_time/:end_time compared with UTC event.time
        $dateRange = \EndoGuard\Utils\DateRange::getLatestNDatesRangeFromRequest(180);
        $offset = \EndoGuard\Utils\Timezones::getCurrentOperatorOffset();

        $params = [
            ':api_key'      => $apiKey,
            ':end_time'     => $dateRange['endDate'],
            ':start_time'   => $dateRange['startDate'],
            ':resolution'   => \EndoGuard\Utils\DateRange::getResolutionFromRequest(),
            ':id'           => \EndoGuard\Utils\Conversion::getIntRequestParam('id'),
            ':offset'       => strval($offset),     // str for postgres
        ];

        $params = array_merge($params, $this->alertTypesParams);
        $params = array_merge($params, $this->editTypesParams);
        $params = array_merge($params, $this->normalTypesParams);

        return $this->execQuery($query, $params);
    }
}
