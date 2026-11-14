<?php
/**
 * Plugin Name: HANA News-2 Redesign
 * Description: Restyles the /news-2/ archive page to match the requested visual mockup.
 */

if (!defined('ABSPATH')) {
	exit;
}

function hana_news2_is_target_request() {
	$request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
	return strpos($request_uri, '/news-2') !== false || strpos($request_uri, '/category/') !== false;
}

function hana_news2_print_styles() {
	if (!hana_news2_is_target_request()) {
		return;
	}
	?>
	<style id="hana-news2-redesign">
		body.blog .entry-hero.post-archive-hero-section {
			background-image: url("<?php echo esc_url(home_url('/wp-content/uploads/2026/04/news-banner-bg.png')); ?>") !important;
			background-size: cover !important;
			background-position: center !important;
			background-repeat: no-repeat !important;
			background-color: transparent !important;
			background-blend-mode: normal !important;
			padding: 92px 0 96px;
			margin-bottom: 26px;
			position: relative;
		}

		body.blog .entry-hero .hero-section-overlay,
		body.blog #kadence-breadcrumbs {
			display: none !important;
			opacity: 0 !important;
			visibility: hidden !important;
			background: transparent !important;
			background-image: none !important;
		}

		body.blog .entry-hero.post-archive-hero-section::before,
		body.blog .entry-hero.post-archive-hero-section::after,
		body.blog .entry-hero .entry-hero-container-inner::before,
		body.blog .entry-hero .entry-hero-container-inner::after,
		body.blog .entry-hero .hero-container::before,
		body.blog .entry-hero .hero-container::after {
			content: none !important;
			display: none !important;
			background: transparent !important;
			background-image: none !important;
		}

		body.blog .entry-hero,
		body.blog .entry-hero .entry-hero-container-inner,
		body.blog .entry-hero .hero-container {
			background: transparent !important;
			background-image: none !important;
		}

		body.blog .entry-hero .entry-header {
			max-width: 980px;
			margin: 0 auto;
			text-align: center;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}

		body.blog .entry-hero .archive-title {
			font-size: clamp(1.55rem, 2.8vw, 2.25rem);
			font-weight: 700;
			color: #fff;
			margin: 0;
		}

		body.blog .hana-news2-subtitle {
			max-width: 760px;
			margin: 14px auto 0;
			color: rgba(255, 255, 255, 0.92);
			font-size: .98rem;
			line-height: 1.5;
		}

		body.blog .hana-news2-cta {
			display: inline-block;
			margin-top: 20px;
			padding: 11px 24px;
			border-radius: 6px;
			background: #f07f1a;
			color: #fff;
			font-weight: 700;
			text-decoration: none;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
		}

		body.blog #blog-filtre,
		body.blog #kb-filtre-wrapper {
			display: none !important;
		}

		body.blog .hana-news2-tabs {
			display: flex;
			align-items: stretch;
			flex-wrap: nowrap;
			gap: 0;
			padding: 0;
			border: 1px solid #d5d5d5;
			border-radius: 3px;
			background: #f3f3f3;
			max-width: 1160px;
			margin: -2px auto 30px;
			overflow: hidden;
		}

		body.blog .hana-news2-tab {
			flex: 1 1 0;
			min-width: 140px;
			padding: 14px 12px;
			text-align: center;
			background: transparent;
			border: 0;
			border-right: 1px solid #d5d5d5;
			border-radius: 0;
			color: #6f6f6f;
			font-size: .94rem;
			font-weight: 700;
			text-decoration: none;
			line-height: 1.1;
			transition: background-color .2s ease, color .2s ease;
			white-space: nowrap;
			cursor: pointer;
		}

		body.blog .hana-news2-tab:last-child {
			border-right: 0;
		}

		body.blog .hana-news2-tab:hover,
		body.blog .hana-news2-tab.is-active {
			background: #f07f1a;
			color: #fff;
		}

		body.blog #archive-container {
			gap: 24px;
		}

		body.blog #archive-container .entry-list-item {
			background: #f3f3f3;
			border: 1px solid #d3d3d3;
			box-shadow: none;
			overflow: hidden;
		}

		body.blog #archive-container article {
			position: relative;
			height: 100%;
			display: flex;
			flex-direction: column;
		}

		body.blog #archive-container article::before {
			content: "";
			display: block;
			width: 100%;
			height: 236px;
			background-image: linear-gradient(rgba(195, 195, 195, 0.45), rgba(195, 195, 195, 0.45)), url("<?php echo esc_url(home_url('/wp-content/uploads/2026/04/news-banner-bg.png')); ?>");
			background-size: cover;
			background-position: center;
			border-bottom: 1px solid #d4d4d4;
		}

		body.blog #archive-container .entry-content-wrap {
			padding: 18px 16px 16px;
			display: flex;
			flex-direction: column;
			flex: 1;
		}

		body.blog #archive-container .entry-taxonomies {
			position: absolute;
			top: 18px;
			left: 14px;
			z-index: 2;
			margin: 0;
		}

		body.blog #archive-container .entry-taxonomies .category-links a {
			display: inline-block;
			background: #f07f1a;
			color: #fff;
			padding: 7px 14px;
			font-size: .82rem;
			font-weight: 700;
			text-transform: none;
			letter-spacing: 0;
			border-radius: 4px;
		}

		body.blog #archive-container .entry-title {
			font-size: clamp(1.2rem, 1.55vw, 1.85rem);
			line-height: 1.14;
			margin-top: 8px;
			margin-bottom: 10px;
			font-weight: 700;
		}

		body.blog #archive-container .entry-title a {
			color: #2f2f2f;
		}

		body.blog #archive-container .entry-meta {
			color: #5e5e5e;
			font-size: .95rem;
			margin-bottom: 16px;
			padding-bottom: 12px;
			border-bottom: 1px solid #dddddd;
		}

		body.blog #archive-container .entry-meta .posted-by {
			display: none;
		}

		body.blog #archive-container .entry-meta-divider-dot > span:not(:last-child):after {
			display: none;
		}

		body.blog #archive-container .entry-summary {
			color: #606060;
			font-size: .94rem;
			line-height: 1.6;
			min-height: 86px;
			flex: 1;
		}

		body.blog #archive-container .entry-footer .post-more-link {
			display: inline-block;
			background: #f07f1a;
			color: #fff;
			padding: 10px 24px;
			border-radius: 6px;
			font-weight: 700;
			font-size: .92rem;
			text-decoration: none;
		}

		body.blog #archive-container .entry-footer .post-more-link:hover {
			background: #d96806;
			color: #fff;
		}

		body.blog #archive-container .entry-footer .post-more-link .kadence-svg-iconset {
			display: none;
		}

		body.blog #archive-container .entry-footer .more-link-wrap {
			margin-top: 14px;
			text-align: right;
		}

		body.blog .hana-news2-bottom-links {
			margin: 42px auto 10px;
			max-width: 1160px;
			display: grid;
			grid-template-columns: repeat(3, minmax(0, 1fr));
			gap: 16px;
		}

		body.blog .hana-news2-bottom-card {
			border: 1px solid #d8d8d8;
			background: #fff;
			padding: 16px;
			display: block;
			text-decoration: none;
		}

		body.blog .hana-news2-bottom-card strong {
			display: flex;
			justify-content: space-between;
			align-items: center;
			color: #f07f1a;
			font-size: 1.05rem;
		}

		body.blog .hana-news2-bottom-card span {
			display: inline-block;
			margin-top: 8px;
			color: #666;
			font-size: .95rem;
		}

		@media (max-width: 1280px) {
			body.blog .entry-hero .entry-header {
				padding: 0 18px;
			}

			body.blog .hana-news2-tabs,
			body.blog #blog-filtre,
			body.blog #kb-filtre-wrapper,
			body.blog .hana-news2-bottom-links {
				margin-left: 16px;
				margin-right: 16px;
			}
		}

		@media (max-width: 1024px) {
			body.blog .entry-hero.post-archive-hero-section {
				padding: 70px 0 72px;
			}

			body.blog .entry-hero .archive-title {
				font-size: clamp(1.65rem, 4.8vw, 2.3rem);
			}

			body.blog .hana-news2-subtitle {
				font-size: 1rem;
				line-height: 1.5;
			}

			body.blog .hana-news2-tabs {
				overflow-x: auto;
				-webkit-overflow-scrolling: touch;
			}

			body.blog .hana-news2-tab {
				flex: 0 0 auto;
				font-size: .95rem;
				padding: 12px 16px;
			}

			body.blog #archive-container {
				grid-template-columns: repeat(2, minmax(0, 1fr));
				gap: 18px;
			}

			body.blog #archive-container article::before {
				height: 205px;
			}

			body.blog .hana-news2-bottom-links {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 1024px) {
			body.blog .entry-meta {
				font-size: .95rem;
			}
		}

		@media (max-width: 767px) {
			body.blog .entry-hero.post-archive-hero-section {
				padding: 54px 0 56px;
			}

			body.blog .entry-hero .archive-title {
				font-size: clamp(1.35rem, 7vw, 1.9rem);
			}

			body.blog .hana-news2-subtitle {
				max-width: 96%;
				font-size: .95rem;
			}

			body.blog .hana-news2-cta {
				padding: 10px 16px;
				font-size: .92rem;
			}

			body.blog #archive-container {
				grid-template-columns: 1fr;
				gap: 16px;
			}

			body.blog #archive-container article::before {
				height: 190px;
			}

			body.blog #archive-container .entry-content-wrap {
				padding: 14px 14px 14px;
			}

			body.blog #archive-container .entry-meta {
				font-size: .95rem;
				margin-bottom: 12px;
				padding-bottom: 10px;
			}

			body.blog #archive-container .entry-summary {
				font-size: .95rem;
				min-height: 0;
			}

			body.blog #archive-container .entry-footer .post-more-link {
				padding: 10px 24px;
				font-size: .95rem;
			}
		}
	</style>
	<?php
}
add_action('wp_head', 'hana_news2_print_styles', 999);

