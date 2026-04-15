<?php
/**
 * Template: Teaser Notice (Members Only)
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="imembers-container">
    <div class="imembers-teaser-lock">
        <div class="imembers-lock-icon">
            <svg viewBox="0 0 24 24" style="width:48px;height:48px;fill:none;stroke:currentColor;stroke-width:2;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0110 0v4"></path></svg>
        </div>
        <h3 style="margin:0 0 10px; font-size:18px; text-align:center;">この続きは会員限定です</h3>
        <p style="margin:0 0 24px; color:var(--imembers-text-muted); font-size:14px;">続きを読むにはログインまたは会員登録が必要です。</p>
        <a href="<?php echo esc_url( $login_url ); ?>" class="imembers-btn imembers-btn-primary">ログイン / 新規登録</a>
    </div>
</div>
