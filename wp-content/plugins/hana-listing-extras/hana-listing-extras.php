<?php
/**
 * Plugin Name: HANA Listing Extras
 * Description: Partage social, formulaire de contact et reviews pour WPCasa
 * Version: 1.0
 * Author: HANA
 * Text Domain: hana-listing-extras
 */

if (!defined('ABSPATH')) exit;

class HANA_Listing_Extras {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Hooks WPCasa - essayer plusieurs pour compatibilité
        add_action('wpsight_listing_single_after', array($this, 'render_all_sections'), 15);
        add_action('wpsight_listing_single_end', array($this, 'render_all_sections'), 15);
        
        // Fallback: ajouter à la fin du contenu si les hooks WPCasa ne fonctionnent pas
        add_filter('the_content', array($this, 'maybe_append_to_content'), 999);
        
        // AJAX handlers
        add_action('wp_ajax_hana_contact_agent', array($this, 'handle_contact_form'));
        add_action('wp_ajax_nopriv_hana_contact_agent', array($this, 'handle_contact_form'));
        add_action('wp_ajax_hana_submit_review', array($this, 'handle_review_submission'));
        add_action('wp_ajax_nopriv_hana_submit_review', array($this, 'handle_review_submission'));
        
        add_shortcode('hana_listing_reviews', array($this, 'reviews_shortcode'));
        add_shortcode('hana_listing_share', array($this, 'share_shortcode'));
        add_shortcode('hana_listing_contact', array($this, 'contact_shortcode'));
    }

    public function enqueue_assets() {
        if (!is_singular('listing')) return;
        
        wp_enqueue_style('hana-listing-css', plugins_url('assets/hana-listing.css', __FILE__), array(), '1.0');
        wp_enqueue_script('hana-listing-js', plugins_url('assets/hana-listing.js', __FILE__), array('jquery'), '1.0', true);
        
        wp_localize_script('hana-listing-js', 'hanaAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hana_listing_nonce'),
            'strings' => array(
                'copied' => 'Lien copie !',
                'error' => 'Une erreur est survenue.',
                'success' => 'Message envoye avec succes !',
                'reviewSuccess' => 'Review soumise avec succes !'
            )
        ));
    }

    // Fallback: ajouter au contenu si on est sur un listing
    public function maybe_append_to_content($content) {
        if (!is_singular('listing') || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        // Eviter les doublons si le hook WPCasa a deja fonctionne
        if (did_action('wpsight_listing_single_after') || did_action('wpsight_listing_single_end')) {
            return $content;
        }
        
        ob_start();
        $this->render_all_sections();
        $extra = ob_get_clean();
        
        return $content . $extra;
    }

    public function render_all_sections() {
        // Eviter les doublons
        static $rendered = false;
        if ($rendered) return;
        $rendered = true;
        
        $this->render_social_share();
        $this->render_contact_modal();
        $this->render_reviews_section();
    }

    public function render_social_share() {
        if (!is_singular('listing')) return;
        
        $listing_id = get_the_ID();
        $permalink = get_permalink($listing_id);
        $title = get_the_title($listing_id);
        $excerpt = wp_strip_all_tags(get_the_excerpt());
        
        $share_text = urlencode($title . ' - ' . $permalink);
        ?>
        <div class="hana-share-section">
            <h3 class="hana-section-title">Partager cette propriete</h3>
            <div class="hana-share-grid">
                <a href="https://wa.me/?text=<?php echo esc_attr( $share_text ); ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="hana-share-btn hana-share-whatsapp"
                   onclick="window.open(this.href,'share','width=600,height=400');return false;">
                   <span>WhatsApp</span>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo esc_attr( urlencode( $permalink ) ); ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="hana-share-btn hana-share-facebook"
                   onclick="window.open(this.href,'share','width=600,height=400');return false;">
                   <span>Facebook</span>
                </a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo esc_attr( urlencode( $title ) ); ?>&url=<?php echo esc_attr( urlencode( $permalink ) ); ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="hana-share-btn hana-share-twitter"
                   onclick="window.open(this.href,'share','width=600,height=400');return false;">
                   <span>X (Twitter)</span>
                </a>
                <a href="mailto:?subject=<?php echo esc_attr( urlencode( $title ) ); ?>&body=<?php echo esc_attr( urlencode( $excerpt . "\n\n" . $permalink ) ); ?>"
                   class="hana-share-btn hana-share-email">
                   <span>Email</span>
                </a>
                <button type="button" class="hana-share-btn hana-share-copy" data-url="<?php echo esc_url($permalink); ?>">
                    <span>Copier le lien</span>
                </button>
            </div>
        </div>
        <?php
    }

    public function render_contact_modal() {
        if (!is_singular('listing')) return;
        
        $listing_id = get_the_ID();
        $listing_title = get_the_title($listing_id);
        ?>
        <div class="hana-contact-section">
            <h3 class="hana-section-title">Interesse par cette propriete ?</h3>
            <button type="button" class="hana-btn-contact" onclick="document.getElementById('hana-contact-modal').classList.add('active')">
                Contacter le proprietaire
            </button>
        </div>

        <div id="hana-contact-modal" class="hana-modal">
            <div class="hana-modal-overlay" onclick="this.parentElement.classList.remove('active')"></div>
            <div class="hana-modal-content">
                <button type="button" class="hana-modal-close" onclick="document.getElementById('hana-contact-modal').classList.remove('active')">&times;</button>
                
                <h3>Contacter le proprietaire</h3>
                <p class="hana-modal-subtitle"><?php echo esc_html($listing_title); ?></p>
                
                <form id="hana-contact-form" class="hana-form">
                    <?php wp_nonce_field('hana_listing_nonce', 'hana_nonce'); ?>
                    <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
                    <input type="hidden" name="agent_id" value="<?php echo esc_attr(get_post_field('post_author', $listing_id)); ?>">
                    
                    <div class="hana-form-group">
                        <label>Votre nom *</label>
                        <input type="text" name="sender_name" required>
                    </div>
                    
                    <div class="hana-form-group">
                        <label>Votre email *</label>
                        <input type="email" name="sender_email" required>
                    </div>
                    
                    <div class="hana-form-group">
                        <label>Telephone</label>
                        <input type="tel" name="sender_phone">
                    </div>
                    
                    <div class="hana-form-group">
                        <label>Message *</label>
                        <textarea name="message" rows="4" required placeholder="Bonjour, je suis interesse par cette propriete..."></textarea>
                    </div>
                    
                    <div class="hana-form-group">
                        <label class="hana-checkbox">
                            <input type="checkbox" name="visit_request" value="1">
                            <span>Je souhaite planifier une visite</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="hana-btn-submit">Envoyer le message</button>
                    
                    <div class="hana-form-message"></div>
                </form>
            </div>
        </div>
        <?php
    }

    public function handle_contact_form() {
        check_ajax_referer('hana_listing_nonce', 'nonce');
        
        $listing_id = intval($_POST['listing_id']);
        $agent_id = intval($_POST['agent_id']);
        $name = sanitize_text_field($_POST['sender_name']);
        $email = sanitize_email($_POST['sender_email']);
        $phone = sanitize_text_field($_POST['sender_phone']);
        $message = sanitize_textarea_field($_POST['message']);
        $visit = isset($_POST['visit_request']) ? true : false;
        
        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => 'Veuillez remplir tous les champs obligatoires.'));
        }
        
        $agent_email = get_the_author_meta('user_email', $agent_id);
        $listing_title = get_the_title($listing_id);
        $permalink = get_permalink($listing_id);
        
        $subject = 'Nouvelle demande pour: ' . $listing_title;
        
        $body = "Nouvelle demande de contact pour la propriete:\n\n" . $listing_title . "\n" . $permalink . "\n\nDe: " . $name . " <" . $email . ">\nTelephone: " . ($phone ? $phone : 'Non fourni') . "\n\nMessage:\n" . $message . "\n\nVisite demandee: " . ($visit ? 'Oui' : 'Non');
        
        $headers = array('Reply-To: ' . $name . ' <' . $email . '>');
        
        $sent = wp_mail($agent_email, $subject, $body, $headers);
        
        $admin_email = get_option('admin_email');
        if ($admin_email !== $agent_email) {
            wp_mail($admin_email, '[COPIE] ' . $subject, $body, $headers);
        }
        
        if ($sent) {
            wp_send_json_success(array('message' => 'Votre message a ete envoye avec succes ! Le proprietaire vous contactera bientot.'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de l\'envoi. Veuillez reessayer.'));
        }
    }

    public function render_reviews_section() {
        if (!is_singular('listing')) return;
        
        $listing_id = get_the_ID();
        $reviews = $this->get_reviews($listing_id);
        $average = $this->get_average_rating($listing_id);
        $total = count($reviews);
        ?>
        <div class="hana-reviews-section" id="hana-reviews">
            <h3 class="hana-section-title">
                Reviews & Verification
                <?php if ($total > 0) : ?>
                    <span class="hana-rating-average"><?php echo number_format($average, 1); ?>/5</span>
                    <span class="hana-rating-stars"><?php echo $this->render_stars($average); ?></span>
                    <span class="hana-rating-count">(<?php echo $total; ?>)</span>
                <?php endif; ?>
            </h3>
            
            <div class="hana-reviews-summary">
                <p class="hana-reviews-intro">
                    Les proprietaires peuvent laisser des reviews sur les locataires pour aider la communaute. Les reviews sont verifiees manuellement.
                </p>
            </div>

            <?php if (is_user_logged_in()) : ?>
                <button type="button" class="hana-btn-review" onclick="document.getElementById('hana-review-modal').classList.add('active')">
                    Laisser une review
                </button>
            <?php else : ?>
                <p class="hana-login-notice">
                    <a href="<?php echo wp_login_url(get_permalink() . '#hana-reviews'); ?>">Connectez-vous</a> pour laisser une review.
                </p>
            <?php endif; ?>

            <div class="hana-reviews-list">
                <?php if (empty($reviews)) : ?>
                    <p class="hana-no-reviews">Aucune review pour le moment. Soyez le premier !</p>
                <?php else : ?>
                    <?php foreach ($reviews as $review) : ?>
                        <div class="hana-review-card">
                            <div class="hana-review-header">
                                <div class="hana-review-author">
                                    <?php echo get_avatar($review->user_id, 50); ?>
                                    <div>
                                        <strong><?php echo esc_html($review->display_name); ?></strong>
                                        <span class="hana-review-badge <?php echo $review->verified ? 'verified' : 'pending'; ?>">
                                            <?php echo $review->verified ? 'Verifie' : 'En attente'; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="hana-review-meta">
                                    <span class="hana-review-stars"><?php echo $this->render_stars($review->rating); ?></span>
                                    <span class="hana-review-date"><?php echo human_time_diff(strtotime($review->created_at), current_time('timestamp')); ?></span>
                                </div>
                            </div>
                            
                            <div class="hana-review-categories">
                                <span class="hana-review-cat">
                                    Paiement: <?php echo $this->render_mini_stars($review->payment_rating); ?>
                                </span>
                                <span class="hana-review-cat">
                                    Regles: <?php echo $this->render_mini_stars($review->rules_rating); ?>
                                </span>
                                <span class="hana-review-cat">
                                    Comm: <?php echo $this->render_mini_stars($review->communication_rating); ?>
                                </span>
                            </div>
                            
                            <div class="hana-review-content">
                                <?php echo wpautop(esc_html($review->comment)); ?>
                            </div>
                            
                            <?php if ($review->pros) : ?>
                                <div class="hana-review-pros">
                                    <strong>Points positifs:</strong> <?php echo esc_html($review->pros); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($review->cons) : ?>
                                <div class="hana-review-cons">
                                    <strong>Points de vigilance:</strong> <?php echo esc_html($review->cons); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (is_user_logged_in()) : ?>
        <div id="hana-review-modal" class="hana-modal">
            <div class="hana-modal-overlay" onclick="this.parentElement.classList.remove('active')"></div>
            <div class="hana-modal-content">
                <button type="button" class="hana-modal-close" onclick="document.getElementById('hana-review-modal').classList.remove('active')">&times;</button>
                
                <h3>Laisser une review sur le locataire</h3>
                <p class="hana-modal-subtitle">Aidez les autres proprietaires a evaluer ce locataire.</p>
                
                <form id="hana-review-form" class="hana-form">
                    <?php wp_nonce_field('hana_listing_nonce', 'hana_nonce'); ?>
                    <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
                    
                    <div class="hana-form-group">
                        <label>Note globale *</label>
                        <div class="hana-star-rating" data-rating="0">
                            <?php for ($i = 1; $i <= 5; $i++) : ?>
                                <button type="button" class="hana-star" data-value="<?php echo $i; ?>">★</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" required>
                        </div>
                    </div>
                    
                    <div class="hana-form-row">
                        <div class="hana-form-group">
                            <label>Paiement a temps</label>
                            <div class="hana-star-rating mini" data-rating="0">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <button type="button" class="hana-star" data-value="<?php echo $i; ?>">★</button>
                                <?php endfor; ?>
                                <input type="hidden" name="payment_rating">
                            </div>
                        </div>
                        
                        <div class="hana-form-group">
                            <label>Respect des regles</label>
                            <div class="hana-star-rating mini" data-rating="0">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <button type="button" class="hana-star" data-value="<?php echo $i; ?>">★</button>
                                <?php endfor; ?>
                                <input type="hidden" name="rules_rating">
                            </div>
                        </div>
                        
                        <div class="hana-form-group">
                            <label>Communication</label>
                            <div class="hana-star-rating mini" data-rating="0">
                                <?php for ($i = 1; $i <= 5; $i++) : ?>
                                    <button type="button" class="hana-star" data-value="<?php echo $i; ?>">★</button>
                                <?php endfor; ?>
                                <input type="hidden" name="communication_rating">
                            </div>
                        </div>
                    </div>
                    
                    <div class="hana-form-group">
                        <label>Votre experience *</label>
                        <textarea name="comment" rows="4" required placeholder="Decrivez votre experience avec ce locataire..."></textarea>
                    </div>
                    
                    <div class="hana-form-group">
                        <label>Points positifs</label>
                        <input type="text" name="pros" placeholder="Ex: Paiement toujours a temps...">
                    </div>
                    
                    <div class="hana-form-group">
                        <label>Points de vigilance</label>
                        <input type="text" name="cons" placeholder="Ex: Bruyant le weekend...">
                    </div>
                    
                    <div class="hana-form-group">
                        <label class="hana-checkbox">
                            <input type="checkbox" name="is_owner" value="1" required>
                            <span>Je confirme etre le proprietaire ou avoir gere cette location *</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="hana-btn-submit">Soumettre la review</button>
                    
                    <div class="hana-form-message"></div>
                </form>
            </div>
        </div>
        <?php endif;
    }

    public function handle_review_submission() {
        check_ajax_referer('hana_listing_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Vous devez etre connecte.'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'hana_reviews';
        
        $data = array(
            'listing_id' => intval($_POST['listing_id']),
            'user_id' => get_current_user_id(),
            'rating' => intval($_POST['rating']),
            'payment_rating' => intval($_POST['payment_rating']) ? intval($_POST['payment_rating']) : null,
            'rules_rating' => intval($_POST['rules_rating']) ? intval($_POST['rules_rating']) : null,
            'communication_rating' => intval($_POST['communication_rating']) ? intval($_POST['communication_rating']) : null,
            'comment' => sanitize_textarea_field($_POST['comment']),
            'pros' => sanitize_text_field($_POST['pros']),
            'cons' => sanitize_text_field($_POST['cons']),
            'verified' => 0,
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($table_name, $data, array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s'));
        
        if ($result) {
            wp_send_json_success(array('message' => 'Review soumise avec succes ! Elle sera visible apres validation par un moderateur.'));
        } else {
            wp_send_json_error(array('message' => 'Erreur lors de la soumission.'));
        }
    }

    private function get_reviews($listing_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hana_reviews';
        
        $where = current_user_can('manage_options') ? '' : 'AND verified = 1';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name FROM {$table_name} r LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID WHERE r.listing_id = %d {$where} ORDER BY r.created_at DESC",
            $listing_id
        ));
    }

    private function get_average_rating($listing_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hana_reviews';

        $avg = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM {$table_name} WHERE listing_id = %d AND verified = 1",
            $listing_id
        ));
        return $avg ? (float) $avg : 0;
    }

    private function render_stars($rating) {
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= round($rating) ? '★' : '☆';
        }
        return $stars;
    }

    private function render_mini_stars($rating) {
        if (!$rating) return '-';
        $stars = '';
        for ($i = 1; $i <= 5; $i++) {
            $stars .= $i <= round($rating) ? '★' : '☆';
        }
        return $stars;
    }

    public function reviews_shortcode($atts) {
        ob_start();
        $this->render_reviews_section();
        return ob_get_clean();
    }
    
    public function share_shortcode($atts) {
        ob_start();
        $this->render_social_share();
        return ob_get_clean();
    }
    
    public function contact_shortcode($atts) {
        ob_start();
        $this->render_contact_modal();
        return ob_get_clean();
    }

    public static function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'hana_reviews';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            listing_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating tinyint(1) NOT NULL,
            payment_rating tinyint(1) DEFAULT NULL,
            rules_rating tinyint(1) DEFAULT NULL,
            communication_rating tinyint(1) DEFAULT NULL,
            comment text NOT NULL,
            pros varchar(255) DEFAULT NULL,
            cons varchar(255) DEFAULT NULL,
            verified tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY listing_id (listing_id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

new HANA_Listing_Extras();

register_activation_hook(__FILE__, array('HANA_Listing_Extras', 'activate'));