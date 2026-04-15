<?php
/**
 * Plugin Name: iMembers
 * Plugin URI: 
 * Description: 高機能メンバーシップ管理プラグイン（会員限定コンテンツ、パスワードレス認証、Stripeサブスクリプション連携機能）
 * Version: 1.0.0
 * Author: Shinkawa
 * Text Domain: imembers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define( 'IMEMBERS_VERSION', '1.0.0' );
define( 'IMEMBERS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMEMBERS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-installer.php';
require_once IMEMBERS_PLUGIN_DIR . 'includes/class-imembers-core.php';

// Activation Hook
register_activation_hook( __FILE__, array( 'iMembers_Installer', 'activate' ) );

// Deactivation Hook
register_deactivation_hook( __FILE__, array( 'iMembers_Installer', 'deactivate' ) );

// Initialize the core plugin
function imembers_init() {
    $core = new iMembers_Core();
    $core->init();
}
add_action( 'plugins_loaded', 'imembers_init' );
