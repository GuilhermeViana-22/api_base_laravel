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
# As chaves ficam onde o Passport procura por padrão: storage/oauth-*.key.
# A pasta storage/ é um volume (docker-compose.prod.yml), então o par gerado
# no primeiro deploy é reaproveitado em todos os seguintes. Gerar um par novo
# invalidaria na hora TODOS os tokens já emitidos (quem estava logado cai).
#
# Só gera quando não existe nenhuma das duas; se sobrou só uma (volume
# corrompido/editado à mão), para o boot em vez de sobrescrever a que existe.
# ---------------------------------------------------------------------------
if [ -f "$PASSPORT_PRIVATE_KEY_PATH" ] && [ -f "$PASSPORT_PUBLIC_KEY_PATH" ]; then
    echo "[start] Chaves do Passport encontradas em storage/, reaproveitando."
elif [ ! -f "$PASSPORT_PRIVATE_KEY_PATH" ] && [ ! -f "$PASSPORT_PUBLIC_KEY_PATH" ]; then
    echo "[start] Nenhuma chave do Passport em storage/: gerando o par (primeiro deploy)..."
    php artisan passport:keys --no-interaction
else
    echo "[start] ERRO: só uma das chaves do Passport existe em storage/. Restaure o par ou apague as duas." >&2
    exit 1
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
# Documentação da API (só quando SWAGGER_ENABLED=true)
#
# Fora de desenvolvimento a especificação não é remontada a cada acesso
# (config/l5-swagger.php), e storage/api-docs/ não vem na imagem: se as telas
# estão abertas, o arquivo precisa ser gerado uma vez aqui.
# ---------------------------------------------------------------------------
if [ "${SWAGGER_ENABLED}" = "true" ]; then
    echo "[start] Gerando a documentação da API (/api/documentation)..."
    php artisan l5-swagger:generate || true
fi

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
