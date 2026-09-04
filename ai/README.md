# Arbor Router — AI Context Layer

Welcome to the AI context documentation for **Arbor Router** (`silviosln/arbor-router`). This directory contains specialized technical documentation designed for LLMs, autonomous coding agents, and human software architects.

## Navigation Map

| Document | Topic & Purpose | Key Contents |
| :--- | :--- | :--- |
| [`overview.md`](overview.md) | Package purpose & boundaries | Target audience, solved problems, architectural philosophy, non-goals. |
| [`architecture.md`](architecture.md) | System mental model & internals | Request lifecycle, filesystem mapping, cascading layouts, Russian doll middleware. |
| [`api.md`](api.md) | Public API reference | Classes, methods, signatures, parameters, return types, injected variables. |
| [`workflows.md`](workflows.md) | Task-oriented guides | "How to create X" recipes for pages, APIs, forms, middleware, validation, filters. |
| [`configuration.md`](configuration.md) | Configuration reference | Options, cache providers (`FileCache`, `NullCache`), security headers, guards. |
| [`errors.md`](errors.md) | Exception & error handling | Exception taxonomy, HTTP status mapping, error boundary behavior. |
| [`patterns.md`](patterns.md) | Recommended idioms | Idiomatic patterns for clean Next.js-like PHP architecture. |
| [`anti-patterns.md`](anti-patterns.md) | What NEVER to do | Forbidden calls, hallucinated methods, common traps, and strict alternatives. |
| [`troubleshooting.md`](troubleshooting.md) | Problem diagnosis | Symptom → Cause → Identification → Fix table. |
| [`examples.md`](examples.md) | Runnable code snippets | Copy-pasteable, verified examples for real use-cases. |
| [`api-reference.json`](api-reference.json) | Structured machine schema | Machine-readable JSON definition of public classes and methods. |

## Quick Start Rule for AI Agents

Before writing code that consumes this library:
1. Review [`AGENTS.md`](../AGENTS.md) in the project root for strict rules and non-negotiables.
2. Check [`api.md`](api.md) to ensure the method and arguments you intend to invoke exist in the code.
3. Check [`patterns.md`](patterns.md) to follow the recommended architectural design.
