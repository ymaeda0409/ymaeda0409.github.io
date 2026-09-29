// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Nyanja Chewa Chichewa (`ny`).
class AppLocalizationsNy extends AppLocalizations {
  AppLocalizationsNy([String locale = 'ny']) : super(locale);

  @override
  String get language_native_name => 'Chichewa';

  @override
  String get app_title => 'Malawi Bento Rider';

  @override
  String get common_retry => 'Yesaninso';

  @override
  String get common_cancel => 'Letsani';

  @override
  String get common_continue => 'Pitirizani';

  @override
  String get common_ok => 'Chabwino';

  @override
  String get language_title => 'Sankhani chilankhulo chanu';

  @override
  String get language_subtitle =>
      'Mutha kuchisintha nthawi iliyonse mu Zokonda.';

  @override
  String get language_settings_title => 'Chilankhulo';

  @override
  String get language_changed => 'Chilankhulo chasinthidwa';

  @override
  String get auth_phone_title => 'Kulowa kwa wobweretsa';

  @override
  String get auth_phone_label => 'Nambala ya foni';

  @override
  String get auth_send_code => 'Tumizani nambala';

  @override
  String get auth_phone_invalid => 'Lembani nambala ya foni yolondola';

  @override
  String get auth_otp_title => 'Lembani nambala yotsimikizira';

  @override
  String auth_otp_sent_to(String phone) {
    return 'Tatumiza nambala ya manambala 6 ku $phone';
  }

  @override
  String get auth_verify => 'Tsimikizirani';

  @override
  String get auth_resend => 'Tumizaninso nambala';

  @override
  String auth_resend_in(int seconds) {
    return 'Tumizaninso pakatha masekondi $seconds';
  }

  @override
  String get auth_logout => 'Tulukani';

  @override
  String get auth_not_a_driver =>
      'Nambala iyi sinalembetsedwe ngati wobweretsa. Chonde lumikizanani ndi sitolo yanu.';

  @override
  String get driver_home_title => 'Zobweretsa';

  @override
  String get driver_status_online => 'Muli pa intaneti';

  @override
  String get driver_status_offline => 'Simuli pa intaneti';

  @override
  String get driver_go_online => 'YAMBANI NTCHITO';

  @override
  String get driver_go_offline => 'SIYANI NTCHITO';

  @override
  String get driver_waiting_requests => 'Tikudikira mapempho obweretsa…';

  @override
  String get driver_offline_hint =>
      'Yambani ntchito kuti mulandire mapempho obweretsa.';

  @override
  String get driver_location_required =>
      'Tikufuna chilolezo cha malo kuti muyambe ntchito.';

  @override
  String get driver_new_delivery => 'Chobweretsa chatsopano';

  @override
  String driver_request_expires_in(int seconds) {
    return 'Masekondi $seconds kuti muyankhe';
  }

  @override
  String driver_distance_to_pickup(String distance) {
    return 'Makilomita $distance kukatenga';
  }

  @override
  String driver_delivery_distance(String distance) {
    return 'Makilomita $distance kubweretsa';
  }

  @override
  String get driver_accept => 'LANDIRANI';

  @override
  String get driver_decline => 'KANANI';

  @override
  String driver_collect_cash(String amount) {
    return 'Landirani $amount ndalama';
  }

  @override
  String get driver_prepaid => 'Zalipiridwa kale';

  @override
  String get driver_pickup_title => 'Kutenga';

  @override
  String get driver_pickup_at => 'Katengeni ku';

  @override
  String get driver_navigate => 'Onani njira';

  @override
  String get driver_call => 'Imbani';

  @override
  String driver_order_number(String number) {
    return 'Oda $number';
  }

