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
    }
}
