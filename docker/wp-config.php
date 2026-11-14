<?php
/**
 * Configuration WordPress HANA Calgary — identique pour staging et production.
 * Toutes les valeurs propres à un environnement (et tous les secrets) viennent
 * des variables d'environnement du conteneur (fichier .env sur le VPS).
 * Ce fichier ne doit JAMAIS contenir de mot de passe ni de clé.
 */

function hana_env( $name, $default = null ) {
	$value = getenv( $name );
	return ( false === $value || '' === $value ) ? $default : $value;
}

// Base de données
define( 'DB_NAME', hana_env( 'WORDPRESS_DB_NAME', 'hana' ) );
define( 'DB_USER', hana_env( 'WORDPRESS_DB_USER', 'hana' ) );
define( 'DB_PASSWORD', hana_env( 'WORDPRESS_DB_PASSWORD', '' ) );
define( 'DB_HOST', hana_env( 'WORDPRESS_DB_HOST', 'db:3306' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );
$table_prefix = hana_env( 'WORDPRESS_TABLE_PREFIX', 'wp_' );

// Clés de sécurité (à générer une fois par environnement : https://api.wordpress.org/secret-key/1.1/salt/)
foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT' ) as $hana_key ) {
	define( $hana_key, hana_env( 'WORDPRESS_' . $hana_key, '' ) );
}
unset( $hana_key );

// URL et environnement
define( 'WP_HOME', hana_env( 'WP_HOME', 'http://localhost:8080' ) );
define( 'WP_SITEURL', hana_env( 'WP_SITEURL', WP_HOME ) );
define( 'WP_ENVIRONMENT_TYPE', hana_env( 'WP_ENVIRONMENT_TYPE', 'production' ) );

// Le site est derrière un proxy HTTPS (repris de la configuration d'origine)
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
	$_SERVER['HTTPS'] = 'on';
}

// Le code est livré par l'image Docker : pas d'installation ni de mise à jour depuis l'admin
// (elles seraient perdues au déploiement suivant). Les mises à jour passent par docker/components.txt.
define( 'DISALLOW_FILE_MODS', true );
define( 'DISALLOW_FILE_EDIT', true );
define( 'WP_AUTO_UPDATE_CORE', false );

// Envoi des e-mails : mot de passe SMTP Titan lu dans l'environnement (WP Mail SMTP)
if ( hana_env( 'WPMS_SMTP_PASS' ) ) {
	define( 'WPMS_ON', true );
	define( 'WPMS_SMTP_PASS', hana_env( 'WPMS_SMTP_PASS' ) );
}

// Débogage
define( 'WP_DEBUG', filter_var( hana_env( 'WP_DEBUG', false ), FILTER_VALIDATE_BOOLEAN ) );
define( 'WP_DEBUG_LOG', WP_DEBUG ? '/proc/self/fd/2' : false ); // vers les logs Docker
define( 'WP_DEBUG_DISPLAY', false );

define( 'WP_MEMORY_LIMIT', '256M' );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
