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
| Seeder | Malawi Bento / Lilongwe Franchise / Central Store & Kitchen / 3 言語 / 弁当 4 種（写真付き, 主食・量・トッピング・ドリンクのオプション）+ ドリンク 3 種 / テストユーザー |
| テスト | 認証, 配送エリア判定, 店舗検索, 権限, 他 FC アクセス禁止, 言語切替, Fallback, 商品翻訳, Accept-Language, 翻訳ファイル整合性 |

**完了条件**: `php artisan migrate:fresh --seed` が PostgreSQL で成功し、`php artisan test` が全件 green。

**結果**: ✅ migrate:fresh --seed（PostgreSQL 16）成功、90 tests green（SQLite / PostgreSQL 両方）、Pint pass。
手動確認: `php artisan serve` 上で languages / stores/available / products / OTP ログイン / 管理 API を 3 言語で curl 確認。

**PHASE 1 で意図的に対象外としたもの**: スタッフユーザー管理 API（Seeder で作成、PHASE 6 で追加済み）、
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

## PHASE 4 — Driver App / Assignment / GPS / Delivery ✅

| 項目 | 内容 |
|---|---|
| DB | drivers, delivery_assignments, driver_locations、orders.driver_id FK、orders.delivery_pin_attempts |
| 割当 | `DeliveryAssignmentService`: READY で最寄り（Kitchen 起点）の空き・オンライン・GPS 新鮮な同 FC 配達員へ 1 件ずつオファー、辞退/失効/オフラインで次へ、`deliveries:dispatch`（毎分, scheduler） |
| 配達 | `DeliveryService`: online/offline、GPS（一括・端末時刻・未来時刻補正・古い点で戻らない）、pickup → ON_THE_WAY、arrive、PIN 検証（5 回でロック）、代引きは完了時に PAID、fail |
| API | Driver API 一式、顧客 `GET /orders/{id}/tracking`、管理 `/admin/drivers` |
| 共通化 | `packages/bento_core`（ApiClient・エラーコード・Locale フォールバック・書式・トークン保存）を顧客/配達員アプリで共有 |
| Driver App | 01 言語選択 → 02 ログイン（DRIVER 以外は拒否）→ 03/04 ONLINE/OFFLINE → 05 依頼（カウントダウン）→ 06/07 受取（ナビ・電話）→ 08/09 配達・到着 → 10 PIN パッド → 11 完了、12 履歴、13 言語設定 |
| オフライン | 現在の配送を端末保存、受取/到着は送信キューで再送（適用済みは破棄）、GPS はバッファして一括送信 |
| 顧客アプリ | 注文詳細に配達員の位置・車両・距離（MAPS_ENABLED 時は地図） |
| テスト | Backend 138（割当条件・辞退/失効・オフライン化・PIN・ロック・GPS・追跡・他配達員不可・管理）、Driver App 15、Customer App 38、bento_core 6、Vue 12 |

**結果**: 実 API で「顧客注文（ja）→ 厨房 API で準備完了（配達員不在で待機）→ 配達員アプリ（Web, ny）で
OTP ログイン → オンライン → 依頼受諾 → 受取 → 到着 → PIN 入力 → 配達完了」を Chromium で確認。
注文は DELIVERED / PAID、履歴 9 段階、全リクエストに `Accept-Language: ny`。

**検出して直した問題**: 小画面・文字拡大時に依頼カード見出し（ny/en）が横にはみ出していた（テストで検出）。

## PHASE 5 — Payment / Tracking / Notification ✅

| 項目 | 内容 |
|---|---|
| DB | payments, notification_templates(+translations), device_tokens, notification_logs |
| 決済 | `PaymentGatewayInterface`（pay/verify/refund/parseWebhook）、`FakePaymentGateway`（電話番号末尾で成功/拒否/保留）、`PayChanguGateway`（Airtel Money / TNM Mpamba, `PAYMENT_GATEWAY=paychangu` で切替） |
| 決済フロー | `PaymentService`: 請求（PENDING の再利用で二重請求防止）→ 状態確認（ポーリング）/ webhook（HMAC 署名検証 → サーバー側 `verify()` で再確認）→ PAID で注文を支払い済みに。取消時の自動返金、未払い注文の自動失効（`orders:expire-unpaid`）。代引きは P4 のとおり配達完了で PAID |
| 通知 | 注文/決済/配達オファーのイベント → `SendOrderNotifications` → キュー → `NotificationService`。**受信者の `preferred_language`** で DB テンプレート → en → lang ファイルの順に解決。Push（`log` / FCM HTTP v1）、SMS は重要 3 種のみ（注文受付・配達開始・到着）で通数超過時は en |
| 端末 | `POST/DELETE /devices`、`bento_core` の `DeviceRegistrar` + `PushTokenSource`（既定は no-op、Firebase 導入時に差し替え）を両アプリでログイン/ログアウトに接続 |
| 管理 | `GET/PUT /admin/notification-templates`（全言語の文面編集, 監査ログ） |
| 顧客アプリ | 11 Payment 画面（請求・確認待ち・失敗/再試行・完了）、注文詳細に決済状態と「今すぐ支払う」 |
| Tracking | P4 で実装済み（ポーリング。将来 WebSocket） |
| テスト | Backend 159、Vue 12、Customer 41、Driver 15、bento_core 9 |

