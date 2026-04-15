<?php
/**
 * Installer class to handle plugin activation/deactivation processes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Installer {

    /**
     * Run on plugin activation
     */
    public static function activate() {
        self::create_default_pages();
        self::create_activity_table();
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create necessary default pages when plugin is activated
     */
    private static function create_default_pages() {
        $pages = array(
            'imembers-login' => array(
                'title'   => 'ログイン / 新規登録',
                'content' => '[imembers_login]',
            ),
            'imembers-mypage' => array(
                'title'   => 'マイページ',
                'content' => '[imembers_mypage]',
            ),
        );

        foreach ( $pages as $slug => $page_data ) {
            $existing_page = get_page_by_path( $slug );
            $option_name = 'imembers_page_' . str_replace('-', '_', $slug);
            $page_id = 0;

            if ( ! $existing_page ) {
                $page_id = wp_insert_post( array(
                    'post_title'     => $page_data['title'],
                    'post_content'   => $page_data['content'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'post_name'      => $slug,
                    'ping_status'    => 'closed',
                    'comment_status' => 'closed',
                ) );
            } else {
                $page_id = $existing_page->ID;
            }

            // Always ensure the option is up to date if we have a valid page ID
            if ( $page_id && ! is_wp_error( $page_id ) ) {
                if ( ! get_option( $option_name ) ) {
                    update_option( $option_name, $page_id );
                }
            }
        }
    }

    /**
     * Create dedicated database table for user activities (Favorites, History)
     */
    private static function create_activity_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'imembers_activity';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) NOT NULL,
            activity_type varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY activity_type (activity_type)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}
