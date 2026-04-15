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

namespace EndoGuard\Models\Enrichment;

class LocalhostIp extends \EndoGuard\Models\Enrichment\Base {
    protected string $ip;           // ipvanyaddress
    protected int $country = 0;
    protected ?int $asn = 0;
    protected string $name = 'Local area network';
    protected ?string $description = null;
    protected bool $checked = true;
    protected ?int $isp;

    public function __construct() {
        // empty
    }

    public function init(array $data): void {
        $this->ip = $data['value'];

        if (!\EndoGuard\Utils\Conversion::filterIp($this->ip) || $data['error'] !== \EndoGuard\Utils\Constants::get()->ENRICHMENT_IP_IS_BOGON) {
            throw new \Exception('Validation failed');
        }
    }

    public function prepareUpdate(): array {
        $params = $this->queryParams();
        unset($params[':ip']);

        // set $params[':isp'] later
        unset($params[':asn']);
        unset($params[':name']);
        unset($params[':description']);
        $placeholders = array_keys($params);
        $updateString = $this->updateStringByPlaceholders($placeholders);

        return [$params, $updateString];
    }

    // TODO: update countries table counters
    public function updateEntityInDb(int $entityId, int $apiKey): void {
        $ipModel = new \EndoGuard\Models\Ip();

        $previousIpData = $ipModel->getFullIpInfoById($entityId, $apiKey);
        $previousIspId = count($previousIpData) ? $previousIpData['ispid'] : null;
        $previousCountryId = count($previousIpData) ? $previousIpData['country_id'] : 0;
        // get current isp id
        $ispModel = new \EndoGuard\Models\Isp();
        $newIspId = $ispModel->getIdByAsn($this->asn, $apiKey);

        $newIspData = [
            'asn'           => $this->asn,
            'name'          => $this->name,
            'description'   => $this->description,
        ];
        $newIspModel = new \EndoGuard\Models\Enrichment\Isp();
        $newIspModel->init($newIspData);

        // new isp is not in db
        if ($newIspId === null) {
            $newIspData['lastseen'] = $previousIpData['lastseen'];
            $newIspId = $ispModel->insertRecord($newIspData, $apiKey);
        } else {
            $newIspModel->updateEntityInDb($newIspId, $apiKey);
        }

        $this->isp = $newIspId;

        $countryModel = new \EndoGuard\Models\Country();
        $newCountryId = $this->country;

        $countryRecord = $countryModel->getCountryById($newCountryId, $apiKey);
        if (!count($countryRecord)) {
            $newCountryData = [
                'id'        => $newCountryId,
                'lastseen'  => $previousIpData['lastseen'],
            ];
            $countryModel->insertRecord($newCountryData, $apiKey);
        }

        // total_visit and total_account should remain still
        [$params, $updateString] = $this->prepareUpdate();

        $params[':country']     = $newCountryId;
        $params[':entity_id']   = $entityId;
        $params[':key']         = $apiKey;

        $query = ("
            UPDATE event_ip
            SET {$updateString}
            WHERE
                event_ip.id = :entity_id AND
                event_ip.key = :key
        ");

        $model = new \EndoGuard\Models\Ip();
        $model->execQuery($query, $params);

        // update totals only after event_ip update!
        $ispIds = $this->slimIds([$previousIspId, $newIspId]);
        $ispModel->updateTotalsByEntityIds($ispIds, $apiKey, true);

        $countryIds = $this->slimIds([$previousCountryId, $newCountryId]);
        $countryModel->updateTotalsByEntityIds($countryIds, $apiKey, true);
    }
}
