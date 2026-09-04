<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class ArrayRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        return is_array($value);
    }
    public function message(): string { return 'The :field field must be an array.'; }
    public function name(): string { return 'array'; }
}
