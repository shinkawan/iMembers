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
        register_setting( 'imembers_settings_group', 'imembers_google_client_secret' );
        register_setting( 'imembers_settings_group', 'imembers_enable_line_login' );
        register_setting( 'imembers_settings_group', 'imembers_enable_google_login' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_public_key' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_secret_key' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_webhook_secret' );
        register_setting( 'imembers_settings_group', 'imembers_stripe_price_id' );
        register_setting( 'imembers_settings_group', 'imembers_enable_stripe' );
        register_setting( 'imembers_settings_group', 'imembers_restricted_archives' ); // Array of post type names
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
                        <th scope="row">LINEログイン を有効にする</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imembers_enable_line_login" value="1" <?php checked( 1, get_option( 'imembers_enable_line_login' ), true ); ?> />
                                有効
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">LINE Client ID (Channel ID)</th>
                        <td><input type="text" name="imembers_line_client_id" value="<?php echo esc_attr( get_option('imembers_line_client_id') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">LINE Client Secret (Channel Secret)</th>
                        <td><input type="password" name="imembers_line_client_secret" value="<?php echo esc_attr( get_option('imembers_line_client_secret') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Googleログイン を有効にする</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imembers_enable_google_login" value="1" <?php checked( 1, get_option( 'imembers_enable_google_login' ), true ); ?> />
                                有効
                            </label>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Google Client ID</th>
                        <td><input type="text" name="imembers_google_client_id" value="<?php echo esc_attr( get_option('imembers_google_client_id') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Google Client Secret</th>
                        <td><input type="password" name="imembers_google_client_secret" value="<?php echo esc_attr( get_option('imembers_google_client_secret') ); ?>" class="regular-text" /></td>
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

                <h2>アーカイブ閲覧制限設定</h2>
                <p>チェックを入れたページのアーカイブ（一覧）ページは会員限定になります。※個別のカテゴリー制限はカテゴリー編集画面で行ってください。</p>
                <table class="form-table">
                    <?php
                    $restricted_archives = get_option( 'imembers_restricted_archives', array() );
                    if ( ! is_array( $restricted_archives ) ) $restricted_archives = array();

                    // Get post types that have archives
                    $post_types = get_post_types( array( 'public' => true, 'has_archive' => true ), 'objects' );
                    ?>
                    <tr valign="top">
                        <th scope="row">ブログ投稿一覧 (HOME / 投稿記事一覧)</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imembers_restricted_archives[]" value="home" <?php checked( in_array( 'home', $restricted_archives ) ); ?> />
                                制限する
                            </label>
                        </td>
                    </tr>
                    <?php foreach ( $post_types as $pt ) : ?>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html( $pt->label ); ?> アーカイブ</th>
                        <td>
                            <label>
                                <input type="checkbox" name="imembers_restricted_archives[]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $restricted_archives ) ); ?> />
                                制限する (<code>/<?php echo esc_html( $pt->name ); ?>/</code>)
                            </label>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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
