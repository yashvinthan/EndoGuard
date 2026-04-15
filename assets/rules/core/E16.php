<?php

namespace EndoGuard\Rules\Core;

class E16 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Domain appears in spam lists';
    public const DESCRIPTION = 'Email appears in spam lists, so the user may have spammed before.';
    public const ATTRIBUTES = ['domain'];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['ld_from_blockdomains']->equalTo(true),
        );
    }
}
