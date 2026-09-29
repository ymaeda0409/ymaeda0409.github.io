# REST API

* Base URL: `/api`
* 認証: Laravel Sanctum Bearer token（`Authorization: Bearer <token>`）
* 言語: `Accept-Language: en | ny | ja`（優先順位: Accept-Language → user.preferred_language → en）
* 形式: JSON, UTF-8。日時は ISO-8601 (UTC)。金額は minor units の整数 + `currency`。
* 「Phase」列: 実装フェーズ。✅ = PHASE 1 で実装済み。

## 共通レスポンス

```json
// 成功
{ "success": true, "data": {}, "meta": { "locale": "ja" } }

// ページネーション (?page=, ?per_page= 最大 100)
{ "success": true, "data": [], "meta": { "locale": "en", "pagination": { "current_page": 1, "per_page": 20, "total": 3, "last_page": 1 } } }

// エラー
{ "success": false, "error": { "code": "OTP_INVALID", "message": "The verification code is incorrect.", "fields": null } }
```

### HTTP ステータスとエラーコード

| HTTP | code | 意味 |
|---|---|---|
| 400 | `BAD_REQUEST` | 不正なリクエスト |
| 401 | `UNAUTHENTICATED` | 未認証 / トークン無効 |
| 401 | `INVALID_CREDENTIALS` | email/password 不一致 |
| 403 | `FORBIDDEN` | 権限なし |
| 403 | `ACCOUNT_DISABLED` | 無効化されたユーザー |
| 404 | `RESOURCE_NOT_FOUND` | 存在しない / 他テナント |
| 404 | `ROUTE_NOT_FOUND` | 不明な URL |
| 405 | `METHOD_NOT_ALLOWED` | 許可されていない HTTP メソッド |
| 409 | `CONFLICT` | 状態競合 |
| 422 | `VALIDATION_FAILED` | バリデーション（`fields` にフィールド別メッセージ） |
| 422 | `OTP_INVALID` | OTP 不一致 |
| 422 | `OTP_EXPIRED` | OTP 期限切れ / 未発行 |
| 422 | `LANGUAGE_NOT_SUPPORTED` | 無効な言語コード |
| 422 | `STORE_NOT_AVAILABLE` | 店舗が営業外/受付停止 |
| 422 | `OUT_OF_DELIVERY_AREA` | 配送エリア外 (P3) |
| 422 | `PRODUCT_NOT_AVAILABLE` | 販売停止・在庫切れ (P3) |
| 422 | `INVALID_STATUS_TRANSITION` | 注文ステータス遷移不可 (P3) |
| 422 | `PAYMENT_REQUIRED` | Mobile Money 未払い注文の厨房受付 (P3) |
| 422 | `DELIVERY_PIN_INVALID` | PIN 不一致 (P4) |
| 422 | `DELIVERY_PIN_LOCKED` | PIN 失敗回数超過 (P4) |
| 409 | `OFFER_NOT_AVAILABLE` | 配達オファー失効・他者受諾 (P4) |
| 422 | `PAYMENT_FAILED` | 決済失敗（ゲートウェイ拒否） (P5) |
| 409 | `PAYMENT_NOT_REQUIRED` | 代引き・支払い済み・取消済み注文への決済開始 (P5) |
| 403 | `INVALID_SIGNATURE` | 決済 webhook の署名不一致 (P5) |
| 429 | `TOO_MANY_REQUESTS` | レート制限（OTP 再送待ち含む） |
| 500 | `SERVER_ERROR` | サーバーエラー |

クライアントは `error.<code 小文字>` をキーに翻訳して表示する。

---

## AUTH

| Method | Path | Auth | Phase | 説明 |
|---|---|---|---|---|
| POST | /auth/send-otp | — | ✅ | `{ phone }` → OTP を SMS 送信 |
| POST | /auth/verify-otp | — | ✅ | `{ phone, code, device_name?, preferred_language? }` → token（未登録なら CUSTOMER 作成） |
| POST | /auth/login | — | ✅ | スタッフ用 `{ email, password, device_name? }` → token |
| POST | /auth/logout | ✔ | ✅ | 現在のトークンを失効 |
| GET | /auth/me | ✔ | ✅ | ログインユーザー |

### POST /auth/send-otp
```json
// request
{ "phone": "0991234567" }       // "+265991234567", "265991234567" も可（E.164 に正規化）
// 200
{ "success": true, "data": { "phone": "+265991234567", "expires_in": 300, "resend_in": 60 } }
```
* 同一番号は `resend_in` 秒以内の再送不可（429 `TOO_MANY_REQUESTS`）。
* 開発環境 `OTP_TEST_MODE=true` ではコードは常に `123456`（本番環境では強制無効）。

