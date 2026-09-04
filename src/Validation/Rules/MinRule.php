<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class MinRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        $min = (int)($parameters[0] ?? 0);
        if (is_string($value)) return mb_strlen($value) >= $min;
        if (is_numeric($value)) return (float)$value >= $min;
        if (is_array($value)) return count($value) >= $min;
        return false;
    }
    public function message(): string { return 'The :field field must be at least :param0.'; }
    public function name(): string { return 'min'; }
}
