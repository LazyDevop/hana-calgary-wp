<?php
/**
 * Plugin Name: HANA — Garde-fous d'environnement
 * Description: Hors production : demande aux moteurs de recherche de ne pas indexer le site
 *              et, si HANA_MAIL_DISABLED=1, bloque l'envoi des e-mails (évite d'écrire aux vrais
 *              visiteurs depuis le staging, qui contient une copie de la base de production).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'production' !== wp_get_environment_type() ) {
	add_action( 'send_headers', function () {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	} );
	add_filter( 'wp_robots', function ( $robots ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		return $robots;
	} );
}

if ( filter_var( getenv( 'HANA_MAIL_DISABLED' ), FILTER_VALIDATE_BOOLEAN ) ) {
	add_filter( 'pre_wp_mail', function ( $return, $atts ) {
		error_log( '[hana] e-mail non envoyé (HANA_MAIL_DISABLED) : ' . implode( ', ', (array) $atts['to'] ) . ' — ' . $atts['subject'] );
		return true; // simule un envoi réussi
	}, 10, 2 );
}