### POST /auth/verify-otp
```json
// request
{ "phone": "+265991234567", "code": "123456", "device_name": "customer-app", "preferred_language": "ny" }
// 200
{ "success": true, "data": { "token": "1|xxxx", "is_new_user": true,
  "user": { "id": 10, "name": null, "phone": "+265991234567", "email": null, "role": "CUSTOMER", "preferred_language": "ny" } } }
```
* `preferred_language` は **新規登録時のみ** 反映（アプリの言語選択を初期値にする）。
* 5 回失敗でその OTP は無効化。

---

## LANGUAGE / ACCOUNT

| Method | Path | Auth | Phase | 説明 |
|---|---|---|---|---|
| GET | /languages | — | ✅ | 有効言語一覧 |
| GET | /account | ✔ | ✅ | プロフィール |
| PUT | /account | ✔ | ✅ | `{ name?, email? }` |
| PUT | /account/language | ✔ | ✅ | `{ language: "ja" }` → preferred_language 更新（監査ログ）。無効な言語は 422 `LANGUAGE_NOT_SUPPORTED` |

```json
// GET /languages
{ "success": true, "data": [
  { "code": "en", "name": "English", "native_name": "English", "direction": "ltr", "is_default": true },
  { "code": "ny", "name": "Chichewa", "native_name": "Chichewa", "direction": "ltr", "is_default": false },
  { "code": "ja", "name": "Japanese", "native_name": "日本語", "direction": "ltr", "is_default": false } ] }
```

---

## CUSTOMER — Catalog / Store

| Method | Path | Auth | Phase | 説明 |
|---|---|---|---|---|
| GET | /stores/available?latitude=&longitude= | — | ✅ | 配送可能な店舗（距離順） |
| GET | /stores/{id} | — | ✅ | 店舗詳細（説明/お知らせは Locale 解決済み） |
| GET | /categories?store_id= | — | ✅ | 店舗で販売中の商品があるカテゴリ |
| GET | /products?store_id=&category_id=&featured= | — | ✅ | 店舗で販売中の商品（店舗価格） |
| GET | /products/{id}?store_id= | — | ✅ | 商品詳細（オプション含む） |

```json
// GET /stores/available?latitude=-13.9626&longitude=33.7741
{ "success": true, "data": [ {
  "store": { "id": 1, "code": "LLW-CENTRAL", "name": "Lilongwe Central Store", "city": "Lilongwe",
             "latitude": -13.9833, "longitude": 33.7833, "currency": "MWK", "is_open": true,
             "description": "…", "announcement": null },
  "kitchen_id": 1, "delivery_zone_id": 1,
  "distance_km": 2.54, "delivery_fee": 150000, "currency": "MWK" } ] }

// GET /products/1?store_id=1   (Accept-Language: ja)
{ "success": true, "data": {
  "id": 1, "sku": "BENTO-CHICKEN", "category_id": 1,
  "name": "チキン弁当", "description": "…", "image_url": "…",
  "price": 350000, "currency": "MWK", "preparation_minutes": 15, "is_featured": true,
  "stock_quantity": null,
  "option_groups": [ { "id": 1, "name": "ご飯の量", "min_select": 1, "max_select": 1,
      "options": [ { "id": 1, "name": "普通", "price": 0 }, { "id": 2, "name": "大盛り", "price": 50000 } ] } ] },
  "meta": { "locale": "ja" } }
```

## CUSTOMER — Addresses

| Method | Path | Auth | Phase |
|---|---|---|---|
| GET | /addresses | CUSTOMER | ✅ |
| POST | /addresses | CUSTOMER | ✅ |
| PUT | /addresses/{id} | CUSTOMER | ✅ |
| DELETE | /addresses/{id} | CUSTOMER | ✅ |

```json
{ "name": "Home", "latitude": -13.9626, "longitude": 33.7741, "area": "Area 47", "street": "…",
  "building": "…", "landmark": "Near the blue gate", "delivery_note": "…", "phone": "+265…", "is_default": true }
```

---

## ORDER (P3)

