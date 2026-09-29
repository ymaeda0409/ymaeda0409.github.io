// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Japanese (`ja`).
class AppLocalizationsJa extends AppLocalizations {
  AppLocalizationsJa([String locale = 'ja']) : super(locale);

  @override
  String get app_title => 'Malawi Bento';

  @override
  String get language_native_name => '日本語';

  @override
  String get common_retry => '再試行';

  @override
  String get common_cancel => 'キャンセル';

  @override
  String get common_save => '保存';

  @override
  String get common_continue => '続ける';

  @override
  String get common_ok => 'OK';

  @override
  String get common_delete => '削除';

  @override
  String get common_edit => '編集';

  @override
  String get common_optional => '任意';

  @override
  String get common_required => '必須';

  @override
  String get language_title => '言語を選択してください';

  @override
  String get language_subtitle => 'アカウント画面からいつでも変更できます。';

  @override
  String get language_settings_title => '言語';

  @override
  String get language_changed => '言語を変更しました';

  @override
  String get auth_phone_title => '電話番号でログイン';

  @override
  String get auth_phone_label => '電話番号';

  @override
  String get auth_send_code => 'コードを送信';

  @override
  String get auth_phone_invalid => '正しい電話番号を入力してください';

  @override
  String get auth_otp_title => '認証コードを入力してください';

  @override
  String auth_otp_sent_to(String phone) {
    return '$phone に6桁のコードを送信しました';
  }

  @override
  String get auth_verify => '認証する';

  @override
  String get auth_resend => 'コードを再送信';

  @override
  String auth_resend_in(int seconds) {
    return '$seconds秒後に再送信できます';
  }

  @override
  String get auth_login_required => '続行するにはログインしてください';

  @override
  String get auth_logout => 'ログアウト';

  @override
  String get nav_home => 'ホーム';

  @override
  String get nav_cart => 'カート';

  @override
  String get nav_account => 'アカウント';

  @override
  String get home_title => 'ホーム';

  @override
  String get home_delivery_to => 'お届け先';

  @override
  String get home_choose_location => 'お届け先を選択';

  @override
  String get home_categories => 'カテゴリー';

  @override
  String get home_featured => 'おすすめ';

  @override
  String get home_all_products => 'すべての商品';

  @override
  String get home_no_store => '申し訳ありません。この場所はまだ配達エリア外です。';

  @override
  String home_store_closed(String store) {
    return '$storeは現在営業時間外です';
  }

  @override
  String get location_title => 'お届け先';

  @override
  String get location_use_current => '現在地を使う';

  @override
  String get location_saved_addresses => '保存済みの住所';

  @override
  String get location_new_address => '新しい住所';

  @override
  String get location_name_label => '名前（例：自宅、職場）';

  @override
  String get location_area_label => 'エリア';

  @override
  String get location_street_label => '通り';

  @override
  String get location_building_label => '建物';

  @override
  String get location_landmark_label => '目印';

  @override
  String get location_landmark_hint => '例：青い門の近く';

  @override
  String get location_note_label => '配達員へのメモ';

  @override
  String get location_confirm => 'ここに届ける';

  @override
  String get location_permission_denied => '現在地の取得には位置情報の許可が必要です';

  @override
  String get location_move_map => '地図を動かしてピンの位置を合わせてください';

  @override
  String get product_add_to_cart => 'カートに追加';

  @override
  String get product_sold_out => '売り切れ';

  @override
  String product_choose_up_to(int count) {
    return '$count個まで選択';
  }

  @override
  String product_choose_exactly(int count) {
    return '$count個選択してください';
  }

  @override
  String get product_quantity => '数量';

  @override
  String product_preparation_time(int minutes) {
    return '約$minutes分';
  }

  @override
  String get product_added => 'カートに追加しました';

  @override
  String get product_list_empty => '商品がありません';

  @override
  String get cart_title => 'カート';

  @override
  String get cart_empty => 'カートは空です';

  @override
  String get cart_browse => 'メニューを見る';

  @override
  String get cart_subtotal => '小計';

  @override
  String get cart_delivery_fee => '配送料';

  @override
  String get cart_service_fee => 'サービス料';

  @override
  String get cart_discount => '割引';

  @override
  String get cart_total => '合計';

  @override
  String get cart_checkout => 'レジに進む';

