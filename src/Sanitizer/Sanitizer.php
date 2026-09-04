<?php

declare(strict_types=1);

namespace Arbor\Router\Sanitizer;

/**
 * Motor de sanitização de dados.
 *
 * @package Arbor\Router\Sanitizer
 */
class Sanitizer implements SanitizerInterface
{
    /** @var array<string, callable> */
    private array $filters = [];

    public function __construct()
    {
        $this->registerBuiltInFilters();
    }

    /**
     * Registra um filtro customizado.
     */
    public function addFilter(string $name, callable $filter): static
    {
        $this->filters[$name] = $filter;
        return $this;
    }

    /**
     * Sanitiza os dados.
     */
    public function sanitize(array $data, array $rules): array
    {
        $sanitized = $data;

        foreach ($rules as $field => $fieldFilters) {
            if (!isset($sanitized[$field])) {
                continue;
            }

            $filters = is_string($fieldFilters) ? explode('|', $fieldFilters) : $fieldFilters;
            $value = $sanitized[$field];

            foreach ($filters as $filterStr) {
                $filterStr = trim($filterStr);
                if ($filterStr === '') continue;

                $parts = explode(':', $filterStr);
                $filterName = $parts[0];
                $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

                if (isset($this->filters[$filterName])) {
                    $filterCb = $this->filters[$filterName];
                    $value = $filterCb($value, $params);
                }
            }

            $sanitized[$field] = $value;
        }

        return $sanitized;
    }

    private function registerBuiltInFilters(): void
    {
        $this->addFilter('trim', fn($v) => is_string($v) ? trim($v) : $v);
        $this->addFilter('strip_tags', fn($v) => is_string($v) ? strip_tags($v) : $v);
        $this->addFilter('htmlentities', fn($v) => is_string($v) ? htmlentities($v, ENT_QUOTES, 'UTF-8') : $v);
        $this->addFilter('lowercase', fn($v) => is_string($v) ? mb_strtolower($v) : $v);
        $this->addFilter('uppercase', fn($v) => is_string($v) ? mb_strtoupper($v) : $v);
        $this->addFilter('numeric', fn($v) => is_numeric($v) ? (str_contains((string)$v, '.') ? (float)$v : (int)$v) : preg_replace('/[^0-9.]/', '', (string)$v));
        $this->addFilter('slug', function($v) {
            if (!is_string($v)) return $v;
            $slug = mb_strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $v), '-'));
            return $slug;
        });
    }
}
