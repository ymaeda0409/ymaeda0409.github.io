# Development Plan (MVP)

各 PHASE 終了時に必ず: **動作確認 → 自動テスト → Git Commit → README 更新**。
次の PHASE へは前 PHASE のテストが green の状態でのみ進む。

---

## PHASE 1 — Backend 基盤 ✅（本コミットで実装）

| 項目 | 内容 |
|---|---|
| 環境 | Laravel 13, PostgreSQL 16, Redis, Docker Compose, `.env` 切替 |
| 認証 | Sanctum。顧客/配達員 = 電話番号 OTP（テストモード 123456）、スタッフ = email/password |
| テナント | organizations, franchises, stores, kitchens, delivery_zones（非正規化 org/franchise id） |
| RBAC | UserRole + Permission enum、Policy、`visibleTo()` スコープ、他 FC は 404 |
| i18n | languages テーブル、SetLocale middleware（Accept-Language → preferred_language → en）、lang/{en,ny,ja} |
| 翻訳 | HasTranslations trait、*_translations テーブル（categories, products, option groups, options, stores） |
| カタログ | categories, products, options, store_products（店舗別価格・販売可否・在庫） |
| 顧客 | OTP 登録、Account、Language 変更、住所 CRUD |
| 店舗検索 | StoreLocatorService::findAvailableStores（Haversine + ゾーン判定 + 配送料） |
| 管理 API | Franchise / Store / Kitchen / DeliveryZone / Category / Product / StoreProduct / Language / AuditLog |
| 監査 | AuditLogger（管理操作・言語変更） |
| Seeder | Malawi Bento / Lilongwe Franchise / Central Store & Kitchen / 3 言語 / 商品 6 種 / テストユーザー |
| テスト | 認証, 配送エリア判定, 店舗検索, 権限, 他 FC アクセス禁止, 言語切替, Fallback, 商品翻訳, Accept-Language, 翻訳ファイル整合性 |

**完了条件**: `php artisan migrate:fresh --seed` が PostgreSQL で成功し、`php artisan test` が全件 green。

## PHASE 2 — Customer App（Flutter）

* プロジェクト雛形、flavor（dev/prod）、`API_BASE_URL`
* gen-l10n（app_en/ny/ja.arb）、FallbackMaterialLocalizationsDelegate、FormattingLocale
* Splash → Language Selection → Login/OTP → Home → Product List/Detail → Cart → Checkout（注文送信は PHASE 3 の API 完成後に接続）
* 状態管理: Riverpod、HTTP: dio（Accept-Language interceptor, token interceptor, retry）
* キャッシュ: dio cache + cached_network_image
* テスト: widget test（3 言語で主要画面がオーバーフローしない）、ARB キー整合性テスト

## PHASE 3 — Order / Kitchen

* orders, order_items, order_item_options, order_status_histories マイグレーション
* `OrderPricingService`（小計・オプション・配送料・サービス料・割引・合計; サーバー側で再計算）
* `OrderService::place()`（エリア判定、在庫減算、スナップショット、Delivery PIN 生成、order_number 採番）
* `OrderStatus::canTransitionTo()` による遷移制御 + 履歴
* Kitchen 画面（Laravel Blade + Vue 3, vue-i18n）: NEW / COOKING / READY、ACCEPT / START COOKING / READY
* Customer App の Checkout/Order Complete/History を接続
* テスト: 注文作成、金額計算、ステータス遷移、他 FC の注文不可視

## PHASE 4 — Driver App / Assignment / GPS / Delivery

* drivers, delivery_assignments, driver_locations
* `DeliveryAssignmentService`（READY 時に ONLINE かつ配送中でない Driver を Kitchen から近い順にオファー、辞退/期限切れで次候補）
* Driver API 一式、Delivery PIN 検証（試行回数制限）
* Driver App（オフラインキュー、GPS バッファ、現在配送ローカル保存）
* テスト: Driver 割当、PIN、Driver のテナント分離

## PHASE 5 — Payment / Tracking / Notification

* `PaymentGatewayInterface`（pay/verify/refund）、`FakePaymentGateway`、PayChangu 実装の雛形、webhook 署名検証
* Cash / Airtel Money / TNM Mpamba
* Order Tracking（ポーリング 10 秒; 将来 WebSocket）
* notification_templates(+translations)、FCM、SMS（重要通知のみ, GSM-7/UCS-2 通数制御）
* テスト: 決済フロー、通知 Locale 選択と fallback

## PHASE 6 — Admin Dashboard / Sales / FC / Translation Management

* Admin SPA（Laravel + Vue 3 + vue-i18n）: Dashboard, Orders, Products（言語タブ）, Categories, Stores, Kitchens,
  Franchises, Drivers, Customers, Delivery Zones, Sales, Translations, Languages, Settings
* Sales 集計（期間・商品別・店舗別・FC 別）、スコープ別 Dashboard
* テスト: 集計値、スコープ

---

## MVP 完成条件（PHASE 5 終了時）

Lilongwe 1 号店で「言語選択 → 商品閲覧 → 注文 → 決済 → 厨房確認 → 調理 → Driver 割当 → 配送 → リアルタイム位置確認 → PIN 確認 → 配達完了」を 3 言語いずれでも完走できること。

## テスト戦略

| レイヤ | ツール | 方針 |
|---|---|---|
| Backend Unit | PHPUnit | Service / Enum / 計算ロジック |
| Backend Feature | PHPUnit + RefreshDatabase | API 単位。テナント分離・権限は必ず「他 FC ユーザー」ケースを書く |
| DB | SQLite in-memory（高速）＋ CI で PostgreSQL | DB 固有 SQL を使わないことで両立 |
| Flutter | flutter_test | widget / golden（多言語レイアウト） |
| E2E | 手動シナリオ（PHASE 5） | MVP 完成条件のシナリオ |