| Method | Path | Auth | Phase | 説明 |
|---|---|---|---|---|
| POST | /orders/quote | 任意 | ✅ | `{ store_id, delivery_address_id \| latitude+longitude, items }` → サーバー計算の金額（注文と同じロジック） |
| POST | /orders | CUSTOMER | ✅ | `{ store_id, delivery_address_id, payment_method, scheduled_at?, items:[{product_id, quantity, option_ids:[]}] }` → 金額再計算・エリア判定・在庫減算・PIN 生成 |
| GET | /orders | CUSTOMER | ✅ | 自分の注文（新しい順, ページネーション） |
| GET | /orders/{id} | CUSTOMER | ✅ | 詳細（snapshot 名称・金額、timeline、PIN は本人のみ） |
| POST | /orders/{id}/cancel | CUSTOMER | ✅ | NEW / CONFIRMED のみ（在庫を戻す） |
| GET | /orders/{id}/tracking | CUSTOMER | P5 | `{ status, driver: { latitude, longitude, updated_at }, timeline: [...] }` |

```json
// POST /orders  (Accept-Language: ja) → 201
{ "success": true, "data": {
  "id": 1, "order_number": "LLW-CENTRAL-260929-0001", "status": "NEW", "payment_status": "PENDING",
  "payment_method": "CASH", "currency": "MWK", "subtotal": 800000, "delivery_fee": 150000,
  "service_fee": 0, "discount": 0, "total": 950000, "delivery_pin": "7919",
  "items": [ { "name": "チキン弁当", "quantity": 2, "unit_price": 350000, "option_amount": 50000, "total": 800000,
               "options": [ { "name": "大盛り", "price": 50000 } ] } ],
  "timeline": [ { "status": "NEW", "at": "2026-09-29T17:10:00+00:00" } ] } }
```

* 金額は常にサーバーで計算（クライアント送信の価格は無視）。店舗価格上書き（store_products.price）を適用。
* オプションは商品に属し有効であること、グループの min/max を満たすこと（違反は `VALIDATION_FAILED` + `fields.items.N.option_ids`）。
* 販売停止・在庫不足は `PRODUCT_NOT_AVAILABLE`（`fields.items.N.product_id`）、エリア外は `OUT_OF_DELIVERY_AREA`、
  営業時間外（予約時は予約時刻で判定）は `STORE_NOT_AVAILABLE`。
* 注文番号: `{店舗コード}-{yymmdd(店舗TZ)}-{当日連番4桁}`。

## KITCHEN / ADMIN ORDERS (P3)

| Method | Path | Permission | Phase | 説明 |
|---|---|---|---|---|
| GET | /admin/orders?status[]=&store_id= | orders.view | ✅ | スコープ内の注文（新しい順） |
| GET | /admin/orders?board=kitchen | orders.view | ✅ | 厨房ボード: NEW / CONFIRMED / COOKING / READY_FOR_PICKUP を古い順 |
| GET | /admin/orders/{id} | orders.view | ✅ | 詳細。商品名は **閲覧スタッフの言語** で返し、`name_snapshot` に注文時の名称 |
| POST | /admin/orders/{id}/accept | kitchen.operate | ✅ | NEW → CONFIRMED（Mobile Money 未払いは `PAYMENT_REQUIRED`） |
| POST | /admin/orders/{id}/start-cooking | kitchen.operate | ✅ | CONFIRMED → COOKING |
| POST | /admin/orders/{id}/ready | kitchen.operate | ✅ | COOKING → READY_FOR_PICKUP（`OrderStatusChanged` イベント → P4 で Driver 割当） |
| POST | /admin/orders/{id}/cancel | kitchen.operate | ✅ | `{ reason_code? }`（大文字コード）。在庫を戻す |

遷移不可は `INVALID_STATUS_TRANSITION`（422）。二重タップ・複数スタッフ同時操作は行ロックで直列化。

厨房画面: `GET /kitchen`（Vue SPA, スタッフトークン認証, 10 秒ポーリング, 新規注文で通知音）。

## PAYMENT (P5) ✅

| Method | Path | 権限 | 説明 |
|---|---|---|---|
| POST | /payments | CUSTOMER（自分の注文のみ, 20/分） | `{ order_id, phone? }` → 注文の `payment_method`（AIRTEL_MONEY / TNM_MPAMBA）で `PaymentGatewayInterface::pay()`。`phone` 省略時は配送先の電話。PENDING の決済があればそれを返す（二重請求防止）。代引き・支払い済み・取消済みは `PAYMENT_NOT_REQUIRED` |
| GET | /payments/{id} | CUSTOMER（自分のみ, 他人は 404） | 状態確認。PENDING ならゲートウェイに `verify()` して更新（アプリは 3 秒ごとにポーリング） |
| POST | /payments/webhook | 公開（120/分） | ゲートウェイ callback。**署名検証 → サーバーから `verify()` で再確認**してから反映（webhook 本文は信用しない）。不一致は `INVALID_SIGNATURE` |

