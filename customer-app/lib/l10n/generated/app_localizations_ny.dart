// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for Nyanja Chewa Chichewa (`ny`).
class AppLocalizationsNy extends AppLocalizations {
  AppLocalizationsNy([String locale = 'ny']) : super(locale);

  @override
  String get app_title => 'Malawi Bento';

  @override
  String get language_native_name => 'Chichewa';

  @override
  String get common_retry => 'Yesaninso';

  @override
  String get common_cancel => 'Letsani';

  @override
  String get common_save => 'Sungani';

  @override
  String get common_continue => 'Pitirizani';

  @override
  String get common_ok => 'Chabwino';

  @override
  String get common_delete => 'Chotsani';

  @override
  String get common_edit => 'Sinthani';

  @override
  String get common_optional => 'Mwakufuna';

  @override
  String get common_required => 'Chofunika';

  @override
  String get language_title => 'Sankhani chilankhulo chanu';

  @override
  String get language_subtitle =>
      'Mutha kuchisintha nthawi iliyonse mu Akaunti.';

  @override
  String get language_settings_title => 'Chilankhulo';

  @override
  String get language_changed => 'Chilankhulo chasinthidwa';

  @override
  String get auth_phone_title => 'Lowani ndi nambala ya foni yanu';

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
  String get auth_login_required => 'Chonde lowani kuti mupitirize';

  @override
  String get auth_logout => 'Tulukani';

  @override
  String get nav_home => 'Poyambira';

  @override
  String get nav_cart => 'Dengu';

  @override
  String get nav_account => 'Akaunti';

  @override
  String get home_title => 'Poyambira';

  @override
  String get home_delivery_to => 'Tibweretsa ku';

  @override
  String get home_choose_location => 'Sankhani malo obweretsera';

  @override
  String get home_categories => 'Magulu';

  @override
  String get home_featured => 'Zosankhidwa';

  @override
  String get home_all_products => 'Zonse';

  @override
  String get home_no_store =>
      'Pepani, sitikubweretsa kumalo amenewa pakadali pano.';

  @override
  String home_store_closed(String store) {
    return '$store yatsekedwa pakadali pano';
  }

  @override
  String get location_title => 'Malo obweretsera';

  @override
  String get location_use_current => 'Gwiritsani ntchito malo omwe ndili pano';

  @override
  String get location_saved_addresses => 'Malo osungidwa';

  @override
  String get location_new_address => 'Malo atsopano';

  @override
  String get location_name_label => 'Dzina (monga Kunyumba, Kuntchito)';

  @override
  String get location_area_label => 'Dera';

  @override
  String get location_street_label => 'Msewu';

  @override
  String get location_building_label => 'Nyumba';

  @override
  String get location_landmark_label => 'Chizindikiro';

  @override
  String get location_landmark_hint => 'Mwachitsanzo: Pafupi ndi geti labuluu';

  @override
  String get location_note_label => 'Uthenga kwa wobweretsa';

  @override
  String get location_confirm => 'Bweretsani apa';

  @override
  String get location_permission_denied =>
      'Tikufuna chilolezo cha malo kuti tikupezeni';

  @override
  String get location_move_map => 'Sunthani mapu kuti muike chizindikiro';

  @override
  String get product_add_to_cart => 'Ikani mu dengu';

  @override
  String get product_sold_out => 'Zatha';

  @override
  String product_choose_up_to(int count) {
    return 'Sankhani mpaka $count';
  }

  @override
  String product_choose_exactly(int count) {
    return 'Sankhani $count';
  }

  @override
  String get product_quantity => 'Kuchuluka';

  @override
  String product_preparation_time(int minutes) {
    return 'Pafupifupi mphindi $minutes';
  }

  @override
  String get product_added => 'Zaikidwa mu dengu';

  @override
  String get product_list_empty => 'Palibe zinthu zomwe zilipo';

  @override
  String get cart_title => 'Dengu';

  @override
  String get cart_empty => 'Dengu lanu mulibe kanthu';

  @override
  String get cart_browse => 'Onani menyu';

  @override
  String get cart_subtotal => 'Mtengo wa zinthu';

  @override
  String get cart_delivery_fee => 'Mtengo wobweretsera';

  @override
  String get cart_service_fee => 'Mtengo wa ntchito';

  @override
  String get cart_discount => 'Kuchotsera';

