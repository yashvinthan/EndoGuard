<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Enrichment;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Enrichment utility.
 *
 * Notes about current behavior (important):
 * - calculateEmailReputation() treats blockemails = null as "not blocked" because !null === true,
 *   so it adds +1 to reputation level.
 * - calculateEmailReputationForContext() sets missing ee_* fields to false, not null, therefore
 *   missing fields produce "medium" (0 + 1) instead of "none".
 *
 * @todo Refactor Enrichment::calculateEmailReputation():
 *   - Fix condition: it checks data_breach twice:
 *       if ($record['data_breach'] !== null && $record['data_breach'] !== null)
 *     should likely be:
 *       if ($record['data_breach'] !== null && $record['blockemails'] !== null)
 *
 * @todo Refactor Enrichment::calculateEmailReputationForContext():
 *   - Decide what "missing fields" means.
 *   - If missing should be "none", set defaults to null (not false) and fix condition above.
 */
final class EnrichmentTest extends TestCase {
    /**
     * @dataProvider ipTypeProvider
     */
    public function testCalculateIpTypeMapsAndCleansFlags(array $record, string $expectedType): void {
        $records = [$record];

        Enrichment::calculateIpType($records);

        $this->assertArrayHasKey('ip_type', $records[0]);
        $this->assertSame($expectedType, $records[0]['ip_type']);

        $this->assertArrayNotHasKey('tor', $records[0]);
        $this->assertArrayNotHasKey('starlink', $records[0]);
        $this->assertArrayNotHasKey('relay', $records[0]);
        $this->assertArrayNotHasKey('vpn', $records[0]);
        $this->assertArrayNotHasKey('data_center', $records[0]);
    }

    public static function ipTypeProvider(): array {
        $base = [
            'fraud_detected' => false,
            'blocklist' => false,
            'country_id' => 1,
            'checked' => true,
            'tor' => false,
            'starlink' => false,
            'relay' => false,
            'vpn' => false,
            'data_center' => false,
        ];

        $blacklisted = $base;
        $blacklisted['fraud_detected'] = true;

        $spam = $base;
        $spam['blocklist'] = true;

        $localhost = $base;
        $localhost['country_id'] = 0;
        $localhost['checked'] = true;

        $tor = $base;
        $tor['tor'] = true;

        $starlink = $base;
        $starlink['starlink'] = true;

        $relay = $base;
        $relay['relay'] = true;

        $vpn = $base;
        $vpn['vpn'] = true;

        $datacenter = $base;
        $datacenter['data_center'] = true;

        $unchecked = $base;
        $unchecked['checked'] = false;

        $priority = $base;
        $priority['fraud_detected'] = true;
        $priority['blocklist'] = true;
        $priority['country_id'] = 0;
        $priority['tor'] = true;
        $priority['starlink'] = true;
        $priority['relay'] = true;
        $priority['vpn'] = true;
        $priority['data_center'] = true;
        $priority['checked'] = true;

        return [
            'blacklisted wins' => [$blacklisted, 'Blacklisted'],
            'spam list' => [$spam, 'Spam list'],
            'localhost when country=0 and checked' => [$localhost, 'Localhost'],
            'tor' => [$tor, 'TOR'],
            'starlink' => [$starlink, 'Starlink'],
            'apple relay' => [$relay, 'AppleRelay'],
            'vpn' => [$vpn, 'VPN'],
            'datacenter' => [$datacenter, 'Datacenter'],
            'unchecked forces unknown' => [$unchecked, 'Unknown'],
            'default residential' => [$base, 'Residential'],
            'priority order (blacklisted first)' => [$priority, 'Blacklisted'],
        ];
    }

    /**
     * @dataProvider emailReputationProvider
     */
    public function testCalculateEmailReputationMapsToExpectedLevel(array $record, string $fieldName, string $expected): void {
        $records = [$record];

        Enrichment::calculateEmailReputation($records, $fieldName);

        $this->assertArrayHasKey($fieldName, $records[0]);
        $this->assertSame($expected, $records[0][$fieldName]);
    }