```json
{ "id": 1, "order_id": 1, "method": "AIRTEL_MONEY", "status": "PENDING", "amount": 200000, "currency": "MWK",
  "phone": "+265991234567", "reference": "MBPYIWDXILLEH6LLUJZEMS", "failure_code": null, "paid_at": null }
```

* 状態: `PENDING → PAID | FAILED`、`PAID → REFUNDED`。PAID で `orders.payment_status = PAID`（厨房が受付可能に）。
* FAILED は再試行可（新しい決済を作成）。`failure_code` はゲートウェイのコード（例 `INSUFFICIENT_FUNDS`）でアプリが翻訳。
* 支払い済み注文のキャンセルは自動返金（`refund()`、失敗時はログに残し手動対応）。
* 未払い Mobile Money 注文は `PAYMENT_UNPAID_TIMEOUT`（分, 既定 30）で自動キャンセル（`orders:expire-unpaid`, 毎分）。
* 代引き（CASH）は配達完了時に PAID（P4）。
* ゲートウェイ: `PAYMENT_GATEWAY=fake`（開発）/ `paychangu`。Fake は電話番号末尾で挙動を切替:
  `…0000` = 即時拒否（`INSUFFICIENT_FUNDS`）、`…9999` = PENDING のまま、それ以外 = 確認時に PAID。
  Fake の webhook 署名は `X-Fake-Signature: hex(HMAC-SHA256(body, FAKE_PAYMENT_WEBHOOK_SECRET))`。

## DEVICES / NOTIFICATIONS (P5) ✅

| Method | Path | 権限 | 説明 |
|---|---|---|---|
| POST | /devices | ログイン済み | `{ token, platform: android\|ios\|web, app: customer\|driver }` Push トークン登録（同じトークンは付け替え） |
| DELETE | /devices?token= | ログイン済み | ログアウト時に削除 |
| GET | /admin/notification-templates | translations.manage | テンプレート一覧（全言語の文面） |
| PUT | /admin/notification-templates/{id} | translations.manage | `{ is_active?, translations: { en: {title, body}, ny: {...} } }`（監査ログ） |

