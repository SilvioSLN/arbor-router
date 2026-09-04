# Instructions for AI Agents: Arbor Router (`silviosln/arbor-router`)

This document is the master operational guideline for AI coding agents (Claude, Gemini, GPT, Cursor, etc.) working with `silviosln/arbor-router`. Follow these rules strictly to ensure 100% correct, bug-free, and idiomatic code generation.

---

## 1. Core Identity & Ground Truth

- **Package**: `silviosln/arbor-router` (Composer)
- **Namespace**: `Arbor\Router\`
- **PHP Version**: `^8.4` (strict types `declare(strict_types=1);` mandatory)
- **Core Concept**: File-system based router for PHP inspired by Next.js App Router.
- **Root Directory for Routes**: Configured via `'appDir'`, typically `__DIR__ . '/../app'`.

---

## 2. File-System Routing Conventions

In the `appDir` tree, folder paths correspond directly to URLs. Files have special meanings:

| File Name | Purpose | What it returns / receives |
| :--- | :--- | :--- |
| `page.php` | Renders HTML views. Wrapped in layouts. | `return function(Request $request, array $params) { return "HTML"; };` OR echoes HTML. Injected: `$request`, `$params`, `$query`, `$body`, `$validator`, `$sanitizer`. |
| `layout.php` | Cascading layout. Wraps child pages and layouts. | Returns HTML with `{{content}}` placeholder or uses `$children` variable. Injected: `$request`, `$params`, `$children`. |
| `layoutroot.php` | Root boundary layout (shell: `<html><head><body>`). Stops upward layout search. | Returns HTML with `{{content}}` or echoes `$children`. |
| `route.php` | API endpoint with HTTP method dispatch. | Returns associative array `['GET' => function($req, $params, $query) {...}, 'POST' => function($req, $params, $body) {...}]` OR defines functions named after verbs. Auto-serializes via `ContentNegotiator`. |
| `action.php` | Server Action for form mutations (POST/PUT/DELETE/PATCH). | Returns `ActionResult` or array `['success' => true, 'redirect' => '/path']`. Injected: `$request`, `$params`, `$query`, `$body`, `$validator`, `$sanitizer`. |
| `middleware.php` | Scoped Russian Doll middleware pipeline. | `return function(RequestInterface $request, Closure $next): Response { ... return $next($request); };` |
| `error.php` | Custom 500 error boundary for directory subtree. | Injected: `$request`, `$error`. |
| `not-found.php` | Custom 404 page for directory subtree. | Injected: `$request`. |
| `loading.php` | Loading state boundary for directory subtree. | Optional UI loading boundary. |

### Directory Naming Rules
- `users` → Static URL `/users`.
- `[id]` → Dynamic parameter captured as `$params['id']`.
- `[...slug]` → Catch-all (1+ segments) captured as array `$params['slug']`.
- `[[...slug]]` → Optional catch-all (0+ segments) captured as array `$params['slug']`.
- `(admin)` → Route Group: Ignored in the URL, used solely to organize layout and middleware scopes.

---

## 3. Preferred APIs & Entrypoints

### Application Bootstrapping (`public/index.php`)
```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Router\Router;
use Arbor\Router\Http\Request;

$router = new Router([
    'appDir' => __DIR__ . '/../app',
    'security' => [
        'headers' => true, // Enables standard security headers
        'csrf'    => true, // Enables session-based CSRF checks for actions
    ],
]);

// Preferred production entrypoint (handles security headers, exception handling, and send()):
$router->dispatch();

// Programmatic alternative (returns Response instance without emitting output):
// $response = $router->handle(Request::fromGlobals());
```

### Data Validation (`Arbor\Router\Validation\Validator`)
```php
use Arbor\Router\Validation\Validator;

// Option A: Safe validation using make() -> returns ValidationResult
$validator = new Validator(); // or use injected $validator in action.php / page.php
$result = $validator->make($payload, [
    'email'    => 'required|email|max:100',
    'password' => 'required|min:8',
    'status'   => 'in:pending,active,completed',
]);

if ($result->fails()) {
    // $result->errors() returns array<string, string[]>
    return ActionResult::error($result->errors(), 'Invalid submission');
}

// Option B: Throwing validation using validate() -> throws ValidationException
try {
    $cleanData = $validator->validate($payload, ['name' => 'required|string']);
} catch (\Arbor\Router\Exception\ValidationException $e) {
    $errors = $e->result?->errors() ?? [];
}
```

### Server Actions (`Arbor\Router\Action\ActionResult`)
```php
use Arbor\Router\Action\ActionResult;
use Arbor\Router\Http\Request;
use Arbor\Router\Validation\Validator;

