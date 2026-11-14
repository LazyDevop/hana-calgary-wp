<?php
/**
 * Plugin Name: HANA Nos Maisons — cartes annonces (style propriété)
 * Description: Cartes WPCasa sur la page Nos maisons, alignées sur la maquette fournie.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return bool
 */
function hana_nos_maisons_cards_active() {
	if (!function_exists('is_page')) {
		return false;
	}
	if (is_page('nos-maisons')) {
		return true;
	}
	// Secours si le slug diffère (ancienne install).
	if (is_page(54)) {
		return true;
	}
	return false;
}

/**
 * @param string[] $classes
 * @return string[]
 */
function hana_nos_maisons_body_class($classes) {
	if (hana_nos_maisons_cards_active()) {
		$classes[] = 'hana-property-cards';
	}
	return $classes;
}
add_filter('body_class', 'hana_nos_maisons_body_class');

/**
 * @return bool
 */
function hana_nos_maisons_is_french() {
	$loc = function_exists('get_locale') ? get_locale() : '';
	return strpos((string) $loc, 'fr') === 0;
}

/**
 * Affiche le formulaire WPCasa avant les annonces si la page ne contient pas déjà le shortcode recherche.
 *
 * @param string $content
 * @return string
 */
function hana_nos_maisons_prepend_listings_search($content) {
	if (!hana_nos_maisons_cards_active() || !is_singular('page') || !is_main_query()) {
		return $content;
	}
	if (strpos($content, 'wpsight-listings-search') !== false) {
		return $content;
	}
	if (!function_exists('do_shortcode')) {
		return $content;
	}
	return do_shortcode('[wpsight_listings_search advanced="false" reset="false"]') . $content;
}
add_filter('the_content', 'hana_nos_maisons_prepend_listings_search', 4);

/**
 * Ne garde que type, chambres, salles de bain + libellés maquette.
 *
 * @param array $fields
 * @param array $defaults
 * @return array
 */
function hana_nos_maisons_filter_search_fields($fields, $defaults) {
	if (!hana_nos_maisons_cards_active()) {
		return $fields;
	}
	$keep = array('listing-type', 'details_1', 'details_2');
	foreach ($fields as $key => $val) {
		if (!in_array($key, $keep, true)) {
			unset($fields[$key]);
		}
	}
	if (isset($fields['listing-type']['data']) && is_array($fields['listing-type']['data'])) {
		$fields['listing-type']['data']['show_option_none'] = hana_nos_maisons_is_french()
			? 'Tous les types'
			: 'All Types';
	}
	if (isset($fields['details_1']['label'])) {
		$fields['details_1']['label'] = hana_nos_maisons_is_french() ? 'Toutes' : 'Any';
	}
	if (isset($fields['details_2']['label'])) {
		$fields['details_2']['label'] = hana_nos_maisons_is_french() ? 'Toutes' : 'Any';
	}
	if (function_exists('wpsight_sort_array_by_priority')) {
		$fields = wpsight_sort_array_by_priority($fields);
	}
	return $fields;
}
add_filter('wpsight_get_search_fields', 'hana_nos_maisons_filter_search_fields', 50, 2);

/**
 * Forcer l’action du formulaire sur la page « Nos maisons » (sinon redirection vers la page annonces du réglage WPCasa).
 *
 * @param string $html
 * @return string
 */
function hana_nos_maisons_search_form_html($html) {
	if (!hana_nos_maisons_cards_active()) {
		return $html;
	}
	$permalink = get_permalink();
	if ($permalink) {
		$html = preg_replace(
			'/action="[^"]*"/',
			'action="' . esc_url($permalink) . '"',
			$html,
			1
		);
	}
	return $html;
}
add_filter('wpsight_get_search', 'hana_nos_maisons_search_form_html', 99, 1);