  @override
  String get cart_total => 'Zonse pamodzi';

  @override
  String get cart_checkout => 'Pitirizani kulipira';

  @override
  String cart_item_count(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: 'Zinthu $count',
      one: 'Chinthu 1',
      zero: 'Palibe chinthu',
    );
    return '$_temp0';
  }

  @override
  String get cart_replace_title => 'Kuyamba dengu latsopano?';

  @override
  String cart_replace_message(String store) {
    return 'Dengu lanu lili ndi zinthu zochokera ku $store. Kuwonjezera ichi kuchotsa zomwe zilimo.';
  }

  @override
  String get cart_replace_confirm => 'Yambani dengu latsopano';

  @override
  String get cart_remove => 'Chotsani';

  @override
  String get checkout_title => 'Kulipira';

  @override
  String get checkout_delivery_address => 'Adilesi yobweretsera';

  @override
  String get checkout_change => 'Sinthani';

  @override
  String get checkout_payment_method => 'Njira yolipirira';

  @override
  String get checkout_delivery_time => 'Nthawi yobweretsera';

  @override
  String get checkout_asap => 'Mwamsanga';

  @override
  String get checkout_schedule => 'Konzani nthawi ina';

  @override
  String checkout_scheduled_for(String time) {
    return 'Nthawi: $time';
  }

  @override
  String get checkout_order_summary => 'Chidule cha oda';

  @override
  String get checkout_mobile_money_phone => 'Nambala ya Mobile Money';

  @override
  String get checkout_estimate_note =>
      'Mtengo weniweni udzatsimikizidwa mukatumiza oda.';

  @override
  String get checkout_select_address =>
      'Chonde sankhani malo obweretsera osungidwa';

  @override
  String get order_place_order => 'Tumizani oda';

  @override
  String get payment_cash => 'Kulipira ndalama pofika';

  @override
  String get payment_airtel_money => 'Airtel Money';

  @override
  String get payment_tnm_mpamba => 'TNM Mpamba';

  @override
  String get account_title => 'Akaunti';

  @override
  String get account_name => 'Dzina';

  @override
  String get account_phone => 'Foni';

  @override
  String get account_language => 'Chilankhulo';

  @override
  String get account_addresses => 'Malo osungidwa';

  @override
  String get account_sign_in => 'Lowani';

  @override
  String get account_guest => 'Simunalowe';

  @override
  String get address_title => 'Malo osungidwa';

  @override
  String get address_empty => 'Palibe malo osungidwa';

  @override
  String get address_default => 'Osankhidwa';

  @override
  String get address_set_default => 'Pangani kukhala osankhidwa';

  @override
  String get address_delete_confirm => 'Chotsani malo awa?';

  @override
  String get error_bad_request => 'Pempho lanu silili bwino.';

  @override
  String get error_unauthenticated => 'Chonde lowani kaye kuti mupitirize.';

  @override
  String get error_invalid_credentials =>
      'Imelo kapena mawu achinsinsi si olondola.';

  @override
  String get error_forbidden => 'Mulibe chilolezo chochita izi.';

  @override
  String get error_account_disabled => 'Akaunti yanu yayimitsidwa.';

  @override
  String get error_resource_not_found => 'Sitinapeze zomwe mukufuna.';

  @override
  String get error_route_not_found => 'Ntchito iyi sinapezeke pakadali pano.';

  @override
  String get error_method_not_allowed => 'Izi siziloledwa.';

  @override
  String get error_conflict => 'Izi sizingatheke pakali pano.';

  @override
  String get error_validation_failed => 'Chonde onaninso zomwe mwalemba.';

  @override
  String get error_otp_invalid => 'Nambala yotsimikizira si yolondola.';

  @override
  String get error_otp_expired => 'Nambala yatha nthawi. Chonde pemphani ina.';

  @override
  String get error_language_not_supported => 'Chilankhulo ichi sichikupezeka.';

  @override
  String get error_store_not_available =>
      'Sitolo iyi sikulandira ma oda pakali pano.';

  @override
  String get error_out_of_delivery_area =>
      'Malo amenewa ali kunja kwa dera lathu lobweretsera.';

  @override
  String get error_product_not_available =>
      'Chinthu ichi sichikupezeka pakadali pano.';

  @override
  String get error_invalid_status_transition =>
      'Izi sizingatheke pa gawo ili la oda.';

  @override
  String get error_delivery_pin_invalid => 'PIN si yolondola.';

  @override
  String get error_payment_failed => 'Kulipira sikunatheke.';

  @override
  String get error_too_many_requests =>
      'Mwayesa kambirimbiri. Dikirani pang\'ono.';

  @override
  String get error_server_error => 'Pachitika vuto kwa ife. Chonde yesaninso.';

  @override
  String get error_network => 'Palibe intaneti. Onani kulumikizana kwanu.';

  @override
  String get error_timeout => 'Kulumikizana kukuchedwa. Chonde yesaninso.';

  @override
  String get error_unknown => 'Pachitika vuto. Chonde yesaninso.';

  @override
  String get order_status_new => 'Oda yalandiridwa';

  @override
  String get order_status_confirmed => 'Yatsimikizidwa';

  @override
  String get order_status_cooking => 'Ikuphikidwa';

  @override
  String get order_status_ready_for_pickup => 'Yakonzeka kutengedwa';

  @override
  String get order_status_rider_assigned => 'Wobweretsa wapezeka';

  @override
  String get order_status_picked_up => 'Yatengedwa';

  @override
  String get order_status_on_the_way => 'Ili pa njira';

  @override
  String get order_status_arrived => 'Wobweretsa wafika';

  @override
  String get order_status_delivered => 'Yaperekedwa';

  @override
  String get order_status_cancelled => 'Yathetsedwa';

  @override
  String get order_status_failed_delivery => 'Kubweretsa sikunatheke';

  @override
  String get order_status_unknown => 'Sizikudziwika';

  @override
  String location_coordinates(String latitude, String longitude) {
    return '$latitude, $longitude';
  }

  @override
  String get nav_orders => 'Ma oda';

  @override
  String get order_complete_title => 'Zikomo! Oda yanu yatumizidwa.';

  @override
  String get order_number_label => 'Nambala ya oda';

  @override
  String get order_pin_label => 'PIN yolandirira';

  @override
  String get order_pin_hint => 'Uzani wobweretsa PIN iyi chakudya chikafika.';

  @override
  String get order_view => 'Onani oda';

  @override
  String get order_history_title => 'Ma oda';

  @override
  String get order_history_empty => 'Palibe ma oda pakadali pano';

  @override
  String get order_detail_title => 'Zambiri za oda';

  @override
  String get order_cancel => 'Letsani oda';

  @override
  String get order_cancel_confirm => 'Letsani oda iyi?';

  @override
  String order_ordered_at(String time) {
    return 'Oda: $time';
  }

  @override
  String get order_status_title => 'Momwe ilili';

  @override
  String get error_payment_required => 'Oda iyi sinalipiridwe.';

  @override
  String get tracking_title => 'Kutsatira pompano';

  @override
  String get tracking_rider => 'Wobweretsa wanu';

  @override
  String tracking_distance(String distance) {
    return 'Ali pa makilomita $distance';
  }

  @override
  String tracking_updated(String time) {
    return 'Zasinthidwa $time';
  }

  @override
  String get vehicle_motorbike => 'Njinga yamoto';

  @override
  String get vehicle_bicycle => 'Njinga';

  @override
  String get vehicle_car => 'Galimoto';

  @override
  String get payment_title => 'Kulipira';

  @override
  String get payment_amount => 'Ndalama zolipira';

  @override
  String get payment_phone_label => 'Nambala ya Mobile Money';

  @override
  String payment_pay_with(String method) {
    return 'Lipirani ndi $method';
  }

  @override
  String get payment_waiting =>
      'Onani foni yanu ndipo vomerezani kulipira ndi PIN yanu.';

  @override
  String get payment_retry => 'Yesaninso';

  @override
  String get payment_received => 'Malipiro alandiridwa';

  @override
  String get payment_pay_now => 'Lipirani tsopano';

  @override
  String get payment_status_pending => 'Tikudikira malipiro';

  @override
  String get payment_status_paid => 'Zalipiridwa';

  @override
  String get payment_status_failed => 'Kulipira sikunatheke';

  @override
  String get payment_status_refunded => 'Ndalama zabwezedwa';

  @override
  String get error_payment_not_required =>
      'Oda iyi sifunika kulipiridwa pa intaneti.';
}
