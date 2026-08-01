# spa-base-laravel-backend

API base em Laravel (Docker, PostgreSQL, Redis, Horizon). Ambiente **somente desenvolvimento**.

Frontend SPA (repositório separado): Quasar em `http://localhost:9020`.

## Pré-requisitos

- Docker + Docker Compose
- Git

Não é necessário PHP/Composer no host para o fluxo diário — use o container `workspace`.

## Portas (dev)

| Serviço | Host |
|---------|------|
| API (Nginx) | http://localhost:8097 |
| Health | http://localhost:8097/api/health |
| Horizon | http://localhost:8097/horizon |
| PostgreSQL | `localhost:5437` |
| Redis | rede Docker interna (`redis:6379`) |

## Instalação passo a passo

### 1. Clonar

```bash
git clone git@github.com:brayanmonteiroo/spa-base-laravel-backend.git
cd spa-base-laravel-backend
```

### 2. Ambiente

```bash
cp .env.example .env
```

Ajuste se precisar (valores padrão já apontam para Docker):

- `DB_HOST=postgres`, `DB_DATABASE=spa_base`, `DB_USERNAME=laravel`, `DB_PASSWORD=secret`
- Testes usam o database **`spa_base_test`** (mesmo user/senha) — ver seção [Banco de testes](#banco-de-testes)
- `REDIS_HOST=redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`
- `APP_URL=http://localhost:8097`
- `FRONTEND_URL=http://localhost:9020`
- `SANCTUM_STATEFUL_DOMAINS=localhost:9020`
- `NGINX_PORT=8097`, `POSTGRES_PORT=5437`

### 3. Subir os containers

```bash
docker compose -f compose.dev.yaml up --build -d
```

Serviços: `web`, `php-fpm`, `workspace`, `postgres`, `redis`, `horizon`.

Em **volume Postgres novo**, o init cria automaticamente o database `spa_base_test`.  
Se o volume já existia, crie o banco de testes uma vez:

```bash
docker compose -f compose.dev.yaml exec postgres \
  psql -U laravel -d spa_base -c "CREATE DATABASE spa_base_test;"
```

(Ignore o erro se o database já existir.)

### 4. Dependências e chave da app

```bash
docker compose -f compose.dev.yaml exec workspace composer install
docker compose -f compose.dev.yaml exec workspace php artisan key:generate
```

### 5. Migrations + seeder

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate --seed
```

### 6. Verificar

```bash
curl http://localhost:8097/api/health
# esperado: {"status":"ok","app":"SpaBase"}
```

Horizon (em `APP_ENV=local`): http://localhost:8097/horizon

## Comandos úteis

```bash
# Artisan
docker compose -f compose.dev.yaml exec workspace php artisan <comando>

# Composer
docker compose -f compose.dev.yaml exec workspace composer <comando>

# Logs
docker compose -f compose.dev.yaml logs -f
docker compose -f compose.dev.yaml logs -f horizon

# Parar
docker compose -f compose.dev.yaml down

# Parar e remover volumes (apaga dados do Postgres)
docker compose -f compose.dev.yaml down -v
```

## Estrutura Docker

```text
compose.dev.yaml
docker/
├── common/php-fpm/Dockerfile
└── development/
    ├── nginx/nginx.conf
    ├── php-fpm/entrypoint.sh
    ├── postgres/init/01-create-test-database.sql
    └── workspace/Dockerfile
```

## Stack

- Laravel + Sanctum (SPA: cookies de sessão + CSRF)
- PostgreSQL 18
- Redis (cache, fila, Horizon)
- Laravel Horizon
- Xdebug no `php-fpm` / `workspace` (opcional via `.env`)

## Autenticação Sanctum SPA

Fluxo:

1. `GET /sanctum/csrf-cookie`
2. `POST /api/login` (guard `web`, sessão)
3. Rotas protegidas com `auth:sanctum`

Endpoints principais:

| Método | Rota | Auth |
|--------|------|------|
| POST | `/api/login` | público |
| POST | `/api/logout` | sanctum |
| GET | `/api/user` | sanctum |
| POST | `/api/forgot-password` | público |
| POST | `/api/reset-password` | público |
| * | `/api/admin/users` | sanctum (CRUD) |

Não há registro público.

### Admin (seeder)

```bash
docker compose -f compose.dev.yaml exec workspace php artisan db:seed
```

Credenciais padrão:

- E-mail: `admin@spa-base.test`
- Senha: `password`
- Role: `admin` (todas as permissions de Painel + Usuários)

O seeder também cria o role `user` (`dashboard.sidebar` + `dashboard.view`) e atribui aos usuários de factory.

### Testes e CI

Rodar **sempre no container `workspace`** (precisa do Postgres no Compose):

```bash
# Só testes (Pest)
docker compose -f compose.dev.yaml exec workspace php artisan test

# Lint (Pint --test) + testes — mesmo pipeline do GitHub Actions
docker compose -f compose.dev.yaml exec workspace composer ci
```

Scripts Composer:

| Script | O que faz |
|--------|-----------|
| `composer lint` | `pint --test` (não altera arquivos) |
| `composer test` | limpa config + Pest |
| `composer ci` | `lint` + `test` |

### GitHub Actions

Workflow [`.github/workflows/ci.yml`](.github/workflows/ci.yml): em push/PR para `main`/`master`, sobe Postgres de serviço, PHP **8.5**, `composer install` e `composer ci`.

`APP_KEY` é gerada no job (não fica hardcoded no YAML). Localmente use o Docker; no Actions o host do banco é `127.0.0.1`.

## Banco de testes

Os Feature tests usam **Postgres** (mesmo motor da aplicação), em um database **isolado**:

| Uso | Database | Credenciais |
|-----|----------|-------------|
| App (browser / `artisan migrate`) | `spa_base` | `laravel` / `secret` |
| Pest / `php artisan test` | `spa_base_test` | mesmas |

Configuração:

- [`phpunit.xml`](phpunit.xml) força `DB_DATABASE=spa_base_test` (via `<server>`, para vencer o `env_file` do Docker)
- [`.env.testing`](.env.testing) espelha as mesmas `DB_*` para `artisan --env=testing`
- `RefreshDatabase` nos Feature limpa **somente** `spa_base_test`

Não use `migrate:fresh` no `.env` de desenvolvimento achando que está “só testando” — isso afeta `spa_base` e a sessão da app.

## Observações

- Timezone: `America/Belem` (`APP_TIMEZONE` + `TZ` no `.env`) — containers PHP herdam via `env_file`.
- Logs: canal `daily` (`LOG_STACK=daily`) com retenção de **30 dias** (`LOG_DAILY_DAYS=30`) — arquivos em `storage/logs/laravel-YYYY-MM-DD.log`.
- `FRONTEND_URL` e `SANCTUM_STATEFUL_DOMAINS` devem bater com o Quasar (`localhost:9020`).
- Links de reset de senha apontam para o frontend (`/reset-password`).
- Mail em Docker usa `MAIL_MAILER=log` (sem SMTP real).
- Não versionar `.env` (já está no `.gitignore`). `.env.testing` pode ser versionado (sem segredos além dos do exemplo).
