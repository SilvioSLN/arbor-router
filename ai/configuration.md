# Configuration Reference: Arbor Router

This document details all configuration options, cache strategies, and security settings for `Arbor\Router\Router`.

---

## 1. Full Configuration Array Schema

The `Router` constructor accepts a single configuration array:

```php
use Arbor\Router\Router;
use Arbor\Router\Cache\FileCache;

$router = new Router([
    // [REQUIRED] Path to your app directory
    'appDir' => __DIR__ . '/../app',

    // [OPTIONAL] Cache instance for route map resolution
    // Defaults to: new NullCache()
    'cacheInstance' => new FileCache(__DIR__ . '/../storage/cache'),

    // [OPTIONAL] Security configurations
    'security' => [
        // Automatically emits standard security headers:
        // X-Content-Type-Options: nosniff
        // X-Frame-Options: DENY
        // X-XSS-Protection: 0
        // Referrer-Policy: strict-origin-when-cross-origin
        // Default: true
        'headers' => true,

        // Enables automatic session CSRF validation for action.php
        // Default: true
        'csrf' => true,

        // Required HTTP header for accessing route.php API endpoints
        'apiHeader' => [
            'name'  => 'X-API-Request',
            'value' => 'true',
        ],

        // HTTP header for action.php endpoints (optional for native HTML forms)
        'actionHeader' => [
            'name'  => 'X-Action-Request',
            'value' => 'true',
        ],
    ],
]);
```

---

## 2. Route Caching (`cacheInstance`)

Arbor Router dynamically scans the filesystem to resolve routes. In high-traffic production environments, route scanning can be cached to achieve **O(1)** route map hydration.

### In Development: `NullCache`
```php
use Arbor\Router\Cache\NullCache;

// Filesystem is re-scanned on every request.
// New folders, page.php, route.php, or action.php are picked up immediately.
'cacheInstance' => new NullCache(),
```

### In Production: `FileCache`
```php
use Arbor\Router\Cache\FileCache;

// Serializes RouteMap to disk. Subsequent requests bypass the filesystem scan.
'cacheInstance' => new FileCache(__DIR__ . '/../storage/framework/cache', ttl: 86400),
```

#### Cache Invalidation
When deploying new code or creating new routes in production, clear the cache folder:
```bash
rm -rf storage/framework/cache/*
```
Or programmatically:
```php
$cache = new FileCache(__DIR__ . '/../storage/framework/cache');
$cache->clear();
```

---

## 3. Security Settings

### Security Headers (`security.headers`)
When `true`, `Router::dispatch()` invokes `SecurityManager::applyHeaders()` before sending output.
Headers emitted:
- `X-Content-Type-Options: nosniff` (prevents MIME type sniffing)
- `X-Frame-Options: DENY` (prevents clickjacking via iframes)
- `X-XSS-Protection: 0` (modern standard to disable legacy XSS auditors)
- `Referrer-Policy: strict-origin-when-cross-origin`

### Anti-CSRF Protection (`security.csrf`)
When enabled (`true` by default), every request handled by `action.php` must carry a valid CSRF token:
- In HTML forms: `<input type="hidden" name="_csrf_token" value="...">`
- In AJAX/Fetch: Header `X-CSRF-Token: ...`

To disable CSRF checks (e.g. for external webhook callbacks or testing):
```php
'security' => [
    'csrf' => false,
]
```

### API Client Verification (`security.apiHeader`)
By default, `route.php` endpoints require the header:
`X-API-Request: true`
This prevents direct navigation to API routes from a browser address bar.
To customize the header:
```php
'security' => [
    'apiHeader' => [
        'name'  => 'X-App-Client',
        'value' => 'mobile-or-web',
    ],
]
```
