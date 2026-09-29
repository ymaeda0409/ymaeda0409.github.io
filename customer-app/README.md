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
├── features/    splash, language, auth, location, catalog, cart, checkout, account
├── l10n/        ARB + generated
├── app.dart     MaterialApp（locale は Riverpod state → 再起動なしで切替）
└── router.dart  go_router（/checkout 等はログイン必須）
```

低通信環境対策: カート・配送先・言語はローカル保存、翻訳はアプリ同梱、画像は縮小デコード + キャッシュ、GET は接続失敗時に 1 回再試行。
