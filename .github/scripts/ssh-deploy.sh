#!/usr/bin/env bash
# Lancé par GitHub Actions : se connecte au VPS et exécute deploy/remote-deploy.sh.
# Variables attendues : SSH_PRIVATE_KEY, SSH_KNOWN_HOSTS, TARGET, DEPLOY_DIR, SITE_URL, TAG, GHCR_TOKEN
# Argument : yes|no (sauvegarde de la base avant déploiement)
set -Eeuo pipefail

BACKUP="${1:-no}"
for v in SSH_PRIVATE_KEY SSH_KNOWN_HOSTS TARGET DEPLOY_DIR SITE_URL TAG GHCR_TOKEN; do
	[ -n "${!v:-}" ] || { echo "::error::Variable ou secret manquant : $v"; exit 1; }
done

install -m 700 -d ~/.ssh
printf '%s\n' "$SSH_PRIVATE_KEY" > ~/.ssh/deploy_key
chmod 600 ~/.ssh/deploy_key
printf '%s\n' "$SSH_KNOWN_HOSTS" > ~/.ssh/known_hosts
SSH=(ssh -i ~/.ssh/deploy_key -o BatchMode=yes -o StrictHostKeyChecking=yes "$TARGET")

# Connexion temporaire du VPS au registre d'images (jeton valable pendant ce job uniquement)
printf '%s' "$GHCR_TOKEN" | "${SSH[@]}" "docker login ghcr.io -u '$GITHUB_ACTOR' --password-stdin" > /dev/null

status=0
"${SSH[@]}" "bash -s -- $(printf '%q ' "$DEPLOY_DIR" "$TAG" "$SITE_URL" "$BACKUP")" < deploy/remote-deploy.sh || status=$?

"${SSH[@]}" "docker logout ghcr.io" > /dev/null || true
rm -f ~/.ssh/deploy_key
exit "$status"
