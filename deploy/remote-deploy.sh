#!/usr/bin/env bash
# Exécuté SUR LE VPS par GitHub Actions (via ssh ... bash -s < deploy/remote-deploy.sh).
# Usage : remote-deploy.sh <dossier> <tag_image> <url_du_site> <sauvegarde:yes|no>
#
# 1. sauvegarde la base (production) ;
# 2. passe le conteneur WordPress sur la nouvelle image ;
# 3. vérifie que le site répond ; sinon revient automatiquement à l'image précédente ;
# 4. met à jour la base WordPress si besoin et vide le cache.
set -Eeuo pipefail

DEPLOY_DIR="$1"
NEW_TAG="$2"
SITE_URL="${3%/}"
BACKUP="${4:-no}"

log() { echo "[deploy $(date +%H:%M:%S)] $*"; }
wp_cli() { docker compose exec -T -u www-data wordpress wp "$@"; }
set_tag() {
	if grep -q '^IMAGE_TAG=' .env; then
		sed -i "s|^IMAGE_TAG=.*|IMAGE_TAG=$1|" .env
	else
		echo "IMAGE_TAG=$1" >> .env
	fi
}
start_wordpress() {
	# --renew-anon-volumes : sans cette option, Docker réutiliserait l'ancien code
	# (l'image officielle déclare /var/www/html comme volume anonyme).
	docker compose up -d --renew-anon-volumes --remove-orphans wordpress
}
site_is_up() {
	for _ in $(seq 1 24); do
		if curl -fsS -o /dev/null --max-time 10 "$SITE_URL/wp-login.php"; then
			return 0
		fi
		sleep 5
	done
	return 1
}

cd "$DEPLOY_DIR"
[ -f .env ] || { log "ERREUR : $DEPLOY_DIR/.env introuvable"; exit 1; }
PREV_TAG="$(sed -n 's/^IMAGE_TAG=//p' .env | head -n1)"
log "Déploiement de $NEW_TAG (version actuelle : ${PREV_TAG:-aucune})"

if [ "$BACKUP" = "yes" ] && [ -n "$(docker compose ps --status running -q wordpress)" ]; then
	mkdir -p backups
	file="backups/db-$(date +%Y%m%d-%H%M%S)-${PREV_TAG:-none}.sql.gz"
	log "Sauvegarde de la base → $file"
	wp_cli db export - | gzip > "$file"
	# garde les 14 dernières sauvegardes
	ls -1t backups/db-*.sql.gz | tail -n +15 | xargs -r rm --
fi

set_tag "$NEW_TAG"
docker compose pull wordpress
docker compose up -d db
start_wordpress

if ! site_is_up; then
	log "ERREUR : $SITE_URL ne répond pas avec $NEW_TAG"
	docker compose logs --tail=50 wordpress || true
	if [ -n "$PREV_TAG" ] && [ "$PREV_TAG" != "$NEW_TAG" ]; then
		log "Retour arrière vers $PREV_TAG"
		set_tag "$PREV_TAG"
		start_wordpress
	fi
	exit 1
fi

wp_cli core update-db || log "avertissement : wp core update-db a échoué"
wp_cli cache flush || true
docker image prune -f > /dev/null
log "OK : $SITE_URL tourne sur $NEW_TAG"
