<?php
/**
 * Content Restriction class for iMembers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Content_Restriction {

    public function init() {
        // Meta box for post/page restriction
        add_action( 'add_meta_boxes', array( $this, 'add_restriction_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_restriction_meta_box' ) );

        // Shortcode for parsing members_only content
        add_shortcode( 'members_only', array( $this, 'members_only_shortcode' ) );
        add_shortcode( 'imembers_download', array( $this, 'download_shortcode' ) );

        // Template redirect for checking access
        add_action( 'template_redirect', array( $this, 'check_access' ) );
    }

    public function add_restriction_meta_box() {
        $screens = array( 'post', 'page' ); // Add to posts and pages
        foreach ( $screens as $screen ) {
            add_meta_box(
                'imembers_restriction_box',
                'iMembers 閲覧制限',
                array( $this, 'render_restriction_meta_box' ),
                $screen,
                'side',
                'high'
            );
        }
    }

    public function render_restriction_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'imembers_restriction_nonce_action', 'imembers_restriction_nonce' );

        $is_restricted = get_post_meta( $post->ID, '_imembers_is_restricted', true );
        ?>
        <label>
            <input type="checkbox" name="imembers_is_restricted" value="1" <?php checked( $is_restricted, '1' ); ?> />
            このページを会員限定にする
        </label>
        <?php
    }

    public function save_restriction_meta_box( $post_id ) {
        // Check nonce
        if ( ! isset( $_POST['imembers_restriction_nonce'] ) || ! wp_verify_nonce( $_POST['imembers_restriction_nonce'], 'imembers_restriction_nonce_action' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }
        }

        // Save restriction flag
        if ( isset( $_POST['imembers_is_restricted'] ) ) {
            update_post_meta( $post_id, '_imembers_is_restricted', '1' );
        } else {
            delete_post_meta( $post_id, '_imembers_is_restricted' );
        }
    }

    public function check_access() {
        if ( is_singular() ) {
            $post_id = get_queried_object_id();
            $is_restricted = get_post_meta( $post_id, '_imembers_is_restricted', true );

            if ( $is_restricted === '1' && ! is_user_logged_in() ) {
                $this->redirect_to_login();
            }
        }
    }

    private function redirect_to_login() {
        // Determine login page ID or slug. We set it in installer to imembers-login
        $login_page_id = get_option( 'imembers_page_imembers_login' );
        $login_url = wp_login_url(); // Default
        
        if ( $login_page_id ) {
            $login_url = get_permalink( $login_page_id );
        }

        wp_redirect( add_query_arg( 'redirect_to', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), $login_url ) );
        exit;
    }

    public function members_only_shortcode( $atts, $content = null ) {
        if ( is_user_logged_in() ) {
            return do_shortcode( $content );
        } else {
            // Suggest to login
            $login_page_id = get_option( 'imembers_page_imembers_login' );
            $login_url = $login_page_id ? get_permalink( $login_page_id ) : wp_login_url();
            
            return '<div class="imembers-teaser-notice" style="padding: 15px; border: 1px solid #ddd; background: #fafafa; margin: 20px 0; text-align: center;">' . 
                   '<p><strong>この続きは会員限定です。</strong></p>' . 
                   '<p>続きを読むには<a href="' . esc_url( $login_url ) . '">ログイン</a>または会員登録が必要です。</p>' . 
                   '</div>';
        }
    }

    public function download_shortcode( $atts ) {
        $a = shortcode_atts( array(
            'url'   => '',
            'label' => 'ファイルをダウンロード',
        ), $atts );

        if ( ! is_user_logged_in() ) {
            return '<div class="imembers-download-block" style="padding:10px; border:1px dashed #ccc; background:#f9f9f9; text-align:center;">' .
                   '<p>ダウンロードは会員限定です。</p>' .
                   '</div>';
        }

        // Potential addition: check for PRO subscriber status if needed
        // $user_id = get_current_user_id();
        // $is_pro = get_user_meta( $user_id, '_imembers_pro_subscriber', true );

        return '<div class="imembers-download-block" style="padding:15px; border:1px solid #0073aa; background:#f0f6fb; text-align:center;">' .
               '<a href="' . esc_url( $a['url'] ) . '" class="button button-primary" download>' . esc_html( $a['label'] ) . '</a>' .
               '</div>';
    }
}
