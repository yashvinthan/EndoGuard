<?php

namespace EndoGuard\Rules\Core;

class B04 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Multiple 5xx errors';
    public const DESCRIPTION = 'The user made multiple requests which evoked internal server error.';
    public const ATTRIBUTES = [];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['event_multiple_5xx_http']->greaterThan(\EndoGuard\Utils\Constants::get()->RULE_MAXIMUM_NUMBER_OF_500_CODES),
        );
    }
}
