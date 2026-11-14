# syntax=docker/dockerfile:1
#
# Image HANA Calgary : WordPress + plugins/thème tiers figés + code maison.
# Construite par GitHub Actions (voir .github/workflows/ci-cd.yml), contexte = racine du dépôt.
# Les médias (uploads) et la base de données ne sont PAS dans l'image : ce sont des volumes.

ARG BASE_IMAGE=wordpress:php8.2-apache

# --- Étape 1 : téléchargement de WordPress, des composants et des traductions ---
FROM alpine:3.20 AS fetch
RUN apk add --no-cache curl unzip jq
ARG WP_VERSION=7.1.4
ARG WP_LOCALES="fr_FR en_GB"
COPY docker/components.txt docker/fetch-components.sh /tmp/build/
RUN sh /tmp/build/fetch-components.sh /build/wordpress "$WP_VERSION" "$WP_LOCALES"
# Code maison (plugins, mu-plugins) + configuration
COPY wp-content/ /build/wordpress/wp-content/
COPY docker/wp-config.php /build/wordpress/wp-config.php

# --- Étape 2 : image finale ---
FROM ${BASE_IMAGE}
ARG WP_CLI_VERSION=2.12.0
RUN curl -fsSL "https://github.com/wp-cli/wp-cli/releases/download/v${WP_CLI_VERSION}/wp-cli-${WP_CLI_VERSION}.phar" \
        -o /usr/local/bin/wp \
    && chmod +x /usr/local/bin/wp \
    && a2enmod headers expires
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-hana.ini

# L'entrypoint officiel copie /usr/src/wordpress vers /var/www/html au démarrage
# d'un conteneur neuf : on remplace donc la source par notre version complète.
RUN rm -rf /usr/src/wordpress
COPY --from=fetch --chown=www-data:www-data /build/wordpress /usr/src/wordpress

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS -o /dev/null http://localhost/wp-includes/images/blank.gif || exit 1

ARG GIT_SHA=unknown
LABEL org.opencontainers.image.title="hana-calgary-wp" \
      org.opencontainers.image.revision="$GIT_SHA"
