# Real-World Examples: Arbor Router

This document contains end-to-end, tested code examples demonstrating the most common architectural patterns in `silviosln/arbor-router`.

---

## 1. Minimal Application Entrypoint

```php
<?php
// public/index.php
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

$router->dispatch();
```

---

## 2. Root HTML Shell Layout

```php
<?php
// app/layoutroot.php
use Arbor\Router\Http\Request;

return function(Request $request, array $params): string {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Arbor Router App</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 text-gray-900 min-h-screen">
        {{content}}
    </body>
    </html>
    HTML;
};
```

---

## 3. Nested Admin Dashboard Layout

```php
<?php
// app/(admin)/layout.php
use Arbor\Router\Http\Request;

return function(Request $request, array $params): string {
    return <<<HTML
    <div class="flex min-h-screen">
        <aside class="w-64 bg-zinc-900 text-white p-6 space-y-4">
            <h2 class="text-xl font-bold tracking-tight">Admin Console</h2>
            <nav class="flex flex-col space-y-2">
                <a href="/admin/products" class="hover:text-blue-400">Products</a>
                <a href="/admin/categories" class="hover:text-blue-400">Categories</a>
            </nav>
        </aside>
        <main class="flex-1 p-8">
            {{content}}
        </main>
    </div>
    HTML;
};
```

---

## 4. Visual Page with Injected Variables & Dynamic Parameter

```php
<?php
// app/(admin)/products/[id]/page.php
use Arbor\Router\Http\Request;
use Arbor\Router\Security\CsrfGuard;

return function(Request $request, array $params): string {
    $productId = htmlspecialchars((string) $params['id'], ENT_QUOTES, 'UTF-8');
    $csrf = new CsrfGuard();

    return <<<HTML
    <div class="max-w-2xl bg-white p-6 rounded-xl shadow-sm">
        <h1 class="text-2xl font-bold mb-4">Edit Product #{$productId}</h1>

        <form action="/admin/products/{$productId}/edit" method="POST" class="space-y-4">
            {$csrf->field()}

            <div>
                <label class="block text-sm font-medium mb-1">Product Title</label>
                <input type="text" name="title" value="Current Name" class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Price ($)</label>
                <input type="number" step="0.01" name="price" value="49.90" class="w-full border p-2 rounded" required>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Save Changes
            </button>
        </form>
    </div>
    HTML;
};
```

---

## 5. Server Action with Validation, Sanitization, and Redirection

```php
<?php
// app/(admin)/products/[id]/edit/action.php
use Arbor\Router\Http\Request;
use Arbor\Router\Action\ActionResult;
use Arbor\Router\Validation\Validator;
use Arbor\Router\Sanitizer\Sanitizer;

return function(
    Request $request,
    array $params,
    array $query,
    array $body,
    Validator $validator,
    Sanitizer $sanitizer
): ActionResult {
    $productId = (int) $params['id'];

    // 1. Sanitize input
    $clean = $sanitizer->sanitize($body, [
        'title' => 'trim|strip_tags',
    ]);

    // 2. Validate input
    $validation = $validator->make($clean, [
        'title' => 'required|string|min:3|max:120',
        'price' => 'required|numeric|min:0.01',
    ]);

    if ($validation->fails()) {
        return ActionResult::error($validation->errors(), 'Validation failed.')
            ->statusCode(422);
    }

    // 3. Persist changes... (database call here)

    // 4. Return result
    return ActionResult::success('Product updated successfully!')
        ->data(['id' => $productId])
        ->redirect('/admin/products/' . $productId);
};
```

---

## 6. Multi-Method API Route with Automatic Content Negotiation

```php
<?php
// app/api/products/route.php
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;

return [
    'GET' => function(Request $request, array $params, array $query) {
        $limit = (int) ($query['limit'] ?? 10);

        // ContentNegotiator automatically converts this to JSON or XML based on Accept header
        return [
            'total' => 2,
            'items' => [
                ['id' => 1, 'name' => 'Mechanical Keyboard', 'price' => 129.99],
                ['id' => 2, 'name' => 'Ergonomic Mouse', 'price' => 79.50],
            ],
            'limit' => $limit,
        ];
    },

    'POST' => function(Request $request, array $params, array $query, array $body) {
        $name = $body['name'] ?? null;
        if (!$name) {
            return new JsonResponse(['error' => 'Product name is required'], 422);
        }

        return new JsonResponse([
            'created' => true,
            'product' => ['id' => rand(10, 999), 'name' => $name],
        ], 201);
    },
];
```

---

## 7. Authentication Middleware

```php
<?php
// app/(admin)/middleware.php
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\RedirectResponse;
use Arbor\Router\Http\Response;

return function(RequestInterface $request, \Closure $next): Response {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if session contains an authenticated admin
    if (!isset($_SESSION['admin_user'])) {
        return new RedirectResponse('/login?return_to=' . urlencode($request->uri()), 302);
    }

    // Continue to child middleware / route
    $response = $next($request);

    // Add security header
    return $response->withHeader('X-Auth-Verified', 'true');
};
```
