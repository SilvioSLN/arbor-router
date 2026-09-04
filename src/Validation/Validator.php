<?php

declare(strict_types=1);

namespace Arbor\Router\Validation;

use Arbor\Router\Exception\ValidationException;

/**
 * Main data validation engine.
 *
 * Interprets validation rules in pipe and dot-notation formats and validates
 * payload data against registered RuleInterface instances.
 *
 * Usage:
 * ```php
 * $validator = new Validator();
 *
 * // 1. Safe validation (does not throw, returns ValidationResult):
 * $result = $validator->make($data, [
 *     'email' => 'required|email|max:100',
 *     'age'   => 'numeric|min:18',
 *     'tags'  => 'array',
 *     'items.*.id' => 'required|numeric',
 * ]);
 *
 * if ($result->fails()) {
 *     // Retrieve errors keyed by field: ['email' => ['...']]
 *     $errors = $result->errors();
 *     return ActionResult::error($errors);
 * }
 *
 * // 2. Strict validation (throws ValidationException if validation fails):
 * try {
 *     $validatedData = $validator->validate($data, ['email' => 'required|email']);
 * } catch (ValidationException $e) {
 *     $errors = $e->result?->errors();
 * }
 * ```
 *
 * Notice: Inside `action.php` and `page.php`, an initialized `$validator` instance
 * is already injected into your scope by the Router.
 *
 * @package Arbor\Router\Validation
 */
class Validator
{
    /** @var array<string, RuleInterface> */
    private array $rules = [];

    public function __construct(
        private readonly RuleParser $parser = new RuleParser(),
    ) {
        $this->registerBuiltInRules();
    }

    /**
     * Registers a custom validation rule.
     *
     * @param RuleInterface $rule Custom rule implementing RuleInterface
     * @return static Fluent interface
     */
    public function addRule(RuleInterface $rule): static
    {
        $this->rules[$rule->name()] = $rule;
        return $this;
    }

    /**
     * Validates data against rules and throws ValidationException if validation fails.
     *
     * @param array<string, mixed> $data Input payload to validate
     * @param array<string, string|string[]> $rules Validation rules
     * @return array<string, mixed> The input data if valid
     * @throws ValidationException When validation fails
     */
    public function validate(array $data, array $rules): array
    {
        $result = $this->make($data, $rules);
        
        if ($result->fails()) {
            throw new ValidationException(result: $result);
        }

        return $data;
    }

    /**
     * Validates data against rules and returns a ValidationResult without throwing.
     *
     * @param array<string, mixed> $data Input payload to validate
     * @param array<string, string|string[]> $rules Validation rules
     * @return ValidationResult Validation outcome (use ->passes(), ->fails(), ->errors())
     */
    public function make(array $data, array $rules): ValidationResult
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            // Expande dot notation (ex: items.*.name)
            $expandedFields = $this->parser->expandDotNotation($field, $data);
            $parsedRules = $this->parser->parse($fieldRules);

            foreach ($expandedFields as $expandedField => $value) {
                // Checa se o campo é nulo e não é required.
                $isRequired = false;
                foreach ($parsedRules as $parsedRule) {
                    if ($parsedRule['name'] === 'required') {
                        $isRequired = true;
                        break;
                    }
                }
                
                if (!$isRequired && ($value === null || $value === '')) {
                     continue; // Pula validação se não for obrigatório e estiver vazio
                }

                foreach ($parsedRules as $parsedRule) {
                    $ruleName = $parsedRule['name'];
                    $ruleParams = $parsedRule['parameters'];

                    if (!isset($this->rules[$ruleName])) {
                        throw new \InvalidArgumentException("Rule not found: {$ruleName}");
                    }

                    $ruleObj = $this->rules[$ruleName];
                    $isValid = $ruleObj->validate($value, $ruleParams, $expandedField, $data);

                    if (!$isValid) {
                        $message = $this->formatMessage($ruleObj->message(), $expandedField, $ruleParams);
                        $errors[$expandedField][] = $message;
                    }
                }
            }
        }

        return new ValidationResult($errors);
    }
    
    private function formatMessage(string $message, string $field, array $params): string
    {
        $formatted = str_replace(':field', $field, $message);
        foreach ($params as $i => $param) {
            $formatted = str_replace(":param{$i}", (string) $param, $formatted);
        }
        return $formatted;
    }

    private function registerBuiltInRules(): void
    {
        $this->addRule(new Rules\RequiredRule())
             ->addRule(new Rules\StringRule())
             ->addRule(new Rules\NumericRule())
             ->addRule(new Rules\EmailRule())
             ->addRule(new Rules\MinRule())
             ->addRule(new Rules\MaxRule())
             ->addRule(new Rules\PhoneRule())
             ->addRule(new Rules\DomainRule())
             ->addRule(new Rules\CpfRule())
             ->addRule(new Rules\CnpjRule())
             ->addRule(new Rules\ArrayRule())
             ->addRule(new Rules\InRule())
             ->addRule(new Rules\RegexRule())
             ->addRule(new Rules\UrlRule())
             ->addRule(new Rules\DateRule())
             ->addRule(new Rules\BooleanRule())
             ->addRule(new Rules\ConfirmedRule());
    }
}
