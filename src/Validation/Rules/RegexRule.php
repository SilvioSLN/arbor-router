<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class RegexRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        if (!is_string($value) && !is_numeric($value)) return false;
        $pattern = implode(',', $parameters);
        return preg_match($pattern, (string)$value) > 0;
    }
    public function message(): string { return 'The :field format is invalid.'; }
    public function name(): string { return 'regex'; }
}
