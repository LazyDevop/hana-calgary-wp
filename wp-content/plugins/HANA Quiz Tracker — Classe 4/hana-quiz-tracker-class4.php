<?php
/**
 * Plugin Name: HANA Quiz Tracker — Classe 4
 * Description: Enregistre les résultats du Quiz Classe 4 (email, score, durée, date) et affiche un tableau dans l'admin avec export CSV.
 * Version:     1.0.0
 * Author:      HANA Calgary
 */

defined( 'ABSPATH' ) || exit;

/* ═══════════════════════════════════════════════════════════════
   1. CRÉATION DE LA TABLE
═══════════════════════════════════════════════════════════════ */
register_activation_hook( __FILE__, 'hqt4_create_table' );

add_action( 'init', function () {
    if ( get_option( 'hqt4_table_version' ) !== '1.0' ) {
        hqt4_create_table();
        update_option( 'hqt4_table_version', '1.0' );
    }
} );

function hqt4_create_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'hana_quiz4_results';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email        VARCHAR(255)    NOT NULL,
        score        SMALLINT        NOT NULL,
        total        SMALLINT        NOT NULL,
        pourcentage  TINYINT         NOT NULL,
        duree_sec    MEDIUMINT       NOT NULL,
        date_passage DATETIME        NOT NULL,
        PRIMARY KEY (id)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
}

/* ═══════════════════════════════════════════════════════════════
   2. ENDPOINT REST  →  POST /wp-json/hana-quiz/v1/submit-class4
═══════════════════════════════════════════════════════════════ */
add_action( 'rest_api_init', function () {
    register_rest_route( 'hana-quiz/v1', '/submit-class4', [
        'methods'             => 'POST',
        'callback'            => 'hqt4_save_result',
        'permission_callback' => '__return_true',
    ] );
} );

function hqt4_save_result( WP_REST_Request $req ) {

    $body = json_decode( $req->get_body(), true );

    if ( ! is_array( $body ) || empty( $body ) ) {
        return new WP_REST_Response(
            [ 'success' => false, 'message' => 'Body JSON manquant ou invalide.' ],
            400
        );
    }

    $email_raw = isset( $body['email'] )     ? $body['email']     : '';
    $score_raw = isset( $body['score'] )     ? $body['score']     : null;
    $total_raw = isset( $body['total'] )     ? $body['total']     : null;
    $duree_raw = isset( $body['duree_sec'] ) ? $body['duree_sec'] : 0;

    $email = sanitize_email( $email_raw );
    if ( ! is_email( $email ) ) {
        return new WP_REST_Response(
            [ 'success' => false, 'message' => 'Email invalide : ' . $email_raw ],
            400
        );
    }

    $score = (int) $score_raw;
    $total = (int) $total_raw;
    $duree = absint( $duree_raw );

    if ( $total <= 0 || $total > 500 || $score < 0 || $score > $total ) {
        return new WP_REST_Response(
            [ 'success' => false, 'message' => 'Score ou total invalide.' ],
            400
        );
    }

    $pct = (int) round( $score / $total * 100 );

    global $wpdb;
    $ok = $wpdb->insert(
        $wpdb->prefix . 'hana_quiz4_results',
        [
            'email'        => $email,
            'score'        => $score,
            'total'        => $total,
            'pourcentage'  => $pct,
            'duree_sec'    => $duree,
            'date_passage' => current_time( 'mysql' ),
        ],
        [ '%s', '%d', '%d', '%d', '%d', '%s' ]
    );

    if ( $ok === false ) {
        return new WP_REST_Response(
            [ 'success' => false, 'message' => 'Erreur base de données : ' . $wpdb->last_error ],
            500
        );
    }

    return new WP_REST_Response(
        [ 'success' => true, 'message' => 'Résultat enregistré.', 'id' => $wpdb->insert_id ],
        200
    );
}

/* ═══════════════════════════════════════════════════════════════
   3. INJECTER L'URL REST dans le frontend
═══════════════════════════════════════════════════════════════ */
add_action( 'wp_footer', function () {
    ?>
    <script>
    window.hanaQuiz4Config = {
        restUrl : "<?php echo esc_url( rest_url( 'hana-quiz/v1/submit-class4' ) ); ?>"
    };
    </script>
    <?php
} );

/* ═══════════════════════════════════════════════════════════════
   4. PAGE D'ADMINISTRATION
═══════════════════════════════════════════════════════════════ */
add_action( 'admin_menu', function () {
    add_menu_page(
        'Results Quiz Class 4',
        'Quiz Class 4',
        'manage_options',
        'hana-quiz4-results',
        'hqt4_admin_page',
        'dashicons-awards',
        31
    );
} );

/* ── Export CSV ── */
add_action( 'admin_init', function () {
    if (
        ! isset( $_GET['page'], $_GET['action'] ) ||
        $_GET['page']   !== 'hana-quiz4-results'  ||
        $_GET['action'] !== 'export_csv'           ||
        ! current_user_can( 'manage_options' )     ||
        ! check_admin_referer( 'hqt4_export_csv' )
    ) {
        return;
    }

    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT email, score, total, pourcentage, duree_sec, date_passage
         FROM {$wpdb->prefix}hana_quiz4_results
         ORDER BY date_passage DESC",
        ARRAY_A
    );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="hana-quiz4-' . date( 'Y-m-d' ) . '.csv"' );
    header( 'Pragma: no-cache' );

    $out = fopen( 'php://output', 'w' );
    fputs( $out, "\xEF\xBB\xBF" ); // BOM UTF-8 pour Excel
    fputcsv( $out, [ 'Email', 'Score', 'Total', '%', 'Duration (s)', 'Duration (mm:ss)', 'Date' ] );

    foreach ( $rows as $r ) {
        $mm_ss = sprintf( '%d:%02d', floor( $r['duree_sec'] / 60 ), $r['duree_sec'] % 60 );
        fputcsv( $out, [
            $r['email'], $r['score'], $r['total'],
            $r['pourcentage'], $r['duree_sec'], $mm_ss, $r['date_passage'],
        ] );
    }

    fclose( $out );
    exit;
} );

