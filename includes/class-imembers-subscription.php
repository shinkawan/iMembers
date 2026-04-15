<?php
/**
 * Subscription class for iMembers (Stripe Integration)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class iMembers_Subscription {

    public function init() {
        // AJAX for Checkout
        add_action( 'wp_ajax_imembers_create_checkout_session', array( $this, 'ajax_create_checkout_session' ) );
        add_action( 'wp_ajax_imembers_create_portal_session', array( $this, 'ajax_create_portal_session' ) );

        // Webhook handler
        add_action( 'init', array( $this, 'handle_stripe_webhook' ) );

        // Add subscription section to My Page
        add_action( 'imembers_mypage_subscription_section', array( $this, 'render_mypage_subscription' ) );
    }

    private function get_stripe_client() {
        $secret_key = get_option( 'imembers_stripe_secret_key' );
        if ( ! $secret_key ) return null;

        // Check if SimpleEC or another plugin already loaded Stripe SDK.
        // For iMembers, we might want to include our own or use a common one.
        // For simplicity in this environment, we'll use wp_remote_post to avoid dependency issues with the SDK.
        return $secret_key;
    }

    /**
     * Create Stripe Checkout Session (Subscription)
     */
    public function ajax_create_checkout_session() {
        check_ajax_referer( 'imembers_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'ログインが必要です。' ) );
        }

        $secret_key = $this->get_stripe_client();
        if ( ! $secret_key ) {
            wp_send_json_error( array( 'message' => 'Stripeの設定が完了していません。' ) );
        }

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        
        $price_id = get_option( 'imembers_stripe_price_id' );
        if ( ! $price_id ) {
            wp_send_json_error( array( 'message' => '価格IDが設定されていません。管理画面で設定してください。' ) );
        }

        $response = wp_remote_post( 'https://api.stripe.com/v1/checkout/sessions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query( array(
                'payment_method_types' => array( 'card' ),
                'line_items' => array(
                    array(
                        'price'    => $price_id,
                        'quantity' => 1,
                    ),
                ),
                'mode' => 'subscription',
                'customer_email' => $user->user_email,
                'success_url' => home_url( '/imembers-mypage/?payment=success' ),
                'cancel_url'  => home_url( '/imembers-mypage/?payment=cancel' ),
                'metadata' => array(
                    'user_id' => $user_id,
                ),
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Stripeとの通信に失敗しました。' ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['url'] ) ) {
            wp_send_json_success( array( 'url' => $body['url'] ) );
        } else {
            wp_send_json_error( array( 'message' => $body['error']['message'] ?? 'セッション作成に失敗しました。' ) );
        }
    }

    /**
     * Create Stripe Customer Portal Session
     */
    public function ajax_create_portal_session() {
        check_ajax_referer( 'imembers_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error();
        }

        $secret_key = $this->get_stripe_client();
        $user_id = get_current_user_id();
        $customer_id = get_user_meta( $user_id, '_imembers_stripe_customer_id', true );

        if ( ! $customer_id ) {
            wp_send_json_error( array( 'message' => '決済履歴が見つかりません。' ) );
        }

        $response = wp_remote_post( 'https://api.stripe.com/v1/billing_portal/sessions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => http_build_query( array(
                'customer'   => $customer_id,
                'return_url' => home_url( '/imembers-mypage/' ),
            ) ),
        ) );

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['url'] ) ) {
            wp_send_json_success( array( 'url' => $body['url'] ) );
        } else {
            wp_send_json_error( array( 'message' => 'ポータル作成に失敗しました。' ) );
        }
    }

    /**
     * Handle Stripe Webhooks
     */
    public function handle_stripe_webhook() {
        if ( ! isset( $_GET['imembers_webhook'] ) || $_GET['imembers_webhook'] !== 'stripe' ) {
            return;
        }

        $payload = file_get_contents( 'php://input' );
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpoint_secret = get_option( 'imembers_stripe_webhook_secret' );

        // Verification logic
        if ( ! $this->verify_stripe_signature( $payload, $sig_header, $endpoint_secret ) ) {
            status_header( 403 );
            exit;
        }

        $event = json_decode( $payload, true );
        if ( ! $event ) {
            status_header( 400 );
            exit;
        }

        switch ( $event['type'] ) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $user_id = $session['metadata']['user_id'] ?? 0;
                if ( $user_id ) {
                    update_user_meta( $user_id, '_imembers_pro_subscriber', '1' );
                    update_user_meta( $user_id, '_imembers_stripe_customer_id', $session['customer'] );
                }
                break;
            
            case 'customer.subscription.deleted':
            case 'invoice.payment_failed':
            case 'customer.subscription.updated':
                $subscription = $event['data']['object'];
                $status = $subscription['status'];
                $customer_id = $subscription['customer'];

                $user = get_users( array(
                    'meta_key'   => '_imembers_stripe_customer_id',
                    'meta_value' => $customer_id,
                    'number'     => 1,
                ) );

                if ( ! empty( $user ) ) {
                    $user_id = $user[0]->ID;
                    if ( $status === 'active' || $status === 'trialing' ) {
                        update_user_meta( $user_id, '_imembers_pro_subscriber', '1' );
                    } else {
                        delete_user_meta( $user_id, '_imembers_pro_subscriber' );
                    }
                }
                break;
        }

        status_header( 200 );
        exit;
    }

    /**
     * Verify Stripe Webhook Signature
     */
    private function verify_stripe_signature( $payload, $sig_header, $secret ) {
        if ( ! $sig_header || ! $secret ) {
            return false;
        }

        // Parse the sig_header
        $pairs = explode( ',', $sig_header );
        $t = '';
        $v1 = '';
        foreach ( $pairs as $pair ) {
            $parts = explode( '=', $pair, 2 );
            if ( count( $parts ) === 2 ) {
                if ( trim( $parts[0] ) === 't' ) $t = $parts[1];
                if ( trim( $parts[0] ) === 'v1' ) $v1 = $parts[1];
            }
        }

        if ( ! $t || ! $v1 ) return false;

        // Check timestamp (within 5 minutes)
        if ( abs( time() - intval( $t ) ) > 300 ) {
            return false;
        }

        $signed_payload = $t . '.' . $payload;
        $expected_sig = hash_hmac( 'sha256', $signed_payload, $secret );

        return hash_equals( $expected_sig, $v1 );
    }

    /**
     * Render Subscription UI in My Page
     */
    public function render_mypage_subscription() {
        if ( ! get_option( 'imembers_enable_stripe' ) ) return;

        $user_id = get_current_user_id();
        $is_pro = get_user_meta( $user_id, '_imembers_pro_subscriber', true );
        ?>
        <div class="imembers-section" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;">
            <h3>プラン・購読管理</h3>
            <?php if ( $is_pro ) : ?>
                <p style="color: #16a34a; font-weight: 600; margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                    <svg style="width:18px;height:18px;fill:currentColor;" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    有料会員（サブスクリプション有効）
                </p>
                <button type="button" id="imembers-portal-btn" class="imembers-btn imembers-btn-outline">カード変更・解約手続きへ</button>
            <?php else : ?>
                <p style="margin-bottom:15px;">現在は無料会員です。有料プランに登録すると全てのコンテンツを閲覧できます。</p>
                <button type="button" id="imembers-checkout-btn" class="imembers-btn imembers-btn-primary">有料プランに登録する</button>
            <?php endif; ?>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var checkoutBtn = document.getElementById('imembers-checkout-btn');
            var portalBtn = document.getElementById('imembers-portal-btn');

            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function() {
                    checkoutBtn.disabled = true;
                    checkoutBtn.innerText = 'リダイレクト中...';
                    jQuery.post(imembers_ajax.url, { 
                        action: 'imembers_create_checkout_session',
                        nonce: imembers_ajax.nonce 
                    }, function(res) {
                        if (res.success) {
                            window.location.href = res.data.url;
                        } else {
                            alert(res.data.message);
                            checkoutBtn.disabled = false;
                            checkoutBtn.innerText = '有料プランに登録する';
                        }
                    });
                });
            }

            if (portalBtn) {
                portalBtn.addEventListener('click', function() {
                    portalBtn.disabled = true;
                    jQuery.post(imembers_ajax.url, { 
                        action: 'imembers_create_portal_session',
                        nonce: imembers_ajax.nonce 
                    }, function(res) {
                        if (res.success) {
                            window.location.href = res.data.url;
                        } else {
                            alert(res.data.message);
                            portalBtn.disabled = false;
                        }
                    });
                });
            }
        });
        </script>
        <?php
    }
}
