<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class MaxRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        $max = (int)($parameters[0] ?? PHP_INT_MAX);
        if (is_string($value)) return mb_strlen($value) <= $max;
        if (is_numeric($value)) return (float)$value <= $max;
        if (is_array($value)) return count($value) <= $max;
        return false;
    }
    public function message(): string { return 'The :field field must not exceed :param0.'; }
    public function name(): string { return 'max'; }
}
