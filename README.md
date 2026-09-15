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
| `MAIL_MAILER=resend`, `RESEND_API_KEY` | envio do código de verificação pelo Resend |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | remetente (domínio verificado no Resend: `guilhermeviana.com`) |
| `AUTH_VERIFICATION_*` | validade do código (15 min), tentativas (5) e intervalo de reenvio (60 s) |

## Rotas principais

| Método | Rota | Acesso |
|---|---|---|
| POST | `/api/auth/register`, `/api/auth/verify-email`, `/api/auth/resend-code` | público |
| POST | `/api/auth/login` | público |
| GET | `/api/posts`, `/api/posts/{id}` | público (só publicadas) |
| GET | `/api/banners/{pagina}` | público |
| CRUD | `/api/admin/posts` (+ `/cover`, `/api/admin/uploads/images`) | token Bearer |
| GET/PUT | `/api/admin/banners/{pagina}` (+ `/image`) | token Bearer |

## Cadastro com verificação de e-mail

1. `POST /api/auth/register` `{ name, email, password, password_confirmation }` → **201**
   `{ message, data: { email, code_length, expires_at, resend_available_at, attempts_remaining } }`.
   A API gera um código de 6 dígitos (guardado só como HMAC) e envia pelo Resend, via fila.
2. `POST /api/auth/verify-email` `{ email, code }` → **200** `{ message, data: { user, access_token, token_type, expires_at } }`
   — confirmou, já está logado.
3. `POST /api/auth/resend-code` `{ email }` → **200** com o mesmo `data` do cadastro. O código anterior deixa de valer.

Erros de negócio sempre vêm como `{ message, code, ... }` (ver `app/Exceptions/AuthException.php`):

| HTTP | `code` | Extras | Quando |
|---|---|---|---|
| 401 | `invalid_credentials` | | login com e-mail/senha errados |
| 403 | `email_not_verified` | `verification` | login antes de confirmar o e-mail |
| 409 | `email_already_verified` | | verificar/reenviar de conta já confirmada |
| 410 | `verification_code_expired` | | código passou dos 15 minutos |
| 422 | `verification_code_invalid` | `attempts_remaining` | código errado |
| 429 | `verification_too_many_attempts` | | 5 erros no mesmo código |
| 429 | `verification_resend_cooldown` | `retry_after` | reenvio antes de 60 s |
| 429 | `too_many_requests` | `retry_after` | mais de 5 chamadas/min por e-mail + IP |

Validação continua no formato padrão do Laravel: 422 `{ message, errors }`.
