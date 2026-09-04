<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class DateRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        if (!is_string($value)) return false;
        return strtotime($value) !== false;
    }
    public function message(): string { return 'The :field is not a valid date.'; }
    public function name(): string { return 'date'; }
}
