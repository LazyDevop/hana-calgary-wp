#!/bin/sh
# Télécharge WordPress, les plugins/thèmes listés dans components.txt
# et leurs traductions, dans le dossier $1 (ex. /build/wordpress).
# Utilisé uniquement pendant la construction de l'image Docker.
set -eu

DEST="$1"
WP_VERSION="$2"
LOCALES="$3"          # ex. "fr_FR en_GB"
LIST="$(dirname "$0")/components.txt"

fetch_zip() { # url dossier_cible
	curl -fsSL --retry 5 --retry-all-errors --retry-delay 3 "$1" -o /tmp/pkg.zip
	unzip -q -o /tmp/pkg.zip -d "$2"
	rm -f /tmp/pkg.zip
}

echo "== WordPress $WP_VERSION"
fetch_zip "https://downloads.wordpress.org/release/wordpress-${WP_VERSION}.zip" /tmp/core
rm -rf "$DEST" && mkdir -p "$(dirname "$DEST")" && mv /tmp/core/wordpress "$DEST"
rm -rf "$DEST/wp-content/plugins/akismet" "$DEST/wp-content/plugins/hello.php"
mkdir -p "$DEST/wp-content/languages/plugins" "$DEST/wp-content/languages/themes"

# Traductions : l'API wordpress.org renvoie un paquet par langue disponible.
fetch_translations() { # type(core|plugins|themes) slug version dossier
	api="https://api.wordpress.org/translations/$1/1.0/?version=$3"
	[ "$1" != core ] && api="$api&slug=$2"
	json="$(curl -fsSL --retry 5 --retry-all-errors --retry-delay 3 "$api" || echo '{}')"
	for loc in $LOCALES; do
		pkg="$(printf '%s' "$json" | jq -r --arg l "$loc" '.translations[]? | select(.language==$l) | .package' | head -n1)"
		if [ -n "$pkg" ]; then
			fetch_zip "$pkg" "$4"
		else
			echo "   (pas de traduction $loc pour $2 $3)"
		fi
	done
}
fetch_translations core wordpress "$WP_VERSION" "$DEST/wp-content/languages"

grep -vE '^[[:space:]]*(#|$)' "$LIST" | while read -r type slug version _rest; do
	echo "== $type $slug $version"
	fetch_zip "https://downloads.wordpress.org/${type}/${slug}.${version}.zip" "$DEST/wp-content/${type}s"
	fetch_translations "${type}s" "$slug" "$version" "$DEST/wp-content/languages/${type}s"
done

# Les fichiers .po ne servent qu'aux traducteurs : WordPress lit .mo / .l10n.php.
find "$DEST/wp-content/languages" -name '*.po' -delete
