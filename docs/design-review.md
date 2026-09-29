# Design Self-Review

設計ドキュメント（architecture / database / i18n / screens / api / development-plan）作成後に実施したセルフレビュー。
「矛盾」「将来問題になりうる点」を洗い出し、対応方針を決めた。✅ = ドキュメント/実装に反映済み。

## A. 矛盾・曖昧さ

| # | 指摘 | 対応 |
|---|---|---|
| A1 | 厨房ボタン「ACCEPT ORDER」とステータス `CONFIRMED` の関係が曖昧。Mobile Money 未払い注文を厨房が受けてしまう恐れ | ✅ `NEW → CONFIRMED` を ACCEPT とし、Mobile Money は `payment_status=PAID` の場合のみ受付可、CASH は即可（database.md §4） |
| A2 | 厨房画面の表示「READY」と内部コード `READY_FOR_PICKUP` の不一致 | ✅ 内部コードは `READY_FOR_PICKUP` 固定。短縮表示は翻訳側の責務 |
| A3 | 要件の翻訳キー例 `order.place_order` は Flutter gen-l10n の ARB キーとして使えない（`.` 不可） | ✅ 正規キーの `.` → `_` 置換を ARB キーとする規約（i18n.md §3） |
| A4 | 要件は `resources/lang/<locale>` だが Laravel 9+ の標準は `lang/<locale>` | ✅ Laravel 標準の `backend/lang/` を採用し明記（役割は同一） |
| A5 | 商品価格は Organization 単位、通貨は Store 単位 → 異通貨店舗で矛盾 | MVP は「店舗通貨 = 組織通貨」を Service で検証。多通貨化時は `store_products.price`（店舗通貨）を必須化する方針 |
| A6 | `orders.driver_id` は PHASE 3 だが `drivers` は PHASE 4 | PHASE 3 では nullable 列のみ作成し、FK 制約は PHASE 4 のマイグレーションで追加 |
| A7 | 「Accept-Language 優先」だと、ブラウザが自動送信する Accept-Language が Admin 画面でユーザー設定を上書きしてしまう | Admin SPA は axios で **選択中 UI 言語を明示的に** Accept-Language に設定する。ワイルドカード `*` や未対応言語のみのヘッダは「明示なし」とみなし preferred_language に進む ✅ |
| A8 | verify-otp は未登録番号を CUSTOMER として自動作成 → Driver App で未登録番号がログインできてしまう | Driver API は DRIVER ロール必須（403 `FORBIDDEN`）。Driver App は verify 後に role を確認しログアウトさせる |

## B. 将来問題になりうる点

| # | リスク | 対応 |
|---|---|---|
| B1 | 1 ユーザー 1 ロール（`users.role`）では複数店舗兼務・FC オーナー兼店長に対応できない | Permission API（`hasPermission`, `visibleTo`）の裏側だけを `role_assignments` に差し替えられるよう、ロール比較をコードに散らさない ✅ |
| B2 | 非正規化した `franchise_id` が、店舗の FC 移管時に不整合 | `StoreService::update` で FC 変更時に kitchens / delivery_zones を同一トランザクションで更新 ✅。過去の orders は売上帰属のため **変更しない**（仕様） |
| B3 | 距離計算を PHP で行うため店舗数増加で遅くなる | SQL バウンディングボックスで候補を絞ってから計算 ✅。数百店舗超で PostGIS (`ST_DWithin`) に移行（Service の I/F は不変） |
| B4 | 翻訳 eager load が言語数に比例して肥大 | `withTranslations()` が要求 Locale + fallback の 2 言語のみロード ✅ |
| B5 | Chichewa は Flutter Material / intl / ICU のロケールデータが不完全 | 「組込み未対応なら en」の汎用 Fallback デリゲートと FormattingLocale（i18n.md §3）。言語固有 if 文は作らない |
| B6 | 日本語 SMS は UCS-2 で 70 文字/通 → コスト増 | 通数上限を超えたら en 文面にフォールバック（i18n.md §7） |
| B7 | OTP 固定コードの本番混入 | `APP_ENV=production` では `OTP_TEST_MODE` を無視 ✅（テストあり） |
| B8 | OTP 総当たり | OTP 5 回失敗で無効化、send-otp は番号単位 60 秒 + IP 単位の rate limit ✅ |
| B9 | 他 FC リソースの ID 推測 | 403 ではなく 404 を返し存在を秘匿 ✅（テストあり） |
| B10 | Chichewa 訳の品質 | 初版は開発者訳。リリース前にネイティブレビュー必須（i18n.md §1）。ファイル/管理画面の修正のみで反映可能 |
| B11 | 金額の float 誤差 | minor units 整数で統一 ✅ |
| B12 | 言語を無効化すると既存ユーザーの preferred_language が無効言語を指す | SetLocale は有効言語のみ採用し、無効なら default へ fallback ✅。FK により言語の物理削除は不可（無効化のみ） |
| B13 | 店舗営業時間とタイムゾーン | `stores.timezone` で判定 ✅。DB は UTC |
| B14 | 業態拡張（Grocery 等）で「厨房」概念が合わない | Kitchen は「フルフィルメント拠点」として汎用に扱う（名称は UI 翻訳で業態別に変えられる）。`stores.business_type` で分岐 |

## C. 過剰設計として見送ったもの

* 独立した `regions` テーブル（`franchises.region` / `stores.city` 文字列で十分。ゾーンが地理を担う）
* 汎用 translations テーブル（polymorphic）: 型安全性・FK・インデックスの観点でエンティティ別テーブルを採用
* WebSocket によるリアルタイム追跡（MVP はポーリング）
* Polygon ゾーン（列のみ予約）
