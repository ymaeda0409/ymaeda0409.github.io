# Malawi Bento — Rider App (Flutter)

PHASE 4 の配達員アプリ。Riverpod 3 / go_router / geolocator / url_launcher、共通部品は [`packages/bento_core`](../packages/bento_core)。

## 起動

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api   # Android エミュレータ
```

テスト用配達員（Seeder）: `+265990000001`（en）/ `+265990000002`（ny）/ `+265990000003`（ja）、OTP `123456`。
配達員の追加は管理 API `POST /api/admin/drivers`（電話番号で DRIVER ユーザーを作成）。

## 画面と流れ

| # | 画面 | 実装 |
|---|---|---|
| 01 | Language Selection | `features/language/language_screen.dart`（選択中の言語で即プレビュー） |
| 02 | Login | `features/auth/`（OTP。DRIVER 以外のアカウントは拒否） |
| 03–04 | Home / ONLINE・OFFLINE | `features/delivery/home_screen.dart` |
| 05 | New Delivery Request | 同上（残り秒数、受ける/断る、代引き回収額） |
| 06–07 | Pickup Navigation / Confirmation | 同上（Google Maps ナビ・電話・「商品を受け取った」） |
| 08–09 | Delivery Navigation / Arrival | 同上（目印・メモ・「到着した」） |
| 10 | Delivery PIN | 同上（大きなテンキー、残り回数） |
| 11 | Delivery Complete | `features/delivery/complete_screen.dart` |
| 12 | Delivery History | `features/delivery/history_screen.dart` |
| 13 | Language Settings | `features/language/language_screen.dart`（`users.preferred_language` にも同期） |

## Push 通知

新しい配達依頼（`DRIVER_NEW_DELIVERY`）はライダーの言語で Push 送信されます。端末登録は顧客アプリと同じ
`PushTokenSource` / `DeviceRegistrar`（ログイン後に登録、ログアウトで削除）。Firebase を使う手順は
[customer-app/README.md](../customer-app/README.md#push-通知firebase) を参照（`pushTokenSourceProvider` を上書き）。
依頼の取得はポーリング（10 秒）でも行うため、Push がなくても配達は可能です。

## 通信の弱い環境への対策

* **現在の配送を端末に保存**（再起動・圏外でも表示を継続）。
* **受取・到着は送信キュー**（`ActionOutbox`）: 圏外なら端末上で先に進め、復帰後に再送。サーバーで適用済みなら破棄。
  PIN 確認はサーバー照合が必要なためオンライン必須。
* **GPS バッファ**（`LocationBuffer`）: 最大 500 点を端末に保持し、端末時刻付きで一括送信。
* 10 秒ごとの同期（依頼の取得・キャンセル検知・キュー再送）は Home 画面表示中のみ動作。

## テスト

```bash
flutter analyze
flutter test   # 15 tests
```

| テスト | 内容 |
|---|---|
| `test/l10n/arb_consistency_test.dart` | 全 ARB のキー・プレースホルダ一致 |
| `test/features/offline_queues_test.dart` | GPS バッファ（圏外保持・一括送信・上限）、送信キュー（順序再送・適用済み破棄） |
| `test/features/rider_flow_test.dart` | 初回言語選択、オンライン → 受諾 → 受取 → 到着 → PIN（誤り → 正解）→ 完了、圏外での受取とキュー再送、再起動後の配送復元、3 言語 × 320dp × 文字 130% のレイアウト |
