<?php
/**
 * Template: My Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="imembers-container imembers-mypage imembers-animate-fade-in">
    <!-- Premium Profile Header -->
    <div class="imembers-profile-header">
        <div class="imembers-avatar-circle">
            <?php echo esc_html( mb_substr( $user->display_name, 0, 1 ) ); ?>
        </div>
        <div class="imembers-profile-info">
            <h2 style="margin:0; font-size:24px; font-weight:700;">こんにちは、<?php echo esc_html( $user->display_name ); ?> さん</h2>
            <p style="margin:4px 0 0; opacity:0.9; font-size:14px;"><?php echo esc_html( $user->user_email ); ?></p>
            <p style="margin:12px 0 0; font-size:13px;"><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:rgba(255,255,255,0.8); text-decoration:underline;">ログアウト</a></p>
        </div>
    </div>

    <div class="imembers-section" style="margin-top: 40px;">
        <h3 style="margin-bottom:20px; font-size:20px; font-weight:700; display:flex; align-items:center; gap:10px;">
            <div class="imembers-activity-icon-box" style="margin-bottom:0;">
                <svg style="width:20px;height:20px;fill:currentColor;" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
            </div>
            お気に入り一覧
        </h3>
        <?php if ( ! empty( $favorites ) ) : ?>
            <div class="imembers-activity-grid">
                <?php foreach ( $favorites as $pid ) : ?>
                    <div class="imembers-activity-item">
                        <h4 style="margin:0; font-size:15px; font-weight:600;"><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" style="color:var(--imembers-text); text-decoration:none;"><?php echo esc_html( get_the_title( $pid ) ); ?></a></h4>
                        <p style="margin:8px 0 0; font-size:12px; color:var(--imembers-text-muted);">お気に入り登録済み</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div style="text-align:center; padding:48px 24px; background:var(--imembers-bg); border-radius:var(--imembers-radius); border: 2px dashed var(--imembers-border);">
                <p style="color:var(--imembers-text-muted); font-size:15px;">お気に入り登録した記事はまだありません。</p>
                <a href="<?php echo esc_url( home_url() ); ?>" class="imembers-btn imembers-btn-outline" style="margin-top:20px;">コンテンツを探す</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="imembers-section" style="margin-top: 48px;">
        <h3 style="margin-bottom:20px; font-size:20px; font-weight:700; display:flex; align-items:center; gap:10px;">
            <div class="imembers-activity-icon-box" style="margin-bottom:0;">
                <svg style="width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:2;" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            最近チェックした記事
        </h3>
        <?php if ( ! empty( $history ) ) : ?>
            <div class="imembers-activity-grid">
                <?php foreach ( $history as $pid ) : ?>
                    <div class="imembers-activity-item">
                        <h4 style="margin:0; font-size:15px; font-weight:600;"><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" style="color:var(--imembers-text); text-decoration:none;"><?php echo esc_html( get_the_title( $pid ) ); ?></a></h4>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p style="color:var(--imembers-text-muted); font-size:15px; padding-left: 10px;">まだ履歴はありません。</p>
        <?php endif; ?>
    </div>

    <div style="margin-top:48px; border-top:1px solid var(--imembers-border); padding-top:48px;">
        <?php do_action( 'imembers_mypage_subscription_section' ); ?>
    </div>
</div>
