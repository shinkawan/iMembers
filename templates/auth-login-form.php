<?php
/**
 * Template: Login Form
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="imembers-container imembers-auth-container" id="imembers-login-container">
    <div class="imembers-card">
        <h3 style="margin-top:0; margin-bottom:20px; text-align:center;">ログイン / 新規登録</h3>
        
        <div class="imembers-email-auth">
            <p style="font-size:14px; color:var(--imembers-text-muted); margin-bottom:15px;">メールアドレスを入力してください。認証コードを送信します。</p>
            <input type="email" id="imembers-email" class="imembers-input" placeholder="example@example.com" required />
            <button type="button" id="imembers-send-otp-btn" class="imembers-btn imembers-btn-primary" style="width:100%;">認証コードを送信</button>
            <div id="imembers-email-message" class="imembers-message"></div>
        </div>

        <div class="imembers-otp-auth" id="imembers-otp-section" style="display:none; margin-top: 24px; padding-top:24px; border-top:1px solid var(--imembers-border);">
            <p style="font-size:14px; margin-bottom:15px;">届いた6桁の認証コードを入力してください。</p>
            <input type="text" id="imembers-otp" class="imembers-input" placeholder="123456" maxlength="6" required />
            <button type="button" id="imembers-verify-otp-btn" class="imembers-btn imembers-btn-primary" style="width:100%;">ログイン</button>
            <div id="imembers-otp-message" class="imembers-message"></div>
        </div>

        <div style="margin: 24px 0; text-align: center; position: relative;">
            <hr style="border:0; border-top:1px solid var(--imembers-border);">
            <span style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: #fff; padding: 0 10px; color: var(--imembers-text-muted); font-size: 12px;">または</span>
        </div>

        <div class="imembers-sns-auth">
            <p style="font-size:14px; text-align:center; margin-bottom:15px; color:var(--imembers-text-muted);">SNSでログイン</p>
            <div class="imembers-sns-grid">
                <button type="button" class="imembers-sns-btn imembers-sns-btn-line" data-provider="line">
                    <svg style="width:20px;height:20px;fill:currentColor;" viewBox="0 0 24 24"><path d="M24 10.3c0-4.5-5.4-8.3-12-8.3S0 5.8 0 10.3c0 4.1 4.3 7.5 10 8.2.4.1.9.3 1.1.7l1 1.7c.3.5.2.6-.1.2l-1.1-1.6c-.2-.4-.6-.9-1.1-1-.1 0-.1 0-.2 0-3.9-.3-7.1-2.9-7.1-6.2 0-3.4 4-6.2 9-6.2s9 2.8 9 6.2c0 3.1-2.9 5.8-6.9 6.2-.4 0-.4.1-.4.3 0 .1.1.2.2.3 4.1-.1 7.4-2.9 7.6-6z"/></svg>
                    LINEでログイン
                </button>
                <button type="button" class="imembers-sns-btn imembers-sns-btn-google" data-provider="google">
                    <svg style="width:20px;height:20px;" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                    Googleでログイン
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var sendBtn = document.getElementById('imembers-send-otp-btn');
    var verifyBtn = document.getElementById('imembers-verify-otp-btn');
    var snsBtns = document.querySelectorAll('.imembers-sns-btn');

    function showMessage(id, text, type) {
        var el = document.getElementById(id);
        el.innerText = text;
        el.className = 'imembers-message imembers-message-' + type;
        el.style.display = 'block';
    }

    snsBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var provider = this.getAttribute('data-provider');
            var originalText = this.innerHTML;
            this.innerText = 'リダイレクト中...';
            this.disabled = true;

            fetch(imembers_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=imembers_sns_login&provider=' + encodeURIComponent(provider) + '&nonce=' + imembers_ajax.nonce
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                if(data.success) {
                    window.location.href = data.data.redirect;
                } else {
                    alert(data.data.message);
                    this.innerHTML = originalText;
                }
            });
        });
    });
    
    if(sendBtn) {
        sendBtn.addEventListener('click', function() {
            var email = document.getElementById('imembers-email').value;
            if(!email) {
                showMessage('imembers-email-message', 'メールアドレスを入力してください。', 'error');
                return;
            }
            sendBtn.disabled = true;
            showMessage('imembers-email-message', '送信中...', 'success');

            fetch(imembers_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=imembers_send_otp&email=' + encodeURIComponent(email) + '&nonce=' + imembers_ajax.nonce
            })
            .then(res => res.json())
            .then(data => {
                sendBtn.disabled = false;
                if(data.success) {
                    showMessage('imembers-email-message', '送信しました。メールをご確認ください。', 'success');
                    document.getElementById('imembers-otp-section').style.display = 'block';
                } else {
                    showMessage('imembers-email-message', data.data.message || 'エラーが発生しました。', 'error');
                }
            });
        });
    }

    if(verifyBtn) {
        verifyBtn.addEventListener('click', function() {
            var email = document.getElementById('imembers-email').value;
            var otp = document.getElementById('imembers-otp').value;
            if(!otp) {
                showMessage('imembers-otp-message', 'コードを入力してください。', 'error');
                return;
            }
            verifyBtn.disabled = true;
            showMessage('imembers-otp-message', '確認中...', 'success');

            fetch(imembers_ajax.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=imembers_verify_otp&email=' + encodeURIComponent(email) + '&otp=' + encodeURIComponent(otp) + '&nonce=' + imembers_ajax.nonce
            })
            .then(res => res.json())
            .then(data => {
                verifyBtn.disabled = false;
                if(data.success) {
                    window.location.href = data.data.redirect || '<?php echo esc_url(home_url()); ?>';
                } else {
                    showMessage('imembers-otp-message', data.data.message || 'コードが正しくありません。', 'error');
                }
            });
        });
    }
});
</script>
