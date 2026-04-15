<?php
/**
 * User Activity class for iMembers (Favorites, History, Status Management)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_User_Activity {

    public function init() {
        // AJAX for Favorites
        add_action( 'wp_ajax_imembers_toggle_favorite', array( $this, 'ajax_toggle_favorite' ) );
        add_action( 'wp_ajax_nopriv_imembers_toggle_favorite', array( $this, 'ajax_toggle_favorite' ) );

        // Track History
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'template_redirect', array( $this, 'track_browsing_history' ) );

        // My Page & Favorite Shortcodes
        add_shortcode( 'imembers_mypage', array( $this, 'render_mypage' ) );
        add_shortcode( 'imembers_favorite', array( $this, 'render_favorite_button' ) );

        // Admin User Columns
        add_filter( 'manage_users_columns', array( $this, 'add_status_column' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'render_status_column' ), 10, 3 );
    }

    public function enqueue_assets() {
        wp_enqueue_script( 'imembers-activity', IMEMBERS_PLUGIN_URL . 'assets/js/activity.js', array('jquery'), IMEMBERS_VERSION, true );
        wp_localize_script( 'imembers-activity', 'imembers_ajax', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('imembers_activity_nonce')
        ) );
    }

    /**
     * Favorites logic
     */
    public function ajax_toggle_favorite() {
        check_ajax_referer( 'imembers_activity_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'お気に入り登録にはログインが必要です。' ) );
        }

        $user_id = get_current_user_id();
        $post_id = intval( $_POST['post_id'] );

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => '無効な投稿IDです。' ) );
        }

        $favorites = get_user_meta( $user_id, '_imembers_favorites', true );
        if ( ! is_array( $favorites ) ) {
            $favorites = array();
        }

        if ( in_array( $post_id, $favorites ) ) {
            // Remove
            $favorites = array_diff( $favorites, array( $post_id ) );
            $action = 'removed';
        } else {
            // Add
            $favorites[] = $post_id;
            $action = 'added';
        }

        update_user_meta( $user_id, '_imembers_favorites', $favorites );
        wp_send_json_success( array( 'action' => $action ) );
    }

    /**
     * Render Favorite Button
     */
    public function render_favorite_button( $atts ) {
        if ( ! is_user_logged_in() || ! is_singular() ) {
            return '';
        }

        $post_id = get_the_ID();
        $user_id = get_current_user_id();
        $favorites = get_user_meta( $user_id, '_imembers_favorites', true );
        if ( ! is_array( $favorites ) ) {
            $favorites = array();
        }

        $is_favorited = in_array( $post_id, $favorites );
        $text = $is_favorited ? '★ お気に入り解除' : '☆ お気に入り登録';
        $class = $is_favorited ? 'favorited' : '';

        return '<button type="button" class="imembers-favorite-btn ' . esc_attr( $class ) . '" data-id="' . esc_attr( $post_id ) . '" style="padding:8px 15px; cursor:pointer;">' . esc_html( $text ) . '</button>';
    }

    /**
     * History Tracking
     */
    public function track_browsing_history() {
        if ( is_singular() && is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $post_id = get_queried_object_id();

            $history = get_user_meta( $user_id, '_imembers_history', true );
            if ( ! is_array( $history ) ) {
                $history = array();
            }

            // Remove if already exists to move to top
            $history = array_diff( $history, array( $post_id ) );
            array_unshift( $history, $post_id );

            // Limit to 20 items
            $history = array_slice( $history, 0, 20 );

            update_user_meta( $user_id, '_imembers_history', $history );
        }
    }

    /**
     * My Page Rendering
     */
    public function render_mypage() {
        if ( ! is_user_logged_in() ) {
            $login_url = get_permalink( get_option( 'imembers_page_imembers_login' ) ) ?: wp_login_url();
            return '<p>マイページを表示するには<a href="' . esc_url( $login_url ) . '">ログイン</a>が必要です。</p>';
        }

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        $favorites = get_user_meta( $user_id, '_imembers_favorites', true );
        $history = get_user_meta( $user_id, '_imembers_history', true );

        ob_start();
        ?>
        <div class="imembers-mypage">
            <h2>こんにちは、<?php echo esc_html( $user->display_name ); ?> さん</h2>
            <p>メールアドレス: <?php echo esc_html( $user->user_email ); ?></p>
            <p><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">ログアウト</a></p>

            <div class="imembers-section" style="margin-top: 30px;">
                <h3>お気に入り一覧</h3>
                <?php if ( ! empty( $favorites ) ) : ?>
                    <ul class="imembers-list">
                        <?php foreach ( $favorites as $pid ) : ?>
                            <li><a href="<?php echo get_permalink( $pid ); ?>"><?php echo get_the_title( $pid ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>お気に入りはまだありません。</p>
                <?php endif; ?>
            </div>

            <div class="imembers-section" style="margin-top: 30px;">
                <h3>閲覧履歴</h3>
                <?php if ( ! empty( $history ) ) : ?>
                    <ul class="imembers-list">
                        <?php foreach ( $history as $pid ) : ?>
                            <li><a href="<?php echo get_permalink( $pid ); ?>"><?php echo get_the_title( $pid ); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>履歴はありません。</p>
                <?php endif; ?>
            </div>

            <?php do_action( 'imembers_mypage_subscription_section' ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Admin Status Columns
     */
    public function add_status_column( $columns ) {
        $columns['imembers_status'] = '会員ステータス';
        return $columns;
    }

    public function render_status_column( $output, $column_name, $user_id ) {
        if ( 'imembers_status' !== $column_name ) {
            return $output;
        }

        $is_pro = get_user_meta( $user_id, '_imembers_pro_subscriber', true );
        if ( $is_pro ) {
            return '<span style="color: #d63638; font-weight: bold;">有料会員</span>';
        } else {
            return '<span>無料会員</span>';
        }
    }
}