**結果**: 159 tests green（SQLite / PostgreSQL）、Pint pass、Flutter analyze 0 件。実 API（PostgreSQL + Redis キュー）で
「顧客（ny）が Airtel Money で注文 → 請求 → 確認 → 注文 PAID → キューワーカーが Push を送信（`Malipiro alandiridwa` = ny の文面、
`notification_logs` に locale=ny / SENT）」を確認。

**未検証・制約**: PayChangu アダプタは公開 API 仕様に基づくが実アカウントでの結合は未実施。FCM 送信は HTTP 形式をテストで確認済みだが、
アプリ側は Firebase プロジェクトがないため既定で no-op（手順は customer-app/README）。実機での Push 受信は未確認。

## PHASE 6 — Admin Dashboard / Sales / FC / Translation Management ✅

| 項目 | 内容 |
|---|---|
| Admin SPA | `/admin`（Vue 3 + vue-i18n, ハッシュルーティング, 厨房画面とログイン・API クライアント・言語切替を共有）。権限に応じてメニューを出し分け（同じアプリで本部・FC・店舗・厨房スタッフ） |
| 画面 | Dashboard, Orders（一覧・詳細・取消）, Sales（期間・日/店舗/FC/商品/支払別・CSV）, Products（言語タブ + オプション編集）, Categories, Store menu & stock（売切れ・店舗価格・在庫）, Stores（営業時間・言語別文言）, Kitchens, Delivery Zones, Riders, Franchises, Staff, Customers, Translations, Languages, Settings, Audit log |
| CRUD | `resources.js` にフィールド定義（型・必須・権限・表示条件）を置き、一覧/フォームは汎用コンポーネントで描画。ラベルはすべて `admin.fields.*` の翻訳キー |
| 売上 | `SalesService`: DELIVERED かつ PAID、組織タイムゾーンの注文日で集計（DB 非依存）、FC 別ロイヤリティ、商品名は閲覧者の言語、CSV |
| Dashboard | 本日の注文・売上、進行中の状態別件数、支払い待ち、オンライン配達員、営業中店舗、7 日推移、売れ筋（金額は sales.view のみ） |
| FC / 人 | スタッフ管理（下位の役割のみ・自スコープのみ・無効化で即ログアウト）、顧客（スコープ内で注文した人だけ見える）|
| 設定 | `settings` テーブル + `SettingsService`（STORE → FRANCHISE → ORGANIZATION → GLOBAL → config）。`service_fee` を注文金額、`delivery_offer_ttl_seconds` を配達オファーに接続 |
| 翻訳管理 | 種類 × 言語のカバレッジ、未翻訳一覧、言語単位の更新（他言語は保持、既定言語の必須は空にできない） |
| 権限 | `staff.manage`, `customers.view`, `settings.manage` を追加（FRANCHISE_ADMIN: 3 つとも、STORE_MANAGER: staff/customers） |
| テスト | Backend 179（売上集計・日付境界・ロイヤリティ・商品名の言語・FC/店舗スコープ・CSV・Dashboard・スタッフ権限・顧客スコープ・設定の解決順・翻訳）、Vue 29（翻訳ファイル整合・ラベル網羅・メニュー権限・一覧/作成/エラー表示・言語タブ・売上・設定） |

**結果**: 179 tests green（SQLite / PostgreSQL）、Vitest 29、Pint pass、Vite build。Chromium で実 API に接続し、本部管理者（ja）でダッシュボード・売上（商品別 = 「水」）、
商品編集（ny, 言語タブ + オプション）、翻訳カバレッジ（ny）、設定を確認。FC 管理者でメニュー 15 項目（翻訳・言語なし）とスタッフ作成、厨房スタッフ（ny, 360px）で 6 項目・横スクロールなしを確認。
コンソールエラーなし。

**検出して直した問題**: 言語切替後もサーバー翻訳済みの商品名が前の言語のまま（ダッシュボード等で再取得するよう修正）、
スタッフ API が `is_active` を返さず、編集画面で保存すると無効化されうる状態だった（レスポンスに追加しテストで固定）、作成直後の「保存しました」が消える。

**制約**: 売上の日別集計はアプリ側で集計（数万件/期間までを想定。増えたら日次集計テーブルへ）、商品画像はURL入力（S3 アップロードは今後）。

---

## MVP 完成条件（PHASE 5 終了時）— ✅ 達成（Push の実機受信・PayChangu 実結合を除く）

Lilongwe 1 号店で「言語選択 → 商品閲覧 → 注文 → 決済 → 厨房確認 → 調理 → Driver 割当 → 配送 → リアルタイム位置確認 → PIN 確認 → 配達完了」を 3 言語いずれでも完走できること。

## テスト戦略

| レイヤ | ツール | 方針 |
|---|---|---|
| Backend Unit | PHPUnit | Service / Enum / 計算ロジック |
| Backend Feature | PHPUnit + RefreshDatabase | API 単位。テナント分離・権限は必ず「他 FC ユーザー」ケースを書く |
| DB | SQLite in-memory（高速）＋ CI で PostgreSQL | DB 固有 SQL を使わないことで両立 |
| Flutter | flutter_test | widget / golden（多言語レイアウト） |
| E2E | 手動シナリオ（PHASE 5） | MVP 完成条件のシナリオ |
