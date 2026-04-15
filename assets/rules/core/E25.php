<?php

namespace EndoGuard\Rules\Core;

class E25 extends \EndoGuard\Assets\Rule {
    public const NAME = 'Military domain (.mil)';
    public const DESCRIPTION = 'Email belongs to military domain.';
    public const ATTRIBUTES = [];

    protected function prepareParams(array $params): array {
        $emailHasMil = false;
        foreach ($params['ee_email'] as $email) {
            if (str_ends_with($email, '.mil')) {
                $emailHasMil = true;
                break;
            }
        }

        $params['ee_has_mil'] = $emailHasMil;

        return $params;
    }

    protected function defineCondition(): \Ruler\Operator\LogicalOperator {
        return $this->rb->logicalAnd(
            $this->rb['ee_has_mil']->equalTo(true),
        );
    }
}
