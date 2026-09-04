<?php

declare(strict_types=1);

namespace Arbor\Router\Routing;

/**
 * Enum representando os tipos de rota suportados.
 *
 * Cada tipo de arquivo na árvore de diretórios corresponde a um
 * tipo de rota com comportamento e validações distintas:
 *
 * - Page: renderiza HTML com sistema de layouts (page.php)
 * - Api: responde a requisições de API com negociação de conteúdo (route.php)
 * - Action: processa formulários/mutações com validação de segurança (action.php)
 *
 * @package Arbor\Router\Routing
 */
enum RouteType: string
{
    /** Rota de página — renderiza HTML com layouts */
    case Page = 'page';

    /** Rota de API — responde JSON/XML/TXT com header obrigatório */
    case Api = 'api';

    /** Rota de ação — processa formulários com validação de origem */
    case Action = 'action';
}
