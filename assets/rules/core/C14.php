<?php

namespace EndoGuard\Rules\Core;

class C14 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Australia IP address';
    public const DESCRIPTION = 'IP address located in Australia.';
    public const ATTRIBUTES = ['ip'];

    protected function prepareParams(array $params): array {
        $params['eip_has_specific_country'] = in_array(\EndoGuard\Utils\Constants::get()->COUNTRY_CODE_AUSTRALIA, $params['eip_country_id']);

        return $params;
    }

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['eip_has_specific_country']->equalTo(true),
        );
    }
}
