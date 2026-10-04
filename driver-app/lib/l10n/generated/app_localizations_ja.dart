// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Japanese (`ja`).
class AppLocalizationsJa extends AppLocalizations {
  AppLocalizationsJa([String locale = 'ja']) : super(locale);

  @override
  String get language_native_name => '日本語';

  @override
  String get app_title => 'Malawi Bento ライダー';

  @override
  String get common_retry => '再試行';

  @override
  String get common_cancel => 'キャンセル';

  @override
  String get common_continue => '続ける';

  @override
  String get common_ok => 'OK';

  @override
  String get language_title => '言語を選択してください';

  @override
  String get language_subtitle => '設定画面からいつでも変更できます。';

  @override
  String get language_settings_title => '言語';

  @override
  String get language_changed => '言語を変更しました';

  @override
  String get auth_phone_title => 'ライダーログイン';

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
  String get auth_logout => 'ログアウト';

  @override
  String get auth_not_a_driver => 'この電話番号はライダーとして登録されていません。店舗に連絡してください。';

  @override
  String get driver_home_title => '配達';

  @override
  String get driver_status_online => 'オンライン中';

  @override
  String get driver_status_offline => 'オフライン';

  @override
  String get driver_go_online => 'オンラインにする';

  @override
  String get driver_go_offline => 'オフラインにする';

  @override
  String get driver_waiting_requests => '配達依頼を待っています…';

  @override
  String get driver_offline_hint => 'オンラインにすると配達依頼を受け取れます。';

  @override
  String get driver_location_required => 'オンラインにするには位置情報の許可が必要です。';

  @override
  String get driver_new_delivery => '新しい配達';

  @override
  String driver_request_expires_in(int seconds) {
    return '残り$seconds秒';
  }

  @override
  String driver_distance_to_pickup(String distance) {
    return '受け取りまで $distance km';
  }

  @override
  String driver_delivery_distance(String distance) {
    return '配達距離 $distance km';
  }

  @override
  String get driver_accept => '受ける';

  @override
  String get driver_decline => '断る';

  @override
  String driver_collect_cash(String amount) {
    return '現金 $amount を受け取る';
  }

  @override
  String get driver_prepaid => '支払済み';

  @override
  String get driver_pickup_title => '受け取り';

  @override
  String get driver_pickup_at => '受け取り場所';

  @override
  String get driver_navigate => 'ナビ';

  @override
  String get driver_call => '電話';

  @override
  String driver_order_number(String number) {
    return '注文 $number';
  }

  @override
  String driver_items_count(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count点',
    );
    return '$_temp0';
  }

  @override
  String get driver_confirm_pickup => '商品を受け取った';

  @override
  String get driver_delivery_title => '配達';

  @override
  String get driver_deliver_to => 'お届け先';

  @override
  String get driver_landmark => '目印';

  @override
  String get driver_note => 'メモ';

  @override
  String get driver_arrived => '到着した';

  @override
  String get driver_pin_title => '受け取りPIN';

  @override
  String get driver_pin_hint => 'お客様に4桁のPINを聞いてください。';

  @override
  String driver_pin_attempts_left(int count) {
    return '残り$count回';
  }

  @override
  String get driver_confirm_delivery => '配達完了';

  @override
  String get driver_delivery_failed => 'お客様不在';

  @override
  String get driver_fail_confirm => 'この配達を失敗として記録しますか？';

  @override
  String get driver_complete_title => '配達完了！';

  @override
  String get driver_complete_message => 'お疲れさまでした。次の配達を受けられます。';

  @override
  String get driver_back_home => 'ホームに戻る';

  @override
  String get driver_history_title => '配達履歴';

  @override
  String get driver_history_empty => 'まだ配達はありません';

  @override
  String get driver_pending_sync => 'オフラインです。接続後に自動で送信します。';

  @override
  String get driver_settings => '設定';

  @override
  String driver_vehicle(String vehicle) {
    return '車両：$vehicle';
  }

  @override
  String get error_bad_request => 'リクエストが正しくありません。';

  @override
  String get error_unauthenticated => 'もう一度ログインしてください。';

  @override
  String get error_forbidden => 'この操作を行う権限がありません。';

  @override
  String get error_account_disabled => 'ライダーアカウントが停止されています。';

  @override
  String get error_resource_not_found => 'この配達は見つかりません。';

  @override
  String get error_validation_failed => '入力内容を確認してください。';

  @override
  String get error_otp_invalid => '認証コードが正しくありません。';

  @override
  String get error_otp_expired => '認証コードの有効期限が切れました。再送信してください。';

  @override
  String get error_invalid_status_transition => 'この配達は既に次の段階に進んでいます。';

  @override
  String get error_offer_not_available => 'この依頼は既に無効です。';

  @override
  String get error_delivery_pin_invalid => 'PINが正しくありません。お客様にもう一度確認してください。';

  @override
  String get error_delivery_pin_locked => 'PINの入力ミスが多すぎます。店舗に電話してください。';

  @override
  String get error_too_many_requests => '試行回数が多すぎます。しばらくお待ちください。';

  @override
  String get error_server_error => 'サーバーでエラーが発生しました。もう一度お試しください。';

  @override
  String get error_network => 'インターネットに接続できません。';

  @override
  String get error_timeout => '通信に時間がかかっています。もう一度お試しください。';

  @override
  String get error_unknown => 'エラーが発生しました。もう一度お試しください。';

  @override
  String get order_status_rider_assigned => '受け取りへ';

  @override
  String get order_status_picked_up => '受け取り済み';

  @override
  String get order_status_on_the_way => '配達中';

  @override
  String get order_status_arrived => '到着';

  @override
  String get order_status_delivered => '配達完了';

  @override
  String get order_status_cancelled => 'キャンセル';

  @override
  String get order_status_failed_delivery => '配達失敗';

  @override
  String get order_status_unknown => '不明';

  @override
  String get vehicle_motorbike => 'バイク';

  @override
  String get vehicle_bicycle => '自転車';

  @override
  String get vehicle_car => '車';

  @override
  String get driver_gps_notice_title => '位置情報を共有しています';

  @override
  String get driver_gps_notice_text => 'オンラインの間だけ共有します。オフラインにすると止まります。';
}
