<?php
/**
 * Core plugin class to initialize and hook into WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Core {

    public function __construct() {
        // Constructor if needed
    }

    public function init() {
        // Load admin settings
        if ( is_admin() ) {
            require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-admin-settings.php';
            $admin_settings = new iMembers_Admin_Settings();
            $admin_settings->init();
        }

        // Load Content Restriction module
        require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-content-restriction.php';
        $content_restriction = new iMembers_Content_Restriction();
        $content_restriction->init();

        // Load Auth module
        require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-auth.php';
        $auth = new iMembers_Auth();
        $auth->init();

        // Load SNS Auth module
        require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-sns-auth.php';
        $sns_auth = new iMembers_SNS_Auth();
        $sns_auth->init();

        // Load User Activity module
        require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-user-activity.php';
        $user_activity = new iMembers_User_Activity();
        $user_activity->init();

        // Load Subscription module
        require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-subscription.php';
        $subscription = new iMembers_Subscription();
        $subscription->init();

        // Load Admin Dashboard module
        require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-admin-dashboard.php';
        $admin_dashboard = new iMembers_Admin_Dashboard();
        $admin_dashboard->init();

        // Security: Set login expiration to 30 days
        add_filter( 'auth_cookie_expiration', array( $this, 'set_login_expiration' ), 10, 3 );

        // Enqueue Shared Assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        // Enqueue Fonts
        wp_enqueue_style( 'imembers-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null );

        // Enqueue Modern CSS
        wp_enqueue_style( 'imembers-style', IMEMBERS_PLUGIN_URL . 'assets/css/imembers-style.css', array(), IMEMBERS_VERSION );

        // Enqueue Shared Data
        wp_localize_script( 'jquery', 'imembers_ajax', array(
            'url'   => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'imembers_ajax_nonce' )
        ) );
    }

    public function set_login_expiration( $expiration, $user_id, $remember ) {
        // Force 30 days expiration (30 * 24 * 60 * 60)
        return 30 * DAY_IN_SECONDS;
    }

    /**
     * Helper to load template files from the plugin
     */
    public static function get_template( $template_name, $args = array() ) {
        if ( $args && is_array( $args ) ) {
            extract( $args );
        }

        $template_path = IMEMBERS_PLUGIN_DIR . 'templates/' . $template_name . '.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }
}
