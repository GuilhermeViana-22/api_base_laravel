# =============================================================================
# Dockerfile de PRODUÇÃO — API Laravel UNIVESP (porta 8019)
#
# Mesmo desenho do Dockerfile do Arquiteto Online: imagem autocontida (código
# e dependências entram no build), .env injetado pelo ambiente (Dokploy /
# docker compose) e um start.sh que prepara storage, Passport, migrations e
# fila antes de subir o servidor HTTP.
#
# Servidor: FrankenPHP (Caddy + PHP embutido) falando HTTP direto na 8019,
# sem nginx/php-fpm no meio. TLS fica com o proxy da frente (Traefik).
#
# Desenvolvimento local continua em docker-compose.yml (nginx + php-fpm, ver
# docker/Dockerfile.dev).
# =============================================================================
FROM dunglas/frankenphp:1-php8.4-alpine

LABEL maintainer="API - UNIVESP"
LABEL description="API Laravel - UNIVESP (notícias e banners do site)"

# bash: start.sh | netcat: espera o banco responder antes das migrations
RUN apk add --no-cache bash netcat-openbsd

# Extensões PHP usadas pelo Laravel e pelo upload/processamento de imagens
RUN install-php-extensions pdo_mysql gd exif bcmath zip pcntl opcache

# Limites de upload/memória (mesmo arquivo do ambiente de desenvolvimento)
COPY php/additional_config.ini /usr/local/etc/php/conf.d/additional_config.ini

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependências primeiro: a camada fica em cache enquanto composer.* não mudar
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader

# Código da aplicação (o .dockerignore tira vendor, .env, chaves e caches locais)
COPY src/ .

# O .env de produção vem do ambiente, nunca da imagem
RUN rm -f .env \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Porta HTTP da API (lida também pelo Caddyfile)
ENV HTTP_PORT=8019
EXPOSE 8019

CMD ["/usr/local/bin/start.sh"]
