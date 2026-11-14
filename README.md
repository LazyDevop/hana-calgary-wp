# HANA Calgary — site WordPress

Code et déploiement du site https://www.hanacalgary.ca (WordPress, bilingue EN/FR avec Polylang).

Le site est livré sous forme d'**image Docker** construite par **GitHub Actions** :

| Action | Résultat |
|---|---|
| Pull request | vérifications + construction de l'image (rien n'est publié) |
| Push sur `main` | image publiée + déploiement sur le **staging** `dev.hanacalgary.ca` |
| Tag `vX.Y.Z` | image publiée + sauvegarde de la base + déploiement en **production** |

Si le site ne répond pas après un déploiement, le script revient automatiquement à la version précédente.

## Contenu du dépôt

```
docker/Dockerfile           Image : WordPress + plugins/thème figés + code maison
docker/components.txt       Versions des plugins et du thème tiers (wordpress.org)
docker/wp-config.php        Configuration lue depuis les variables d'environnement (aucun secret)
wp-content/plugins/         Plugins maison (hana-listing-extras, hana-quiz-tracker…)
wp-content/mu-plugins/      Must-use plugins maison
deploy/docker-compose.yml   Services sur le VPS (WordPress + MySQL)
deploy/.env.example         Modèle du fichier .env du VPS
deploy/remote-deploy.sh     Script exécuté sur le VPS à chaque déploiement
.github/workflows/ci-cd.yml Pipeline
```

**Hors du dépôt** (volumes sur le VPS) : médias `wp-content/uploads`, base de données, fichier `.env`.

## Travailler au quotidien

- **Modifier le code maison** : branche → pull request → fusion dans `main` → vérifier sur dev.hanacalgary.ca.
- **Mettre en production** : `git tag v1.4.0 && git push origin v1.4.0`.
- **Mettre à jour un plugin ou WordPress** : changer la version dans `docker/components.txt`
  (ou `WP_VERSION` dans le Dockerfile), puis même circuit. Les mises à jour depuis l'admin
  WordPress sont volontairement désactivées : elles seraient effacées au déploiement suivant.
- **Revenir à une version précédente** : relancer le job de déploiement d'un ancien tag
  (Actions → run du tag → *Re-run jobs*), ou sur le VPS : mettre `IMAGE_TAG=v1.3.0` dans `.env`
  puis `docker compose up -d --renew-anon-volumes wordpress`.
- **Commandes WP-CLI sur le VPS** : `cd /opt/hana/production && docker compose exec -u www-data wordpress wp plugin list`

## Mise en place (une seule fois)

### 1. Sur le VPS

```bash
sudo mkdir -p /opt/hana/staging /opt/hana/production
# dans chaque dossier : copier deploy/docker-compose.yml et créer .env à partir de deploy/.env.example
sudo chmod 600 /opt/hana/*/.env
sudo chown -R 33:33 /opt/hana/*/data/uploads    # www-data doit pouvoir écrire les médias
```

Utilisateur de déploiement dédié, membre du groupe `docker`, avec une clé SSH réservée à GitHub :

```bash
sudo adduser --disabled-password deploy && sudo usermod -aG docker deploy
ssh-keygen -t ed25519 -f deploy_key -C "github-actions-hana" -N ""
# contenu de deploy_key.pub → /home/deploy/.ssh/authorized_keys
# contenu de deploy_key     → secret GitHub SSH_PRIVATE_KEY (puis supprimer le fichier local)
ssh-keyscan -t ed25519 <IP_DU_VPS>   # → secret GitHub SSH_KNOWN_HOSTS
```

Le proxy HTTPS existant redirige `dev.hanacalgary.ca` vers `127.0.0.1:8081` et
`www.hanacalgary.ca` vers le `HTTP_PORT` de production. Il doit transmettre l'en-tête
`X-Forwarded-Proto: https`.

### 2. Reprise des données actuelles

Avant le premier déploiement, dans chaque environnement :

1. arrêter l'ancien conteneur ;
2. copier les médias actuels dans `data/uploads` (ou faire pointer `UPLOADS_DIR` dessus) ;
3. importer la base : `docker compose up -d db` puis
   `gunzip -c sauvegarde.sql.gz | docker compose exec -T db mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME"` ;
4. lancer le premier déploiement depuis GitHub (Actions → CI/CD → *Run workflow*, ou un push).

### 3. Dans GitHub (Settings → Environments)

Créer les environnements `staging` et `production` (pour `production`, activer
*Required reviewers* : un déploiement en production attend alors une validation manuelle).

| Type | Nom | Exemple staging | Exemple production |
|---|---|---|---|
| Variable | `SSH_HOST` | `62.72.7.240` | `62.72.7.240` |
| Variable | `SSH_USER` | `deploy` | `deploy` |
| Variable | `DEPLOY_DIR` | `/opt/hana/staging` | `/opt/hana/production` |
| Variable | `SITE_URL` | `https://dev.hanacalgary.ca` | `https://www.hanacalgary.ca` |
| Secret | `SSH_PRIVATE_KEY` | clé privée de déploiement | idem |
| Secret | `SSH_KNOWN_HOSTS` | sortie de `ssh-keyscan` | idem |

Le jeton d'accès au registre d'images est fourni automatiquement par GitHub (`GITHUB_TOKEN`).

## Garde-fous du staging

Le staging tourne sur une copie de la base de production. Le must-use plugin
`hana-environment.php` :
- envoie `noindex` aux moteurs de recherche hors production ;
- bloque tous les e-mails si `HANA_MAIL_DISABLED=1` (à garder sur le staging pour ne pas
  écrire aux vrais visiteurs).

## Limite connue

Une partie du code du site vit **dans la base de données** et n'est donc pas versionnée :
les snippets de l'extension Code Snippets (mise en page des fiches logement, page blog,
accusé de réception des formulaires…) et les blocs « HTML personnalisé » des pages.
À migrer progressivement dans un plugin `hana-core` de ce dépôt.
