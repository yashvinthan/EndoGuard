<?php

namespace EndoGuard\Rules\Core;

class E01 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Invalid email format';
    public const DESCRIPTION = 'Invalid email format. Should be \'username@domain.com\'.';
    public const ATTRIBUTES = [];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['le_is_invalid']->equalTo(true),
        );
    }
}
