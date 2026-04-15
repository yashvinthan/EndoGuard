<?php

declare(strict_types=1);

namespace Tests\Unit\Utils;

use EndoGuard\Utils\Rules;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EndoGuard\Utils\Rules.
 *
 * Covered (unit-testable without refactor):
 * - Rules::checkPhoneCountryMatchIp() (pure array logic, null/0 handling)
 * - Rules::countryIsNewByIpId() (pure lookup + count==1)
 * - Rules::cidrIsNewByIpId() (pure lookup + count==1)
 *
 * Not covered (recommended to refactor first):
 * - Rules::eventDeviceIsNew() (depends on Constants::get('RULE_NEW_DEVICE_MAX_AGE_IN_SECONDS'); hard static/global)
 *
 * @todo Refactor:
 * - extract RULE_NEW_DEVICE_MAX_AGE_IN_SECONDS behind an interface:
 *   RulesConfigInterface (getNewDeviceMaxAgeSeconds(): int)
 * - then eventDeviceIsNew() becomes deterministic and properly unit-testable.
 */
final class RulesTest extends TestCase {
    /**
     * @dataProvider checkPhoneCountryMatchIpProvider
     */
    public function testCheckPhoneCountryMatchIpReturnsExpected(?int $lpCountryCode, array $eipCountryId, ?bool $expected): void {
        $params = [
            'lp_country_code' => $lpCountryCode,
            'eip_country_id' => $eipCountryId,
        ];

        $result = Rules::checkPhoneCountryMatchIp($params);

        $this->assertSame($expected, $result);
    }

    public static function checkPhoneCountryMatchIpProvider(): array {
        return [
            'null country => null' => [
                null,
                [1, 2],
                null,
            ],
            '0 country => null' => [
                0,
                [1, 2],
                null,
            ],
            'match => true' => [
                2,
                [1, 2, 3],
                true,
            ],
            'no match => false' => [
                9,
                [1, 2, 3],
                false,
            ],
        ];
    }

    /**
     * @dataProvider countryIsNewByIpIdProvider
     */
    public function testCountryIsNewByIpIdReturnsExpected(array $params, int $ipId, bool $expected): void {
        $result = Rules::countryIsNewByIpId($params, $ipId);

        $this->assertSame($expected, $result);
    }

    public static function countryIsNewByIpIdProvider(): array {
        return [
            'ipId not found => countryId null => count null => false' => [
                [
                    'eip_ip_id' => [],
                    'eip_country_count' => [],
                ],
                10,
                false,
            ],
            'country count == 1 => true' => [
                [
                    'eip_ip_id' => [
                        7 => ['country' => 5],
                    ],
                    'eip_country_count' => [
                        5 => 1,
                    ],
                ],
                7,
                true,
            ],
            'country count > 1 => false' => [
                [
                    'eip_ip_id' => [
                        7 => ['country' => 5],
                    ],
                    'eip_country_count' => [
                        5 => 3,
                    ],
                ],
                7,
                false,
            ],
            'country missing in count => false' => [
                [
                    'eip_ip_id' => [
                        7 => ['country' => 5],
                    ],
                    'eip_country_count' => [
                        9 => 1,
                    ],
                ],
                7,
                false,
            ],
        ];
    }

    /**
     * @dataProvider cidrIsNewByIpIdProvider
     */
    public function testCidrIsNewByIpIdReturnsExpected(array $params, int $ipId, bool $expected): void {
        $result = Rules::cidrIsNewByIpId($params, $ipId);

        $this->assertSame($expected, $result);
    }

    public static function cidrIsNewByIpIdProvider(): array {
        return [
            'ipId not found => cidr null => count null => false' => [
                [
                    'eip_ip_id' => [],
                    'eip_cidr_count' => [],
                ],
                10,
                false,
            ],
            'cidr count == 1 => true' => [
                [
                    'eip_ip_id' => [
                        7 => ['cidr' => '1.2.3.0/24'],
                    ],
                    'eip_cidr_count' => [
                        '1.2.3.0/24' => 1,
                    ],
                ],
                7,
                true,
            ],
            'cidr count > 1 => false' => [
                [
                    'eip_ip_id' => [
                        7 => ['cidr' => '1.2.3.0/24'],
                    ],
                    'eip_cidr_count' => [
                        '1.2.3.0/24' => 2,
                    ],
                ],
                7,
                false,
            ],
            'cidr missing in count => false' => [
                [
                    'eip_ip_id' => [
                        7 => ['cidr' => '1.2.3.0/24'],
                    ],
                    'eip_cidr_count' => [
                        '9.9.9.0/24' => 1,
                    ],
                ],
                7,
                false,
            ],
        ];
    }
}
