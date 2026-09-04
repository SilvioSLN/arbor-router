# API Reference: Arbor Router

This document lists the public classes, interfaces, methods, parameters, return types, and exceptions in `silviosln/arbor-router`.

---

## 1. Core Entrypoint: `Arbor\Router\Router`

### Constructor
```php
public function __construct(array $config)
```
- **`$config`** (`array`):
  - `'appDir'` (`string`, **required**): Absolute path to the directory holding your routes (e.g. `__DIR__ . '/../app'`).
  - `'cacheInstance'` (`CacheInterface`, optional): Cache implementation (defaults to `new NullCache()`). Use `new FileCache('/path/to/cache')` in production.
  - `'security'` (`array`, optional):
    - `'headers'` (`bool`, default `true`): Whether to apply default security HTTP headers.
    - `'csrf'` (`bool`, default `true`): Whether to enable automatic CSRF verification for actions.
    - `'apiHeader'` (`array{name?: string, value?: string}`, optional): API route client gate (defaults to `X-API-Request: true`).
    - `'actionHeader'` (`array{name?: string, value?: string}`, optional): Action client gate (defaults to `X-Action-Request: true`).

### Methods
- **`dispatch(?RequestInterface $request = null): void`**
  - Dispatches the request. Applies security headers, calls `handle()`, handles exceptions, and calls `send()` to emit the response.
- **`handle(RequestInterface $request): Response`**
  - Resolves the route and returns a `Response` instance without emitting output. Catches 404 and 500 errors internally, delegating to `ErrorHandler`.

---

## 2. HTTP Abstraction: `Arbor\Router\Http`

### `Request` (`final class Request implements RequestInterface`)
- **`Request::fromGlobals(): self`**: Factory method using `$_SERVER`, `$_GET`, `$_POST`, `$_FILES`, and `php://input`.
- **`Request::create(string $method = 'GET', string $uri = '/', array $query = [], array $body = [], array $headers = [], string $rawBody = ''): self`**: Factory method for testing.
- **`method(): string`**: Uppercase HTTP method (`GET`, `POST`, etc.).
- **`uri(): string`**: Full URI path with query string.
- **`path(): string`**: Clean path without query string and without trailing slash (e.g. `'/users'`).
- **`query(): array<string, mixed>`**: Query parameters (`$_GET`).
- **`body(): array<string, mixed>`**: Parsed body parameters (from `$_POST` or parsed JSON).
- **`rawBody(): string`**: Raw input stream.
- **`headers(): array<string, string>`**: Normalized lowercase headers.
- **`header(string $name): ?string`**: Case-insensitive header lookup.
- **`contentType(): ?string`**: Content-Type header.
- **`accept(): ?string`**: Accept header.
- **`isAjax(): bool`**: Returns true if `X-Requested-With: XMLHttpRequest`.
- **`files(): array<string, mixed>`**: Uploaded files (`$_FILES`).

### `Response` (`class Response`)
- **`__construct(string $body = '', int $statusCode = 200, array $headers = [])`**
- **`body(): string`**: Response body.
- **`statusCode(): int`**: HTTP status code.
- **`headers(): array<string, string>`**: Response headers.
- **`header(string $name): ?string`**: Header lookup.
- **`withStatus(int $statusCode): static`**: Returns a clone with updated status code.
- **`withHeader(string $name, string $value): static`**: Returns a clone with added/replaced header.
- **`withBody(string $body): static`**: Returns a clone with updated body.
- **`send(): void`**: Emits headers via `header()` and echoes body.

### Response Subclasses
- **`HtmlResponse`**: Sets `Content-Type: text/html; charset=utf-8`.
- **`JsonResponse`**: Accepts mixed data, serializes with `json_encode()`, sets `Content-Type: application/json; charset=utf-8`.
- **`RedirectResponse`**: Sets `Location: $url` header.
  - `RedirectResponse::permanent(string $url): self` (301)
  - `RedirectResponse::temporary(string $url): self` (302)
- **`TextResponse`**: Sets `Content-Type: text/plain; charset=utf-8`.
- **`XmlResponse`**: Sets `Content-Type: application/xml; charset=utf-8`.

---

## 3. Server Actions: `Arbor\Router\Action`

