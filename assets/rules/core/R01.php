<?php

namespace EndoGuard\Rules\Core;

class R01 extends \EndoGuard\Assets\Rule {
    public const NAME = 'IP in blacklist';
    public const DESCRIPTION = 'This IP address appears in the blacklist.';
    public const ATTRIBUTES = [];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['eip_has_fraud']->equalTo(true),
        );
    }
}
