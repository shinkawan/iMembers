<?php
/**
 * Admin settings class for iMembers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Admin_Settings {

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_settings_page() {
        add_menu_page(
            'iMembers 設定',
            'iMembers',
            'manage_options',
            'imembers-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-groups',
            100
        );

        add_submenu_page(
            'imembers-settings',
            'iMembers マニュアル',
            'マニュアル',
            'manage_options',
            'imembers-manual',
            array( $this, 'render_manual_page' )
        );
    }

    public function register_settings() {
        register_setting( 'imembers_settings_group', 'imembers_line_client_id' );
        register_setting( 'imembers_settings_group', 'imembers_line_client_secret' );
        register_setting( 'imembers_settings_group', 'imembers_google_client_id' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_public_key' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_secret_key' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_webhook_secret' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_price_id' );
        register_setting( 'imembers_settings_group', 'imembers_enable_stripe' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>iMembers 設定</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'imembers_settings_group' ); ?>
                <?php do_settings_sections( 'imembers_settings_group' ); ?>
                
                <h2>SNSログイン設定</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">LINE Client ID (Channel ID)</th>
                        <td><input type="text" name="imembers_line_client_id" value="<?php echo esc_attr( get_option('imembers_line_client_id') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">LINE Client Secret (Channel Secret)</th>
                        <td><input type="password" name="imembers_line_client_secret" value="<?php echo esc_attr( get_option('imembers_line_client_secret') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Google Client ID</th>
                        <td><input type="text" name="imembers_google_client_id" value="<?php echo esc_attr( get_option('imembers_google_client_id') ); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <h2>Stripe サブスクリプション設定</h2>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Stripe 決済機能を有効にする</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imembers_enable_stripe" value="1" <?php checked( 1, get_option( 'imembers_enable_stripe' ), true ); ?> />
                                有効
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">サブスクリプション 価格ID (Price ID)</th>
                        <td><input type="text" name="imembers_stripe_price_id" value="<?php echo esc_attr( get_option('imembers_stripe_price_id') ); ?>" class="regular-text" placeholder="price_..." /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">公開可能キー (Public Key)</th>
                        <td><input type="text" name="imembers_stripe_public_key" value="<?php echo esc_attr( get_option('imembers_stripe_public_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">シークレットキー (Secret Key)</th>
                        <td><input type="password" name="imembers_stripe_secret_key" value="<?php echo esc_attr( get_option('imembers_stripe_secret_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Webhook シークレット</th>
                        <td><input type="password" name="imembers_stripe_webhook_secret" value="<?php echo esc_attr( get_option('imembers_stripe_webhook_secret') ); ?>" class="regular-text" /></td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_manual_page() {
        ob_start();
        iMembers_Core::get_template( 'admin-manual' );
        echo ob_get_clean();
    }
}
