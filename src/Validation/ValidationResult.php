<?php

declare(strict_types=1);

namespace Arbor\Router\Validation;

/**
 * Immutable value object holding the outcome of a validation run.
 *
 * @package Arbor\Router\Validation
 */
final class ValidationResult
{
    /**
     * @param array<string, string[]> $errors Validation errors grouped by field name
     */
    public function __construct(
        private readonly array $errors = [],
    ) {}

    /**
     * Returns true if there are no validation errors.
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns true if there is at least one validation error.
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Returns all errors grouped by field name: `['field' => ['Error 1', 'Error 2']]`.
     *
     * @return array<string, string[]>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Returns all error messages for a specific field name.
     *
     * @return string[] List of error messages for the given field
     */
    public function errorsFor(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Returns the first error message for a specific field, or null if none.
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Returns a flat list of all error messages across all fields.
     *
     * @return string[] Flat list of error messages
     */
    public function allErrors(): array
    {
        $all = [];
        foreach ($this->errors as $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $all[] = $error;
            }
        }
        return $all;
    }

    /**
     * Returns errors as an associative array formatted for JSON serialization.
     *
     * @return array<string, string[]>
     */
    public function toArray(): array
    {
        return $this->errors;
    }
}
