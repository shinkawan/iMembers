<?php
/**
 * Authentication class for iMembers (OTP & SNS)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Auth {

    public function init() {
        // Shortcodes
        add_shortcode( 'imembers_login', array( $this, 'render_login_form' ) );
        add_shortcode( 'imembers_register', array( $this, 'render_register_form' ) );

        // AJAX handlers for OTP
        add_action( 'wp_ajax_nopriv_imembers_send_otp', array( $this, 'ajax_send_otp' ) );
        add_action( 'wp_ajax_nopriv_imembers_verify_otp', array( $this, 'ajax_verify_otp' ) );
    }

    public function render_login_form() {
        if ( is_user_logged_in() ) {
            return '<p>すでにログインしています。<a href="' . esc_url( wp_logout_url( home_url() ) ) . '">ログアウト</a></p>';
        }

        ob_start();
        iMembers_Core::get_template( 'auth-login-form' );
        return ob_get_clean();
    }

    public function render_register_form() {
        // Register and login process are the same for passwordless OTP
        return $this->render_login_form();
    }

    public function ajax_send_otp() {
        $email = sanitize_email( $_POST['email'] ?? '' );
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => '有効なメールアドレスを入力してください。' ) );
        }

        // Rate limit: 60 seconds between requests
        $limit_key = 'imembers_otp_limit_' . md5( $email );
        if ( get_transient( $limit_key ) ) {
            wp_send_json_error( array( 'message' => '短時間に何度も送信できません。しばらく待ってから再度お試しください。' ) );
        }

        // Generate 6 digit OTP
        $otp = wp_rand( 100000, 999999 );
        
        // Save to transient (valid for 10 mins)
        set_transient( 'imembers_otp_' . md5( $email ), $otp, 10 * MINUTE_IN_SECONDS );
        // Set rate limit (60 seconds)
        set_transient( $limit_key, '1', 60 );

        // Send Email
        $subject = '【iMembers】ログイン用の認証コード';
        $message = "以下の6桁の認証コードを入力してログインしてください。\n\n認証コード: {$otp}\n\n※このコードの有効期限は10分間です。";
        wp_mail( $email, $subject, $message );

        wp_send_json_success();
    }

    public function ajax_verify_otp() {
        $email = sanitize_email( $_POST['email'] ?? '' );
        $otp = sanitize_text_field( $_POST['otp'] ?? '' );

        if ( ! $email || ! $otp ) {
            wp_send_json_error( array( 'message' => '情報が不足しています。' ) );
        }

        $transient_key = 'imembers_otp_' . md5( $email );
        $saved_otp = get_transient( $transient_key );

        if ( ! $saved_otp || $saved_otp != $otp ) {
            wp_send_json_error( array( 'message' => '認証コードが正しくないか、有効期限が切れています。' ) );
        }

        // Clean up transient
        delete_transient( $transient_key );

        // Find or create user
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            // Register new user
            $username = explode( '@', $email )[0] . '_' . wp_rand( 100, 999 );
            $password = wp_generate_password( 12, true );
            $user_id = wp_create_user( $username, $password, $email );
            
            if ( is_wp_error( $user_id ) ) {
                wp_send_json_error( array( 'message' => 'ユーザーの作成に失敗しました。' ) );
            }
            $user = get_user_by( 'id', $user_id );
        }

        // Log the user in
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
        do_action( 'after_member_login', $user->ID ); // Custom hook for devs

        // Handle redirect
        $redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( $_GET['redirect_to'] ) : '';
        if ( ! $redirect_to ) {
            $mypage_id = get_option( 'imembers_page_imembers_mypage' );
            $redirect_to = $mypage_id ? get_permalink( $mypage_id ) : home_url();
        }

        wp_send_json_success( array( 'redirect' => $redirect_to ) );
    }
}
