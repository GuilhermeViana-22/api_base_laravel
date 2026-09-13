#!/bin/sh
set -e

APP_DIR="/var/www/html"
ENV_FILE="$APP_DIR/.env"

# DB_HOST/DB_PORT lidos do ambiente, com fallback pro serviço "mysql" do
# docker-compose.yml de dev local — em produção (Dokploy) isso vem do .env
# apontando pro banco gerenciado externo.
DB_WAIT_HOST="${DB_HOST:-mysql}"
DB_WAIT_PORT="${DB_PORT:-3306}"

echo "[entrypoint] Aguardando banco em $DB_WAIT_HOST:$DB_WAIT_PORT..."
until nc -z "$DB_WAIT_HOST" "$DB_WAIT_PORT" 2>/dev/null; do sleep 2; done
echo "[entrypoint] Banco disponível."

# Retry: a rede docker às vezes entrega o 1o handshake de auth corrompido
# logo após o container subir — retry resolve sem precisar reiniciar.
retry() {
    n=1
    max=8
    until "$@"; do
        if [ "$n" -ge "$max" ]; then
            echo "[entrypoint] Comando falhou após $max tentativas: $*"
            return 1
        fi
        echo "[entrypoint] Tentativa $n/$max falhou, tentando de novo em 3s: $*"
        n=$((n + 1))
        sleep 3
    done
}

cd "$APP_DIR"

chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

php artisan config:clear

# `vendor:publish` sempre gera um novo timestamp de arquivo a cada chamada
# (não deduplica pelo nome da migration) — sem este guard, todo boot criaria
# um arquivo oauth_* duplicado e quebraria o migrate (tabela já existe).
if ! ls "$APP_DIR"/database/migrations/*_create_oauth_clients_table.php >/dev/null 2>&1; then
    echo "[entrypoint] Publicando migrations do Passport..."
    php artisan vendor:publish --tag=passport-migrations --no-interaction
else
    echo "[entrypoint] Migrations do Passport já publicadas."
fi

echo "[entrypoint] Aplicando migrations..."
retry php artisan migrate --force --no-interaction

echo "[entrypoint] Link simbólico storage (uploads públicos)..."
php artisan storage:link --force 2>/dev/null || php artisan storage:link 2>/dev/null || true

echo "[entrypoint] Garantindo chaves RSA do Passport..."
if [ ! -f "$APP_DIR/storage/oauth-private.key" ]; then
    php artisan passport:keys --no-interaction
    echo "[entrypoint] Chaves Passport geradas."
fi

# league/oauth2-server recusa (com erro fatal) chaves com permissão fora de
# 400/440/600/640/660 — o chmod -R 775 acima (necessário pro resto de
# storage/) reseta isso a cada boot, então corrigimos na sequência.
chmod 600 "$APP_DIR/storage/oauth-private.key" "$APP_DIR/storage/oauth-public.key"

echo "[entrypoint] Garantindo Passport personal access client..."
CLIENT_EXISTS=""
n=1
while [ "$n" -le 8 ] && [ -z "$CLIENT_EXISTS" ]; do
    CLIENT_EXISTS=$(php artisan tinker --execute="echo \Laravel\Passport\Client::whereJsonContains('grant_types', 'personal_access')->exists() ? '1' : '0';" 2>/dev/null | tr -d '[:space:]')
    case "$CLIENT_EXISTS" in
        0|1) ;;
        *) echo "[entrypoint] Tentativa $n/8 falhou ao checar client Passport, tentando de novo em 3s"; CLIENT_EXISTS=""; n=$((n + 1)); sleep 3 ;;
    esac
done

if [ "$CLIENT_EXISTS" != "1" ]; then
    echo "[entrypoint] Criando Passport personal access client..."
    retry php artisan passport:client --personal --name="API Laravel Base Personal Access Client" --no-interaction
else
    echo "[entrypoint] Passport personal access client já existe."
fi

php artisan config:cache
php artisan route:cache

echo "[entrypoint] Iniciando processo principal: $*"
exec "$@"
