# Contexto do Projeto — Blog API (Symfony 8 + Clean Architecture)

## Stack

| Componente        | Versão/Imagem       |
|-------------------|---------------------|
| PHP               | 8.5-FPM             |
| Symfony           | 8.0                 |
| Doctrine ORM      | 3.x                 |
| PostgreSQL        | 16                  |
| Nginx             | alpine              |
| Docker            | Compose v2+         |
| NelmioApiDocBundle| 5.9 (OpenAPI 3.x)   |
| PHPUnit           | 13                  |

## Ambiente Docker

```
Container       Porta externa   Finalidade
blog_php        —               PHP-FPM (app)
blog_nginx      8080            HTTP entry point
blog_postgres   5432            Banco de dados
```

```bash
docker compose up -d          # subir ambiente
docker compose exec php bash  # shell no container
```

**Credenciais do banco:**
- Host: `postgres` (dentro dos containers) / `localhost:5432` (host)
- Banco: `blog` | Usuário: `blog_user` | Senha: `blog_password`

## Arquitetura — Clean Architecture

```
src/
├── Domain/          # Regras de negócio puras — ZERO dependências externas
├── Application/     # Casos de uso (CQRS: Commands + Queries + Handlers)
├── Infrastructure/  # Doctrine ORM, repositórios concretos
└── Presentation/    # Controllers HTTP (adaptadores finos)
```

**Regra de dependência:** as setas apontam sempre para dentro.
`Presentation → Application → Domain ← Infrastructure`

### Domain Layer

Entidades de domínio são **POPOs** (plain PHP objects) sem annotations — totalmente isolados do framework.

```
Domain/
├── Shared/ValueObject/Uuid.php              ← base abstrata para todos os UUID VOs
├── Author/
│   ├── Entity/Author.php
│   ├── ValueObject/{AuthorId, Email}.php
│   ├── Repository/AuthorRepositoryInterface.php
│   └── Exception/{AuthorNotFoundException, EmailAlreadyExistsException}.php
└── Post/
    ├── Entity/Post.php                      ← aggregate root
    ├── ValueObject/{PostId, Title, Slug, Content}.php
    ├── Enum/PostStatus.php                  ← DRAFT → PUBLISHED → ARCHIVED
    ├── Repository/PostRepositoryInterface.php
    └── Exception/{PostNotFoundException, InvalidPostStatusTransitionException, SlugAlreadyExistsException}.php
```

**Value Objects** são imutáveis e auto-validantes. Sempre instanciar pelo construtor ou factory estático.

**Regras de transição de status do Post:**
- `DRAFT` → pode publicar (`publish()`)
- `PUBLISHED` → pode arquivar (`archive()`)
- `ARCHIVED` → sem transição possível (lança `InvalidPostStatusTransitionException`)
- `publish()` e `archive()` são idempotentes

### Application Layer (CQRS)

```
Application/
├── Author/
│   ├── Create/{CreateAuthorCommand, CreateAuthorHandler}
│   ├── Find/{FindAuthorByIdQuery, FindAuthorByIdHandler}
│   └── DTO/AuthorDTO.php         ← também carrega #[OA\Schema] para documentação
└── Post/
    ├── Create/   Update/   Delete/   Publish/   Archive/
    ├── Find/     List/
    └── DTO/{PostDTO, PostListDTO}.php  ← também carregam #[OA\Schema]
```

Handlers recebem Commands/Queries como único argumento e retornam DTOs.
Nunca retornam entidades de domínio diretamente para fora da camada de aplicação.

**DTOs carregam os schemas OpenAPI** (`#[OA\Schema]`, `#[OA\Property]`) — as anotações ficam
na Application layer, não nos controllers, mantendo os controllers finos.

### Infrastructure Layer

Mapeamento Doctrine via **XML** (não annotations) — mantém o domínio limpo.

```
Infrastructure/Persistence/Doctrine/
├── Mapping/
│   ├── Post.Entity.Post.orm.xml     ← mapeamento de App\Domain\Post\Entity\Post
│   └── Author.Entity.Author.orm.xml ← mapeamento de App\Domain\Author\Entity\Author
└── Repository/
    ├── DoctrinePostRepository.php   ← implementa PostRepositoryInterface
    └── DoctrineAuthorRepository.php ← implementa AuthorRepositoryInterface
```

**Naming dos arquivos XML:** o nome segue o namespace relativo ao `prefix: 'App\Domain'` com separador `.`.
Exemplo: classe `App\Domain\Post\Entity\Post` → arquivo `Post.Entity.Post.orm.xml`.

**Doctrine config** (`config/packages/doctrine.yaml`):
```yaml
orm:
  auto_mapping: false
  mappings:
    Domain:
      type: xml
      dir: '%kernel.project_dir%/src/Infrastructure/Persistence/Doctrine/Mapping'
      prefix: 'App\Domain'
```

**Nota sobre FK:** `posts.author_id` é armazenado como campo `guid` simples (não `many-to-one`),
preservando o isolamento entre aggregates. Não existe FK no banco entre posts e authors — é intencional.

