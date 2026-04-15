<?php
/**
 * Template: Admin Manual Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap imembers-admin-manual">
    <h1>iMembers セットアップ・運用マニュアル</h1>

    <div class="notice notice-info" style="margin-top: 20px;">
        <p>このページでは iMembers プラグインの初期設定と運用方法について解説します。</p>
    </div>

    <div class="imembers-manual-content" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; margin-top: 20px; max-width: 1000px; line-height: 1.6;">
        
        <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px;">1. コンテンツの閲覧制限方法</h2>
        <p>iMembersでは「ページ単位」「カテゴリー単位」「記事の一部（チラ見せ）」の3種類の制限が可能です。</p>
        <ul>
            <li><strong>ページ・投稿単位:</strong> 編集画面の右サイドバー「iMembers 閲覧制限」のチェックを入れます。</li>
            <li><strong>カテゴリー単位:</strong> 「カテゴリー編集」画面で「このカテゴリー全体を会員限定にする」にチェックを入れます。</li>
            <li><strong>チラ見せ（部分制限）:</strong> 本文中で <code>[members_only] ... [/members_only]</code> ショートコードを使用します。</li>
        </ul>

        <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 40px;">2. SNSログイン連携の設定</h2>
        <p>「iMembers 設定」画面に各情報を入力してください。</p>
        
        <h3 style="color: #0073aa;">■ LINEログイン</h3>
        <ol>
            <li><a href="https://developers.line.biz/" target="_blank">LINE Developers</a> で「LINEログイン」チャネルを作成。</li>
            <li>Channel ID / Secret を入力。</li>
            <li>LINE側の「コールバックURL」に以下を設定：<br>
                <code><?php echo esc_url( home_url( '/?imembers_sns=line' ) ); ?></code>
            </li>
        </ol>

        <h3 style="color: #0073aa;">■ Googleログイン</h3>
        <ol>
            <li><a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a> でOAuthクライアントIDを作成。</li>
            <li>クライアントIDを入力。</li>
            <li>Google側の「承認済みのリダイレクトURI」に以下を設定：<br>
                <code><?php echo esc_url( home_url( '/?imembers_sns=google' ) ); ?></code>
            </li>
        </ol>

        <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 40px;">3. Stripe サブスクリプション決済</h2>
        <p>決済機能を有効にするには、APIキーとWebhookの設定が必要です。</p>
        
        <h3 style="color: #0073aa;">■ Webhookの設定（重要）</h3>
        <p>支払失敗時の自動ダウングレードに必須の設定です。</p>
        <ol>
            <li>Stripeの「Webhook」画面で以下を登録：<br>
                <strong>エンドポイントURL:</strong> <code><?php echo esc_url( home_url( '/?imembers_webhook=stripe' ) ); ?></code>
            </li>
            <li>送信イベント: <code>checkout.session.completed</code>, <code>customer.subscription.deleted</code>, <code>invoice.payment_failed</code></li>
            <li>取得した署名シークレット（whsec_...）を設定画面に入力。</li>
        </ol>

        <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; margin-top: 40px;">4. ショートコード一覧</h2>
        <table class="widefat striped" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>ショートコード</th>
                    <th>説明</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[imembers_login]</code></td>
                    <td>ログイン・新規登録フォームを表示します。</td>
                </tr>
                <tr>
                    <td><code>[imembers_mypage]</code></td>
                    <td>お気に入り、閲覧履歴、購読管理などのマイページ機能を表示します。</td>
                </tr>
                <tr>
                    <td><code>[imembers_favorite]</code></td>
                    <td>お気に入り（★）ボタンを表示します。</td>
                </tr>
                <tr>
                    <td><code>[imembers_download url="URL" label="ボタン名"]</code></td>
                    <td>会員限定のダウンロードボタンを表示します。</td>
                </tr>
            </tbody>
        </table>

        <div style="margin-top: 40px; padding: 15px; background: #f0f6fb; border-left: 4px solid #0073aa;">
            <p style="margin: 0;">※ さらに詳細なドキュメントは、プロジェクトフォルダ内の <code>docs/manual.md</code> を参照してください。</p>
        </div>
    </div>
</div>
