<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class UrlRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        if (!is_string($value)) return false;
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    public function message(): string { return 'The :field format is invalid.'; }
    public function name(): string { return 'url'; }
}
