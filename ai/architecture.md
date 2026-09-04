# Architecture: Arbor Router

This document details the mental model, internal architecture, and execution lifecycle of **Arbor Router**.

---

## 1. Request Lifecycle Diagram

```text
HTTP Request (Browser / API Client)
       │
       ▼
public/index.php
       │
       ▼
Router::dispatch() [or Router::handle()]
       │
       ├── 1. SecurityManager::applyHeaders()
       │      (Applies X-Frame-Options, X-Content-Type-Options, etc.)
       │
       ├── 2. RouteMatcher::matchAny($path, $method)
       │      (Scans cached RouteMap or scans filesystem via RouteScanner)
       │      ├── Exact static route match? (O(1) lookup)
       │      └── Dynamic regex segment match? (Ordered by specificity)
       │
       ├── 3. If No Route Matched:
       │      └── ErrorHandler::handleNotFound()
       │          └── Resolves nearest 'not-found.php' upwards to root
       │
       ├── 4. MiddlewarePipeline::process()
       │      └── Executes middleware.php chain top-down (root → leaf)
       │          in a "Russian Doll" wrapper
       │
       ├── 5. Route Execution (Based on RouteType):
       │      │
       │      ├── RouteType::Page (`page.php`)
       │      │   └── PageRenderer::renderWithRoute()
       │      │       ├── Render page.php (buffering & injected vars)
       │      │       └── Wrap with LayoutRenderer (leaf layout → ... → layoutroot.php)
       │      │
       │      ├── RouteType::Api (`route.php`)
       │      │   ├── ApiGuard::validate() (checks X-API-Request header)
       │      │   ├── ApiRenderer::render() (dispatches to GET/POST/etc.)
       │      │   └── ContentNegotiator::negotiate() (serializes to JSON/XML/text)
       │      │
       │      └── RouteType::Action (`action.php`)
       │          ├── ActionGuard::validate() (method, origin, CSRF)
       │          ├── ActionHandler::executeAction()
       │          └── Builds RedirectResponse (HTML forms) or JsonResponse (AJAX)
       │
       └── 6. Error Boundary (if any unhandled \Throwable occurs):
              └── ErrorHandler::handleError()
                  └── Resolves nearest 'error.php' upwards to root
```

---

## 2. Directory Hierarchy & Special Files

The router inspects the `app/` directory recursively. Every folder represents a URL path segment unless wrapped in parentheses `(...)`.

### Handlers
- **`page.php`**: Renders visual HTML pages.
- **`route.php`**: Exposes programmatic HTTP endpoints (`GET`, `POST`, `PUT`, `DELETE`, etc.).
- **`action.php`**: Handles data mutations/form submissions.

### Boundaries & Cascadings
- **`layout.php`**: Nested layout wrapper. Receives `$children` or replaces `{{content}}`.
- **`layoutroot.php`**: Anchor/root layout boundary. When encountered during upward traversal, layout search terminates. Ideal for the main HTML shell (`<!DOCTYPE html><html><head><body>`).
- **`middleware.php`**: Directory-scoped request/response interceptor.
- **`error.php`**: 500 error boundary for its directory and subdirectories.
- **`not-found.php`**: 404 error page for its directory and subdirectories.
- **`loading.php`**: Optional loading state indicator.

---

## 3. URL Segment Parsing Rules

| Directory Name | Segment Type | URL Example | Injected `$params` |
| :--- | :--- | :--- | :--- |
| `users` | Static | `/users` | `[]` |
| `[id]` | Dynamic Single | `/users/123` | `['id' => '123']` |
| `[...slug]` | Catch-All (1+ segments) | `/posts/php/router/v1` | `['slug' => ['php', 'router', 'v1']]` |
| `[[...slug]]` | Optional Catch-All (0+ segments) | `/docs` OR `/docs/quickstart` | `['slug' => []]` OR `['slug' => ['quickstart']]` |
| `(admin)` | Route Group | `/admin/dashboard` → `/dashboard` | Groups are stripped from URL path |

---

## 4. Cascading Layouts: The Inside-Out Algorithm

When `/dashboard/settings` is requested with:
- `app/layoutroot.php`
- `app/(dashboard)/layout.php`
- `app/(dashboard)/settings/page.php`

1. `settings/page.php` renders first to produce `$content`.
2. `(dashboard)/layout.php` renders, replacing `{{content}}` or receiving `$children = $content`. The result becomes the new `$content`.
3. `layoutroot.php` renders, wrapping the result inside the final `<html>` document.

---

## 5. Russian Doll Middleware Pipeline

All `middleware.php` files from the root `app/` directory down to the matched route folder are discovered in top-down order:

```text
Request
  │
  ▼
[Root Middleware: app/middleware.php]
  │  calls $next($request)
  ▼
[(dashboard) Middleware: app/(dashboard)/middleware.php]
  │  calls $next($request)
  ▼
[Final Route Handler: page.php / route.php / action.php]
  │  returns $response
  ▲
[(dashboard) Middleware post-processing]
  ▲
[Root Middleware post-processing]
  ▲
Client
```

---

## 6. Security Architecture

1. **`SecurityManager`**: Emits global headers:
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: DENY`
   - `X-XSS-Protection: 0`
   - `Referrer-Policy: strict-origin-when-cross-origin`
2. **`CsrfGuard`**: Generates a 64-char hex session token. Checks `_csrf_token` POST field or `X-CSRF-Token` header.
3. **`ActionGuard`**: Enforces POST/PUT/DELETE/PATCH, verifies request path matches action path, checks CSRF, allows header-less submissions for native HTML forms.
4. **`ApiGuard`**: Guards `route.php` against direct browser URL bar navigation by requiring `X-API-Request: true`.
