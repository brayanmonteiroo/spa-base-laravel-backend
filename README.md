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

### 4. Dependências e chave da app

```bash
docker compose -f compose.dev.yaml exec workspace composer install
docker compose -f compose.dev.yaml exec workspace php artisan key:generate
```

### 5. Migrations

```bash
docker compose -f compose.dev.yaml exec workspace php artisan migrate
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
    └── workspace/Dockerfile
```

## Stack

- Laravel + Sanctum (API)
- PostgreSQL 18
- Redis (cache, fila, Horizon)
- Laravel Horizon
- Xdebug no `php-fpm` / `workspace` (opcional via `.env`)

## Observações

- Autenticação SPA completa fica para um plano futuro; Sanctum/CORS já estão preparados para o front em `:9020`.
- Não versionar `.env` (já está no `.gitignore`).
