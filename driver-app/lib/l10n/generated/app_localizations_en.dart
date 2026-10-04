// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get language_native_name => 'English';

  @override
  String get app_title => 'Malawi Bento Rider';

  @override
  String get common_retry => 'Retry';

  @override
  String get common_cancel => 'Cancel';

  @override
  String get common_continue => 'Continue';

  @override
  String get common_ok => 'OK';

  @override
  String get language_title => 'Choose your language';

  @override
  String get language_subtitle => 'You can change this anytime in Settings.';

  @override
  String get language_settings_title => 'Language';

  @override
  String get language_changed => 'Language updated';

  @override
  String get auth_phone_title => 'Rider sign in';

  @override
  String get auth_phone_label => 'Phone number';

  @override
  String get auth_send_code => 'Send code';

  @override
  String get auth_phone_invalid => 'Enter a valid phone number';

  @override
  String get auth_otp_title => 'Enter the verification code';

  @override
  String auth_otp_sent_to(String phone) {
    return 'We sent a 6-digit code to $phone';
  }

  @override
  String get auth_verify => 'Verify';

  @override
  String get auth_resend => 'Resend code';

  @override
  String auth_resend_in(int seconds) {
    return 'Resend in ${seconds}s';
  }

  @override
  String get auth_logout => 'Sign out';

  @override
  String get auth_not_a_driver =>
      'This phone number is not registered as a rider. Please contact your store.';

  @override
  String get driver_home_title => 'Deliveries';

  @override
  String get driver_status_online => 'You are online';

  @override
  String get driver_status_offline => 'You are offline';

  @override
  String get driver_go_online => 'GO ONLINE';

  @override
  String get driver_go_offline => 'GO OFFLINE';

  @override
  String get driver_waiting_requests => 'Waiting for delivery requests…';

  @override
  String get driver_offline_hint => 'Go online to receive delivery requests.';

  @override
  String get driver_location_required =>
      'Location access is needed to go online.';

  @override
  String get driver_new_delivery => 'New delivery';

  @override
  String driver_request_expires_in(int seconds) {
    return '${seconds}s to respond';
  }

  @override
  String driver_distance_to_pickup(String distance) {
    return '$distance km to pickup';
  }

  @override
  String driver_delivery_distance(String distance) {
    return '$distance km delivery';
  }

  @override
  String get driver_accept => 'ACCEPT';

  @override
  String get driver_decline => 'DECLINE';

  @override
  String driver_collect_cash(String amount) {
    return 'Collect $amount in cash';
  }

  @override
  String get driver_prepaid => 'Already paid';

  @override
  String get driver_pickup_title => 'Pick up';

  @override
  String get driver_pickup_at => 'Pick up at';

  @override
  String get driver_navigate => 'Navigate';

  @override
  String get driver_call => 'Call';

  @override
  String driver_order_number(String number) {
    return 'Order $number';
  }

  @override
  String driver_items_count(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count items',
      one: '1 item',
    );
    return '$_temp0';
  }

  @override
  String get driver_confirm_pickup => 'I HAVE THE ORDER';

  @override
  String get driver_delivery_title => 'Deliver';

  @override
  String get driver_deliver_to => 'Deliver to';

  @override
  String get driver_landmark => 'Landmark';

  @override
  String get driver_note => 'Note';

  @override
  String get driver_arrived => 'I HAVE ARRIVED';

  @override
  String get driver_pin_title => 'Delivery PIN';

  @override
  String get driver_pin_hint => 'Ask the customer for their 4-digit PIN.';

  @override
  String driver_pin_attempts_left(int count) {
    return '$count attempts left';
  }

  @override
  String get driver_confirm_delivery => 'CONFIRM DELIVERY';

  @override
  String get driver_delivery_failed => 'Customer not available';

  @override
  String get driver_fail_confirm => 'Mark this delivery as failed?';

  @override
  String get driver_complete_title => 'Delivered!';

  @override
  String get driver_complete_message =>
      'Great job. You are ready for the next delivery.';

  @override
  String get driver_back_home => 'Back to home';

  @override
  String get driver_history_title => 'Delivery history';

  @override
  String get driver_history_empty => 'No deliveries yet';

  @override
  String get driver_pending_sync =>
      'Offline — your update will be sent automatically.';

  @override
  String get driver_settings => 'Settings';

  @override
  String driver_vehicle(String vehicle) {
    return 'Vehicle: $vehicle';
  }

  @override
  String get error_bad_request => 'The request is invalid.';

  @override
  String get error_unauthenticated => 'Please sign in again.';

  @override
  String get error_forbidden => 'You do not have permission to do this.';

  @override
  String get error_account_disabled => 'Your rider account is suspended.';

  @override
  String get error_resource_not_found => 'This delivery was not found.';

  @override
  String get error_validation_failed =>
      'Please check the information you entered.';

  @override
  String get error_otp_invalid => 'The verification code is incorrect.';

  @override
  String get error_otp_expired =>
      'The code has expired. Please request a new one.';

  @override
  String get error_invalid_status_transition =>
      'This delivery has already moved on.';

  @override
  String get error_offer_not_available =>
      'This request is no longer available.';

  @override
  String get error_delivery_pin_invalid =>
      'The PIN is incorrect. Ask the customer again.';

  @override
  String get error_delivery_pin_locked =>
      'Too many wrong PINs. Please call the store.';

  @override
  String get error_too_many_requests =>
      'Too many attempts. Please wait a moment.';

  @override
  String get error_server_error =>
      'Something went wrong on our side. Please try again.';

  @override
  String get error_network => 'No internet connection.';

  @override
  String get error_timeout => 'The connection is slow. Please try again.';

  @override
  String get error_unknown => 'Something went wrong. Please try again.';

  @override
  String get order_status_rider_assigned => 'Go to pickup';

  @override
  String get order_status_picked_up => 'Picked up';

  @override
  String get order_status_on_the_way => 'On the way';

  @override
  String get order_status_arrived => 'Arrived';

  @override
  String get order_status_delivered => 'Delivered';

  @override
  String get order_status_cancelled => 'Cancelled';

  @override
  String get order_status_failed_delivery => 'Delivery failed';

  @override
  String get order_status_unknown => 'Unknown';

  @override
  String get vehicle_motorbike => 'Motorbike';

  @override
  String get vehicle_bicycle => 'Bicycle';

  @override
  String get vehicle_car => 'Car';

  @override
  String get driver_gps_notice_title => 'Sharing your location';

  @override
  String get driver_gps_notice_text =>
      'Only while you are online. Go offline to stop.';
}