  @override
  String cart_item_count(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count点',
    );
    return '$_temp0';
  }

  @override
  String get cart_replace_title => '新しいカートを始めますか？';

  @override
  String cart_replace_message(String store) {
    return 'カートに$storeの商品が入っています。この商品を追加するとカートが空になります。';
  }

  @override
  String get cart_replace_confirm => '新しいカートを始める';

  @override
  String get cart_remove => '削除';

  @override
  String get checkout_title => '注文手続き';

  @override
  String get checkout_delivery_address => 'お届け先';

  @override
  String get checkout_change => '変更';

  @override
  String get checkout_payment_method => '支払い方法';

  @override
  String get checkout_delivery_time => 'お届け時間';

  @override
  String get checkout_asap => 'できるだけ早く';

  @override
  String get checkout_schedule => '日時を指定';

  @override
  String checkout_scheduled_for(String time) {
    return '指定日時：$time';
  }

  @override
  String get checkout_order_summary => '注文内容';

  @override
  String get checkout_mobile_money_phone => 'モバイルマネーの電話番号';

  @override
  String get checkout_estimate_note => '最終金額は注文確定時に確定します。';

  @override
  String get checkout_select_address => '保存済みのお届け先を選択してください';

  @override
  String get order_place_order => '注文する';

  @override
  String get payment_cash => '代金引換';

  @override
  String get payment_airtel_money => 'Airtel Money';

  @override
  String get payment_tnm_mpamba => 'TNM Mpamba';

  @override
  String get account_title => 'アカウント';

  @override
  String get account_name => '名前';

  @override
  String get account_phone => '電話番号';

  @override
  String get account_language => '言語';

  @override
  String get account_addresses => '保存済みの住所';

  @override
  String get account_sign_in => 'ログイン';

  @override
  String get account_guest => 'ログインしていません';

  @override
  String get address_title => '保存済みの住所';

  @override
  String get address_empty => '保存済みの住所はありません';

  @override
  String get address_default => '既定';

  @override
  String get address_set_default => '既定にする';

  @override
  String get address_delete_confirm => 'この住所を削除しますか？';

  @override
  String get error_bad_request => 'リクエストが正しくありません。';

  @override
  String get error_unauthenticated => '続行するにはログインしてください。';

  @override
  String get error_invalid_credentials => 'メールアドレスまたはパスワードが正しくありません。';

  @override
  String get error_forbidden => 'この操作を行う権限がありません。';

  @override
  String get error_account_disabled => 'このアカウントは無効化されています。';

  @override
  String get error_resource_not_found => '指定されたデータが見つかりません。';

  @override
  String get error_route_not_found => 'この機能はまだ利用できません。';

  @override
  String get error_method_not_allowed => 'この操作は許可されていません。';

  @override
  String get error_conflict => '現在の状態ではこの操作を実行できません。';

  @override
  String get error_validation_failed => '入力内容を確認してください。';

  @override
  String get error_otp_invalid => '認証コードが正しくありません。';

  @override
  String get error_otp_expired => '認証コードの有効期限が切れました。再送信してください。';

  @override
  String get error_language_not_supported => 'この言語には対応していません。';

  @override
  String get error_store_not_available => 'この店舗は現在注文を受け付けていません。';

  @override
  String get error_out_of_delivery_area => 'この場所は配達エリア外です。';

  @override
  String get error_product_not_available => 'この商品は現在ご注文いただけません。';

  @override
  String get error_invalid_status_transition => '注文の現在の状態ではこの操作はできません。';

  @override
  String get error_delivery_pin_invalid => 'PINが正しくありません。';

  @override
  String get error_payment_failed => 'お支払いを完了できませんでした。';

  @override
  String get error_too_many_requests => '試行回数が多すぎます。しばらくお待ちください。';

  @override
  String get error_server_error => 'サーバーでエラーが発生しました。もう一度お試しください。';

  @override
  String get error_network => 'インターネットに接続できません。通信環境を確認してください。';

  @override
  String get error_timeout => '通信に時間がかかっています。もう一度お試しください。';

  @override
  String get error_unknown => 'エラーが発生しました。もう一度お試しください。';

  @override
  String get order_status_new => '注文受付';

  @override
  String get order_status_confirmed => '確定';

  @override
  String get order_status_cooking => '調理中';

  @override
  String get order_status_ready_for_pickup => '受け取り待ち';

  @override
  String get order_status_rider_assigned => '配達員決定';

  @override
  String get order_status_picked_up => '配達員が受け取り済み';

  @override
  String get order_status_on_the_way => '配達中';

  @override
  String get order_status_arrived => '配達員到着';

  @override
  String get order_status_delivered => '配達完了';

  @override
  String get order_status_cancelled => 'キャンセル';

  @override
  String get order_status_failed_delivery => '配達失敗';

  @override
  String get order_status_unknown => '不明';

  @override
  String location_coordinates(String latitude, String longitude) {
    return '$latitude, $longitude';
  }
}
