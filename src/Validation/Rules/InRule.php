<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class InRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        return in_array((string)$value, $parameters, true);
    }
    public function message(): string { return 'The selected :field is invalid.'; }
    public function name(): string { return 'in'; }
}
