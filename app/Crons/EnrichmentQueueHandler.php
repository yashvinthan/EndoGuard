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

namespace EndoGuard\Crons;

class EnrichmentQueueHandler extends BaseQueue {
    private \EndoGuard\Controllers\Admin\Enrichment\Data $controller;

    public function __construct() {
        $this->controller = new \EndoGuard\Controllers\Admin\Enrichment\Data();
    }

    public function process(): void {
        parent::baseProcess(\EndoGuard\Utils\Constants::get()->ENRICHMENT_QUEUE_ACTION_TYPE);
    }

    protected function processItem(array $item): void {
        $start = time();
        $apiKey = $item['key'];
        $userId = $item['event_account'];

        $entities = $this->controller->getNotCheckedEntitiesByUserId($userId, $apiKey);

        $key = (new \EndoGuard\Models\ApiKeys())->getKeyById($apiKey);
        $subscriptionKey = $key['token'];

        // TODO: check key ?
        $this->addLog(sprintf('Items to enrich for account %s: %s.', $userId, json_encode($entities)));

        $summary = [];
        $success = 0;
        $failed = 0;

        foreach ($entities as $type => $items) {
            if (count($items)) {
                $summary[$type] = count($items);
            }
            foreach ($items as $item) {
                $result = $this->controller->enrichEntity($type, null, $item, $apiKey, $subscriptionKey);
                if (isset($result['ERROR_CODE'])) {
                    $failed += 1;
                } else {
                    $success += 1;
                }
            }
        }

        // TODO: if failed !== 0 add to queue again?
        // TODO: recalculate score after all?
        $this->addLog(sprintf('Enrichment for account %s: %s enriched, %s failed in %s s (%s).', $userId, $success, $failed, time() - $start, json_encode($summary)));
    }
}