### `ActionResult` (`final class ActionResult`)
- **`ActionResult::success(?string $message = null): self`**: Creates success result.
- **`ActionResult::error(array $errors = [], ?string $message = null): self`**: Creates error result with 422 status.
- **`ActionResult::fromArray(array $data): self`**: Creates result from array (`['success' => bool, 'message' => string, 'errors' => array, 'data' => array, 'redirect' => string, 'status' => int]`).
- **`data(array $data): self`**: Sets return data.
- **`redirect(string $url): self`**: Sets redirect URL.
- **`statusCode(int $code): self`**: Overrides HTTP status code.
- **`isSuccess(): bool`**: Returns true if successful.
- **`isError(): bool`**: Returns true if failed.
- **`getMessage(): ?string`**: Feedback message.
- **`getErrors(): array<string, string|string[]>`**: Validation errors.
- **`getData(): array<string, mixed>`**: Custom data.
- **`getRedirectUrl(): ?string`**: Redirect destination.
- **`hasRedirect(): bool`**: True if redirect is present.
- **`getHttpStatusCode(): int`**: Status code.
- **`toArray(): array<string, mixed>`**: Serializes for JSON responses.

### `ActionGuard` (`class ActionGuard`)
- **`validate(RequestInterface $request, string $actionPath): void`**:
  - Validates HTTP method (`POST`, `PUT`, `DELETE`, `PATCH`).
  - Validates origin path matches `$actionPath`.
  - Validates `X-Action-Request` header if present (allows missing for plain HTML forms).
  - Validates CSRF token if `CsrfGuard` is configured.

---

## 4. Validation: `Arbor\Router\Validation`

### `Validator` (`class Validator`)
- **`__construct(RuleParser $parser = new RuleParser())`**
- **`addRule(RuleInterface $rule): static`**: Registers custom rule.
- **`make(array $data, array $rules): ValidationResult`**: Non-throwing validation.
- **`validate(array $data, array $rules): array`**: Validates or throws `ValidationException`.

### `ValidationResult` (`final class ValidationResult`)
- **`passes(): bool`**: True if no errors.
- **`fails(): bool`**: True if 1 or more errors.
- **`errors(): array<string, string[]>`**: All errors keyed by field name.
- **`errorsFor(string $field): string[]`**: Errors for a specific field.
- **`firstError(string $field): ?string`**: First error for a field.
- **`allErrors(): string[]`**: Flat list of all error messages.
- **`toArray(): array<string, string[]>`**: Serializes error bag.

---

## 5. Security: `Arbor\Router\Security`

### `CsrfGuard` (`class CsrfGuard`)
- **`getToken(): string`**: Retrieves or generates a 64-character hex CSRF token in session (`_arbor_csrf_token`).
- **`regenerate(): string`**: Generates and stores a new token.
- **`validate(RequestInterface $request): void`**: Validates request against session token using timing-safe comparison (`hash_equals`). Throws `CsrfTokenMismatchException` on failure.
- **`field(): string`**: Returns HTML `<input type="hidden" name="_csrf_token" value="...">`.

### `ApiGuard` (`class ApiGuard`)
- **`validate(RequestInterface $request): void`**: Validates `X-API-Request` header. Throws `ForbiddenException` on failure.

---

## 6. Middleware: `Arbor\Router\Middleware`

### `MiddlewareInterface`
```php
public function handle(RequestInterface $request, callable $next): Response;
```

### Callable Signature
```php
function(RequestInterface $request, \Closure $next): Response
```

---

## 7. Injected Variables by File Type

When files in `appDir` are invoked, variables are automatically available in their scope:

| Variable | `page.php` | `layout.php` / `layoutroot.php` | `action.php` | `route.php` |
| :--- | :--- | :--- | :--- | :--- |
| `$request` | Yes (`Request`) | Yes (`Request`) | Yes (`Request`) | Passed as argument 1 |
| `$params` | Yes (`array`) | Yes (`array`) | Yes (`array`) | Passed as argument 2 |
| `$query` | Yes (`array`) | No | Yes (`array`) | Passed as argument 3 |
| `$body` | Yes (`array`) | No | Yes (`array`) | Passed as argument 4 |
| `$validator` | Yes (`Validator`) | No | Yes (`Validator`) | No |
| `$sanitizer` | Yes (`Sanitizer`) | No | Yes (`Sanitizer`) | No |
| `$children` | No | Yes (`string`) | No | No |
