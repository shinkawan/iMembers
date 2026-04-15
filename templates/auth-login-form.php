<?php
/**
 * Template: Login Form
 */
if ( ! defined( 'ABSPATH' ) ) exit;
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
