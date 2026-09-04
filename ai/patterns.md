# Recommended Patterns: Arbor Router

This document highlights idiomatic, clean architecture patterns recommended when developing with `silviosln/arbor-router`.

---

## 1. Separate Shell from Module Layouts (`layoutroot.php` + `layout.php`)

### Pattern
Place `layoutroot.php` at `app/layoutroot.php` containing the `<!DOCTYPE html>`, `<html>`, `<head>`, and `<body>` shell. Use localized `layout.php` files inside nested subdirectories for section-specific navigation (e.g. sidebars, headers).

```php
// app/layoutroot.php - HTML Shell
return function($request, $params): string {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head><meta charset="utf-8"><title>My App</title></head>
    <body class="antialiased">{{content}}</body>
    </html>
    HTML;
};
```

```php
// app/(dashboard)/layout.php - Admin Dashboard Shell
return function($request, $params): string {
    return <<<HTML
    <div class="flex">
        <aside>Sidebar</aside>
        <main>{{content}}</main>
    </div>
    HTML;
};
```

---

## 2. Use Route Groups `(group)` for Clean URLs with Distinct Layouts

### Pattern
Wrap directory names in parentheses `(name)` to group routes under shared layouts or middlewares **without changing the public URL path**:

```text
app/
├── (marketing)/
│   ├── layout.php          <-- Marketing navbar/footer
│   ├── page.php            <-- Responds to "/"
│   └── about/
│       └── page.php        <-- Responds to "/about" (NOT /marketing/about)
└── (app)/
    ├── middleware.php      <-- Auth guard for app
    ├── layout.php          <-- App sidebar
    └── dashboard/
        └── page.php        <-- Responds to "/dashboard" (NOT /app/dashboard)
```

---

## 3. Server Actions with Typed `ActionResult`

### Pattern
In `action.php`, return an `ActionResult` using the fluent builder. It handles both AJAX JSON responses and traditional HTTP 302/303 form redirects automatically.

```php
<?php
// app/settings/profile/action.php
use Arbor\Router\Http\Request;
use Arbor\Router\Action\ActionResult;
use Arbor\Router\Validation\Validator;

return function(Request $request, array $params, array $query, array $body, Validator $validator) {
    $validation = $validator->make($body, [
        'name'  => 'required|string|min:3',
        'email' => 'required|email',
    ]);

    if ($validation->fails()) {
        return ActionResult::error($validation->errors(), 'Please fix the highlighted fields.')
            ->statusCode(422);
    }

    // Update profile...

    return ActionResult::success('Profile updated successfully!')
        ->redirect('/settings/profile');
};
```

---

## 4. Multi-Method API Endpoints (`route.php`)

### Pattern
In `route.php`, return an associative array mapping uppercase HTTP verbs to handlers. Take advantage of content negotiation by returning raw arrays.

```php
<?php
// app/api/orders/[id]/route.php
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;

return [
    'GET' => function(Request $request, array $params) {
        $order = findOrder((int) $params['id']);
        if (!$order) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }
        return $order; // Serialized to JSON or XML based on client Accept header
    },

    'PATCH' => function(Request $request, array $params) {
        $updated = updateOrder((int) $params['id'], $request->body());
        return ['success' => true, 'order' => $updated];
    },
];
```

---

## 5. Onion/Pipeline Middleware with Early Exit

### Pattern
A middleware intercepts before `$next($request)` and can inspect or modify after `$next($request)` or abort early:

```php
<?php
// app/(admin)/middleware.php
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\RedirectResponse;
use Arbor\Router\Http\Response;

return function(RequestInterface $request, \Closure $next): Response {
    // 1. Pre-execution check
    if (!isAdmin()) {
        return new RedirectResponse('/unauthorized', 303);
    }

    // 2. Delegate execution to deeper handlers
    $response = $next($request);

    // 3. Post-execution response modification
    return $response->withHeader('X-Security-Level', 'High');
};
```
