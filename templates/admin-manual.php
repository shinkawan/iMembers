<?php
/**
 * Template: Admin Manual Page
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<div class="wrap imembers-admin-manual">
    <h1>iMembers セットアップ・運用マニュアル</h1>

    <div class="notice notice-info" style="margin-top: 20px; border-left-color: #0073aa;">
        <p>iMembers プラグインの初期設定、会員管理、閲覧制限、およびStripe決済の運用方法を解説します。</p>
    </div>

    <div class="imembers-manual-content" style="background: #fff; padding: 30px; border: 1px solid #ccd0d4; margin-top: 20px; max-width: 1000px; line-height: 1.8; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
        
        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; color: #1d2327;">1. はじめに（クイックスタート）</h2>
            <p>プラグインを有効化すると、以下のページが自動的に作成されます。設定不要ですぐに利用可能です。</p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>ログイン / 新規登録:</strong> <code>/imembers-login/</code> - 全ての認証の入り口です。</li>
                <li><strong>マイページ:</strong> <code>/imembers-mypage/</code> - お気に入りや履歴、購読管理が表示されます。</li>
            </ul>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; color: #1d2327;">2. 会員登録とログイン</h2>
            <p>iMembersは、利便性とセキュリティを両立するため、パスワード不要の認証システムを採用しています。</p>
            
            <h3 style="color: #0073aa; margin-top: 20px;">■ メール認証（OTP）</h3>
            <p>基本設定で有効です。ユーザーがメールアドレスを入力すると6桁のコードが送信され、それを入力することでログイン・登録が完了します。</p>

            <h3 style="color: #0073aa; margin-top: 20px;">■ SNSログイン（オプション）</h3>
            <p>管理画面「設定」から以下の連携が可能です。各開発者コンソールで「リダイレクトURI」の設定が必要です。</p>
            <table class="widefat" style="margin-top: 10px;">
                <tr>
                    <th>サービス</th>
                    <th>設定すべきリダイレクトURI</th>
                </tr>
                <tr>
                    <td><strong>LINE</strong></td>
                    <td><code><?php echo esc_url( home_url( '/?imembers_sns=line' ) ); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Google</strong></td>
                    <td><code><?php echo esc_url( home_url( '/?imembers_sns=google' ) ); ?></code></td>
                </tr>
            </table>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; color: #1d2327;">3. コンテンツの閲覧制限</h2>
            
            <h3 style="color: #0073aa;">■ 記事・ページ単位</h3>
            <p>投稿編集画面のサイドバーで「閲覧権限（公開 / 会員限定 / 有料会員限定）」を選択できます。</p>

            <h3 style="color: #0073aa;">■ カテゴリー・アーカイブ単位</h3>
            <p>カテゴリー等のタクソノミー編集画面、または「iMembers 設定」の下部にある「アーカイブ閲覧制限設定」からページ全体をロックできます。</p>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; color: #1d2327;">4. 有料会員（Stripe連携）</h2>
            <p>Stripeのサブスクリプション機能を利用して、有料会員限定のコンテンツを提供できます。</p>
            <ol style="margin-left: 20px;">
                <li><strong>APIキー設定:</strong> 公開可能キーとシークレットキーを入力。</li>
                <li><strong>価格ID:</strong> Stripe上で作成した継続商品の <code>price_...</code> から始まるIDを入力。</li>
                <li><strong>Webhook設定（必須）:</strong> Stripe側の「Webhook」に以下を登録し、イベント <code>checkout.session.completed</code>, <code>customer.subscription.deleted</code>, <code>customer.subscription.updated</code> を受信可能にしてください。<br>
                    URL: <code><?php echo esc_url( home_url( '/?imembers_webhook=stripe' ) ); ?></code>
                </li>
            </ol>
        </section>

        <section style="margin-bottom: 40px;">
            <h2 style="border-bottom: 2px solid #0073aa; padding-bottom: 10px; color: #1d2327;">5. ショートコード一覧</h2>
            <table class="widefat striped" style="margin-bottom: 20px;">
                <thead>
                    <tr>
                        <th style="width: 200px;">コード</th>
                        <th>用途・説明</th>
                        <th>属性（オプション）</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[imembers_login]</code></td>
                        <td>ログインフォームを表示。</td>
                        <td>なし</td>
                    </tr>
                    <tr>
                        <td><code>[imembers_mypage]</code></td>
                        <td>マイページを表示。</td>
                        <td>なし</td>
                    </tr>
                    <tr>
                        <td><code>[imembers_favorite]</code></td>
                        <td>お気に入り登録ボタンを表示。</td>
                        <td>なし</td>
                    </tr>
                    <tr>
                        <td><code>[members_only]</code></td>
                        <td>限定コンテンツを作成。</td>
                        <td><code>level="paid"</code> で有料限定</td>
                    </tr>
                    <tr>
                        <td><code>[imembers_download]</code></td>
                        <td>限定ダウンロードボタンを表示。</td>
                        <td><code>url</code>, <code>label</code>, <code>level</code></td>
                    </tr>
                </tbody>
            </table>

            <h3 style="color: #0073aa;">■ 詳しい使い方と例</h3>
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #eee; border-radius: 4px;">
                <p><strong>限定コンテンツの作成:</strong><br>
                <code>[members_only level="paid"]ここには有料会員にしか見せたい情報を書きます。[/members_only]</code></p>
                
                <p><strong>ファイルのダウンロードボタン:</strong><br>
                <code>[imembers_download url="https://.../file.zip" label="資料をダウンロード" level="paid"]</code></p>

                <p><strong>お気に入りボタンの設置:</strong><br>
                記事詳細のテンプレートや本文内に <code>[imembers_favorite]</code> を記述すると、動的にお気に入り登録ボタンに置換されます。</p>
            </div>
        </section>

        <div style="margin-top: 50px; padding: 20px; background: #f0f6fb; border: 1px solid #c3d9ec; border-radius: 6px;">
            <h4 style="margin-top: 0;">管理者の方へ</h4>
            <ul style="margin: 0; padding-left: 20px;">
                <li><strong>ユーザー管理:</strong> 「ユーザー」メニューで各ユーザーが「有料会員」かどうかを確認できます。</li>
                <li><strong>統計:</strong> ダッシュボードに本日の新規数と有料会員数の推移を表示するウィジェットがあります。</li>
                <li><strong>詳細ドキュメント:</strong> さらに技術的な詳細は <code>docs/manual.md</code> を確認してください。</li>
            </ul>
        </div>
    </div>
</div>
