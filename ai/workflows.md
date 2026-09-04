# Workflows: Arbor Router

This document provides task-oriented recipes ("How to do X") for common implementation scenarios.

---

## 1. How to Bootstrap an Arbor Router Project

### Goal
Initialize the router in your public web root.

### Steps
1. Create `public/index.php`.
2. Configure the `Router` instance pointing to your `app/` directory.
3. Call `$router->dispatch()`.

```php
<?php
// public/index.php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Router\Router;
use Arbor\Router\Cache\FileCache;

$router = new Router([
    'appDir' => __DIR__ . '/../app',
    // In production, enable route caching:
    // 'cacheInstance' => new FileCache(__DIR__ . '/../storage/cache'),
    'security' => [
        'headers' => true,
        'csrf'    => true,
    ],
]);

$router->dispatch();
```

---

## 2. How to Create a Static Page (`page.php`)

### Goal
Render an HTML view for `/about`.

### Steps
1. Create `app/about/page.php`.
2. Return a callable or output HTML directly.

```php
<?php
// app/about/page.php
use Arbor\Router\Http\Request;

return function(Request $request, array $params): string {
    return <<<HTML
    <section class="max-w-4xl mx-auto py-12">
        <h1 class="text-3xl font-bold">About Us</h1>
        <p class="mt-4 text-zinc-600">Welcome to our application built with Arbor Router.</p>
    </section>
    HTML;
};
```

---

## 3. How to Create a Dynamic Route (`[param]`)

### Goal
Render a profile page for `/users/:id`.

### Steps
1. Create directory `app/users/[id]/`.
2. Create `app/users/[id]/page.php`.
3. Read the parameter from `$params['id']`.

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

## 4. How to Create a Catch-All Route (`[...slug]`)

### Goal
Handle docs or category paths of arbitrary depth (e.g. `/docs/getting-started/installation`).

### Steps
1. Create directory `app/docs/[...slug]/`.
2. Create `app/docs/[...slug]/page.php`.
3. `$params['slug']` is an array of path segments.

```php
<?php
// app/docs/[...slug]/page.php
use Arbor\Router\Http\Request;

return function(Request $request, array $params): string {
    // $params['slug'] is an array: ['getting-started', 'installation']
    $path = implode('/', (array) $params['slug']);

    return "<h2>Documentation: {$path}</h2>";
};
```

---

## 5. How to Create Nested Cascading Layouts

### Goal
Provide a global HTML shell (`layoutroot.php`) and an admin sidebar layout (`layout.php`).

### Steps
1. Create `app/layoutroot.php` for the outer document:
```php
<?php
// app/layoutroot.php
return function($request, $params): string {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>App</title>
    </head>
    <body class="bg-gray-50 text-gray-900">
        {{content}}
    </body>
    </html>
    HTML;
};
```

2. Create `app/(admin)/layout.php` for the admin section:
```php
<?php
// app/(admin)/layout.php
return function($request, $params): string {
    return <<<HTML
    <div class="flex min-h-screen">
        <aside class="w-64 bg-zinc-900 text-white p-6">Sidebar Menu</aside>
        <main class="flex-1 p-8">{{content}}</main>
    </div>
    HTML;
};
```

3. Any page inside `app/(admin)/products/page.php` is automatically wrapped by `(admin)/layout.php` and then `layoutroot.php`.

---

## 6. How to Build an API Endpoint (`route.php`)

### Goal
Create REST API `/api/products/[id]` supporting `GET` and `DELETE`.

### Steps
1. Create `app/api/products/[id]/route.php`.
2. Return an associative array of HTTP method handlers.

```php
<?php
// app/api/products/[id]/route.php
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;

return [
    'GET' => function(Request $request, array $params) {
        $productId = (int) $params['id'];
        
        // Return raw array: ContentNegotiator converts it to JSON automatically
        return [
            'id'    => $productId,
            'name'  => 'Widget',
            'price' => 29.99,
        ];
    },

    'DELETE' => function(Request $request, array $params) {
        $productId = (int) $params['id'];
        // Or return an explicit Response instance:
        return new JsonResponse(['deleted' => true, 'id' => $productId], 200);
    },
];
```

*Note: The client must send the `X-API-Request: true` header to access `route.php` endpoints.*

---

## 7. How to Handle Forms with Server Actions (`action.php`)

### Goal
Process a product creation form submission with CSRF protection and validation.

### Steps
1. In your `page.php`, include the CSRF token:
```php
<form action="/products/create" method="POST">
    <?= (new \Arbor\Router\Security\CsrfGuard())->field() ?>
    <input type="text" name="name" required>
    <input type="number" name="price" step="0.01" required>
    <button type="submit">Save</button>
</form>
```

2. Create `app/products/create/action.php`:
```php
<?php
// app/products/create/action.php
use Arbor\Router\Http\Request;
use Arbor\Router\Action\ActionResult;
use Arbor\Router\Validation\Validator;

return function(Request $request, array $params, array $query, array $body, Validator $validator) {
    // Validate inputs
    $validation = $validator->make($body, [
        'name'  => 'required|string|min:3|max:100',
        'price' => 'required|numeric|min:0',
    ]);

    if ($validation->fails()) {
        return ActionResult::error($validation->errors(), 'Please fix the errors below.');
    }

    // Persist data...
    $newId = 99;

    // Return success result
    return ActionResult::success('Product created successfully!')
        ->data(['id' => $newId])
        ->redirect('/products/' . $newId);
};
```

---

## 8. How to Protect Routes with Middleware (`middleware.php`)

### Goal
Authenticate users accessing any route inside `app/(admin)/`.

### Steps
1. Create `app/(admin)/middleware.php`.
2. Return a callable accepting `($request, $next)`.

```php
<?php
// app/(admin)/middleware.php
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\RedirectResponse;

return function(RequestInterface $request, \Closure $next) {
    // Check session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return new RedirectResponse('/login');
    }

    // Delegate to next middleware or route
    $response = $next($request);

    // Post-process response (optional)
    return $response->withHeader('X-User-Role', $_SESSION['user_role'] ?? 'user');
};
```

---

## 9. How to Implement Custom Error Pages (`not-found.php` & `error.php`)

### Goal
Provide branded 404 and 500 error pages.

### Steps
1. Place `app/not-found.php` and `app/error.php` in your root or subdirectory.
2. In `app/not-found.php`:
```php
<?php
// app/not-found.php
return function($request): string {
    return '<h1>Page Not Found (404)</h1><p>Sorry, the page does not exist.</p>';
};
```

3. In `app/error.php`:
```php
<?php
// app/error.php
return function($request, \Throwable $error): string {
    return '<h1>Something went wrong (500)</h1><p>' . htmlspecialchars($error->getMessage()) . '</p>';
};
```

---

## 10. How to Register a Custom Validation Rule

### Goal
Create a custom `slug` validation rule.

### Steps
1. Implement `Arbor\Router\Validation\RuleInterface`.
```php
<?php
namespace App\Validation;

use Arbor\Router\Validation\RuleInterface;

class SlugRule implements RuleInterface
{
    public function name(): string
    {
        return 'slug';
    }

    public function validate(mixed $value, array $parameters, string $field, array $allData): bool
    {
        if (!is_string($value)) return false;
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value) === 1;
    }

    public function message(): string
    {
        return 'The :field field must be a valid URL slug.';
    }
}
```

2. Register it on the `$validator`:
```php
$validator->addRule(new \App\Validation\SlugRule());
```
