#!/bin/sh
set -e

# Entrypoint leve para os serviços "artisan" (comandos avulsos via
# `docker compose run --rm artisan ...`), "worker" (queue:work) e
# "scheduler" (schedule:work). Diferente do /entrypoint.sh do serviço
# "php": não roda migrate nem provisiona Passport de novo (isso já
# acontece uma vez no boot do container "php", mesmo volume compartilhado)
# — só garante permissão de storage/ antes de executar o comando pedido.

cd /var/www/html

chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# league/oauth2-server recusa chaves com permissão fora de 400/440/600/640/660
# (ver /entrypoint.sh) — o chmod -R 775 acima reseta isso, corrige de novo.
if [ -f storage/oauth-private.key ]; then
    chmod 600 storage/oauth-private.key storage/oauth-public.key 2>/dev/null || true
fi

exec php artisan "$@"
