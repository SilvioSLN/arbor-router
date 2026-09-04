# Errors and Exceptions: Arbor Router

This document maps all exceptions, error handling mechanics, and HTTP response codes in `silviosln/arbor-router`.

---

## 1. Exception Hierarchy

```text
\RuntimeException
   │
   ├── RouteNotFoundException (HTTP 404)
   │
   ├── ForbiddenException (HTTP 403)
   │      │
   │      ├── ActionOriginMismatchException (HTTP 403)
   │      │
   │      └── CsrfTokenMismatchException (HTTP 403)
   │
   ├── MethodNotAllowedException (HTTP 405)
   │      └── Has public readonly array $allowedMethods
   │
   └── ValidationException (HTTP 422)
          └── Has public readonly ?ValidationResult $result
```

---

## 2. HTTP Status Code Mapping

When `Router::dispatch()` or `Router::handle()` runs, exceptions are transformed into HTTP responses:

| Exception | HTTP Code | Trigger Condition | Default Response |
| :--- | :--- | :--- | :--- |
| `RouteNotFoundException` | 404 | No route matched the requested URL path and method. | Renders nearest `not-found.php`, or fallback HTML `"<h1>404 - Not Found</h1>"`. |
| `ForbiddenException` | 403 | Missing `X-API-Request` on API route, or invalid method/header on action. | `{"error": "<message>"}` (JSON) with status 403. |
| `ActionOriginMismatchException` | 403 | Request path does not match the action's defined URL pattern. | `{"error": "<message>"}` (JSON) with status 403. |
| `CsrfTokenMismatchException` | 403 | CSRF token missing or mismatch in session token comparison. | `{"error": "<message>"}` (JSON) with status 403. |
| `MethodNotAllowedException` | 405 | Method not implemented in `route.php` or `action.php`. | `{"error": "<message>", "allowed": [...]}` (JSON) with status 405. |
| `ValidationException` | 422 | Form or API validation failed. | AJAX: `{"error": "Validation failed", "errors": {...}}` with status 422. |
| `\Throwable` (generic) | 500 | Unhandled PHP exception, fatal error, or logic error. | Renders nearest `error.php`, or fallback HTML `"<h1>500 - Internal Server Error</h1>"`. |

---

## 3. Error Boundary Resolution (Bubbling Up)

When a 404 or 500 occurs, `ErrorHandler` resolves error pages via `ErrorPageResolver`:
1. It looks in the current route directory for `not-found.php` or `error.php`.
2. If not found, it moves to the parent directory.
3. It repeats upwards until it reaches `appDir`.
4. If an error page is found, it wraps it inside the applicable layout chain!
5. If no error page exists, a clean fallback HTML response is returned.

### Example `not-found.php`
```php
<?php
// app/not-found.php
use Arbor\Router\Http\Request;

return function(Request $request): string {
    return <<<HTML
    <div class="text-center py-20">
        <h1 class="text-4xl font-extrabold text-red-600">404</h1>
        <p class="text-lg text-gray-700">The page at '{$request->path()}' was not found.</p>
        <a href="/" class="mt-4 inline-block text-blue-600 underline">Back to Home</a>
    </div>
    HTML;
};
```

### Example `error.php`
```php
<?php
// app/error.php
use Arbor\Router\Http\Request;

return function(Request $request, \Throwable $error): string {
    return <<<HTML
    <div class="text-center py-20">
        <h1 class="text-4xl font-extrabold text-red-600">500 - Server Error</h1>
        <p class="text-gray-700">An unexpected error occurred.</p>
        <pre class="bg-gray-100 p-4 rounded text-left max-w-xl mx-auto mt-4 text-xs">
            <?= htmlspecialchars($error->getMessage()) ?>
        </pre>
    </div>
    HTML;
};
```
