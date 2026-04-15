<?php

namespace EndoGuard\Rules\Core;

class E10 extends \EndoGuard\Assets\Rule {
    public const NAME = 'The website is unavailable';
    public const DESCRIPTION = 'Domain\'s website seems to be inactive, which could be a sign of fake mailbox.';
    public const ATTRIBUTES = ['domain'];

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['ld_website_is_disabled']->equalTo(true),
            $this->rb['ld_domain_free_email_provider']->notEqualTo(true),
        );
    }
}