  @override
  String driver_items_count(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: 'Zinthu $count',
      one: 'Chinthu 1',
    );
    return '$_temp0';
  }

  @override
  String get driver_confirm_pickup => 'NDATENGA ODA';

  @override
  String get driver_delivery_title => 'Kubweretsa';

  @override
  String get driver_deliver_to => 'Bweretsani kwa';

  @override
  String get driver_landmark => 'Chizindikiro';

  @override
  String get driver_note => 'Uthenga';

  @override
  String get driver_arrived => 'NDAFIKA';

  @override
  String get driver_pin_title => 'PIN yolandirira';

  @override
  String get driver_pin_hint => 'Funsani kasitomala PIN yake ya manambala 4.';

  @override
  String driver_pin_attempts_left(int count) {
    return 'Mwatsala ndi mayesero $count';
  }

  @override
  String get driver_confirm_delivery => 'TSIMIKIZIRANI KUPEREKA';

  @override
  String get driver_delivery_failed => 'Kasitomala sakupezeka';

  @override
  String get driver_fail_confirm => 'Lembani kuti kubweretsa kwalephereka?';

  @override
  String get driver_complete_title => 'Zaperekedwa!';

  @override
  String get driver_complete_message =>
      'Mwachita bwino. Mwakonzeka kubweretsa china.';

  @override
  String get driver_back_home => 'Bwererani kuyambira';

  @override
  String get driver_history_title => 'Mbiri ya zobweretsa';

  @override
  String get driver_history_empty => 'Palibe zobweretsa pakadali pano';

  @override
  String get driver_pending_sync => 'Palibe intaneti — tidzatumiza zokha.';

  @override
  String get driver_settings => 'Zokonda';

  @override
  String driver_vehicle(String vehicle) {
    return 'Galimoto: $vehicle';
  }

  @override
  String get error_bad_request => 'Pempho lanu silili bwino.';

  @override
  String get error_unauthenticated => 'Chonde lowaninso.';

  @override
  String get error_forbidden => 'Mulibe chilolezo chochita izi.';

  @override
  String get error_account_disabled => 'Akaunti yanu yayimitsidwa.';

  @override
  String get error_resource_not_found => 'Sitinapeze chobweretsa ichi.';

  @override
  String get error_validation_failed => 'Chonde onaninso zomwe mwalemba.';

  @override
  String get error_otp_invalid => 'Nambala yotsimikizira si yolondola.';

  @override
  String get error_otp_expired => 'Nambala yatha nthawi. Chonde pemphani ina.';

  @override
  String get error_invalid_status_transition =>
      'Chobweretsa ichi chasintha kale.';

  @override
  String get error_offer_not_available => 'Pempho ili palibenso.';

  @override
  String get error_delivery_pin_invalid =>
      'PIN si yolondola. Funsaninso kasitomala.';

  @override
  String get error_delivery_pin_locked =>
      'PIN yalakwika kambirimbiri. Chonde imbirani sitolo.';

  @override
  String get error_too_many_requests =>
      'Mwayesa kambirimbiri. Dikirani pang\'ono.';

  @override
  String get error_server_error => 'Pachitika vuto kwa ife. Chonde yesaninso.';

  @override
  String get error_network => 'Palibe intaneti.';

  @override
  String get error_timeout => 'Kulumikizana kukuchedwa. Chonde yesaninso.';

  @override
  String get error_unknown => 'Pachitika vuto. Chonde yesaninso.';

  @override
  String get order_status_rider_assigned => 'Pitani mukatenge';

  @override
  String get order_status_picked_up => 'Yatengedwa';

  @override
  String get order_status_on_the_way => 'Ili pa njira';

  @override
  String get order_status_arrived => 'Wafika';

  @override
  String get order_status_delivered => 'Yaperekedwa';

  @override
  String get order_status_cancelled => 'Yathetsedwa';

  @override
  String get order_status_failed_delivery => 'Kubweretsa sikunatheke';

  @override
  String get order_status_unknown => 'Sizikudziwika';

  @override
  String get vehicle_motorbike => 'Njinga yamoto';

  @override
  String get vehicle_bicycle => 'Njinga';

  @override
  String get vehicle_car => 'Galimoto';
}
