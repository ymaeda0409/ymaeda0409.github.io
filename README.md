# Malawi Bento — Delivery Platform

マラウイ向け Uber Eats 型の弁当デリバリーサービス。1 店舗（Lilongwe）から始め、FC 方式で全国・多業態へ拡張できる
**マルチテナント（Organization → Franchise → Store → Kitchen → Delivery Zone）** かつ **多言語（en / ny / ja）前提** の設計。

| ディレクトリ | 内容 | 状態 |
|---|---|---|
| [`backend/`](backend) | Laravel 13 REST API + Kitchen Web + Admin Web（Vue） | ✅ PHASE 1・3・4・5・6 |
| [`customer-app/`](customer-app) | Flutter 顧客アプリ（en / ny / ja） | ✅ PHASE 2・3 |
| [`driver-app/`](driver-app) | Flutter 配達員アプリ（en / ny / ja） | ✅ PHASE 4 |
| [`packages/bento_core`](packages/bento_core) | 両アプリ共通の Dart パッケージ（API クライアント・Locale フォールバック・書式） | ✅ |
| [`docs/`](docs) | 設計ドキュメント | ✅ |

**Web デモ（スマホ可）**: https://ymaeda0409.github.io/ — お客様アプリをサーバーなしで体験できます（認証コード `123456`）。
下の「5c. Web デモ」参照。

設計ドキュメント: [architecture](docs/architecture.md) · [database](docs/database.md) · [i18n](docs/i18n.md) ·
[screens](docs/screens.md) · [api](docs/api.md) · [development-plan](docs/development-plan.md) · [design-review](docs/design-review.md)

---

## 1. 環境構築

### A. Docker（推奨）

```bash
cp backend/.env.example backend/.env
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
(cd backend && npm install && npm run build)   # 厨房画面の JS/CSS
# API: http://localhost:8080/api/languages   厨房画面: http://localhost:8080/kitchen   管理画面: http://localhost:8080/admin
```

サービス: `app`（php-fpm）, `nginx`（:8080）, `queue`（Redis queue worker）, `scheduler`（`deliveries:dispatch` 毎分）, `postgres`（:5432）, `redis`（:6379）。

