# Overview: Arbor Router

## 1. What is Arbor Router?

**Arbor Router** (`silviosln/arbor-router`) is an ultra-modern, framework-agnostic, strictly typed PHP 8.4+ library that brings **File-System Based Routing** (inspired by Next.js App Router) to PHP.

Instead of registering hundreds of routes manually in a single monolithic configuration file, Arbor Router automatically turns your filesystem hierarchy inside an `app/` directory into web routes, API endpoints, and mutation actions.

---

## 2. Core Value Proposition & Solved Problems

### A. Zero Manual Route Registration
- **Traditional PHP**: Developers maintain bloated `routes/web.php` or `routes/api.php` files that frequently suffer from merge conflicts and stale routes.
- **Arbor Router**: Dropping a file at `app/users/[id]/page.php` instantly exposes the route `/users/:id`.

### B. Cascading Nested Layouts
- **Traditional PHP**: Layouts require manual `@extends` or template inheritance engines (Blade, Twig), often creating tight coupling.
- **Arbor Router**: `layout.php` files automatically nest hierarchically from the root directory down to the leaf node, injecting child views seamlessly with `{{content}}` or `$children`.

### C. Context Separation
- Visual HTML views: `page.php`
- Programmable REST APIs: `route.php` with automatic content negotiation (JSON, XML, text)
- Server form mutations: `action.php` with built-in CSRF and origin validation

### D. Scoped "Russian Doll" Middleware
- Middlewares are organized in folders (`app/middleware.php`, `app/admin/middleware.php`). Every route executes all middlewares from the root down to its specific folder in sequence.

### E. Zero Heavy Framework Dependencies
- Arbor Router runs on vanilla PHP 8.4+ with only `ext-json` and `ext-mbstring`. It does not require Laravel, Symfony, or any specific ORM.

---

## 3. What Problems Arbor Router DOES NOT Solve (Non-Goals)

To prevent AI hallucinations, understand what Arbor Router is NOT:

1. **NOT an ORM or Database Abstraction**: Arbor Router does not interact with databases. Use PDO, Doctrine, or your preferred persistence library.
2. **NOT a Full-Stack Framework**: It does not bundle an asset bundler, queue runner, cron scheduler, or mailer.
3. **NOT a Template Engine**: It executes native PHP files with output buffering and isolated variable extraction. You can render native PHP/HTML, or delegate to a template engine from inside `page.php`.
4. **NOT a Component Component State Manager**: It renders server-side HTML/JSON. Client-side reactivity (e.g. Alpine.js, React, HTMX) runs in the browser.

---

## 4. Target Audience

- Developers building modern PHP web applications, admin dashboards, or REST APIs who appreciate Next.js-style Developer Experience (DX).
- Teams modernizing legacy PHP projects that want structured routing without the overhead of migrating to a heavy full-stack framework.
- Autonomous coding agents that benefit from clear, predictable, file-system-driven code organization.

---

## 5. Technical Requirements

- **PHP**: `^8.4` (using strict types, property hooks, constructor promotion, readonly classes)
- **Required Extensions**: `ext-json`, `ext-mbstring`
- **Recommended**: Composer with PSR-4 autoloader
