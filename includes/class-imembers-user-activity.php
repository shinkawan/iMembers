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

        global $wpdb;
        $table_name = $wpdb->prefix . 'imembers_activity';
        $user_id = get_current_user_id();
        $post_id = intval( $_POST['post_id'] );

        if ( ! $post_id ) {
            wp_send_json_error( array( 'message' => '無効な投稿IDです。' ) );
        }

        // Check if exists
        $id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND post_id = %d AND activity_type = 'favorite'",
            $user_id, $post_id
        ) );

        if ( $id ) {
            // Remove
            $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
            $action = 'removed';
        } else {
            // Add
            $wpdb->insert( $table_name, array(
                'user_id'       => $user_id,
                'post_id'       => $post_id,
                'activity_type' => 'favorite',
                'created_at'    => current_time( 'mysql' )
            ), array( '%d', '%d', '%s', '%s' ) );
            $action = 'added';
        }

        wp_send_json_success( array( 'action' => $action ) );
    }

    /**
     * Render Favorite Button
     */
    public function render_favorite_button( $atts ) {
        if ( ! is_user_logged_in() || ! is_singular() ) {
            return '';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'imembers_activity';
        $post_id = get_the_ID();
        $user_id = get_current_user_id();

        $is_favorited = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND post_id = %d AND activity_type = 'favorite'",
            $user_id, $post_id
        ) );

        $text = $is_favorited ? '★ お気に入り解除' : '☆ お気に入り登録';
        $class = $is_favorited ? 'favorited' : '';

        return '<button type="button" class="imembers-favorite-btn ' . esc_attr( $class ) . '" data-id="' . esc_attr( $post_id ) . '" style="padding:8px 15px; cursor:pointer;">' . esc_html( $text ) . '</button>';
    }

    /**
     * History Tracking
     */
    public function track_browsing_history() {
        if ( is_singular() && is_user_logged_in() ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'imembers_activity';
            $user_id = get_current_user_id();
            $post_id = get_queried_object_id();

            // Check if this post is already in the history to avoid duplicates
            $wpdb->delete( $table_name, array(
                'user_id'       => $user_id,
                'post_id'       => $post_id,
                'activity_type' => 'history'
            ), array( '%d', '%d', '%s' ) );

            // Insert new history record
            $wpdb->insert( $table_name, array(
                'user_id'       => $user_id,
                'post_id'       => $post_id,
                'activity_type' => 'history',
                'created_at'    => current_time( 'mysql' )
            ), array( '%d', '%d', '%s', '%s' ) );

            // Limit to 20 items: delete older records for this user
            $ids_to_keep = $wpdb->get_col( $wpdb->prepare(
                "SELECT id FROM $table_name WHERE user_id = %d AND activity_type = 'history' ORDER BY created_at DESC LIMIT 20",
                $user_id
            ) );

            if ( ! empty( $ids_to_keep ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $ids_to_keep ), '%d' ) );
                $wpdb->query( $wpdb->prepare(
                    "DELETE FROM $table_name WHERE user_id = %d AND activity_type = 'history' AND id NOT IN ($placeholders)",
                    array_merge( array( $user_id ), $ids_to_keep )
                ) );
            }
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

        global $wpdb;
        $table_name = $wpdb->prefix . 'imembers_activity';
        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );

        // Fetch Favorites
        $favorites = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM $table_name WHERE user_id = %d AND activity_type = 'favorite' ORDER BY created_at DESC",
            $user_id
        ) );

        // Fetch History
        $history = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM $table_name WHERE user_id = %d AND activity_type = 'history' ORDER BY created_at DESC",
            $user_id
        ) );

        ob_start();
        iMembers_Core::get_template( 'activity-mypage', array(
            'user'      => $user,
            'favorites' => $favorites,
            'history'   => $history
        ) );
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
