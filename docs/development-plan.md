# Development Plan (MVP)

各 PHASE 終了時に必ず: **動作確認 → 自動テスト → Git Commit → README 更新**。
次の PHASE へは前 PHASE のテストが green の状態でのみ進む。

---

## PHASE 1 — Backend 基盤 ✅

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

**結果**: ✅ migrate:fresh --seed（PostgreSQL 16）成功、90 tests green（SQLite / PostgreSQL 両方）、Pint pass。
手動確認: `php artisan serve` 上で languages / stores/available / products / OTP ログイン / 管理 API を 3 言語で curl 確認。

**PHASE 1 で意図的に対象外としたもの**: スタッフユーザー管理 API（Seeder で作成、管理画面は PHASE 6）、
本番 SMS ドライバ（`SmsGateway` 実装の追加のみで対応）、`drivers` テーブル（PHASE 4。DRIVER ロールのユーザーは作成済み）。

## PHASE 2 — Customer App（Flutter）✅

| 項目 | 内容 |
|---|---|
| 構成 | Flutter 3.47 / Dart 3.13、Riverpod 3、go_router、dio、shared_preferences、flutter_secure_storage |
| 設定 | `--dart-define=API_BASE_URL`, `MAPS_ENABLED`（環境ごとに再ビルドのみ） |
| i18n | gen-l10n（app_en/ny/ja.arb, 140 キー）、FallbackLocalizationsDelegate、FormattingLocale、`code_labels.dart`（エラーコード・ステータス → 翻訳の唯一の対応表） |
| 画面 | 01 Splash, 02 Language Selection, 03 Phone, 04 OTP, 05 Home, 06 Delivery Location（+ 住所追加）, 07 Product List, 08 Product Detail, 09 Cart, 10 Checkout, 16 Account, 17 Saved Addresses, 18 Language Settings |
| 通信 | Accept-Language / Bearer 自動付与、封筒展開、エラー→コード、GET 1 回再試行、401 でセッション破棄 |
| オフライン | カート・配送先・言語・ユーザー情報をローカル保存、翻訳はアプリ同梱 |
| テスト | 39 tests（ARB 整合性、ロケール判定、書式、エラーコード網羅、API クライアント、カート、3 言語 × 小画面 × 文字 130% のオーバーフロー検出、画面フロー） |

**結果**: `flutter analyze` 0 件、`flutter test` 39 件 green。Web ビルドを実 Laravel API に接続し、Chromium で
言語選択 → Home（en/ny/ja）→ 商品詳細 → カート → OTP ログイン → アカウントを確認
（全 API リクエストに選択言語の `Accept-Language` が付与されることを確認）。

**PHASE 3 で接続するもの**: Checkout の「注文する」は `POST /api/orders` を呼ぶ実装済みだが、API は PHASE 3 で追加。
11 Payment / 12 Order Complete / 13 Tracking / 14–15 Order History・Detail は PHASE 3・5。

## PHASE 3 — Order / Kitchen ✅

| 項目 | 内容 |
|---|---|
| DB | orders, order_items, order_item_options, order_status_histories（driver_id の FK は P4） |
| 金額 | `OrderPricingService`: 店舗価格・オプション（所属/有効/min-max 検証）・配送料（ゾーン）・サービス料（`SERVICE_FEE`）・割引・合計。`POST /orders/quote` で事前表示 |
| 注文 | `OrderService::place()`: 住所所有・エリア判定・営業時間（予約は予約時刻）・在庫ロック/減算・名称スナップショット（注文言語）・PIN・注文番号採番（店舗行ロック） |
| 状態 | `OrderStatus::allowedTransitions()` を唯一の遷移表に、`OrderStatusService` がロック・タイムスタンプ・履歴・在庫戻し・`OrderStatusChanged` イベント |
| 厨房 | `/kitchen` Vue 3 + vue-i18n（en/ny/ja JSON）。NEW / COOKING / READY の 3 列、ACCEPT ORDER / START COOKING / READY、スタッフ言語で商品名、10 秒ポーリング + 通知音 |
| 顧客アプリ | Orders タブ、12 Order Complete（番号 + PIN）、14 History、15 Detail（タイムライン・20 秒更新・キャンセル）、Checkout はサーバー見積もりで検証 |
| テスト | Backend 119（注文作成・金額・スナップショット・エリア外・在庫・オプション規則・遷移・支払い要件・他 FC 不可視・厨房言語）、Vue 12、Flutter 43 |

**結果**: 実環境で「顧客アプリ（Web ビルド, ja）で OTP ログイン → 見積もり → 注文 → 注文完了（PIN 表示）→
厨房画面（ny → ja 切替）で受付 → 調理開始 → 準備完了」を Chromium で確認。タイムライン `NEW → CONFIRMED → COOKING → READY_FOR_PICKUP`。

**修正したバグ**: 予約日時（+02:00 付き）が UTC 変換されずに保存されていた（テストで検出）、チェックアウトの商品名が追加時の言語のままだった。

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
