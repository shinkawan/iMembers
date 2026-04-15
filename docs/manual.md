# iMembers セットアップ・運用マニュアル

iMembersプラグインのインストールから、Stripe決済・SNSログイン連携の設定方法までを分かり易く解説します。

---

## 1. インストールと初期設定

1.  **プラグインの有効化**: 
    WordPress管理画面の「プラグイン」から `iMembers` を有効化します。
2.  **固定ページの確認**: 
    有効化時に以下のページが自動生成されます。
    - **ログイン / 新規登録**: `[imembers_login]`
    - **マイページ**: `[imembers_mypage]`
    ※ ページ名やURL（スラッグ）は自由に変更可能ですが、ショートコードは消さないでください。

---

## 2. SNSログイン連携の設定

管理画面の「iMembers」メニュー内にある「SNSログイン設定」に必要な情報を入力します。

### ■ LINEログインの連携
1.  [LINE Developers](https://developers.line.biz/) でプロバイダーと「LINEログイン」チャネルを作成します。
2.  **Channel ID** と **Channel Secret** を iMembers 設定画面の LINE 項目に入力。
3.  LINE Developers 側の「LINEログイン設定」にある **「コールバックURL」** に以下を設定します：
    `https://あなたのドメイン.com/?imembers_sns=line`

### ■ Googleログインの連携
1.  [Google Cloud Console](https://console.cloud.google.com/) でプロジェクトを作成し、「OAuth 同意画面」を設定します。
2.  「認証情報」から「OAuth 2.0 クライアント ID」を作成。
3.  **クライアント ID** を iMembers 設定画面に入力。
4.  **「承認済みのリダイレクト URI」** に以下を設定します：
    `https://あなたのドメイン.com/?imembers_sns=google`

---

## 3. Stripe サブスクリプション決済の設定

### ■ APIキーの設定
1.  [Stripe ダッシュボード](https://dashboard.stripe.com/) から「開発者」>「APIキー」にアクセス。
2.  **公開可能キー** と **シークレットキー** を iMembers 設定画面に入力。
3.  販売したいサブスクリプション商品の **「価格ID（price_...）」** を「サブスクリプション価格ID」欄に入力します。

### ■ Webhookの設定（重要：自動権限管理のため）
支払失敗時に自動で会員権限を剥奪するために必要です。
1.  Stripeダッシュボードの「開発者」>「Webhook」で「エンドポイントを追加」をクリック。
2.  **エンドポイントURL**: `https://あなたのドメイン.com/?imembers_webhook=stripe`
3.  **送信するイベント**: 
    - `checkout.session.completed` (契約成功時)
    - `customer.subscription.deleted` (解約時)
    - `invoice.payment_failed` (支払い失敗時)
4.  作成後に表示される **「署名シークレット（whsec_...）」** を iMembers 設定画面の「Webhookシークレット」欄に入力します。

---

## 4. コンテンツの閲覧制限方法

### ■ ページ・投稿単位の制限
記事編集画面の右サイドバーにある **「iMembers 閲覧制限」** ボックスの「このページを会員限定にする」にチェックを入れます。

### ■ カテゴリー単位の制限
「投稿」>「カテゴリー」の編集画面から、特定のカテゴリー全体を会員限定に設定できます。そのカテゴリーに属する記事と、カテゴリー一覧ページの両方が制限されます。

### ■ 「チラ見せ」機能（部分制限）
記事の途中で以下のショートコードを使用します：
`[members_only] ここから下は会員しか読めません [/members_only]`

---

## 5. ショートコード一覧

| ショートコード | 機能 | 設置場所 |
| :--- | :--- | :--- |
| `[imembers_login]` | ログイン/OTP送信フォーム | ログインページ |
| `[imembers_mypage]` | お気に入り・履歴・購読管理 | マイページ |
| `[imembers_favorite]` | お気に入りボタン（★） | 記事の本文やテンプレート |
| `[imembers_download url="..." label="..."]` | 会員限定ダウンロードボタン | 記事内 |

---

## 6. 開発者向けカスタマイズ

iMembersは外部連携を想定したフックを用意しています。

- **ログイン時処理**: 
  `add_action('after_member_login', function($user_id) { ... });` 
  (例: ログイン時にkintoneへログを送信するなど)
- **テンプレートのオーバーライド**:
  CSSや細かいUIを変更したい場合は、プラグイン内の `templates/` フォルダにあるファイルを参考にしてください。
