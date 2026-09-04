# Arbor Router 🌳

![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)
![Tests](https://img.shields.io/badge/Coverage-100%25-brightgreen?style=flat-square)

> 🇧🇷 **Leia esta documentação em Português:** [README.pt-BR.md](README.pt-BR.md)  
> 🤖 **Instructions for AI Agents:** See [AGENTS.md](AGENTS.md) and the [ai/](ai/README.md) context layer.

**Arbor Router** is a modern, strictly-typed, and framework-agnostic PHP 8.4+ library providing **File-System Based Routing**, heavily inspired by the mental model of **Next.js App Router**. It eliminates the need for manual, bloated routing configuration files by mapping your directory tree inside `app/` directly into URL routes, nested layouts, API endpoints, and server actions.

---

## 🌟 Key Features

- **File-System Routing**: Folders and files automatically define URL endpoints (e.g. `app/users/page.php` handles `/users`).
- **Dynamic Parameters**: Folders with brackets like `[id]` or `[...slug]` (catch-all) naturally extract route parameters into `$params`.
- **Route Groups `(group)`**: Organize layouts, auth, and middlewares without altering public URL segments.
- **Hierarchical Layout Cascading**: Nested `layout.php` and `layoutroot.php` render from the inside out, wrapping child views seamlessly via `{{content}}` or `$children`.
- **Clear Context Separation**:
  - `page.php` for visual HTML view rendering.
  - `route.php` for REST API endpoints with HTTP method dispatching (`GET`, `POST`, `PUT`, `DELETE`).
  - `action.php` for Server Actions handling mutations and form submissions.
- **Cascading Russian Doll Middleware**: Directory-scoped `middleware.php` files compose an onion pipeline from the root down to the target route.
- **Zero Heavy Dependencies**: Pure PHP 8.4+ utilizing constructor promotion, readonly classes, and property hooks. Requires only `ext-json` and `ext-mbstring`.
- **Built-in Security**: Session-based Anti-CSRF protection, HTTP Security Headers (`nosniff`, `DENY`), and API route client gating.
- **Smart Content Negotiation**: `route.php` endpoints automatically serialize return arrays into JSON, XML, or plain text based on the client's `Accept` header.

---

## 🚀 Installation

Install via Composer:

```bash
composer require silviosln/arbor-router
```

> **Requirements:** PHP 8.4 or higher (`ext-json` and `ext-mbstring`).

---

## 📦 Directory Structure & Resource Conventions

```text
app/
├── layoutroot.php          # Global HTML shell (<!DOCTYPE html>, <html>, <head>, <body>)
├── not-found.php           # Custom 404 error page
├── error.php               # Custom 500 error boundary
├── page.php                # Homepage view ("/")
├── about/
│   └── page.php            # Visual page ("/about")
├── (dashboard)/            # Route group: excluded from public URL
│   ├── layout.php          # Shared dashboard layout (sidebar + header)
│   ├── middleware.php      # Authentication guard for dashboard routes
│   └── admin/
│       ├── page.php        # Admin page ("/admin")
│       └── products/
│           ├── [id]/
│           │   ├── page.php        # View/Edit product ("/admin/products/123")
│           │   └── edit/
│           │       └── action.php  # Form mutation action ("/admin/products/123/edit")
│           └── route.php   # REST API endpoint ("/admin/products")
```

---

## 🛠 Quick Start Guide

### 1. Bootstrapping the Router (`public/index.php`)

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Router\Router;

$router = new Router([
    'appDir'   => __DIR__ . '/../app',
    'security' => [
        'headers' => true,
        'csrf'    => true,
    ],
]);

// Dispatches the complete lifecycle (security headers, matching, execution, and sending)
$router->dispatch();
```

---

### 2. Static and Dynamic Pages (`page.php`)

```php
<?php
// app/users/[id]/page.php
use Arbor\Router\Http\Request;

return function(Request $request, array $params): string {
    $userId = htmlspecialchars((string) $params['id'], ENT_QUOTES, 'UTF-8');
    return "<h1>User Profile #{$userId}</h1>";
};
```

---

### 3. Cascading Layouts (`layoutroot.php` and `layout.php`)

```php
<?php
// app/layoutroot.php (Global HTML Shell)
return function($request, $params): string {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>My Arbor App</title>
    </head>
    <body class="bg-gray-50">
        <main>{{content}}</main>
    </body>
    </html>
    HTML;
};
```

---

### 4. REST APIs and Content Negotiation (`route.php`)

```php
<?php
// app/api/products/[slug]/route.php
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;

return [
    'GET' => function(Request $request, array $params) {
        // ContentNegotiator automatically serializes to JSON or XML
        return [
            'slug'  => $params['slug'],
            'name'  => 'Mechanical Keyboard',
            'price' => 149.99,
        ];
    },

    'DELETE' => function(Request $request, array $params) {
        return new JsonResponse(['deleted' => true], 200);
    }
];
```

*Note: By default, `route.php` endpoints require the `X-API-Request: true` header to guard against direct browser address bar visits.*

---

### 5. Server Actions & Form Mutations (`action.php`)

```php
<?php
// app/products/create/action.php
use Arbor\Router\Http\Request;
use Arbor\Router\Action\ActionResult;
use Arbor\Router\Validation\Validator;

return function(Request $request, array $params, array $query, array $body, Validator $validator) {
    // 1. Data Validation
    $validation = $validator->make($body, [
        'name'  => 'required|string|min:3|max:100',
        'price' => 'required|numeric|min:0.01',
    ]);

    if ($validation->fails()) {
        return ActionResult::error($validation->errors(), 'Invalid submission')
            ->statusCode(422);
    }

    // 2. Persist data...
    $newId = 42;

    // 3. Success with automatic redirection
    return ActionResult::success('Product created successfully!')
        ->data(['id' => $newId])
        ->redirect('/products/' . $newId);
};
```

In your HTML form, embed the built-in CSRF token field:

```html
<form action="/products/create" method="POST">
    <?= (new \Arbor\Router\Security\CsrfGuard())->field() ?>
    <input type="text" name="name" required>
    <input type="number" step="0.01" name="price" required>
    <button type="submit">Create</button>
</form>
```

---

### 6. Russian Doll Middleware (`middleware.php`)

```php
<?php
// app/(dashboard)/middleware.php
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\RedirectResponse;
use Arbor\Router\Http\Response;

return function(RequestInterface $request, \Closure $next): Response {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return new RedirectResponse('/login');
    }

    return $next($request);
};
```

---

## 🤖 AI-Native Context Layer

Arbor Router is designed to be **AI-Friendly**. If you are building with AI coding assistants (Claude, Cursor, Copilot, ChatGPT, Gemini), explore:
- [`AGENTS.md`](AGENTS.md): Strict rules, entrypoints, and anti-patterns.
- [`ai/`](ai/README.md): Modular context directory containing:
  - [`overview.md`](ai/overview.md): Goals and non-goals.
  - [`architecture.md`](ai/architecture.md): Visual request lifecycle and cascading algorithms.
  - [`api.md`](ai/api.md): Complete public class, method, and signature reference.
  - [`workflows.md`](ai/workflows.md): Step-by-step implementation recipes.
  - [`configuration.md`](ai/configuration.md): Cache and security configuration options.
  - [`errors.md`](ai/errors.md): Exception taxonomy and HTTP status code mappings.
  - [`patterns.md`](ai/patterns.md) & [`anti-patterns.md`](ai/anti-patterns.md): Best practices vs forbidden calls.
  - [`troubleshooting.md`](ai/troubleshooting.md): Diagnosis matrix for common errors.
  - [`examples.md`](ai/examples.md): Copy-pasteable runnable snippets.
  - [`api-reference.json`](ai/api-reference.json): Machine-readable schema.

---

## ⚖️ License

This library is licensed under the **MIT License**. See [LICENSE](LICENSE) for details.