通知は注文・決済・配達オファーのイベントから非同期（キュー）で送信。文面の言語は **受信者の `preferred_language`**
（API リクエストの言語ではない）。詳細は [i18n.md §6–7](i18n.md#6-push-notificationphase-5)。

## DRIVER (P4) ✅

`auth:sanctum` + `role:DRIVER` + 有効な drivers プロフィール（無い → 403 `FORBIDDEN`、停止中 → 403 `ACCOUNT_DISABLED`）。
`{id}` は **注文 ID**。他の配達員の注文は 404。

| Method | Path | 説明 |
|---|---|---|
| GET | /driver/me | プロフィール + 現在の配送（`active_delivery`） |
| POST | /driver/online | `{ latitude?, longitude? }` オンライン化（待機中の READY 注文を即オファー） |
| POST | /driver/offline | オフライン化（保留中オファーは次の配達員へ） |
| POST | /driver/location | `{ latitude, longitude, order_id?, timestamp? }` または `{ points: [...] }`（最大 500, オフラインバッファ一括）。古い点で現在地は戻らない |
| GET | /driver/delivery-requests | 自分宛ての有効なオファー（`expires_in` 秒） |
| POST | /driver/deliveries/{id}/accept | READY_FOR_PICKUP → RIDER_ASSIGNED（失効/他者受諾は 409 `OFFER_NOT_AVAILABLE`） |
| POST | /driver/deliveries/{id}/decline | 辞退 → 次の候補へ |
| POST | /driver/deliveries/{id}/pickup | RIDER_ASSIGNED → PICKED_UP → ON_THE_WAY |
| POST | /driver/deliveries/{id}/arrive | ON_THE_WAY → ARRIVED |
| POST | /driver/deliveries/{id}/complete | `{ pin }` ARRIVED → DELIVERED。不一致 `DELIVERY_PIN_INVALID`、5 回失敗で `DELIVERY_PIN_LOCKED`。代引きはここで PAID |
| POST | /driver/deliveries/{id}/fail | `{ reason_code }` → FAILED_DELIVERY |
| GET | /driver/deliveries | 配達履歴 |
| GET | /driver/deliveries/{id} | 詳細 |

配達員向けレスポンスには **PIN を含めない**（顧客が口頭で伝える）。`amount_to_collect` は代引き未払い時の回収額。

### 配達員割当（DeliveryAssignmentService）

READY_FOR_PICKUP になった注文を、同じ FC の配達員のうち
「ACTIVE・オンライン・GPS が 10 分以内・配送中でない・他のオファーを保留していない・（店舗専属なら同じ店舗）」
から **Kitchen に最も近い 1 名** にオファー（有効 60 秒, `DISPATCH_OFFER_TTL`）。
辞退・失効・オフライン化で次の候補へ。候補がいない注文は `deliveries:dispatch`（毎分）で再試行。
注文キャンセル時は保留オファーを取り消す。

### 顧客の配送追跡

| Method | Path | 説明 |
|---|---|---|
| GET | /orders/{id}/tracking | `{ status, pickup, dropoff, driver: { name, vehicle_type, latitude, longitude, updated_at } \| null, timeline }`。driver は RIDER_ASSIGNED〜ARRIVED の間のみ |

### 管理: 配達員

| Method | Path | Permission | 説明 |
|---|---|---|---|
| GET | /admin/drivers?online= | drivers.manage | スコープ内の配達員 |
| POST | /admin/drivers | drivers.manage | `{ phone, name, vehicle_type, vehicle_number?, store_id?, preferred_language?, franchise_id(SAのみ) }` → DRIVER ユーザー + プロフィール作成 |
| PUT | /admin/drivers/{id} | drivers.manage | 車両・所属店舗・状態（SUSPENDED で強制オフライン） |

---|---|
| POST | /driver/online |
| POST | /driver/offline |
| POST | /driver/location  `{ latitude, longitude, order_id?, timestamp }` もしくは `{ points: [...] }`（オフラインバッファ一括） |
| GET | /driver/delivery-requests |
| POST | /driver/deliveries/{id}/accept |
| POST | /driver/deliveries/{id}/decline |
| POST | /driver/deliveries/{id}/pickup |
| POST | /driver/deliveries/{id}/arrive |
| POST | /driver/deliveries/{id}/complete  `{ pin }` |
| GET | /driver/deliveries（履歴） |

---

## ADMIN ✅（管理 API。Web UI = `/admin`, PHASE 6）

全て `auth:sanctum` + スタッフロール。Permission + テナントスコープで制御（他 FC は 404）。
管理系レスポンスの翻訳可能項目は `translations: { "<locale>": { ... } }` 形式。

| Method | Path | Permission | Phase |
|---|---|---|---|
| GET/POST | /admin/franchises | franchises.view / franchises.manage | ✅ |
| GET/PUT/DELETE | /admin/franchises/{id} | 同上 | ✅ |
| GET/POST | /admin/stores | stores.view / stores.create | ✅ |
| GET/PUT/DELETE | /admin/stores/{id} | stores.view / stores.update / stores.create | ✅ |
| GET/POST | /admin/kitchens | kitchens.view / kitchens.manage | ✅ |
| GET/PUT/DELETE | /admin/kitchens/{id} | 同上 | ✅ |
| GET/POST | /admin/delivery-zones | delivery_zones.* | ✅ |
| GET/PUT/DELETE | /admin/delivery-zones/{id} | 同上 | ✅ |
| GET/POST | /admin/categories | catalog.* | ✅ |
| GET/PUT/DELETE | /admin/categories/{id} | 同上 | ✅ |
| GET/POST | /admin/products | catalog.* | ✅ |
| GET/PUT/DELETE | /admin/products/{id} | 同上（option_groups を含めて保存） | ✅ |
| GET | /admin/stores/{id}/products | store_products.manage | ✅ |
| PUT | /admin/stores/{id}/products/{productId} | `{ is_available, price, stock_quantity }` | ✅ |
| GET/POST | /admin/languages | languages.manage | ✅ |
| PUT | /admin/languages/{id} | 同上 | ✅ |
| GET | /admin/audit-logs | audit_logs.view | ✅ |
| GET | /admin/dashboard | orders.view（金額は sales.view のみ） | ✅ P6 |
| GET | /admin/sales | sales.view | ✅ P6 |
| GET | /admin/customers?search= | customers.view | ✅ P6 |
| GET | /admin/customers/{id} | 同上（直近 20 件の注文。スコープ内のみ） | ✅ P6 |
| GET/POST | /admin/staff | staff.manage | ✅ P6 |
| GET/PUT | /admin/staff/{id} | 同上 | ✅ P6 |
| GET/PUT | /admin/settings | settings.manage | ✅ P6 |
| GET | /admin/translations | translations.manage | ✅ P6 |
| GET | /admin/translations/{type}?locale=&missing=1 | 同上 | ✅ P6 |
| PUT | /admin/translations/{type}/{id} | 同上 | ✅ P6 |

### 売上（`GET /admin/sales`）

`from`, `to`（YYYY-MM-DD, 組織タイムゾーンの暦日, 最大 366 日, 既定は直近 7 日）, `group_by`（`day` / `store` / `franchise` / `product` / `payment_method`）,
`franchise_id?`, `store_id?`（スコープ外 ID は 422）, `format=csv`（UTF-8 BOM 付き, 金額は主単位, 列名は安定コード）。

* **売上 = DELIVERED かつ PAID の注文**、日付は `ordered_at` の現地日付。`day` は空の日も 0 で返す。
* `franchise` 行には `commission_rate` と `commission`（= 商品小計 × 率。配送料・サービス料は除外）。
* `product` 行の名前は閲覧者の言語（現在の商品翻訳 → 注文時スナップショット）。
* 集計は常に閲覧者のテナント内（FC 管理者は自 FC、店長は自店舗のみ）。

```json
{ "from": "2026-03-04", "to": "2026-03-10", "timezone": "Africa/Blantyre", "currency": "MWK", "group_by": "day",
  "summary": { "orders": 42, "gross_sales": 12600000, "subtotal": 11000000, "delivery_fees": 1600000, "service_fees": 0,
               "discounts": 0, "average_order_value": 300000, "cancelled": 3, "failed_deliveries": 0 },
  "rows": [ { "key": "2026-03-04", "label": "2026-03-04", "orders": 5, "gross_sales": 1500000, "...": "..." } ] }
```

### スタッフ（`/admin/staff`）

`{ name, email, password, role, preferred_language?, is_active?, franchise_id?(FRANCHISE_ADMIN), store_id?(STORE_MANAGER / KITCHEN_STAFF) }`。
作成できる役割は自分より下のみ（SUPER_ADMIN → 全スタッフ役割, FRANCHISE_ADMIN → 店長・キッチン, STORE_MANAGER → キッチン）、
店舗/FC は自分のスコープ内のみ。組織・FC の ID は選んだ店舗から自動設定。`is_active: false` で即時に全トークン失効。

### 設定（`/admin/settings`）

`GET ?scope_type=GLOBAL|ORGANIZATION|FRANCHISE|STORE&scope_id=`、`PUT { scope_type, scope_id, values: { key: value | null } }`（null = 上書き解除）。
解決順は **STORE → FRANCHISE → ORGANIZATION → GLOBAL → config**。キー: `service_fee`（minor units, 注文金額に反映）、
`delivery_offer_ttl_seconds`（配達オファーの有効秒数）。GLOBAL / ORGANIZATION は本部のみ、FC/店舗は自スコープのみ（他は 404）。

### 翻訳管理（`/admin/translations`）

種類: `categories`, `products`, `option_groups`, `options`, `stores`（description / announcement）, `notification_templates`。
一覧はカバレッジ（既定言語の文面がある件数に対する各言語の翻訳済み件数）、`{type}?locale=ny&missing=1` で未翻訳のみ、
`PUT { locale, values: { name, description } }` は **その言語だけ** を更新（送らなかった属性は保持。既定言語の必須項目は空にできない）。

### 例: 商品作成
```json
POST /admin/products
{
  "category_id": 1, "sku": "BENTO-CHICKEN", "price": 350000, "preparation_minutes": 15,
  "is_active": true, "is_featured": true, "image_url": null,
  "translations": {
    "en": { "name": "Chicken Bento", "description": "Grilled chicken with rice" },
    "ny": { "name": "Bento ya Nkhuku", "description": "…" },
    "ja": { "name": "チキン弁当", "description": "…" }
  },
  "option_groups": [
    { "min_select": 1, "max_select": 1,
      "translations": { "en": { "name": "Rice size" }, "ja": { "name": "ご飯の量" } },
      "options": [ { "price": 0, "translations": { "en": { "name": "Regular" } } } ] }
  ]
}
```
`translations.<default locale>.name` は必須。未知の Locale キーは `VALIDATION_FAILED`（`fields.translations`）。
管理系の書き込みは **バリデーションより先に認可** を行う（権限なし → 403、他テナントのレコード → 404）。
