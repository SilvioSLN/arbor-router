<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class ConfirmedRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        $confirmField = $field . '_confirmation';
        $confirmValue = $allData[$confirmField] ?? null;
        return $value === $confirmValue;
    }
    public function message(): string { return 'The :field confirmation does not match.'; }
    public function name(): string { return 'confirmed'; }
}
