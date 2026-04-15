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
        // [Stub] Exchange code for token, fetch profile, login user.
        // For MVP, we just redirect back with an error stating it needs full config.
        wp_die( 'LINEログイン連携のバックエンド処理が未完了、もしくは動作テスト環境です。本来はここでトークンを交換しユーザーを自動登録・ログインさせます。<br><a href="'.home_url().'">戻る</a>' );
    }

    private function process_google_callback( $code ) {
        // [Stub] Exchange code for token, fetch profile, login user.
        wp_die( 'Googleログイン連携のバックエンド処理が未完了、もしくは動作テスト環境です。<br><a href="'.home_url().'">戻る</a>' );
    }
}
