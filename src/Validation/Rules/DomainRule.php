<?php declare(strict_types=1); namespace Arbor\Router\Validation\Rules; use Arbor\Router\Validation\RuleInterface;
class DomainRule implements RuleInterface {
    public function validate(mixed $value, array $parameters, string $field, array $allData): bool {
        if (!is_string($value)) return false;
        return (bool) preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $value);
    }
    public function message(): string { return 'The :field field must be a valid domain name.'; }
    public function name(): string { return 'domain'; }
}