    public static function emailReputationProvider(): array {
        return [
            // Core matrix (data_breach int + !blockemails int)
            'high when breach=1 and blockemails=false' => [
                ['data_breach' => 1, 'blockemails' => false],
                'reputation',
                'high',
            ],
            'medium when breach=1 and blockemails=true' => [
                ['data_breach' => 1, 'blockemails' => true],
                'reputation',
                'medium',
            ],
            'medium when breach=0 and blockemails=false' => [
                ['data_breach' => 0, 'blockemails' => false],
                'reputation',
                'medium',
            ],
            'low when breach=0 and blockemails=true' => [
                ['data_breach' => 0, 'blockemails' => true],
                'reputation',
                'low',
            ],

            // Current (quirky) behavior: blockemails=null => !null === true => +1
            'high when breach=1 and blockemails=null (because !null === true)' => [
                ['data_breach' => 1, 'blockemails' => null],
                'reputation',
                'high',
            ],
            'medium when breach=0 and blockemails=null (because !null === true)' => [
                ['data_breach' => 0, 'blockemails' => null],
                'reputation',
                'medium',
            ],

            // Current (quirky) behavior: condition checks data_breach twice, so blockemails=null still enters.
            'none when data_breach=null and blockemails=null (condition fails)' => [
                ['data_breach' => null, 'blockemails' => null],
                'reputation',
                'none',
            ],

            // Custom field name
            'writes to custom field name' => [
                ['data_breach' => 1, 'blockemails' => false],
                'custom_reputation',
                'high',
            ],
        ];
    }

    public function testCalculateEmailReputationForContextWritesEeReputationAndRemovesTempFields(): void {
        $records = [
            [
                'ee_data_breach' => 1,
                'ee_blockemails' => false,
            ],
        ];

        Enrichment::calculateEmailReputationForContext($records);

        $this->assertArrayHasKey('ee_reputation', $records[0]);
        $this->assertSame('high', $records[0]['ee_reputation']);

        $this->assertArrayNotHasKey('data_breach', $records[0]);
        $this->assertArrayNotHasKey('blockemails', $records[0]);
    }

    public function testCalculateEmailReputationForContextDefaultsToMediumWhenFieldsMissing(): void {
        // Current behavior:
        // ee_data_breach missing => false
        // ee_blockemails missing => false
        // condition passes (false !== null) and computes:
        // intVal(false)=0 + intVal(!false)=1 => 1 => "medium"
        $records = [[]];

        Enrichment::calculateEmailReputationForContext($records);

        $this->assertArrayHasKey('ee_reputation', $records[0]);
        $this->assertSame('medium', $records[0]['ee_reputation']);
    }

    /**
     * @dataProvider deviceParamsProvider
     */
    public function testApplyDeviceParamsBuildsDerivedFields(array $record, array $expected): void {
        $records = [$record];

        Enrichment::applyDeviceParams($records);

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $records[0]);
            $this->assertSame($value, $records[0][$key]);
        }
    }

    public static function deviceParamsProvider(): array {
        return [
            'complete record' => [
                [
                    'device' => 'desktop',
                    'browser_name' => 'Chrome',
                    'browser_version' => '120.0',
                    'os_name' => 'Windows',
                    'os_version' => '10',
                ],
                [
                    'os' => 'Windows 10',
                    'browser' => 'Chrome 120.0',
                    'device_name' => 'desktop',
                ],
            ],
            'missing optional keys => defaults applied' => [
                [],
                [
                    'os' => ' ',
                    'browser' => ' ',
                    'device_name' => 'unknown',
                ],
            ],
            'partial record' => [
                [
                    'device' => 'tablet',
                    'browser_name' => 'Safari',
                    'os_name' => 'iOS',
                ],
                [
                    'os' => 'iOS ',
                    'browser' => 'Safari ',
                    'device_name' => 'tablet',
                ],
            ],
        ];
    }

    public function testCalculateIpTypeProcessesMultipleRecords(): void {
        $records = [
            [
                'fraud_detected' => true,
                'blocklist' => false,
                'country_id' => 1,
                'checked' => true,
                'tor' => false,
                'starlink' => false,
                'relay' => false,
                'vpn' => false,
                'data_center' => false,
            ],
            [
                'fraud_detected' => false,
                'blocklist' => false,
                'country_id' => 1,
                'checked' => true,
                'tor' => true,
                'starlink' => false,
                'relay' => false,
                'vpn' => false,
                'data_center' => false,
            ],
            [
                'fraud_detected' => false,
                'blocklist' => false,
                'country_id' => 1,
                'checked' => true,
                'tor' => false,
                'starlink' => false,
                'relay' => false,
                'vpn' => false,
                'data_center' => false,
            ],
        ];

        Enrichment::calculateIpType($records);

        $this->assertSame('Blacklisted', $records[0]['ip_type']);
        $this->assertSame('TOR', $records[1]['ip_type']);
        $this->assertSame('Residential', $records[2]['ip_type']);
    }
}
