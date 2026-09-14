# API UNIVESP (Laravel)

API REST do site da UNIVESP: login da área restrita (Passport), notícias e banners.
O código Laravel fica em `src/`.

## Desenvolvimento (nginx + php-fpm + MySQL)

```bash
cp .env.example .env            # porta e senhas do MySQL local
cp src/.env.example src/.env    # configuração do Laravel
docker network create adsplayx-public   # só na primeira vez
docker run --rm -u "$(id -u):$(id -g)" -v "$PWD/src":/app -w /app composer:2.8 \
  sh -c "composer install && php artisan key:generate"
docker compose up -d --build
```

API em **http://localhost:8019/api**. Testes: `docker compose exec php php artisan test`.

## Produção (Dokploy + Traefik)

Mesmo formato do Arquiteto Online: `Dockerfile` na raiz gera uma imagem autocontida
(FrankenPHP) e `docker/start.sh` espera o banco, prepara storage e Passport, roda as
migrations, sobe a fila em segundo plano e serve a API na porta **8019** (só dentro do
container).

A API é publicada pelo Traefik do Dokploy em **https://univesp.guilhermeviana.com/api**.
O roteamento (HTTP → HTTPS, certificado Let's Encrypt) está nos labels do
`docker-compose.prod.yml`; não cadastre o domínio de novo na aba *Domains* do Dokploy.

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Variáveis importantes (em `src/.env` ou no painel do Dokploy):

| Variável | Uso |
|---|---|
| `APP_KEY`, `DB_*` | padrão do Laravel |
| `APP_URL` | `https://univesp.guilhermeviana.com` (padrão do compose; monta a URL das imagens) |
| `FRONTEND_URL` | front liberado no CORS: `https://univesp-tv-front.vercel.app` |
| `CORS_ALLOW_LOCALHOST` | `true` libera `localhost`/`127.0.0.1` em qualquer porta |
| `PASSPORT_PRIVATE_KEY_BASE64`, `PASSPORT_PUBLIC_KEY_BASE64` | mantêm os tokens válidos entre deploys |

## Rotas principais

| Método | Rota | Acesso |
|---|---|---|
| POST | `/api/auth/login` | público |
| GET | `/api/posts`, `/api/posts/{id}` | público (só publicadas) |
| GET | `/api/banners/{pagina}` | público |
| CRUD | `/api/admin/posts` (+ `/cover`, `/api/admin/uploads/images`) | token Bearer |
| GET/PUT | `/api/admin/banners/{pagina}` (+ `/image`) | token Bearer |
