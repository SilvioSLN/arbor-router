# Troubleshooting Guide: Arbor Router

This guide helps diagnose and resolve issues encountered while using `silviosln/arbor-router`.

---

## Quick Diagnostic Matrix

| Symptom / Error | Root Cause | Solution |
| :--- | :--- | :--- |
| **HTTP 404 on known route** | File missing, wrong filename, or stale route cache. | Check that file is named `page.php`, `route.php`, or `action.php`. Clear cache directory if `FileCache` is used. |
| **HTTP 403: "API request header 'X-API-Request' is missing"** | Client navigated to `route.php` without required header. | Add header `X-API-Request: true` in your fetch/HTTP client, or adjust `'security.apiHeader'` in config. |
| **HTTP 403: "CSRF token missing from request"** | Form submitted without `_csrf_token` input. | Add `<?= (new \Arbor\Router\Security\CsrfGuard())->field() ?>` inside the `<form>`. |
| **HTTP 403: "CSRF token mismatch"** | Session expired, session not started, or token mismatch. | Ensure `session_start()` is called and check session persistence across domains/cookies. |
| **HTTP 403: "Action origin mismatch"** | Form submitted to an action whose path does not match the request path. | Ensure the form `action="/exact/path"` matches the folder where `action.php` resides. |
| **HTTP 405: "Method ... not allowed"** | `route.php` does not export a handler for the requested HTTP verb. | Add the required method (e.g. `'POST' => function(...)`) to the array returned by `route.php`. |
| **HTTP 422: "Validation failed"** | Form payload failed validation rules in `action.php`. | Inspect `$result->errors()` to see which fields failed validation. |
| **`{{content}}` displayed literally** | Layout returned HTML without placeholder replacement. | Return a string containing `{{content}}` or print `<?= $children ?>`. |
| **Headers already sent warning** | Output emitted before `$router->dispatch()` or in middleware. | Ensure no whitespace or `echo` precedes `$router->dispatch()`. |

---

## Detailed Scenarios & Solutions

### Scenario 1: Accessing an API Route from Browser Address Bar (403)
- **Problem**: Opening `http://localhost:8000/api/users` in Chrome returns `{"error": "API request header 'X-API-Request' is missing..."}`.
- **Cause**: By default, `ApiGuard` blocks browser address bar visits to prevent unintended API access.
- **Solution**:
  - In frontend JavaScript:
    ```javascript
    fetch('/api/users', {
      headers: { 'X-API-Request': 'true' }
    });
    ```
  - Or disable `apiHeader` in `public/index.php` for testing:
    ```php
    $router = new Router([
        'appDir' => __DIR__ . '/../app',
        'security' => ['apiHeader' => null]
    ]);
    ```

---

### Scenario 2: Action Form Fails on Sub-Route (Action Origin Mismatch)
- **Problem**: Form on `/admin/products/1` submits to `/products/delete` and throws `ActionOriginMismatchException`.
- **Cause**: The action defined at `app/admin/products/[id]/delete/action.php` requires requests targeted directly at its own path (`/admin/products/1/delete`).
- **Solution**: Ensure `<form action="/admin/products/<?= $id ?>/delete" method="POST">` targets the exact action route.

---

### Scenario 3: Newly Added Routes Ignored in Production
- **Problem**: A new folder `app/reports/page.php` was uploaded, but the server keeps returning 404.
- **Cause**: `FileCache` is active and retains the previous route map.
- **Solution**: Clear the cache directory on deployment:
  ```bash
  rm -rf storage/cache/*
  ```