function hana_news2_print_script() {
	if (!hana_news2_is_target_request()) {
		return;
	}
	?>
	<script id="hana-news2-redesign-script">
		document.addEventListener('DOMContentLoaded', function() {
			function slugify(value) {
				return (value || '')
					.toString()
					.toLowerCase()
					.normalize('NFD')
					.replace(/[\u0300-\u036f]/g, '')
					.replace(/[^a-z0-9]+/g, '-')
					.replace(/(^-|-$)/g, '');
			}

			var title = document.querySelector('.entry-hero .archive-title');
			var header = document.querySelector('.entry-hero .entry-header');
			var heroSection = document.querySelector('.entry-hero.post-archive-hero-section');
			if (title) {
				title.textContent = 'Actualites & Evenements';
			}

			if (header && !document.querySelector('.hana-news2-subtitle')) {
				var subtitle = document.createElement('p');
				subtitle.className = 'hana-news2-subtitle';
				subtitle.textContent = 'Decouvrez nos actions, participations et nouvelles pour le logement des nouveaux arrivants en Alberta.';
				header.appendChild(subtitle);

				var cta = document.createElement('a');
				cta.className = 'hana-news2-cta';
				cta.href = '#archive-container';
				cta.textContent = 'Voir les prochains evenements';
				header.appendChild(cta);
			}

			if (heroSection && !document.querySelector('.hana-news2-tabs')) {
				var cards = Array.prototype.slice.call(document.querySelectorAll('#archive-container .entry-list-item'));
				var tabsData = [{ label: 'Tous', slug: 'all' }];
				var seenSlugs = { all: true };

				cards.forEach(function(card) {
					var categoryLink = card.querySelector('.entry-taxonomies .category-links a');
					if (!categoryLink) {
						return;
					}
					var label = (categoryLink.textContent || '').trim();
					var slug = slugify(label);
					if (!slug || seenSlugs[slug]) {
						return;
					}
					seenSlugs[slug] = true;
					tabsData.push({
						label: label,
						slug: slug
					});
				});

				var tabs = document.createElement('nav');
				tabs.className = 'hana-news2-tabs';
				tabs.setAttribute('aria-label', 'Filtre du blogue');

				tabsData.forEach(function(tabItem, index) {
					var tab = document.createElement('a');
					tab.className = 'hana-news2-tab';
					tab.href = '#';
					tab.textContent = tabItem.label || 'Categorie';
					tab.setAttribute('data-filter', tabItem.slug);
					if (index === 0) {
						tab.classList.add('is-active');
					}
					tabs.appendChild(tab);
				});

				heroSection.insertAdjacentElement('afterend', tabs);

				function applyFilter(filterSlug) {
					cards.forEach(function(card) {
						var categoryLink = card.querySelector('.entry-taxonomies .category-links a');
						var cardSlug = categoryLink ? slugify(categoryLink.textContent || '') : '';
						var showCard = filterSlug === 'all' || cardSlug === filterSlug;
						card.style.display = showCard ? '' : 'none';
					});
				}

				tabs.addEventListener('click', function(event) {
					var clickedTab = event.target.closest('.hana-news2-tab');
					if (!clickedTab) {
						return;
					}
					event.preventDefault();
					var filterSlug = clickedTab.getAttribute('data-filter') || 'all';
					Array.prototype.forEach.call(tabs.querySelectorAll('.hana-news2-tab'), function(tabEl) {
						tabEl.classList.remove('is-active');
					});
					clickedTab.classList.add('is-active');
					applyFilter(filterSlug);
				});
			}

			document.querySelectorAll('#archive-container article').forEach(function(article) {
				var readMore = article.querySelector('.post-more-link');
				if (readMore) {
					readMore.childNodes[0].textContent = 'Lire plus';
				}
			});

			var main = document.querySelector('#main.site-main');
			if (main && !document.querySelector('.hana-news2-bottom-links')) {
				var wrapper = document.createElement('div');
				wrapper.className = 'hana-news2-bottom-links';
				wrapper.innerHTML = '' +
					'<a class="hana-news2-bottom-card" href="#"><strong>Evenements a venir <em>></em></strong><span>Voir le calendrier ></span></a>' +
					'<a class="hana-news2-bottom-card" href="#"><strong>HANA dans la communaute <em>></em></strong><span>Nos participations recentes ></span></a>' +
					'<a class="hana-news2-bottom-card" href="#"><strong>Nos Realisations 2024 <em>></em></strong><span>Voir le rapport annuel ></span></a>';

				var pagination = main.querySelector('.pagination');
				if (pagination && pagination.parentNode) {
					pagination.parentNode.insertBefore(wrapper, pagination.nextSibling);
				} else {
					main.appendChild(wrapper);
				}
			}
		});
	</script>
	<?php
}
add_action('wp_footer', 'hana_news2_print_script', 999);