### Presentation Layer

Controllers em `src/Presentation/Http/Controller/` (não em `src/Controller/`).
Symfony auto-descobre via `routing.controllers` (`config/routes.yaml`).

Responsabilidade dos controllers: parsear JSON → criar Command/Query → chamar Handler → retornar JsonResponse.
**Nunca** colocar lógica de negócio nos controllers.

Cada método de controller carrega os attributes OpenAPI (`#[OA\Get]`, `#[OA\Post]`, etc.)
diretamente sobre a action, documentando parâmetros, request body e todos os status de resposta.

## API Endpoints

```
GET    /ping                              healthcheck (fora do escopo do /api/doc)

GET    /api/doc                           Swagger UI (NelmioApiDocBundle)
GET    /api/doc.json                      OpenAPI 3.x spec em JSON

POST   /api/authors                       criar autor
GET    /api/authors/{id}                  buscar autor por ID (UUID)

GET    /api/posts?page=1&limit=10&status=draft   listar posts (paginado, filtrável)
POST   /api/posts                         criar post (status inicial: draft)
GET    /api/posts/{id}                    buscar post por ID (UUID)
GET    /api/posts/by-slug/{slug}          buscar post por slug
PUT    /api/posts/{id}                    atualizar post
DELETE /api/posts/{id}                    deletar post
PATCH  /api/posts/{id}/publish            publicar post  (DRAFT → PUBLISHED)
PATCH  /api/posts/{id}/archive            arquivar post  (PUBLISHED → ARCHIVED)
```

**Status HTTP usados:**
- `200` OK, `201` Created, `204` No Content
- `400` Bad Request (campo inválido/UUID mal formado)
- `404` Not Found
- `409` Conflict (slug ou e-mail duplicado)
- `422` Unprocessable Entity (transição de status inválida)

**Envelope de erro padrão (todas as respostas 4xx):**
```json
{ "error": "Mensagem descritiva do problema." }
```

## Documentação OpenAPI (NelmioApiDocBundle)

### Pacotes necessários
```
nelmio/api-doc-bundle  ^5.9
symfony/twig-bundle    8.0.*
symfony/asset          8.0.*   ← obrigatório; sem ele o controller swagger_ui é removido
twig/extra-bundle      ^3.24
```

### Configuração (`config/packages/nelmio_api_doc.yaml`)
```yaml
nelmio_api_doc:
    documentation:
        info:
            title: Blog API
            version: 1.0.0
        components:
            schemas:
                ErrorResponse:          # schema global compartilhado pelos controllers
                    type: object
                    required: [error]
                    properties:
                        error: { type: string }
    areas:
        default:
            path_patterns: ['^/api(?!/doc)']
```

### Rotas (`config/routes.yaml`)
```yaml
app.swagger_ui:
    path: /api/doc
    defaults: { _controller: nelmio_api_doc.controller.swagger_ui, area: default }

app.swagger_json:
    path: /api/doc.json
    defaults: { _controller: nelmio_api_doc.controller.swagger, area: default }
```

### Onde ficam os schemas
| Schema         | Arquivo                                      |
|----------------|----------------------------------------------|
| `AuthorDTO`    | `src/Application/Author/DTO/AuthorDTO.php`   |
| `PostDTO`      | `src/Application/Post/DTO/PostDTO.php`       |
| `PostListDTO`  | `src/Application/Post/DTO/PostListDTO.php`   |
| `ErrorResponse`| `config/packages/nelmio_api_doc.yaml`        |

### Gotcha: `area` obrigatório na rota
No NelmioApiDocBundle v5, o parâmetro `area` **deve** ser declarado em `defaults` da rota.
Sem ele, o controller lança `BadRequestHttpException("Area 'default' is not supported")`.

## Testes

```bash
# rodar todos os testes
docker compose exec php vendor/bin/phpunit --configuration phpunit.dist.xml

# com cobertura (requer PCOV instalado no container)
docker compose exec php vendor/bin/phpunit --configuration phpunit.dist.xml --coverage-text
```

- Framework: **PHPUnit 13**
- Cobertura: **PCOV** (instalado no Dockerfile via `pecl install pcov`)
- Usar `#[DataProvider('...')]` (attribute PHP 8.1) — `@dataProvider` annotation está depreciada no v13
- Escopo de cobertura: `src/Domain` e `src/Application` (Infrastructure e Presentation excluídos)
- 128 testes unitários, todos passando

## Padrões e Convenções

### Adicionar novo Aggregate (ex: Category)

1. Criar `src/Domain/Category/Entity/Category.php` (POPO, sem annotations)
2. Criar Value Objects em `src/Domain/Category/ValueObject/`
3. Criar `src/Domain/Category/Repository/CategoryRepositoryInterface.php`
4. Criar XML em `src/Infrastructure/Persistence/Doctrine/Mapping/Category.Entity.Category.orm.xml`
5. Criar `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineCategoryRepository.php`
6. Registrar binding em `config/services.yaml`
7. Criar handlers em `src/Application/Category/` (com `#[OA\Schema]` no DTO)
8. Criar controller em `src/Presentation/Http/Controller/CategoryController.php` (com attributes OpenAPI)
9. Rodar `php bin/console doctrine:migrations:diff` e `doctrine:migrations:migrate`

