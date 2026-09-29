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
| 409 | `CONFLICT` | 状態競合 |
| 422 | `VALIDATION_FAILED` | バリデーション（`fields` にフィールド別メッセージ） |
| 422 | `OTP_INVALID` | OTP 不一致 |
| 422 | `OTP_EXPIRED` | OTP 期限切れ / 未発行 |
| 422 | `LANGUAGE_NOT_SUPPORTED` | 無効な言語コード |
| 422 | `STORE_NOT_AVAILABLE` | 店舗が営業外/受付停止 |
| 422 | `OUT_OF_DELIVERY_AREA` | 配送エリア外 (P3) |
| 422 | `PRODUCT_NOT_AVAILABLE` | 販売停止・在庫切れ (P3) |
| 422 | `INVALID_STATUS_TRANSITION` | 注文ステータス遷移不可 (P3) |
| 422 | `DELIVERY_PIN_INVALID` | PIN 不一致 (P4) |
| 422 | `PAYMENT_FAILED` | 決済失敗 (P5) |
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
| PUT | /account/language | ✔ | ✅ | `{ language: "ja" }` → preferred_language 更新（監査ログ） |

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

| Method | Path | 説明 |
|---|---|---|
| POST | /orders | `{ store_id, delivery_address_id, payment_method, scheduled_at?, items:[{product_id, quantity, option_ids:[]}] }` → Server 側で金額再計算・エリア判定・PIN 生成 |
| GET | /orders | 自分の注文 |
| GET | /orders/{id} | 詳細（snapshot） |
| POST | /orders/{id}/cancel | NEW/CONFIRMED のみ |
| GET | /orders/{id}/tracking | `{ status, driver: { latitude, longitude, updated_at }, timeline: [...] }` (P5) |

## KITCHEN / ADMIN ORDERS (P3)

| Method | Path | 説明 |
|---|---|---|
| GET | /admin/orders?status= | スコープ内の注文 |
| POST | /admin/orders/{id}/accept | NEW → CONFIRMED |
| POST | /admin/orders/{id}/start-cooking | CONFIRMED → COOKING |
| POST | /admin/orders/{id}/ready | COOKING → READY_FOR_PICKUP（→ Driver 割当開始） |
| POST | /admin/orders/{id}/cancel | |

## PAYMENT (P5)

| Method | Path | 説明 |
|---|---|---|
| POST | /payments | `{ order_id, method, phone? }` → `PaymentGatewayInterface::pay()` |
| POST | /payments/webhook | ゲートウェイ callback（署名検証 → `verify()`） |

## DRIVER (P4)

| Method | Path |
|---|---|
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

## ADMIN (PHASE 1 実装分: 管理 API。Web UI は PHASE 6)

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
| GET | /admin/dashboard | (P6) | |
| GET | /admin/sales | (P6) | |

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
`translations.<default locale>.name` は必須。未知の Locale キーは `LANGUAGE_NOT_SUPPORTED`（422）。