### B. ローカル（PHP 8.3+ / Composer / PostgreSQL 16 / Redis）

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
# .env の DB_* / REDIS_* を環境に合わせて編集
createdb malawi_bento           # 例: PostgreSQL
php artisan migrate --seed
npm install && npm run build    # 厨房画面（Vue）のビルド
php artisan serve               # http://127.0.0.1:8000  厨房画面: /kitchen  管理画面: /admin
php artisan queue:work          # SMS 等のキュー（別ターミナル）
php artisan schedule:work       # 配達オファーの失効・再割当（毎分, 別ターミナル）
```

MySQL を使う場合は `DB_CONNECTION=mysql`, `DB_PORT=3306` に変更（DB 固有 SQL は使っていない）。

### 主な `.env` 項目

| Key | 説明 |
|---|---|
| `DB_*` | PostgreSQL / MySQL 接続 |
| `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION` | Redis キャッシュ / キュー |
| `DEFAULT_LOCALE` | 既定・フォールバック言語（`en`） |
| `DEFAULT_CURRENCY` | `MWK` |
| `OTP_TEST_MODE`, `OTP_TEST_CODE` | 開発用固定 OTP（`123456`）。**`APP_ENV=production` では常に無効** |
| `SMS_DRIVER` | `log`（開発）。本番ドライバは `SmsGateway` 実装を追加 |
| `PAYMENT_GATEWAY`, `PAYCHANGU_*` | 決済。開発は `fake`、本番は `paychangu`（`PAYCHANGU_SECRET_KEY`, `PAYCHANGU_WEBHOOK_SECRET`, `PAYCHANGU_AIRTEL_REF_ID` / `PAYCHANGU_TNM_REF_ID`） |
| `PAYMENT_UNPAID_TIMEOUT` | 未払い Mobile Money 注文を自動キャンセルするまでの分数（30） |
| `PUSH_DRIVER` | `log`（開発, ログ出力）/ `fcm` |
| `GOOGLE_MAPS_API_KEY` | Google Maps |
| `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS` | FCM HTTP v1（サービスアカウント JSON のパス） |
| `FILESYSTEM_DISK`, `AWS_*`, `AWS_ENDPOINT` | S3 互換ストレージ |
| `SERVICE_FEE` | 注文ごとのサービス料（minor units, 既定 0） |
| `DISPATCH_OFFER_TTL`, `DISPATCH_LOCATION_MAX_AGE` | 配達オファーの有効秒数（60）、割当対象とする GPS の鮮度（分, 10） |

---

## 2. Migration / Seed

```bash
cd backend
php artisan migrate              # マイグレーション
php artisan db:seed              # シード（冪等: 何度実行しても重複しない）
php artisan migrate:fresh --seed # 全削除して作り直し（開発用）
```

Seed 内容:

* Organization **Malawi Bento** → Franchise **Lilongwe Franchise** → Store **Lilongwe Central Store** → Kitchen **Lilongwe Central Kitchen** → Delivery Zone（10 km, 基本 MK 1,500 / 3 km, 以降 MK 300/km）
* Languages: English（既定）, Chichewa, 日本語
* 商品: Chicken / Beef / Fish / Vegetarian Bento, Water, Coke（en / ny / ja 翻訳、弁当は「ご飯の量」オプション付き）

### テストアカウント（開発専用）

| Role | ログイン |
|---|---|
| SUPER_ADMIN | `superadmin@malawibento.test` / `password` |
| FRANCHISE_ADMIN | `franchise.admin@malawibento.test` / `password` |
| STORE_MANAGER | `store.manager@malawibento.test` / `password` |
| KITCHEN_STAFF | `kitchen@malawibento.test` / `password`（preferred_language = ny） |
| DRIVER ×3 | `+265990000001` (en), `+265990000002` (ny), `+265990000003` (ja) — OTP `123456` |
| CUSTOMER | `+265991234567` — OTP `123456`（住所 1 件登録済み） |

スタッフは `POST /api/auth/login`、電話番号ユーザーは `POST /api/auth/send-otp` → `POST /api/auth/verify-otp`。
管理画面 `/admin` にはスタッフ 4 アカウントのどれでもログインでき、役割に応じてメニューが変わります（厨房スタッフは閲覧中心、店長は自店舗、FC 管理者は自 FC、本部は全体 + 翻訳・言語）。

### 動作確認例

```bash
B=http://127.0.0.1:8000/api
curl -s $B/languages
curl -s "$B/stores/available?latitude=-13.97&longitude=33.78" -H 'Accept-Language: ny'
curl -s "$B/products?store_id=1" -H 'Accept-Language: ja'
curl -s -X POST $B/auth/send-otp -H 'Content-Type: application/json' -d '{"phone":"0991234567"}'
TOKEN=$(curl -s -X POST $B/auth/verify-otp -H 'Content-Type: application/json' \
  -d '{"phone":"0991234567","code":"123456"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->data->token;')
curl -s -X POST $B/orders -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' -H 'Accept-Language: ja' \
  -d '{"store_id":1,"delivery_address_id":1,"payment_method":"CASH","items":[{"product_id":1,"quantity":2,"option_ids":[2]}]}'
# → 厨房画面 http://127.0.0.1:8000/kitchen に kitchen@malawibento.test / password でログインして受付・調理・準備完了
```

---

## 3. テスト

```bash
cd backend
php artisan test                 # SQLite in-memory（高速, 既定）
vendor/bin/pint --test           # コードスタイル

# PostgreSQL で実行（例: DB malawi_bento_test を作成済み）
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_DATABASE=malawi_bento_test \
DB_USERNAME=bento DB_PASSWORD=secret php artisan test