return function(Request $request, array $params, array $query, array $body, Validator $validator) {
    $validation = $validator->make($body, [
        'name' => 'required|min:3',
        'price' => 'required|numeric',
    ]);

    if ($validation->fails()) {
        return ActionResult::error($validation->errors(), 'Please fix the errors below.');
    }

    // Success with redirect:
    return ActionResult::success('Product created')
        ->data(['id' => 42])
        ->redirect('/products/42');
};
```

---

## 4. Absolute "Never Do This" (Anti-Patterns)

1. **NEVER invent `new Validator([...])`**:
   - `Validator::__construct()` accepts an optional `RuleParser`, NOT an array of rules!
   - Always call `$validator->make($data, $rules)` or `$validator->validate($data, $rules)`.
2. **NEVER invent `$result->isValid()` or `$result->getErrors()`**:
   - `ValidationResult` uses:
     - `->passes(): bool`
     - `->fails(): bool`
     - `->errors(): array<string, string[]>`
     - `->errorsFor(string $field): string[]`
     - `->firstError(string $field): ?string`
3. **NEVER instantiate internal engine classes in application code**:
   - Do NOT manually instantiate `RouteScanner`, `RouteMatcher`, `LayoutResolver`, `PageRenderer`, or `ApiRenderer`. `Router` handles all orchestration internally.
4. **NEVER mix `page.php` and `route.php` in the same directory**:
   - A directory should serve either as a Page endpoint (`page.php`) or an API endpoint (`route.php`).
5. **NEVER echo output in `route.php`**:
   - `route.php` handlers should return raw data arrays (which are automatically negotiated to JSON/XML/text) or return an explicit `Response` instance (`JsonResponse`, etc.).
6. **NEVER bypass CSRF on action forms**:
   - Always include the CSRF token in HTML forms:
     `<?= (new \Arbor\Router\Security\CsrfGuard())->field() ?>`
     or send header `X-CSRF-Token` in AJAX requests.
7. **NEVER expect `Router::handle()` to throw unhandled `RouteNotFoundException`**:
   - `Router::handle()` catches 404s and 500s internally and returns a formatted `Response` via `ErrorHandler`.

---

## 5. Built-In Validation Rules Reference

Use standard pipe notation (`'field' => 'rule1|rule2:param1,param2'`) or arrays:

| Rule | Syntax | Description |
| :--- | :--- | :--- |
| `required` | `required` | Field must be present and not null/empty string |
| `string` | `string` | Value must be a string |
| `numeric` | `numeric` | Value must be numeric |
| `email` | `email` | Value must be a valid email format |
| `min` | `min:3` | String length or numeric value >= parameter |
| `max` | `max:100` | String length or numeric value <= parameter |
| `in` | `in:a,b,c` | Value must be in the specified comma-separated list |
| `array` | `array` | Value must be an array |
| `boolean` | `boolean` | Value must be a boolean or boolean-like value |
| `date` | `date` | Value must be a parseable date |
| `url` | `url` | Value must be a valid URL |
| `regex` | `regex:/^[a-z]+$/` | Value must match regular expression |
| `phone` | `phone` | Value must be a valid telephone number format |
| `domain` | `domain` | Value must be a valid domain format |
| `cpf` | `cpf` | Validates Brazilian individual taxpayer ID (CPF) |
| `cnpj` | `cnpj` | Validates Brazilian company registration ID (CNPJ) |
| `confirmed` | `confirmed` | Checks that `:field_confirmation` matches `:field` |

---

## 6. Built-In Sanitizer Filters Reference

The `Sanitizer` class provides:
- `trim` — Strips whitespace from beginning and end.
- `strip_tags` — Strips HTML and PHP tags.
- `htmlentities` — Converts special characters to HTML entities.
- `lowercase` / `uppercase` — Case conversion with multibyte support.
- `numeric` — Extracts numeric/decimal value.
- `slug` — Generates URL-friendly slug.

---

## 7. Error Handling Strategy

All exceptions thrown during route execution are mapped to HTTP responses:

- `RouteNotFoundException` → 404 Response (resolves `not-found.php` if present in hierarchy).
- `ForbiddenException` / `ActionOriginMismatchException` / `CsrfTokenMismatchException` → 403 Response.
- `MethodNotAllowedException` → 405 Response with `allowed` methods header/json.
- `ValidationException` → 422 Response with validation error bag.
- `\Throwable` (generic errors) → 500 Response (resolves `error.php` if present in hierarchy).

---

## 8. Directory Index for Deep AI Context

When you need deeper context, read the specialized files in `ai/`:
- `ai/overview.md` — High-level goals, problems solved, non-goals.
- `ai/architecture.md` — Full mental model, request pipeline diagram, layout cascading.
- `ai/api.md` — Exhaustive signatures and contracts for all public classes.
- `ai/workflows.md` — Step-by-step implementation recipes.
- `ai/configuration.md` — Full configuration schema.
- `ai/errors.md` — Exception hierarchy and debugging.
- `ai/patterns.md` — Idiomatic design patterns.
- `ai/anti-patterns.md` — Prohibited practices with rationale.
- `ai/troubleshooting.md` — Symptom-cause-solution reference.
- `ai/examples.md` — Tested, copy-pasteable real-world examples.
- `ai/api-reference.json` — Machine-readable structured schema.
