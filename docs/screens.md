# Screens

共通 UI 方針
* Uber Eats のコピーではない独自デザイン。ブランドカラー: 深緑（#1F6F50, マラウイの緑を意識）＋暖色アクセント（#F2A541）。
* スマホ最優先・片手操作。主要ボタンは高さ 52dp 以上、全幅。本文 16sp 以上。
* 固定幅でテキストを切らない（`Flexible` + 折返し）。英語/Chichewa/日本語で文字量が変わっても崩れない。
* 低価格 Android 前提: 画像は遅延読込・キャッシュ、アニメーション最小限、リストは `ListView.builder`。
* 画面内の全文言は翻訳キー（`screen.key`）。下表の「Key prefix」がその画面の名前空間。

---

## 1. Customer App（Flutter）

| # | Screen | 主な要素 | Key prefix | API |
|---|---|---|---|---|
| 01 | Splash | ロゴ、保存 Locale/トークン読込 | `splash.` | — |
| 02 | Language Selection | English / Chichewa / 日本語（`native_name` 表示、端末言語を初期選択） | `language.` | `GET /languages`（失敗時は同梱リスト） |
| 03 | Login / Phone | 国番号 +265 固定表示、電話番号入力、「コードを送信」 | `auth.` | `POST /auth/send-otp` |
| 04 | OTP | 6 桁入力、再送（カウントダウン） | `auth.otp_` | `POST /auth/verify-otp` |
| 05 | Home | 上部「Deliver to + 現在の配送先」、カテゴリ横スクロール、おすすめ、商品一覧 | `home.` | `GET /stores/available`, `/categories`, `/products` |
| 06 | Delivery Location | 地図ピン（Google Maps）、現在地、保存済み住所、ランドマーク入力 | `location.` | `/addresses`, `/stores/available` |
| 07 | Product List | カテゴリ別一覧、価格（通貨 Formatter） | `product.` | `GET /products?category_id=` |
| 08 | Product Detail | 画像、名称、説明、オプション（必須/任意）、数量、カート追加 | `product.` | `GET /products/{id}` |
| 09 | Cart | 明細、数量変更、小計/配送料/サービス料/割引/合計 | `cart.` | ローカル |
| 10 | Checkout | 配送先、支払方法、配送希望時間（今すぐ/予約）、注文内容 | `checkout.` | `POST /orders` (P3) |
| 11 | Payment | Cash / Airtel Money / TNM Mpamba、Mobile Money の電話番号 | `payment.` | `POST /payments` (P5) |
| 12 | Order Complete | 注文番号、Delivery PIN、追跡へ | `order.` | — |
| 13 | Order Tracking | ステータスタイムライン、地図上の配達員位置、PIN | `tracking.` / `order.status.*` | `GET /orders/{id}/tracking` (P5) |
| 14 | Order History | 注文一覧 | `order.` | `GET /orders` |
| 15 | Order Detail | 明細（snapshot 名称）、金額、ステータス | `order.` | `GET /orders/{id}` |
| 16 | Account | 名前、電話、言語、住所、ログアウト | `account.` | `GET /account` |
| 17 | Saved Addresses | 一覧/追加/編集/デフォルト設定 | `address.` | `/addresses` |
| 18 | Language Settings | 02 と同じ部品を再利用、即時反映 | `language.` | `PUT /account/language` |

### Navigation

```
Splash ─┬─(初回)→ Language Selection → Login/Phone → OTP → Home
        └─(ログイン済)→ Home
Home ─ BottomNav: [Home] [Orders] [Account]
Home → Product List → Product Detail → Cart → Checkout → Payment → Order Complete → Order Tracking
Account → Saved Addresses / Language Settings / Logout
```

> 未ログインでも Home 閲覧可（カート→Checkout 時にログイン要求）にすると初回離脱が減る。MVP はこの方式。

---

## 2. Driver App（Flutter）

