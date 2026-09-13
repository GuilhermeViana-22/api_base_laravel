#!/bin/bash
# =============================================================================
# Inicialização do container de PRODUÇÃO (Dockerfile na raiz).
# Ordem: banco -> storage -> Passport -> migrations -> caches -> fila -> HTTP.
# =============================================================================
set -e

# ---------------------------------------------------------------------------
# Caminhos e parâmetros — em um lugar só, para não repetir literal pelo script.
# ---------------------------------------------------------------------------
PASSPORT_PRIVATE_KEY_PATH="storage/oauth-private.key"
PASSPORT_PUBLIC_KEY_PATH="storage/oauth-public.key"

DB_WAIT_HOST="${DB_HOST:-mysql}"
DB_WAIT_PORT="${DB_PORT:-3306}"

QUEUE_SLEEP="3"
QUEUE_TRIES="3"
QUEUE_MAX_TIME="3600"

RESTART_DELAY="3"

cd /var/www/html

# ---------------------------------------------------------------------------
# Banco: espera responder (o container da API pode subir antes do MySQL)
# ---------------------------------------------------------------------------
echo "[start] Aguardando banco em ${DB_WAIT_HOST}:${DB_WAIT_PORT}..."
until nc -z "$DB_WAIT_HOST" "$DB_WAIT_PORT" 2>/dev/null; do sleep 2; done

# ---------------------------------------------------------------------------
# Storage
# ---------------------------------------------------------------------------
mkdir -p storage/app/public storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
chmod -R 775 storage bootstrap/cache || true
php artisan optimize:clear || true
php artisan storage:link --force || true

# ---------------------------------------------------------------------------
# Passport
#
# As chaves precisam sobreviver ao deploy: gerar um par novo invalida na hora
# TODOS os tokens já emitidos (quem estava logado no painel cai). Por isso o
# par vem das env vars quando elas existem, e só é gerado quando não há nenhum.
#   PASSPORT_PRIVATE_KEY_BASE64=$(base64 -w0 storage/oauth-private.key)
#   PASSPORT_PUBLIC_KEY_BASE64=$(base64 -w0 storage/oauth-public.key)
# ---------------------------------------------------------------------------
if [ -n "$PASSPORT_PRIVATE_KEY_BASE64" ] && [ -n "$PASSPORT_PUBLIC_KEY_BASE64" ]; then
    echo "$PASSPORT_PRIVATE_KEY_BASE64" | base64 -d > "$PASSPORT_PRIVATE_KEY_PATH"
    echo "$PASSPORT_PUBLIC_KEY_BASE64" | base64 -d > "$PASSPORT_PUBLIC_KEY_PATH"
fi

if [ ! -f "$PASSPORT_PRIVATE_KEY_PATH" ] || [ ! -f "$PASSPORT_PUBLIC_KEY_PATH" ]; then
    echo "[start] Gerando chaves do Passport (tokens antigos deixam de valer)..."
    php artisan passport:keys --no-interaction
fi

# league/oauth2-server recusa chaves com permissão aberta demais
chmod 600 "$PASSPORT_PRIVATE_KEY_PATH" "$PASSPORT_PUBLIC_KEY_PATH"

# ---------------------------------------------------------------------------
# Migrations (idempotente: só aplica o que falta)
# ---------------------------------------------------------------------------
php artisan migrate --force --no-interaction

# Client de "personal access" do Passport (é ele que emite o token do login).
# "passport:client --personal" cria um novo a cada execução, então só roda
# quando ainda não existe nenhum.
CLIENT_EXISTS=$(php artisan tinker --execute="echo \Laravel\Passport\Client::whereJsonContains('grant_types', 'personal_access')->exists() ? '1' : '0';" 2>/dev/null | tr -d '[:space:]')
if [ "$CLIENT_EXISTS" != "1" ]; then
    echo "[start] Criando Passport personal access client..."
    php artisan passport:client --personal --name="UNIVESP Personal Access Client" --no-interaction
fi

# ---------------------------------------------------------------------------
# Caches de produção
# ---------------------------------------------------------------------------
php artisan config:cache
php artisan route:cache

# ---------------------------------------------------------------------------
# Fila (e-mails de verificação) — reiniciada caso morra
# ---------------------------------------------------------------------------
(
while true; do
    php artisan queue:work \
        --sleep="$QUEUE_SLEEP" \
        --tries="$QUEUE_TRIES" \
        --max-time="$QUEUE_MAX_TIME"
    sleep "$RESTART_DELAY"
done
) &

# ---------------------------------------------------------------------------
# Servidor da aplicação (porta em HTTP_PORT, padrão 8019 — ver Caddyfile)
# ---------------------------------------------------------------------------
echo "[start] API no ar na porta ${HTTP_PORT:-8019}"
exec frankenphp run --config /etc/caddy/Caddyfile