function hana_nos_maisons_cards_styles() {
	if (!hana_nos_maisons_cards_active()) {
		return;
	}
	?>
	<style id="hana-nos-maisons-listing-cards">
		.hana-property-cards .wpsight-listings-sc .wpsight-listings {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr));
			gap: 1.5rem;
			margin-top: 0.5rem;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive {
			background: #fff;
			border: 1px solid #f1d7c7;
			border-radius: 14px;
			box-shadow: 0 6px 18px rgba(232, 98, 26, 0.08);
			margin-bottom: 0 !important;
			overflow: hidden;
			display: flex;
			flex-direction: column;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive > div[itemprop="offers"] {
			display: flex;
			flex-direction: column;
			flex: 1;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-left {
			float: none !important;
			width: 100% !important;
			margin: 0 !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-right {
			overflow: visible !important;
			padding: 1.15rem 1.25rem 1.25rem;
			display: flex;
			flex-direction: column;
			flex: 1;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section-image {
			margin-bottom: 0 !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-image {
			position: relative;
			text-align: left;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-image > a {
			display: block;
			position: relative;
			overflow: hidden;
			border-radius: 14px 14px 0 0;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-thumbnail {
			display: block !important;
			width: 100% !important;
			margin: 0 !important;
			line-height: 0;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-thumbnail img {
			width: 100%;
			height: 200px;
			object-fit: cover;
			display: block;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-thumbnail .dashicons {
			width: 100% !important;
			height: 200px !important;
			line-height: 200px !important;
			background: #edf2f7 !important;
			border-radius: 0;
			font-size: 48px !important;
		}

		.hana-property-cards .hana-listing-type-badge {
			position: absolute;
			left: 12px;
			bottom: 12px;
			z-index: 2;
			background: #e8621a;
			color: #fff;
			font-size: 0.78rem;
			font-weight: 700;
			padding: 0.4rem 0.75rem;
			border-radius: 6px;
			line-height: 1.2;
			pointer-events: none;
		}

		.hana-property-cards .hana-listing-fav {
			position: absolute;
			top: 12px;
			right: 12px;
			z-index: 3;
			width: 40px;
			height: 40px;
			border-radius: 50%;
			border: none;
			background: #fff;
			box-shadow: 0 2px 8px rgba(15, 23, 42, 0.12);
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #4a5568;
			font-size: 1.1rem;
			line-height: 1;
			padding: 0;
		}

		.hana-property-cards .hana-listing-fav:hover {
			color: #e8621a;
		}

		.hana-property-cards .hana-listing-fav.is-active {
			color: #e8621a;
			border-color: #e2b394;
			background: #fff8f3;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section {
			margin-bottom: 0 !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section-title {
			order: 2;
			margin-top: 0.35rem;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section-info {
			order: 1;
			margin-bottom: 0 !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section-summary {
			display: none !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section-description {
			display: none !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-right {
			display: flex;
			flex-direction: column;
		}

		.hana-property-cards .hana-listing-location {
			order: 3;
			font-size: 0.92rem;
			color: #718096;
			margin: 0.35rem 0 0;
			display: flex;
			align-items: center;
			gap: 0.35rem;
		}

		.hana-property-cards .hana-listing-location::before {
			content: "";
			display: inline-block;
			width: 14px;
			height: 14px;
			background: no-repeat center / contain;
			opacity: 0.85;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23718096' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z'/%3E%3C/svg%3E");
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-section-compare {
			display: block !important;
			order: 4;
			margin-top: auto;
			padding-top: 0.85rem;
			border-top: 1px solid #f3dfd1;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare {
			display: flex !important;
			flex-wrap: wrap;
			justify-content: space-between;
			align-items: center;
			gap: 0.5rem 0.75rem;
			margin: 0 !important;
			font-size: 0.88rem;
			color: #718096;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-detail {
			display: none !important;
			margin: 0 !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-1,
		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-2,
		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-4 {
			display: inline-flex !important;
			align-items: center;
			gap: 0.35rem;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-1::before {
			content: "";
			width: 16px;
			height: 16px;
			background: no-repeat center / contain;
			opacity: 0.9;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23718096' viewBox='0 0 24 24'%3E%3Cpath d='M7 13c1.66 0 3-1.34 3-3S8.66 7 7 7s-3 1.34-3 3 1.34 3 3 3zm12-6h-8v7H3V5H1v15h2v-3h18v3h2v-9c0-2.21-1.79-4-4-4z'/%3E%3C/svg%3E");
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-2::before {
			content: "";
			width: 16px;
			height: 16px;
			background: no-repeat center / contain;
			opacity: 0.9;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23718096' viewBox='0 0 24 24'%3E%3Cpath d='M7 2v2h1v3H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V9c0-1.1-.9-2-2-2h-3V4h1V2H7zm0 9h10v9H7v-9z'/%3E%3C/svg%3E");
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-4::before {
			content: "";
			width: 16px;
			height: 16px;
			background: no-repeat center / contain;
			opacity: 0.9;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23718096' viewBox='0 0 24 24'%3E%3Cpath d='M3 5v14h18V5H3zm16 6h-4V7h4v4zm-6 6H9v-4h4v4zm0-6H9V7h4v4zm-6 6H5v-4h4v4zm0-6H5V7h4v4z'/%3E%3C/svg%3E");
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-label {
			display: none;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-1 .listing-details-value::after {
			content: " lits";
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-2 .listing-details-value::after {
			content: " sdb";
		}

		html[lang^="en"] .hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-1 .listing-details-value::after {
			content: " Beds";
		}

		html[lang^="en"] .hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-compare .listing-details-2 .listing-details-value::after {
			content: " Baths";
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-info {
			display: flex !important;
			justify-content: space-between;
			align-items: flex-start;
			gap: 0.75rem;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-info .alignleft,
		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-info .alignright {
			float: none !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-status {
			display: none;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-price {
			font-size: 0;
			line-height: 1.2;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .listing-price-symbol,
		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .listing-price-value {
			font-size: 1.55rem;
			font-weight: 700;
			color: #1a202c;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .listing-rental-period {
			font-size: 0.92rem;
			font-weight: 500;
			color: #718096;
			margin-left: 0.35rem;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-title h2 {
			font-size: 1.05rem !important;
			font-weight: 700 !important;
			line-height: 1.35 !important;
			margin: 0 !important;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-title h2 a {
			color: #1a202c;
			text-decoration: none;
		}

		.hana-property-cards .wpsight-listings-sc .listing.listing-archive .wpsight-listing-title h2 a:hover {
			color: #e8621a;
		}

		/* Barre de filtres — rendu doux (maquette) */
		.hana-property-cards .wpsight-listings-search-sc {
			margin-bottom: 0;
		}

		.hana-property-cards .wpsight-listings-search.horizontal {
			background: #fff !important;
			border: 1px solid #f2e1d6;
			border-radius: 16px;
			padding: 1.35rem 1.5rem 1.4rem;
			margin-bottom: 1.15rem;
			box-shadow: 0 4px 24px rgba(232, 98, 26, 0.05);
			position: relative;
			display: flex;
			flex-direction: column;
			align-items: stretch;
			gap: 0;
		}

		.hana-property-cards .wpsight-listings-search .listings-search-default {
			display: flex;
			flex-wrap: wrap;
			align-items: flex-end;
			gap: 1rem 1.35rem;
			margin: 0;
		}

		.hana-property-cards .wpsight-listings-search .listings-search-default::before {
			content: "Filters:";
			font-weight: 700;
			font-size: 1rem;
			color: #111827;
			align-self: flex-end;
			margin-right: 0.2rem;
			padding-bottom: 0.5rem;
			letter-spacing: -0.01em;
		}

		html[lang^="fr"] .hana-property-cards .wpsight-listings-search .listings-search-default::before {
			content: "Filtres :";
		}

		.hana-property-cards .wpsight-listings-search.horizontal .listings-search-field {
			width: auto !important;
			max-width: 100%;
			margin-bottom: 0 !important;
			padding-right: 0 !important;
			display: flex;
			flex-direction: column;
			gap: 0.45rem;
			min-width: 9rem;
		}

		.hana-property-cards .listings-search-field-listing-type::before {
			content: "Property Type:";
			font-size: 0.8125rem;
			font-weight: 600;
			color: #374151;
		}

		html[lang^="fr"] .hana-property-cards .listings-search-field-listing-type::before {
			content: "Type de propriété :";
		}

		.hana-property-cards .listings-search-field-details_1::before {
			content: "Beds:";
			font-size: 0.8125rem;
			font-weight: 600;
			color: #374151;
		}

		html[lang^="fr"] .hana-property-cards .listings-search-field-details_1::before {
			content: "Chambres :";
		}

		.hana-property-cards .listings-search-field-details_2::before {
			content: "Baths:";
			font-size: 0.8125rem;
			font-weight: 600;
			color: #374151;
		}

		html[lang^="fr"] .hana-property-cards .listings-search-field-details_2::before {
			content: "Salles de bain :";
		}

		.hana-property-cards .wpsight-listings-search .listings-search-field select {
			appearance: none;
			-webkit-appearance: none;
			background-color: #f0f2f5;
			border: 1px solid #f0dfd3;
			border-radius: 10px;
			padding: 0.5rem 2.15rem 0.5rem 0.85rem;
			font-size: 0.875rem;
			color: #1f2937;
			min-height: 2.45rem;
			line-height: 1.3;
			background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%236b7280' d='M1.41 0L6 4.58 10.59 0 12 1.41l-6 6-6-6z'/%3E%3C/svg%3E");
			background-repeat: no-repeat;
			background-position: right 0.65rem center;
			cursor: pointer;
			transition: border-color 0.15s ease, box-shadow 0.15s ease;
		}

		.hana-property-cards .wpsight-listings-search .listings-search-field select:hover {
			border-color: #e9c7b1;
		}

		.hana-property-cards .wpsight-listings-search .listings-search-field select:focus {
			outline: none;
			border-color: #e8621a;
			box-shadow: 0 0 0 3px rgba(232, 98, 26, 0.16);
		}

		.hana-property-cards .listings-panel {
			border-bottom: none;
			margin-bottom: 0.75rem;
			padding: 0.25rem 0 0;
		}

		.hana-property-cards .listings-panel-title {
			float: left;
			width: auto;
			position: static !important;
		}

		.hana-property-cards .listings-panel-actions {
			float: none !important;
			width: 100%;
			text-align: right;
		}

		@media (max-width: 599px) {
			.hana-property-cards .wpsight-listings-sc .wpsight-listings {
				grid-template-columns: 1fr;
			}

			.hana-property-cards .wpsight-listings-search.horizontal {
				padding: 1.15rem 1.15rem 1.2rem;
				border-radius: 14px;
			}

			.hana-property-cards .wpsight-listings-search .listings-search-default {
				flex-direction: column;
				align-items: stretch;
				gap: 0.85rem;
			}

			.hana-property-cards .wpsight-listings-search .listings-search-default::before {
				align-self: flex-start;
				padding-bottom: 0;
				margin-bottom: -0.25rem;
			}

			.hana-property-cards .wpsight-listings-search.horizontal .listings-search-field {
				min-width: 0;
				width: 100% !important;
			}

			.hana-property-cards .listings-panel-title,
			.hana-property-cards .listings-panel-actions {
				float: none;
				width: 100%;
				text-align: left;
			}
		}
	</style>
	<?php
}
add_action('wp_head', 'hana_nos_maisons_cards_styles', 998);

function hana_nos_maisons_cards_script() {
	if (!hana_nos_maisons_cards_active()) {
		return;
	}
	?>
	<script id="hana-nos-maisons-listing-cards-js">
		document.addEventListener('DOMContentLoaded', function() {
			var isFr = document.documentElement.lang && document.documentElement.lang.toLowerCase().indexOf('fr') === 0;

			(function listingsFilterBar() {
				var form = document.querySelector('.hana-property-cards form.wpsight-listings-search');
				if (!form) {
					return;
				}
				form.querySelectorAll('select').forEach(function(sel) {
					sel.addEventListener('change', function() {
						if (typeof form.requestSubmit === 'function') {
							form.requestSubmit();
						} else {
							form.submit();
						}
					});
				});
			})();

			var favoritesStoreKey = 'hana_nos_maisons_favorites';
			function getFavoritesMap() {
				try {
					var raw = window.localStorage.getItem(favoritesStoreKey);
					var parsed = raw ? JSON.parse(raw) : {};
					return parsed && typeof parsed === 'object' ? parsed : {};
				} catch (e) {
					return {};
				}
			}
			function saveFavoritesMap(map) {
				try {
					window.localStorage.setItem(favoritesStoreKey, JSON.stringify(map));
				} catch (e) {
					// Ignore storage errors silently.
				}
			}
			function listingKey(card) {
				if (!card) {
					return '';
				}
				var fromId = card.getAttribute('id') || '';
				if (fromId) {
					return fromId;
				}
				var link = card.querySelector('.wpsight-listing-image > a');
				return link ? (link.getAttribute('href') || '') : '';
			}
			function setFavVisual(button, active) {
				if (!button) {
					return;
				}
				button.classList.toggle('is-active', !!active);
				button.setAttribute('aria-pressed', active ? 'true' : 'false');
				button.innerHTML = active ? '&#9829;' : '&#9825;';
			}

			var typeLabels = {
				apartment: isFr ? 'Appartement' : 'Apartment',
				house: isFr ? 'Maison' : 'House',
				condo: isFr ? 'Condo' : 'Condo',
				townhouse: isFr ? 'Maison de ville' : 'Townhouse',
				land: isFr ? 'Terrain' : 'Land',
				commercial: isFr ? 'Commercial' : 'Commercial'
			};

			function titleCaseWords(s) {
				return s.split(/[\s-]+/).map(function(w) {
					return w.charAt(0).toUpperCase() + w.slice(1).toLowerCase();
				}).join(' ');
			}

			function locationFromCard(card) {
				var cls = card.className.split(/\s+/);
				var loc = null;
				cls.forEach(function(c) {
					if (c.indexOf('location-') === 0) {
						loc = c.replace('location-', '').replace(/-/g, ' ');
					}
				});
				return loc ? titleCaseWords(loc) : '';
			}

			function categoryLabel(card) {
				var cls = card.className.split(/\s+/);
				var slug = '';
				cls.forEach(function(c) {
					if (c.indexOf('listing-category-') === 0) {
						slug = c.replace('listing-category-', '');
					}
				});
				if (slug && typeLabels[slug]) {
					return typeLabels[slug];
				}
				if (slug) {
					return titleCaseWords(slug.replace(/-/g, ' '));
				}
				return isFr ? 'Annonce' : 'Listing';
			}

			document.querySelectorAll('.hana-property-cards .listing.listing-archive').forEach(function(card) {
				var imgWrap = card.querySelector('.wpsight-listing-image');
				var link = card.querySelector('.wpsight-listing-image > a');
				if (link && !link.querySelector('.hana-listing-type-badge')) {
					var badge = document.createElement('span');
					badge.className = 'hana-listing-type-badge';
					badge.textContent = categoryLabel(card);
					link.appendChild(badge);
				}

				if (imgWrap && !imgWrap.querySelector('.hana-listing-fav')) {
					var fav = document.createElement('button');
					fav.type = 'button';
					fav.className = 'hana-listing-fav';
					fav.setAttribute('aria-label', isFr ? 'Ajouter aux favoris' : 'Add to favorites');
					fav.setAttribute('aria-pressed', 'false');
					fav.innerHTML = '&#9825;';
					var key = listingKey(card);
					var favoritesMap = getFavoritesMap();
					setFavVisual(fav, !!favoritesMap[key]);
					fav.addEventListener('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
						var map = getFavoritesMap();
						var nowActive = !map[key];
						if (nowActive) {
							map[key] = true;
						} else {
							delete map[key];
						}
						saveFavoritesMap(map);
						setFavVisual(fav, nowActive);
					});
					imgWrap.appendChild(fav);
				}

				var right = card.querySelector('.wpsight-listing-right');
				var titleSection = card.querySelector('.wpsight-listing-section-title');
				if (right && titleSection && !right.querySelector('.hana-listing-location')) {
					var locText = locationFromCard(card);
					if (locText) {
						var loc = document.createElement('p');
						loc.className = 'hana-listing-location';
						loc.textContent = locText;
						titleSection.insertAdjacentElement('afterend', loc);
					}
				}
			});
		});
	</script>
	<?php
}
add_action('wp_footer', 'hana_nos_maisons_cards_script', 998);
