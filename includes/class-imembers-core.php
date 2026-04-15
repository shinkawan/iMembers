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
    }
}
