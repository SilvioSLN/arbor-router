<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class BooleanRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        return in_array($value, [true, false, 1, 0, '1', '0', 'true', 'false'], true);
    }
    public function message(): string { return 'The :field field must be true or false.'; }
    public function name(): string { return 'boolean'; }
}
