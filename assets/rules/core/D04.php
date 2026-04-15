<?php

namespace EndoGuard\Rules\Core;

class D04 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Rare browser device';
    public const DESCRIPTION = 'User operates device with uncommon browser.';
    public const ATTRIBUTES = [];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['eup_has_rare_browser']->equalTo(true),
        );
    }
}
