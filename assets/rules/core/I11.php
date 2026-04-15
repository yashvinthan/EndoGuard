<?php

namespace EndoGuard\Rules\Core;

class I11 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Single network';
    public const DESCRIPTION = 'IP addresses belong to one network.';
    public const ATTRIBUTES = ['ip'];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['eip_unique_cidrs']->equalTo(1),
        );
    }
}
