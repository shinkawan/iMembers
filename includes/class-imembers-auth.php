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
        ?>
        <div class="imembers-auth-container" id="imembers-login-container">
            <h3>ログイン / 新規登録</h3>
            
            <div class="imembers-email-auth">
                <p>メールアドレスを入力してください。6桁の認証コードを送信します。</p>
                <input type="email" id="imembers-email" placeholder="example@example.com" required />
                <button type="button" id="imembers-send-otp-btn">認証コードを送信</button>
                <div id="imembers-email-message" style="color:red; margin-top:5px;"></div>
            </div>

            <div class="imembers-otp-auth" id="imembers-otp-section" style="display:none; margin-top: 20px;">
                <p>届いた6桁の認証コードを入力してください。</p>
                <input type="text" id="imembers-otp" placeholder="123456" maxlength="6" required />
                <button type="button" id="imembers-verify-otp-btn">ログイン</button>
                <div id="imembers-otp-message" style="color:red; margin-top:5px;"></div>
            </div>

            <hr style="margin: 20px 0;">

            <div class="imembers-sns-auth">
                <p>SNSでログイン</p>
                <button type="button" class="imembers-sns-btn" data-provider="line" style="background:#06C755; color:#fff; border:none; padding:10px; width:100%; margin-bottom:10px; cursor:pointer;">LINEでログイン</button>
                <button type="button" class="imembers-sns-btn" data-provider="google" style="background:#4285F4; color:#fff; border:none; padding:10px; width:100%; cursor:pointer;">Googleでログイン</button>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sendBtn = document.getElementById('imembers-send-otp-btn');
            var verifyBtn = document.getElementById('imembers-verify-otp-btn');
            var snsBtns = document.querySelectorAll('.imembers-sns-btn');

            snsBtns.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var provider = this.getAttribute('data-provider');
                    this.innerText = 'リダイレクト中...';
                    this.disabled = true;

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=imembers_sns_login&provider=' + encodeURIComponent(provider)
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.disabled = false;
                        if(data.success) {
                            window.location.href = data.data.redirect;
                        } else {
                            alert(data.data.message);
                            this.innerText = provider === 'line' ? 'LINEでログイン' : 'Googleでログイン';
                        }
                    });
                });
            });
            
            if(sendBtn) {
                sendBtn.addEventListener('click', function() {
                    var email = document.getElementById('imembers-email').value;
                    if(!email) {
                        document.getElementById('imembers-email-message').innerText = 'メールアドレスを入力してください。';
                        return;
                    }
                    sendBtn.disabled = true;
                    document.getElementById('imembers-email-message').innerText = '送信中...';

                    // AJAX request
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=imembers_send_otp&email=' + encodeURIComponent(email)
                    })
                    .then(res => res.json())
                    .then(data => {
                        sendBtn.disabled = false;
                        if(data.success) {
                            document.getElementById('imembers-email-message').style.color = 'green';
                            document.getElementById('imembers-email-message').innerText = '送信しました。メールをご確認ください。';
                            document.getElementById('imembers-otp-section').style.display = 'block';
                        } else {
                            document.getElementById('imembers-email-message').innerText = data.data.message || 'エラーが発生しました。';
                        }
                    });
                });
            }

            if(verifyBtn) {
                verifyBtn.addEventListener('click', function() {
                    var email = document.getElementById('imembers-email').value;
                    var otp = document.getElementById('imembers-otp').value;
                    if(!otp) {
                        document.getElementById('imembers-otp-message').innerText = 'コードを入力してください。';
                        return;
                    }
                    verifyBtn.disabled = true;
                    document.getElementById('imembers-otp-message').innerText = '確認中...';

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=imembers_verify_otp&email=' + encodeURIComponent(email) + '&otp=' + encodeURIComponent(otp)
                    })
                    .then(res => res.json())
                    .then(data => {
                        verifyBtn.disabled = false;
                        if(data.success) {
                            window.location.href = data.data.redirect || '<?php echo esc_url(home_url()); ?>';
                        } else {
                            document.getElementById('imembers-otp-message').innerText = data.data.message || 'コードが正しくありません。';
                        }
                    });
                });
            }
        });
        </script>
        <?php
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

        // Generate 6 digit OTP
        $otp = wp_rand( 100000, 999999 );
        
        // Save to transient (valid for 10 mins)
        set_transient( 'imembers_otp_' . md5( $email ), $otp, 10 * MINUTE_IN_SECONDS );

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
