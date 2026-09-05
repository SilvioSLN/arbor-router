# Arbor Router 🌳 (Português)

![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php)
![License](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)
[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](tests/)
[![AI Ready](https://img.shields.io/badge/AI-Ready-8A2BE2.svg)](AGENTS.md)

> 🇬🇧 **Read this documentation in English:** [README.md](README.md)  
> 🤖 **Manual para Agentes de IA:** Veja [AGENTS.md](AGENTS.md) e o diretório [ai/](ai/README.md).

**Arbor Router** é uma biblioteca PHP ultra-moderna, estrita e _framework-agnostic_, projetada para oferecer **roteamento baseado em sistema de arquivos (File-System Based Routing)**. Fortemente inspirada no modelo mental do **App Router do Next.js**, ela elimina a necessidade de registrar rotas manualmente em um arquivo extenso e transforma a própria árvore de pastas em suas rotas da aplicação.

---

## 🌟 Principais Funcionalidades

- **Roteamento via Sistema de Arquivos:** Suas pastas e arquivos determinam a URL (ex: `app/users/page.php` responde a `/users`).
- **Parâmetros Dinâmicos:** Pastas com brackets como `[id]` ou `[...slug]` (catch-all) resolvem naturalmente os parâmetros para a requisição.
- **Route Groups `(nome)`:** Agrupe layouts e middlewares sem alterar o caminho público da URL.
- **Hierarquia de Layouts:** Renderização em cascata de `layout.php` e `layoutroot.php`, unindo o shell HTML a layouts aninhados com `{{content}}`.
- **Separação Clara de Contextos:**
  - `page.php` para renderização de páginas visuais em HTML.
  - `route.php` para desenvolvimento de APIs (JSON/XML) com suporte a verbos HTTP (GET, POST, etc).
  - `action.php` para Server Actions que processam envios de formulários com validação e CSRF.
- **Middleware em Cascata:** Crie `middleware.php` em pastas específicas para proteger fluxos e rotas (estilo "Russian Doll" com Request/Response).
- **Sem Dependências Pesadas:** Zero acoplamento com frameworks. Requer apenas PHP 8.4 puro, `ext-json` e `ext-mbstring`.
- **Segurança Nativa:** Proteção CSRF out-of-the-box, API Guards e gerenciamento unificado de Security Headers HTTP.
- **Content Negotiation Inteligente:** API Routes transformam retornos arrays em JSON, XML ou Texto baseados no cabeçalho `Accept` do cliente.

---

## 🚀 Instalação

Instale via Composer:

```bash
composer require silviosln/arbor-router
```

> **Requisitos:** PHP 8.4 ou superior. (`ext-json` e `ext-mbstring`).

---

## 📦 Estrutura de Arquivos e Recursos

```text
app/
├── layoutroot.php          # Shell HTML global (<!DOCTYPE html>, <html>, <head>, <body>)
├── not-found.php           # Página de erro 404 personalizada
├── error.php               # Página de erro 500 personalizada
├── page.php                # Rota visual da raiz ("/")
├── sobre/
│   └── page.php            # Rota visual ("/sobre")
├── (dashboard)/            # Route group: não afeta a URL pública
│   ├── layout.php          # Layout compartilhado do painel (sidebar + topo)
│   ├── middleware.php      # Middleware de autenticação para o painel
│   └── admin/
│       ├── page.php        # Painel ("admin")
│       └── produtos/
│           ├── [id]/
│           │   ├── page.php        # Ver/Editar produto ("/admin/produtos/123")
│           │   └── edit/
│           │       └── action.php  # Server Action de mutação ("/admin/produtos/123/edit")
│           └── route.php   # API REST de produtos
```

---

## 🛠 Como Usar: Guia Rápido

### 1. Inicializando o Roteador (`public/index.php`)

```php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Arbor\Router\Router;

// Instancie e configure o Roteador apontando para sua pasta "app"
$router = new Router([
    'appDir' => __DIR__ . '/../app',
    'security' => [
        'headers' => true,
        'csrf'    => true,
    ],
]);

// Dispara o ciclo de vida completo (headers, roteamento e envio)
$router->dispatch();
```

---

### 2. Páginas e Parâmetros Dinâmicos (`page.php`)

```php
<?php
// app/users/[id]/page.php
use Arbor\Router\Http\Request;

return function(Request $request, array $params): string {
    $userId = htmlspecialchars((string) $params['id'], ENT_QUOTES, 'UTF-8');
    return "<h1>Perfil do Usuário #{$userId}</h1>";
};
```

---

### 3. Layouts Aninhados (`layoutroot.php` e `layout.php`)

```php
<?php
// app/layoutroot.php (Shell Global)
return function($request, $params): string {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <title>Minha Aplicação Arbor</title>
    </head>
    <body>
        <main>{{content}}</main>
    </body>
    </html>
    HTML;
};
```

---

### 4. APIs e Rotas JSON (`route.php`)

```php
<?php
// app/api/produtos/[slug]/route.php
use Arbor\Router\Http\Request;
use Arbor\Router\Http\JsonResponse;

return [
    'GET' => function(Request $request, array $params) {
        // ContentNegotiator transforma automaticamente em JSON ou XML
        return [
            'slug'  => $params['slug'],
            'nome'  => 'Teclado Mecânico',
            'preco' => 250.00,
        ];
    },

    'DELETE' => function(Request $request, array $params) {
        return new JsonResponse(['removido' => true], 200);
    }
];
```

*Nota: Endpoints `route.php` exigem o cabeçalho `X-API-Request: true` por padrão.*

---

### 5. Server Actions e Formulários (`action.php`)

```php
<?php
// app/produtos/create/action.php
use Arbor\Router\Http\Request;
use Arbor\Router\Action\ActionResult;
use Arbor\Router\Validation\Validator;

return function(Request $request, array $params, array $query, array $body, Validator $validator) {
    // 1. Validação de Dados
    $validation = $validator->make($body, [
        'nome'  => 'required|string|min:3|max:100',
        'preco' => 'required|numeric|min:0.01',
    ]);

    if ($validation->fails()) {
        return ActionResult::error($validation->errors(), 'Dados inválidos')
            ->statusCode(422);
    }

    // 2. Persistência de dados...
    $novoId = 10;

    // 3. Sucesso com redirecionamento automático
    return ActionResult::success('Produto cadastrado!')
        ->data(['id' => $novoId])
        ->redirect('/produtos/' . $novoId);
};
```

No seu formulário HTML, inclua a proteção Anti-CSRF nativa:

```html
<form action="/produtos/create" method="POST">
    <?= (new \Arbor\Router\Security\CsrfGuard())->field() ?>
    <input type="text" name="nome" required>
    <input type="number" step="0.01" name="preco" required>
    <button type="submit">Cadastrar</button>
</form>
```

---

### 6. Middlewares em Cascata (`middleware.php`)

```php
<?php
// app/(painel)/middleware.php
use Arbor\Router\Http\RequestInterface;
use Arbor\Router\Http\RedirectResponse;
use Arbor\Router\Http\Response;

return function(RequestInterface $request, \Closure $next): Response {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        return new RedirectResponse('/login');
    }

    return $next($request);
};
```

---

## 🤖 Contexto para Agentes de IA

Esta biblioteca é **AI-Native**. Se você estiver desenvolvendo com agentes de IA (Cursor, Claude, Copilot, ChatGPT, Gemini), consulte:
- [`AGENTS.md`](AGENTS.md): Regras de ouro e instruções operacionais imediatas.
- [`ai/`](ai/README.md): Documentação modular detalhada com especificações completas de API, arquitetura, fluxos de trabalho, anti-patterns e troubleshooting.

---

## ⚖️ Licença

Licença **MIT**. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.
