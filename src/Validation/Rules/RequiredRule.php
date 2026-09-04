<?php

declare(strict_types=1);

namespace Arbor\Router\Validation\Rules;

use Arbor\Router\Validation\RuleInterface;

class RequiredRule implements RuleInterface
{
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool
    {
        if ($value === null) return false;
        if (is_string($value) && trim($value) === '') return false;
        if (is_array($value) && empty($value)) return false;
        return true;
    }

    public function message(): string { return 'The :field field is required.'; }
    public function name(): string { return 'required'; }
}