### DI Bindings (`config/services.yaml`)

Toda interface de repositório precisa de binding explícito:
```yaml
App\Domain\Post\Repository\PostRepositoryInterface:
    class: App\Infrastructure\Persistence\Doctrine\Repository\DoctrinePostRepository
```

### UUIDs

Usa `symfony/uid` — `Uuid::v7()` (monotônico, ordenável por tempo).
Geração acontece nos Value Objects (`PostId::generate()`, `AuthorId::generate()`).
No banco PostgreSQL: coluna do tipo `UUID` (Doctrine type `guid`).

## Gotchas Conhecidos

| Problema | Causa | Solução |
|---|---|---|
| `php:8.5-fpm` — extensão `zip` falha ao compilar | já embutida no PHP 8.5 | não usar `docker-php-ext-install zip` |
| `opcache` e `pdo` também já embutidos | idem | não instalar; apenas `pdo_pgsql` precisa ser instalado |
| `static` como tipo de parâmetro | não suportado em PHP (só como retorno) | usar `self` em Value Objects base |
| Arquivo XML de mapping não encontrado | nome não segue o padrão | nome = namespace relativo ao prefix com `.` como separador |
| Permissões do `var/` no container | composer roda como root | `docker/php/entrypoint.sh` faz `chown www-data var/` na inicialização |
| `compose.override.yaml` gerado pelo recipe do Doctrine | conflito com postgres já configurado | deletar o arquivo após instalar `doctrine/doctrine-bundle` |
| NelmioApiDocBundle v5: `swagger_ui` controller não registrado | `symfony/asset` ausente | instalar `symfony/asset`; o bundle remove o controller se o pacote não existir |
| NelmioApiDocBundle v5: `Area "default" is not supported` | parâmetro `area` faltando na rota | adicionar `area: default` nos `defaults` da rota |
| Coluna `id` aparece no final da tabela no DBeaver | Doctrine com `strategy="NONE"` cria `id` por último | apenas cosmético; PK existe e funciona normalmente |
| FK entre posts e authors não existe no banco | `author_id` mapeado como `guid` simples, não `many-to-one` | intencional (isolamento de aggregates); adicionar via migration manual se necessário |

## Migrações

```bash
# gerar migration a partir das entidades
docker compose exec php php bin/console doctrine:migrations:diff

# executar migrações pendentes
docker compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# ver status
docker compose exec php php bin/console doctrine:migrations:status
```

## Estrutura de Arquivos Completa

```
blog-symfony/
├── .devcontainer/devcontainer.json        # VS Code Dev Container (conecta ao serviço php)
├── .vscode/settings.json                  # configurações Intelephense para o workspace
├── docker/
│   ├── nginx/default.conf
│   └── php/
│       ├── Dockerfile                     # php:8.5-fpm + pdo_pgsql + pcov + composer
│       └── entrypoint.sh                  # corrige permissões do var/
├── docker-compose.yml
├── migrations/                            # geradas pelo Doctrine
├── phpunit.dist.xml                       # config ativa do PHPUnit (usada pelo runner)
├── tests/
│   ├── bootstrap.php
│   └── Unit/
│       ├── Domain/
│       │   ├── Shared/ValueObject/UuidTest.php
│       │   ├── Author/...
│       │   └── Post/...
│       └── Application/
│           ├── Author/...
│           └── Post/...
├── src/
│   ├── Domain/
│   │   ├── Shared/ValueObject/Uuid.php
│   │   ├── Author/...
│   │   └── Post/...
│   ├── Application/
│   │   ├── Author/
│   │   │   ├── Create/  Find/
│   │   │   └── DTO/AuthorDTO.php          # inclui #[OA\Schema]
│   │   └── Post/
│   │       ├── Create/  Update/  Delete/  Publish/  Archive/  Find/  List/
│   │       └── DTO/{PostDTO, PostListDTO}.php  # incluem #[OA\Schema]
│   ├── Infrastructure/
│   │   └── Persistence/Doctrine/
│   │       ├── Mapping/*.orm.xml
│   │       └── Repository/Doctrine*.php
│   └── Presentation/
│       └── Http/Controller/
│           ├── PingController.php         # GET /ping
│           ├── AuthorController.php       # POST/GET /api/authors
│           └── PostController.php         # CRUD + publish/archive /api/posts
└── config/
    ├── bundles.php                        # inclui NelmioApiDocBundle
    ├── routes.yaml                        # inclui rotas /api/doc e /api/doc.json
    ├── packages/
    │   ├── doctrine.yaml                  # auto_mapping: false, XML mapping
    │   ├── nelmio_api_doc.yaml            # config OpenAPI, area default, ErrorResponse schema
    │   └── twig.yaml
    └── services.yaml                      # bindings interface → implementação
```