| # | Screen | 主な要素 | Key prefix | API (P4) |
|---|---|---|---|---|
| 01 | Language Selection | Customer と同部品 | `language.` | `GET /languages` |
| 02 | Login | 電話番号 + OTP（DRIVER ロールのみ許可） | `auth.` | `/auth/send-otp`, `/auth/verify-otp` |
| 03 | Home | ONLINE/OFFLINE 大トグル、今日の配達数、現在の配送カード | `driver.` | — |
| 04 | ONLINE / OFFLINE | 切替確認、位置権限チェック | `driver.status_` | `POST /driver/online`, `/offline` |
| 05 | New Delivery Request | 店舗名、距離、配送先エリア、配達料、承諾/辞退（カウントダウン） | `driver.request_` | `GET /driver/delivery-requests`, `accept`, `decline` |
| 06 | Pickup Navigation | 店舗への地図/Google Maps 起動 | `driver.pickup_` | `POST /driver/location` |
| 07 | Pickup Confirmation | 注文番号照合、受取完了 | `driver.pickup_` | `POST /driver/deliveries/{id}/pickup` |
| 08 | Delivery Navigation | 配送先ピン、ランドマーク、顧客へ電話 | `driver.delivery_` | `POST /driver/location` |
| 09 | Customer Arrival | 到着ボタン | `driver.arrival_` | `POST /driver/deliveries/{id}/arrive` |
| 10 | Delivery PIN | 4 桁入力（大きなテンキー） | `driver.pin_` | `POST /driver/deliveries/{id}/complete` |
| 11 | Delivery Complete | 完了、次の依頼待ちへ | `driver.complete_` | — |
| 12 | Delivery History | 過去の配達 | `driver.history_` | `GET /driver/deliveries` |
| 13 | Language Settings | 即時反映 | `language.` | `PUT /account/language` |

オフライン対策: 現在の配送を SQLite に保存、失敗した状態変更 API は送信キューで再送（冪等）、GPS は端末でバッファ。

---

## 3. Admin / Kitchen Web（Laravel + Vue, PHASE 3 / 6）

言語切替: ヘッダの言語メニュー（`users.preferred_language` を更新）。Vue 側は `vue-i18n` + `resources/js/locales/{en,ny,ja}.json`。

| Screen | 内容 | 利用ロール |
|---|---|---|
| Login | email + password | スタッフ全般 |
| Dashboard | 今日の注文数/売上、調理中、配送中、配達済、キャンセル、オンライン配達員、平均単価、平均配達時間（スコープ別） | SA, FA, SM |
| Orders | 一覧・検索・詳細・キャンセル | SA, FA, SM |
| **Kitchen** | NEW / COOKING / READY の 3 カラム。ボタン ACCEPT ORDER / START COOKING / READY。自動更新・音 | SM, KS |
| Products | 一覧、編集（言語タブ: 名称/説明）、画像、価格、オプション | SA |
| Store Products | 店舗ごとの販売可否・価格上書き・在庫 | SA, FA, SM |
| Categories | 一覧、編集（言語タブ） | SA |
| Franchises | Add Franchise, 編集, 手数料率 | SA |
| Stores | Add Store, 営業時間, 受付停止, 説明/お知らせ（言語タブ） | SA（作成）, FA, SM |
| Kitchens | Add Kitchen | SA, FA |
| Delivery Zones | 料金・距離設定、地図プレビュー | SA, FA, SM |
| Drivers | 登録、所属、状態、オンライン一覧 | SA, FA, SM |
| Customers | 検索、注文履歴 | SA, FA |
| Sales | 期間（Today/Yesterday/This Week/This Month/Custom）、商品別、店舗別、FC 別 | SA, FA, SM |
| Translations | 商品/カテゴリ/オプション/通知テンプレートの未翻訳一覧と編集 | SA |
| Languages | 追加、有効化、並び順、既定 | SA |
| Settings | サービス料、OTP、SMS、決済設定 | SA |
| Audit Logs | 操作履歴 | SA, FA |
