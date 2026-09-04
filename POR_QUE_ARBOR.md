# 🤔 Por que usar o Arbor Router?

> Uma análise sincera sobre o posicionamento da biblioteca, seu público-alvo, e os cenários ideais (e não ideais) para sua utilização.

Com base na arquitetura do **Arbor Router** — que prioriza *File-System Routing*, separação estrita de contextos (`page.php`, `route.php`, `action.php`), middlewares em cascata e zero dependências pesadas —, elaboramos este guia para ajudá-lo a decidir se esta é a ferramenta certa para o seu próximo projeto.

---

## 🎯 1. Para qual tipo de projeto a lib é indicada?

O Arbor Router brilha especialmente nos seguintes cenários:

- **Aplicações Híbridas (B2B, SaaS, Dashboards, Lojas):** Sistemas que misturam painéis administrativos tradicionais (renderizados no servidor com HTML) e chamadas reativas e dinâmicas no front-end (consumindo endpoints JSON/XML de forma assíncrona).
- **MVPs e Projetos de Médio Porte:** Onde a velocidade de desenvolvimento e a organização lógica de pastas importam muito mais do que ter dezenas de pacotes e integrações complexas pré-instaladas.
- **Refatorações de Código Legado:** Graças ao seu baixo acoplamento, é muito fácil plugar o Arbor num sistema PHP antigo para organizar as rotas HTTP sem a necessidade de reescrever toda a camada de banco de dados para o padrão de um grande framework.

---

## 👥 2. Qual público ela atende?

- **Desenvolvedores "Modernos" (Fullstacks):** Profissionais familiarizados com o ecossistema JavaScript atual (como Next.js, SvelteKit, Remix) e que sentem falta da simplicidade do *File-System Routing* (onde a estrutura de pastas dita a URL) quando programam em PHP.
- **Minimalistas (Anti-Bloatware):** Desenvolvedores que evitam baixar frameworks monolíticos pesados, que trazem bibliotecas nunca utilizadas apenas para fazer um sistema simples funcionar. O Arbor foca exclusivamente e de forma estrita na camada HTTP (Rotas, Request e Response) utilizando PHP 8.2+.

---

## 💊 3. Qual é a MAIOR dor que ela resolve?

O Arbor Router ataca problemas crônicos no desenvolvimento PHP web tradicional:

### ❌ O "Route Hell" (O inferno das rotas gigantes)
Em frameworks clássicos, é comum encontrar arquivos como `web.php` ou `api.php` com centenas (ou milhares) de linhas contendo definições de rotas impossíveis de rastrear.  
**✅ A Solução:** No Arbor, **a árvore de diretórios é o mapa de rotas**. Você sabe exatamente onde o código de `/admin/pedidos` está: basta abrir a pasta `app/admin/pedidos`.

### ❌ O Espaguete de Contextos
Controladores monstruosos que, ao mesmo tempo, renderizam views HTML complexas e devolvem respostas JSON misturadas, tornando a manutenção um pesadelo.  
**✅ A Solução:** O Arbor força a organização separando as responsabilidades:
- Quem renderiza HTML visual -> `page.php`
- Quem processa formulários seguros (via POST/PUT) -> `action.php`
- Quem fornece dados programáticos em JSON/XML -> `route.php`

### ❌ Complexidade de Layouts
**✅ A Solução:** Lidar com menus globais versus menus internos específicos de um painel (dashboard) torna-se trivial com a hierarquia em cascata do `layout.php`.

---

## ⚠️ 4. Quais problemas ela NÃO resolve? (Quando NÃO é indicada)

O Arbor é um **Roteador Avançado (Micro-framework)** focado puramente em requisições HTTP, e não um **Full-Stack Framework** tradicional (como Laravel ou Symfony). Portanto, **ele NÃO é indicado** se o seu projeto depende fortemente das seguintes features nativas *prontas para uso*:

1. **Mapeamento de Banco de Dados (ORM) e Migrations:**  
   O Arbor não acessa nem gerencia bancos de dados. Ele não dita se você deve usar PDO puro, Doctrine ou Eloquent. Se o seu projeto exige regras de relacionamento complexas prontas, um ORM nativo integrado fará falta.
   
2. **Processamento em Segundo Plano (Queues, Jobs e CRONs):**  
   Projetos de alta escalabilidade que precisam enfileirar milhares de e-mails via Redis, processar WebSockets em tempo real ou executar Workers. O Arbor não possui motores assíncronos.
   
3. **Ecossistemas Fechados e Corporativos:**  
   Se o projeto requer integrações nativas oficiais prontas para processamento de pagamentos (como Laravel Cashier), painéis auto-gerados (Laravel Nova) ou autenticação social/OAuth nativa (Socialite), o Arbor exigirá que você programe ou instale pacotes externos para resolver cada um desses domínios manualmente.

> **Resumo Final:** O Arbor Router é perfeito para quem deseja **controle absoluto, extrema velocidade de execução e organização impecável do tráfego HTTP**. Porém, para domínios absurdamente grandes e complexos onde você prefere delegar até o acesso ao Banco de Dados e as Filas de Tarefas para a ferramenta, um Framework Gigante tradicional será o caminho mais rápido.