npm test                         # 厨房・管理画面（Vitest: 翻訳 JSON 整合性・ラベル網羅・権限別メニュー・フォーム・売上）
```

GitHub Actions: `backend.yml`（Pint + SQLite + PostgreSQL + Vitest + Vite build）、`flutter.yml`（bento_core / customer-app / driver-app の gen-l10n 差分・format・analyze・test）。

Backend テスト範囲（PHPUnit 179 tests + Vitest 29 tests）:

| 観点 | テスト |
|---|---|
| 認証 / OTP | `Feature/OtpAuthTest`（登録、試行回数制限、期限切れ、再送制限、本番で固定コード無効、スタッフログイン） |
| 配送エリア判定・配送料 | `Unit/DeliveryFeeTest`, `Feature/StoreLocatorTest` |
| 店舗検索 | `Feature/StoreLocatorTest`（距離順、Kitchen 起点、停止中の除外） |
| 権限 | `Feature/Admin/PermissionTest` |
| 他 FC データへのアクセス禁止 | `Feature/Admin/TenantIsolationTest` |
| 言語切替 / Fallback / Accept-Language | `Feature/LocaleTest` |
| 商品翻訳取得 | `Feature/CatalogTest`, `Feature/Admin/CatalogManagementTest` |
| 翻訳ファイル整合性 | `Unit/TranslationFilesTest`, `Unit/SmsMessageTest` |
| 営業時間（タイムゾーン） | `Unit/StoreHoursTest` |
| 注文作成・金額計算・在庫・スナップショット | `Feature/OrderTest` |
| 注文ステータス遷移 | `Unit/OrderStatusTest`, `Feature/Admin/KitchenOrderTest` |
| 厨房の FC 分離・スタッフ言語表示 | `Feature/Admin/KitchenOrderTest` |
| 厨房画面 UI | `resources/js/kitchen/kitchen.test.js` |
| 売上集計・Dashboard（日付境界・ロイヤリティ・言語・スコープ・CSV） | `Feature/Admin/SalesReportTest` |
| スタッフ・顧客管理（下位役割のみ・スコープ・無効化） | `Feature/Admin/BackOfficeManagementTest` |
| 設定の解決順・翻訳管理 | `Feature/Admin/SettingsAndTranslationsTest` |
| 管理画面 UI | `resources/js/admin/admin.test.js` |
| Driver 割当・Delivery PIN・GPS・配送追跡 | `Feature/DeliveryTest` |
| 配達員管理（FC 分離） | `Feature/Admin/DriverManagementTest` |
| 決済（Fake/再試行/二重請求防止/webhook 署名/返金/未払い失効/他人不可） | `Feature/PaymentTest`, `Unit/GatewayAdaptersTest`（PayChangu・FCM の HTTP 形式） |
| 通知（受信者の言語・fallback・SMS 対象/通数・端末登録・テンプレート管理） | `Feature/NotificationTest` |

---

## 4. 多言語ファイルの追加方法

詳細は [docs/i18n.md §10](docs/i18n.md#10-言語追加手順例-tumbuka-tum)。例: Tumbuka (`tum`)

1. **言語を登録**（無効状態で）
   ```bash
   curl -X POST $B/admin/languages -H "Authorization: Bearer <SUPER_ADMIN token>" -H 'Content-Type: application/json' \
     -d '{"code":"tum","name":"Tumbuka","native_name":"Chitumbuka","is_active":false,"sort_order":4}'
   ```
2. **Backend 翻訳ファイル**: `backend/lang/en/` を `backend/lang/tum/` にコピーして翻訳
   （`errors.php`, `sms.php` は必須、`validation.php` は未訳キーが英語にフォールバック）。
   `php artisan test --filter=TranslationFilesTest` でキー欠落を検出。
3. **アプリ翻訳**: `customer-app/lib/l10n/app_en.arb` → `app_tum.arb` を作成し `flutter gen-l10n`（`language_native_name` に自言語名を入れる）。`flutter test` の ARB 整合性テストで欠落を検出。
   配達員アプリも `driver-app/lib/l10n/` に同様に追加。厨房・管理画面は `backend/resources/js/locales/en.json` → `tum.json` と `locales/admin/en.json` → `admin/tum.json` を作成し、`resources/js/shared/i18n.js` の `messages` と `resources/js/admin/messages.js` に登録（`npm test` で欠落検出）。
4. **DB コンテンツ**: 管理画面「翻訳」で「Chitumbuka（未公開）」を選び、未翻訳の商品・カテゴリ・オプション・店舗文言・通知を入力（無効な言語にも入力できる）。
5. アプリ配布後に管理画面「言語」（または `PUT /api/admin/languages/{id}`）で `is_active: true`。

コード中に言語コードを書いた分岐は存在しないため、上記以外の変更は不要。

---

## 5. Customer App（Flutter）

```bash
cd customer-app
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api   # Android エミュレータ
flutter test                                                       # 45 tests
```

## 5b. Rider App（Flutter）

```bash
cd driver-app
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
flutter test                                                       # 15 tests
```

詳細は [driver-app/README.md](driver-app/README.md)。共通パッケージは `cd packages/bento_core && flutter test`（9 tests）。

詳細は [customer-app/README.md](customer-app/README.md)。アプリの翻訳追加は `lib/l10n/app_en.arb` をコピーして `app_<code>.arb` を作成 → `flutter gen-l10n`。

## 5c. Web デモ（GitHub Pages）

`https://ymaeda0409.github.io/`（トップ）→ `/app/`（お客様アプリ）。GitHub Pages がこのブランチのルートをそのまま配信します（`.nojekyll`）。

