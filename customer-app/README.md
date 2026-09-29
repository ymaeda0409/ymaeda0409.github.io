# Malawi Bento — Customer App (Flutter)

PHASE 2 の顧客アプリ。Riverpod 3 / go_router / dio / gen-l10n（ARB）。

## 起動

```bash
flutter pub get
# Android エミュレータ（ホストの API: 10.0.2.2）
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api
# 実機 / Web
flutter run -d chrome --dart-define=API_BASE_URL=http://127.0.0.1:8000/api
# Google Maps を使う場合（プラットフォーム側の API キー設定も必要）
flutter run --dart-define=MAPS_ENABLED=true
```

| dart-define | 既定 | 説明 |
|---|---|---|
| `API_BASE_URL` | `http://10.0.2.2:8000/api` | Laravel API |
| `MAPS_ENABLED` | `false` | Google Maps のピン選択を有効化（無効時は GPS + 保存済み住所） |

Google Maps キー: Android は `android/local.properties` に `MAPS_API_KEY=...`、iOS は `ios/Runner/AppDelegate.swift` で `GMSServices.provideAPIKey` を設定。

## テスト

```bash
flutter analyze
flutter test
```

| テスト | 内容 |
|---|---|
| `test/l10n/arb_consistency_test.dart` | 全 ARB が en と同じキー・プレースホルダを持つ／キー命名規約 |
| `test/core/locale_and_format_test.dart` | 端末言語判定・English fallback・通貨/日付書式（intl 非対応の ny は en 書式） |
| `test/core/code_labels_test.dart` | 全 API エラーコード・注文ステータスが 3 言語で翻訳される／未知コードで落ちない |
| `test/core/api_client_test.dart` | 封筒形式の展開、Accept-Language / Bearer 付与、エラー→コード変換、GET 再試行 |
| `test/features/cart_test.dart` | カート集計（minor units）、店舗切替、永続化 |
| `test/features/app_flow_test.dart` | 初回言語選択、即時言語切替、3 言語 × 320dp × 文字 130% でオーバーフローなし、カート追加、要ログイン |
| `test/features/order_flow_test.dart` | 注文 → 完了（PIN）→ 履歴/詳細、キャンセル、Mobile Money 決済（請求 → 確認待ち → 支払い済み → 完了画面） |
| `test/core/push_registration_test.dart` | ログイン時の Push 端末登録、Firebase なしでの動作 |

## 決済（PHASE 5）

Checkout で Airtel Money / TNM Mpamba を選ぶと注文後に決済画面（`features/payment/`）へ。電話番号を確認して請求 →
端末で承認 → 3 秒ごとに状態確認 → 支払い済みで注文完了画面。失敗時は再試行、注文詳細の「今すぐ支払う」からも再開できます。
開発用 Fake ゲートウェイでは電話番号末尾 `0000` で拒否、`9999` で確認待ちのままになります。

## Push 通知（Firebase）

アプリは `PushTokenSource`（`packages/bento_core`）経由で端末トークンを取得し、ログイン後に `POST /devices`、
ログアウト時に `DELETE /devices` します。既定の `NoPushTokenSource` はトークンを返さないため、
**Firebase プロジェクトなしでもそのまま動作**します（通知が届かないだけ）。有効化する手順:

1. `flutterfire configure` で Firebase を設定し、`firebase_core` / `firebase_messaging` を追加
2. `PushTokenSource` を実装（`FirebaseMessaging.instance.getToken()` を返す）し、`main()` の
   `ProviderScope` で `pushTokenSourceProvider.overrideWithValue(...)`
3. バックエンドを `PUSH_DRIVER=fcm`, `FIREBASE_PROJECT_ID`, `FIREBASE_CREDENTIALS`（サービスアカウント JSON のパス）に設定

通知の文面はサーバーが受信者の言語で作成済み。`data.code`（例 `ORDER_ON_THE_WAY`）と `data.order_id` も届きます。

## 多言語

* 翻訳: `lib/l10n/app_{en,ny,ja}.arb`（テンプレート = en）。生成物は `lib/l10n/generated/`（`flutter gen-l10n`）。
* キー: 正規キー `order.place_order` → ARB キー `order_place_order`（[docs/i18n.md](../docs/i18n.md)）。
* API エラーコード / 注文ステータス → 翻訳の対応は `lib/core/ui/code_labels.dart` の 1 箇所のみ。
* Flutter 組込み非対応言語（ny）は `FallbackLocalizationsDelegate`、intl 非対応の書式は `FormattingLocale` が汎用ルールで英語にフォールバック。
* 言語追加: `app_en.arb` をコピーして `app_<code>.arb` を作り翻訳 → `flutter gen-l10n` → `flutter test`。

## 構成

```
lib/
├── core/        config, locale, network (ApiClient), format (money/date), storage, ui
├── features/    splash, language, auth, location, catalog, cart, checkout, orders, payment, account
├── l10n/        ARB + generated
├── app.dart     MaterialApp（locale は Riverpod state → 再起動なしで切替）
└── router.dart  go_router（/checkout 等はログイン必須）
```

低通信環境対策: カート・配送先・言語はローカル保存、翻訳はアプリ同梱、画像は縮小デコード + キャッシュ、GET は接続失敗時に 1 回再試行。
