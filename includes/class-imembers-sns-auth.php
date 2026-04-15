<?php
/**
 * SNS Authentication class for iMembers (LINE & Google)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_SNS_Auth {

    public function init() {
        add_action( 'init', array( $this, 'handle_sns_callbacks' ) );
        add_action( 'wp_ajax_nopriv_imembers_sns_login', array( $this, 'ajax_sns_login_redirect' ) );
    }

    public function ajax_sns_login_redirect() {
        check_ajax_referer( 'imembers_ajax_nonce', 'nonce' );

        $provider = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';
        
        if ( $provider === 'line' ) {
            $client_id = get_option( 'imembers_line_client_id' );
            if ( ! $client_id ) {
                wp_send_json_error( array( 'message' => 'LINEログインが設定されていません。' ) );
            }
            $redirect_uri = urlencode( home_url( '/?imembers_sns=line' ) );
            $state = wp_create_nonce( 'imembers_sns_line' );
            $url = "https://access.line.me/oauth2/v2.1/authorize?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&state={$state}&scope=profile%20openid%20email";
            wp_send_json_success( array( 'redirect' => $url ) );
        } elseif ( $provider === 'google' ) {
            $client_id = get_option( 'imembers_google_client_id' );
            if ( ! $client_id ) {
                wp_send_json_error( array( 'message' => 'Googleログインが設定されていません。' ) );
            }
            $redirect_uri = urlencode( home_url( '/?imembers_sns=google' ) );
            $state = wp_create_nonce( 'imembers_sns_google' );
            $url = "https://accounts.google.com/o/oauth2/v2/auth?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}&state={$state}&scope=email%20profile";
            wp_send_json_success( array( 'redirect' => $url ) );
        }

        wp_send_json_error( array( 'message' => '無効なプロバイダです。' ) );
    }

    public function handle_sns_callbacks() {
        if ( isset( $_GET['imembers_sns'] ) ) {
            $provider = sanitize_text_field( $_GET['imembers_sns'] );
            $code = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : '';
            $state = isset( $_GET['state'] ) ? sanitize_text_field( $_GET['state'] ) : '';

            if ( ! $code || ! wp_verify_nonce( $state, 'imembers_sns_' . $provider ) ) {
                wp_die( '無効なリクエストです。' );
            }

            if ( $provider === 'line' ) {
                $this->process_line_callback( $code );
            } elseif ( $provider === 'google' ) {
                $this->process_google_callback( $code );
            }
        }
    }

    private function process_line_callback( $code ) {
        // Exchange code for token
        $client_id = get_option( 'imembers_line_client_id' );
        $client_secret = get_option( 'imembers_line_client_secret' );
        $redirect_uri = home_url( '/?imembers_sns=line' );

        $response = wp_remote_post( 'https://api.line.me/oauth2/v2.1/token', array(
            'body' => array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => $redirect_uri,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_die( 'LINEとの通信に失敗しました。' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! isset( $body['id_token'] ) ) {
            wp_die( 'LINE認証に失敗しました。' );
        }

        // Decode ID Token (JWT) to get email
        $parts = explode( '.', $body['id_token'] );
        if ( count( $parts ) < 2 ) {
            wp_die( '無効なIDトークンです。' );
        }
        $payload = json_decode( base64_decode( strtr( $parts[1], '-_', '+/' ) ), true );

        $email = $payload['email'] ?? '';
        $name = $payload['name'] ?? 'LINE User';
        $sns_id = $payload['sub'] ?? '';

        if ( ! $email ) {
            wp_die( 'LINEからメールアドレスを取得できませんでした。LINE側の設定でメールアドレスの取得を許可してください。' );
        }

        $this->login_or_register_sns_user( $email, $name, 'line', $sns_id );
    }

    private function process_google_callback( $code ) {
        $client_id = get_option( 'imembers_google_client_id' );
        $client_secret = get_option( 'imembers_google_client_secret' );
        $redirect_uri = home_url( '/?imembers_sns=google' );

        // Exchange code for token
        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code'          => $code,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_die( 'Googleとの通信に失敗しました。' );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $access_token = $body['access_token'] ?? '';

        if ( ! $access_token ) {
            wp_die( 'Google認証に失敗しました。' );
        }

        // Fetch user info
        $info_response = wp_remote_get( 'https://www.googleapis.com/oauth2/v3/userinfo?access_token=' . $access_token );
        $user_info = json_decode( wp_remote_retrieve_body( $info_response ), true );

        $email = $user_info['email'] ?? '';
        $name = $user_info['name'] ?? 'Google User';
        $sns_id = $user_info['sub'] ?? '';

        if ( ! $email ) {
            wp_die( 'Googleからメールアドレスを取得できませんでした。' );
        }

        $this->login_or_register_sns_user( $email, $name, 'google', $sns_id );
    }

    private function login_or_register_sns_user( $email, $name, $provider, $sns_id ) {
        $user = get_user_by( 'email', $email );

        if ( ! $user ) {
            // Create new user
            $username = $provider . '_' . wp_rand( 1000, 9999 );
            $password = wp_generate_password( 16, true );
            $user_id = wp_create_user( $username, $password, $email );

            if ( is_wp_error( $user_id ) ) {
                wp_die( 'ユーザーの作成に失敗しました。' );
            }

            $user = get_user_by( 'id', $user_id );
            
            // Update display name
            wp_update_user( array(
                'ID' => $user->ID,
                'display_name' => $name,
            ) );
        }

        // Save SNS metadata
        update_user_meta( $user->ID, '_imembers_sns_provider', $provider );
        update_user_meta( $user->ID, '_imembers_sns_id', $sns_id );

        // Login
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );
        do_action( 'after_member_login', $user->ID );

        // Redirect to My Page
        $mypage_id = get_option( 'imembers_page_imembers_mypage' );
        $redirect_to = $mypage_id ? get_permalink( $mypage_id ) : home_url();

        wp_redirect( $redirect_to );
        exit;
    }
}
