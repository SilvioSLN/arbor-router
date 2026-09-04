# Anti-Patterns: What NOT To Do

This document describes prohibited practices, common pitfalls, and incorrect assumptions that AI agents and human developers must avoid when using `silviosln/arbor-router`.

---

## 1. Hallucinated Validation APIs

### ❌ WRONG: Passing rules into `new Validator(...)`
```php
// WRONG: Validator constructor accepts an optional RuleParser, NOT an array of rules!
$validator = new Validator([
    'email' => 'required|email',
]);
$res = $validator->validate($payload);
```

### ✅ CORRECT: Pass rules to `make()` or `validate()`
```php
$validator = new Validator();
// Option A: Non-throwing
$result = $validator->make($payload, [
    'email' => 'required|email',
]);

// Option B: Throwing
$validated = $validator->validate($payload, [
    'email' => 'required|email',
]);
```

---

## 2. Hallucinated ValidationResult Methods

### ❌ WRONG: Calling `isValid()` or `getErrors()`
```php
// WRONG: ValidationResult does NOT have isValid() or getErrors()!
if ($result->isValid()) { ... }
$errors = $result->getErrors();
```

### ✅ CORRECT: Use `passes()`, `fails()`, and `errors()`
```php
if ($result->passes()) {
    // Valid
}

if ($result->fails()) {
    $errors = $result->errors(); // Returns array<string, string[]>
}
```

---

## 3. Co-Locating `page.php` and `route.php` in the Same Folder

### ❌ WRONG: Placing both visual page and API handler in the same folder
```text
app/
└── users/
    ├── page.php   <-- Conflict!
    └── route.php  <-- Conflict!
```
When both are present in the same directory, route resolution priority will cause one to shadow the other depending on the HTTP method.

### ✅ CORRECT: Place API routes under a dedicated folder (e.g. `api/`)
```text
app/
├── users/
│   └── page.php        <-- GET /users renders HTML view
└── api/
    └── users/
        └── route.php   <-- GET /api/users returns JSON data
```

---

## 4. Echoing Raw Output in `route.php`

### ❌ WRONG: Echoing or json_encoding manually in `route.php`
```php
// WRONG: Breaks ContentNegotiator and bypasses response wrapping!
return [
    'GET' => function($request) {
        echo json_encode(['data' => 123]);
        exit;
    }
];
```

### ✅ CORRECT: Return raw array or a Response instance
```php
return [
    'GET' => function($request) {
        // Option A: ContentNegotiator formats this automatically
        return ['data' => 123];

        // Option B: Explicit JsonResponse
        // return new \Arbor\Router\Http\JsonResponse(['data' => 123], 200);
    }
];
```

---

## 5. Submitting HTML Forms Without CSRF

### ❌ WRONG: Submitting POST forms without `_csrf_token`
```html
<!-- WRONG: Will fail with 403 ForbiddenException when CSRF is enabled -->
<form action="/login" method="POST">
    <input name="email">
    <button>Submit</button>
</form>
```

### ✅ CORRECT: Include the CSRF hidden field
```html
<form action="/login" method="POST">
    <?= (new \Arbor\Router\Security\CsrfGuard())->field() ?>
    <input name="email">
    <button>Submit</button>
</form>
```

---

## 6. Breaking Middleware Pipeline Delegation

### ❌ WRONG: Neither calling `$next($request)` nor returning a Response
```php
// WRONG: Returning void or null terminates the pipeline improperly!
return function($request, $next) {
    if (checkSomething()) {
        // missing return $next($request)!
    }
};
```

### ✅ CORRECT: Always return `$next($request)` or an explicit `Response`
```php
return function($request, $next): Response {
    if (!checkSomething()) {
        return new RedirectResponse('/login');
    }
    return $next($request);
};
```

---

## 7. Instantiating Internal Subsystems Directly

### ❌ WRONG: Creating internal engine instances
```php
// WRONG: Internal pipeline classes are orchestrated solely by Router!
$scanner = new RouteScanner('/app');
$matcher = new RouteMatcher($scanner->scan());
```

### ✅ CORRECT: Rely on the public `Router` interface
```php
$router = new Router(['appDir' => '/app']);
$router->dispatch();
```
