# テストサーバーの構築（Ubuntu 24.04）

1 台のサーバーに API・キッチン画面・管理画面・お客様 Web アプリ・配達員 Web アプリをまとめて載せます。

## 手順

SSH でサーバーに入り、次の 1 行を実行します（10〜15 分）。

```bash
curl -fsSL https://raw.githubusercontent.com/ymaeda0409/ymaeda0409.github.io/claude/malawi-bento-delivery-mvp-iu1alu/deploy/server-setup.sh | sudo bash
```

独自ドメインを使う場合は、先に DNS の A レコードをサーバーの IP に向けてから:

```bash
curl -fsSL .../deploy/server-setup.sh | sudo DOMAIN=bento.example.com CERT_EMAIL=you@example.com bash
```

ドメインが無い場合は `<IP のドットをハイフンにしたもの>.sslip.io`（例: `172-233-78-130.sslip.io`）を使い、
Let's Encrypt の HTTPS 証明書を自動で取得します。**HTTPS が無いとブラウザが GPS とログイン情報の保存を拒否する**ため必須です。

## できあがるもの

| URL | 内容 |
|---|---|
| `https://<ドメイン>/` | お客様アプリ（Web） |
| `https://<ドメイン>/driver/` | 配達員アプリ（Web） |
| `https://<ドメイン>/kitchen` | キッチン画面 |
| `https://<ドメイン>/admin` | 管理画面（配達員マップ含む） |
| `https://<ドメイン>/up` | 死活監視 |

* スタッフのログイン（メール + パスワード）は `/root/malawi-bento-credentials.txt`（初回にランダム生成）。
* お客様・配達員は電話番号 + 認証コード `123456`（テストモード）。サンプル配達員: `0990000001`〜`0990000003`。
* 支払いは模擬（電話番号の末尾 `0000` で失敗、`9999` で保留）。

## 構成

PHP 8.4-FPM / PostgreSQL 16 / Redis / nginx / certbot。キューワーカーは systemd（`malawi-bento-queue`）、
スケジューラ（配達オファーの失効処理）は `/etc/cron.d/malawi-bento`。ファイアウォールは SSH・HTTP・HTTPS のみ許可。
コードは `/var/www/malawi-bento`、設定は `/var/www/malawi-bento/backend/.env`。

## 更新

同じコマンドをもう一度実行すると、最新コードの取得・マイグレーション・再起動を行います（データと .env は保持）。

Web アプリと管理画面の JS はリポジトリにビルド済みで入っています（サーバーに Flutter / Node.js は不要）。
アプリを変更したら、開発機で `scripts/build-server-web.sh` を実行してコミットしてください。

## 本番運用の前に必要なこと

* **SMS 送信業者**の接続（`SMS_DRIVER`）と `OTP_TEST_MODE=false`・`APP_ENV=production`。現在は誰でもコード 123456 でログインできます。
* **PayChangu** の本番キー（`PAYMENT_GATEWAY=paychangu`）。
* 地図タイルの提供元（OpenStreetMap 公式タイルは少量利用のみ）。
* 配達員のバックグラウンド GPS はスマホアプリ（Android/iOS ビルド）でのみ動作。Web 版は画面を開いている間だけ位置を送ります。
* root パスワードでの SSH ログインをやめ、SSH 鍵に切り替えること。
* データベースのバックアップ（例: `pg_dump` の定期実行）。
