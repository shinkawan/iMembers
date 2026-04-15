<?php
/**
 * Template: My Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="imembers-mypage">
    <h2>こんにちは、<?php echo esc_html( $user->display_name ); ?> さん</h2>
    <p>メールアドレス: <?php echo esc_html( $user->user_email ); ?></p>
    <p><a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">ログアウト</a></p>

    <div class="imembers-section" style="margin-top: 30px;">
        <h3>お気に入り一覧</h3>
        <?php if ( ! empty( $favorites ) ) : ?>
            <ul class="imembers-list">
                <?php foreach ( $favorites as $pid ) : ?>
                    <li><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>お気に入りはまだありません。気になる記事の★ボタンを押してみましょう！</p>
            <p><a href="<?php echo esc_url( home_url() ); ?>" class="button">記事を読みに行く</a></p>
        <?php endif; ?>
    </div>

    <div class="imembers-section" style="margin-top: 30px;">
        <h3>閲覧履歴</h3>
        <?php if ( ! empty( $history ) ) : ?>
            <ul class="imembers-list">
                <?php foreach ( $history as $pid ) : ?>
                    <li><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <p>履歴はありません。</p>
        <?php endif; ?>
    </div>

    <?php do_action( 'imembers_mypage_subscription_section' ); ?>
</div>
