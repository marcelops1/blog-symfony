# Contexto do Projeto — Blog API (Symfony 8 + Clean Architecture)

## Stack

| Componente   | Versão/Imagem       |
|--------------|---------------------|
| PHP          | 8.5-FPM             |
| Symfony      | 8.0                 |
| Doctrine ORM | 3.x                 |
| PostgreSQL   | 16                  |
| Nginx        | alpine              |
| Docker       | Compose v2+         |

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
├── Author/
│   ├── Entity/Author.php
│   ├── ValueObject/{AuthorId, Email}.php
│   ├── Repository/AuthorRepositoryInterface.php   ← contrato (interface)
│   └── Exception/{AuthorNotFoundException, EmailAlreadyExistsException}.php
└── Post/
    ├── Entity/Post.php                            ← aggregate root
    ├── ValueObject/{PostId, Title, Slug, Content}.php
    ├── Enum/PostStatus.php                        ← DRAFT → PUBLISHED → ARCHIVED
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
│   └── DTO/AuthorDTO.php
└── Post/
    ├── Create/   Update/   Delete/   Publish/   Archive/
    ├── Find/     List/
    └── DTO/{PostDTO, PostListDTO}.php
```

Handlers recebem Commands/Queries como único argumento e retornam DTOs.
Nunca retornam entidades de domínio diretamente para fora da camada de aplicação.

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

### Presentation Layer

Controllers em `src/Presentation/Http/Controller/` (não em `src/Controller/`).
Symfony auto-descobre via `routing.controllers` (config/routes.yaml).

Responsabilidade dos controllers: parsear JSON → criar Command/Query → chamar Handler → retornar JsonResponse.
**Nunca** colocar lógica de negócio nos controllers.

## API Endpoints

```
GET    /ping                         healthcheck

POST   /api/authors                  criar autor
GET    /api/authors/{id}             buscar autor por ID

GET    /api/posts?page=1&limit=10&status=draft   listar posts (paginado)
POST   /api/posts                    criar post (status inicial: draft)
GET    /api/posts/{id}               buscar post por ID
GET    /api/posts/by-slug/{slug}     buscar post por slug
PUT    /api/posts/{id}               atualizar post
DELETE /api/posts/{id}               deletar post
PATCH  /api/posts/{id}/publish       publicar post
PATCH  /api/posts/{id}/archive       arquivar post
```

**Status HTTP usados:**
- `200` OK, `201` Created, `204` No Content
- `400` Bad Request (campo inválido), `404` Not Found, `409` Conflict (slug/email duplicado), `422` Unprocessable (transição de status inválida)

## Padrões e Convenções

### Adicionar novo Aggregate (ex: Category)

1. Criar `src/Domain/Category/Entity/Category.php` (POPO, sem annotations)
2. Criar Value Objects em `src/Domain/Category/ValueObject/`
3. Criar `src/Domain/Category/Repository/CategoryRepositoryInterface.php`
4. Criar XML em `src/Infrastructure/Persistence/Doctrine/Mapping/Category.Entity.Category.orm.xml`
5. Criar `src/Infrastructure/Persistence/Doctrine/Repository/DoctrineCategoryRepository.php`
6. Registrar binding em `config/services.yaml`
7. Criar handlers em `src/Application/Category/`
8. Criar controller em `src/Presentation/Http/Controller/CategoryController.php`
9. Rodar `php bin/console doctrine:migrations:diff` e `doctrine:migrations:migrate`

### DI Bindings (config/services.yaml)

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
| Arquivo XML de mapping não encontrado | nome do arquivo não segue o padrão | nome = namespace relativo ao prefix com `.` como separador |
| Permissões do `var/` no container | composer roda como root no container | `docker/php/entrypoint.sh` faz `chown www-data var/` na inicialização |
| `compose.override.yaml` gerado pelo recipe do Doctrine | conflito com `postgres` já configurado | deletar o arquivo após instalar `doctrine/doctrine-bundle` |

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
├── .devcontainer/devcontainer.json   # VS Code Dev Container (conecta ao serviço php)
├── .vscode/settings.json             # configurações Intelephense para o workspace
├── docker/
│   ├── nginx/default.conf
│   └── php/
│       ├── Dockerfile                # php:8.5-fpm + pdo_pgsql + composer
│       └── entrypoint.sh             # corrige permissões do var/
├── docker-compose.yml
├── migrations/                       # geradas pelo Doctrine
├── src/
│   ├── Domain/
│   │   ├── Shared/ValueObject/Uuid.php
│   │   ├── Author/...
│   │   └── Post/...
│   ├── Application/
│   │   ├── Author/...
│   │   └── Post/...
│   ├── Infrastructure/
│   │   └── Persistence/Doctrine/
│   │       ├── Mapping/*.orm.xml
│   │       └── Repository/Doctrine*.php
│   └── Presentation/
│       └── Http/Controller/
│           ├── PingController.php
│           ├── AuthorController.php
│           └── PostController.php
└── config/
    ├── packages/doctrine.yaml        # auto_mapping: false, XML mapping
    └── services.yaml                 # bindings interface → implementação
```