* `--dart-define=DEMO_MODE=true` でビルドしたお客様アプリ。API 呼び出しは `customer-app/lib/demo/demo_backend.dart` がブラウザ内で応答します
  （本物の API と同じ封筒形式・エンドポイント。商品データは本物の API から取得した `assets/demo/*.json`）。画面・リポジトリ・JSON 解析は本番と同じコード。
* 注文は時間で自動進行（受付 6 秒 → 調理 15 秒 → … → 配達完了 約 2 分）。Mobile Money は数秒で支払い完了（末尾 `0000` で失敗、`9999` で保留）。
* 「現在地」は常にリロングウェ（Area 47）。データは閲覧者のブラウザ（localStorage）だけに保存。画面右上に「DEMO / デモ」表示。
* 更新手順: `./scripts/build-web-demo.sh` → `app/` と `index.html` をコミット・push（数分で反映）。
* 含まれないもの: キッチン画面・配達員アプリ・管理画面（サーバーが必要）。

---

## 5d. テストサーバー（Ubuntu 24.04）

`deploy/server-setup.sh` 1 本で API・キッチン・管理画面・お客様/配達員 Web アプリを HTTPS で構築。手順は [deploy/README.md](deploy/README.md)。

## 6. 開発フェーズ

[docs/development-plan.md](docs/development-plan.md) 参照。現在 **PHASE 6 完了**（PHASE 1: Backend 基盤 / PHASE 2: Customer App / PHASE 3: 注文・厨房 / PHASE 4: 配達員アプリ・割当・GPS・Delivery PIN・配送追跡 / PHASE 5: 決済・多言語通知 / PHASE 6: 管理画面・売上・FC・翻訳管理）。

> **本番前に必要なこと**: PayChangu のアカウントとサンドボックスでの実結合確認（アダプタは公開ドキュメントに基づく実装で、実 API では未検証）、
> Firebase プロジェクト作成とアプリへの `firebase_messaging` 組込み、本番 SMS ドライバ（`SmsGateway` 実装）の追加。

> **Chichewa 訳について**: 同梱の Chichewa 文言は初版です。リリース前にネイティブ話者のレビューを受けてください（翻訳ファイル / 管理画面の修正のみで反映できます）。