/* ── Page admin ── */
function hqt4_admin_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'hana_quiz4_results';

    $per_page    = 25;
    $current_p   = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
    $offset      = ( $current_p - 1 ) * $per_page;
    $total_rows  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    $total_pages = (int) ceil( $total_rows / $per_page );

    $stats = $wpdb->get_row( "
        SELECT COUNT(*) AS nb,
               ROUND(AVG(pourcentage),1) AS moy_pct,
               MAX(pourcentage)          AS max_pct,
               ROUND(AVG(duree_sec))     AS moy_duree
        FROM {$table}
    " );

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$table} ORDER BY date_passage DESC LIMIT %d OFFSET %d",
        $per_page, $offset
    ) );

    $export_url = wp_nonce_url(
        admin_url( 'admin.php?page=hana-quiz4-results&action=export_csv' ),
        'hqt4_export_csv'
    );
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            🏆 Results Quiz — Class 4 Alberta
            <a href="<?php echo esc_url( $export_url ); ?>"
               style="background:#E8621A;color:#fff;border-color:#C04E0E;padding:6px 16px;border-radius:4px;text-decoration:none;font-size:.85rem;margin-left:8px;">
                ⬇ Export CSV
            </a>
        </h1>

        <!-- Stats cards -->
        <?php if ( $stats && $stats->nb > 0 ) : ?>
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin:16px 0 24px;">
            <?php foreach ( [
                [ '👥', 'Participants',  $stats->nb ],
                [ '📊', 'Avg. Score',   $stats->moy_pct . '%' ],
                [ '🏅', 'Best Score',   $stats->max_pct . '%' ],
                [ '⏱',  'Avg. Duration', hqt4_fmt( $stats->moy_duree ) ],
            ] as $c ) : ?>
            <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:18px 24px;min-width:140px;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,.06);">
                <div style="font-size:1.8rem;"><?php echo $c[0]; ?></div>
                <div style="font-size:1.5rem;font-weight:800;color:#E8621A;"><?php echo esc_html( $c[2] ); ?></div>
                <div style="font-size:.75rem;color:#888;text-transform:uppercase;"><?php echo esc_html( $c[1] ); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Table -->
        <table class="widefat fixed striped">
            <thead style="background:#1A3550;">
                <tr>
                    <th style="color:#fff;width:40px;">#</th>
                    <th style="color:#fff;">Email</th>
                    <th style="color:#fff;width:90px;">Score</th>
                    <th style="color:#fff;width:100px;">Result</th>
                    <th style="color:#fff;width:100px;">Duration</th>
                    <th style="color:#fff;width:160px;">Date</th>
                </tr>
            </thead>
            <tbody>
            <?php if ( empty( $rows ) ) : ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:#888;">
                    No results yet. Complete a quiz to see data here.
                </td></tr>
            <?php else : ?>
                <?php foreach ( $rows as $i => $r ) :
                    $pct = (int) $r->pourcentage;
                    $col = $pct >= 80 ? '#2D9E6B' : ( $pct >= 60 ? '#E8621A' : '#d63031' );
                    $bg  = $pct >= 80 ? '#edfaf4' : ( $pct >= 60 ? '#FEF0E6' : '#fff0f0' );
                ?>
                <tr>
                    <td style="color:#aaa;"><?php echo $offset + $i + 1; ?></td>
                    <td><strong><?php echo esc_html( $r->email ); ?></strong></td>
                    <td><?php echo (int)$r->score . ' / ' . (int)$r->total; ?></td>
                    <td><span style="background:<?php echo $bg; ?>;color:<?php echo $col; ?>;font-weight:700;padding:3px 10px;border-radius:999px;">
                        <?php echo $pct; ?>%
                    </span></td>
                    <td><?php echo esc_html( hqt4_fmt( $r->duree_sec ) ); ?></td>
                    <td style="color:#555;"><?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( $r->date_passage ) ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ( $total_pages > 1 ) : ?>
        <div style="margin-top:16px;display:flex;gap:8px;align-items:center;">
            <?php for ( $p = 1; $p <= $total_pages; $p++ ) :
                $s = $p === $current_p ? 'background:#E8621A;color:#fff;' : 'background:#fff;color:#333;';
            ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=hana-quiz4-results&paged=' . $p ) ); ?>"
               style="<?php echo $s; ?>padding:5px 12px;border:1px solid #ccc;border-radius:4px;text-decoration:none;font-size:.85rem;">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
            <span style="color:#888;font-size:.82rem;"><?php echo $total_rows; ?> result(s)</span>
        </div>
        <?php endif; ?>

    </div>
    <?php
}

function hqt4_fmt( $s ) {
    $s = (int) $s;
    return sprintf( '%d min %02d s', floor( $s / 60 ), $s % 60 );
}
