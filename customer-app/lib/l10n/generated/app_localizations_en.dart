// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get app_title => 'Malawi Bento';

  @override
  String get language_native_name => 'English';

  @override
  String get common_retry => 'Retry';

  @override
  String get common_cancel => 'Cancel';

  @override
  String get common_save => 'Save';

  @override
  String get common_continue => 'Continue';

  @override
  String get common_ok => 'OK';

  @override
  String get common_delete => 'Delete';

  @override
  String get common_edit => 'Edit';

  @override
  String get common_optional => 'Optional';

  @override
  String get common_required => 'Required';

  @override
  String get language_title => 'Choose your language';

  @override
  String get language_subtitle => 'You can change this anytime in Account.';

  @override
  String get language_settings_title => 'Language';

  @override
  String get language_changed => 'Language updated';

  @override
  String get auth_phone_title => 'Sign in with your phone';

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
  String get auth_login_required => 'Please sign in to continue';

  @override
  String get auth_logout => 'Sign out';

  @override
  String get nav_home => 'Home';

  @override
  String get nav_cart => 'Cart';

  @override
  String get nav_account => 'Account';

  @override
  String get home_title => 'Home';

  @override
  String get home_delivery_to => 'Deliver to';

  @override
  String get home_choose_location => 'Choose delivery location';

  @override
  String get home_categories => 'Categories';

  @override
  String get home_featured => 'Recommended';

  @override
  String get home_all_products => 'All items';

  @override
  String get home_no_store => 'Sorry, we don\'t deliver to this location yet.';

  @override
  String home_store_closed(String store) {
    return '$store is closed right now';
  }

  @override
  String get location_title => 'Delivery location';

  @override
  String get location_use_current => 'Use my current location';

  @override
  String get location_saved_addresses => 'Saved addresses';

  @override
  String get location_new_address => 'New address';

  @override
  String get location_name_label => 'Label (e.g. Home, Office)';

  @override
  String get location_area_label => 'Area';

  @override
  String get location_street_label => 'Street';

  @override
  String get location_building_label => 'Building';

  @override
  String get location_landmark_label => 'Landmark';

  @override
  String get location_landmark_hint => 'e.g. Near the blue gate';

  @override
  String get location_note_label => 'Note for the rider';

  @override
  String get location_confirm => 'Deliver here';

  @override
  String get location_permission_denied =>
      'Location permission is needed to find you';

  @override
  String get location_move_map => 'Move the map to place the pin';

  @override
  String get product_add_to_cart => 'Add to cart';

  @override
  String get product_sold_out => 'Sold out';

  @override
  String product_choose_up_to(int count) {
    return 'Choose up to $count';
  }

  @override
  String product_choose_exactly(int count) {
    return 'Choose $count';
  }

  @override
  String get product_quantity => 'Quantity';

  @override
  String product_preparation_time(int minutes) {
    return 'About $minutes min';
  }

  @override
  String get product_added => 'Added to cart';

  @override
  String get product_list_empty => 'No items available';

  @override
  String get cart_title => 'Cart';

  @override
  String get cart_empty => 'Your cart is empty';

  @override
  String get cart_browse => 'Browse the menu';

  @override
  String get cart_subtotal => 'Subtotal';

  @override
  String get cart_delivery_fee => 'Delivery fee';

  @override
  String get cart_service_fee => 'Service fee';

  @override
  String get cart_discount => 'Discount';

  @override
  String get cart_total => 'Total';

  @override
  String get cart_checkout => 'Checkout';

  @override
  String cart_item_count(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count items',
      one: '1 item',
      zero: 'No items',
    );
    return '$_temp0';
  }

  @override
  String get cart_replace_title => 'Start a new cart?';

  @override
  String cart_replace_message(String store) {
    return 'Your cart has items from $store. Adding this item will clear it.';
  }

  @override
  String get cart_replace_confirm => 'Start new cart';

  @override
  String get cart_remove => 'Remove';

  @override
  String get checkout_title => 'Checkout';

  @override
  String get checkout_delivery_address => 'Delivery address';

  @override
  String get checkout_change => 'Change';

  @override
  String get checkout_payment_method => 'Payment method';

  @override
  String get checkout_delivery_time => 'Delivery time';

  @override
  String get checkout_asap => 'As soon as possible';

  @override
  String get checkout_schedule => 'Schedule for later';

  @override
  String checkout_scheduled_for(String time) {
    return 'Scheduled: $time';
  }

  @override
  String get checkout_order_summary => 'Order summary';

  @override
  String get checkout_mobile_money_phone => 'Mobile Money number';

  @override
  String get checkout_estimate_note =>
      'The final amount is confirmed when you place the order.';

  @override
  String get checkout_select_address =>
      'Please choose a saved delivery address';

  @override
  String get order_place_order => 'Place Order';

  @override
  String get payment_cash => 'Cash on delivery';

  @override
  String get payment_airtel_money => 'Airtel Money';

  @override
  String get payment_tnm_mpamba => 'TNM Mpamba';

  @override
  String get account_title => 'Account';

  @override
  String get account_name => 'Name';

  @override
  String get account_phone => 'Phone';

  @override
  String get account_language => 'Language';

  @override
  String get account_addresses => 'Saved addresses';

  @override
  String get account_sign_in => 'Sign in';

  @override
  String get account_guest => 'You are not signed in';

  @override
  String get address_title => 'Saved addresses';

  @override
  String get address_empty => 'No saved addresses yet';

  @override
  String get address_default => 'Default';

  @override
  String get address_set_default => 'Set as default';

  @override
  String get address_delete_confirm => 'Delete this address?';

  @override
  String get error_bad_request => 'The request is invalid.';

  @override
  String get error_unauthenticated => 'Please sign in to continue.';

  @override
  String get error_invalid_credentials =>
      'The e-mail or password is incorrect.';

  @override
  String get error_forbidden => 'You do not have permission to do this.';

  @override
  String get error_account_disabled => 'Your account has been disabled.';

  @override
  String get error_resource_not_found => 'The requested item was not found.';

  @override
  String get error_route_not_found => 'This feature is not available yet.';

  @override
  String get error_method_not_allowed => 'This action is not allowed.';

  @override
  String get error_conflict => 'This can\'t be done right now.';

  @override
  String get error_validation_failed =>
      'Please check the information you entered.';

  @override
  String get error_otp_invalid => 'The verification code is incorrect.';

  @override
  String get error_otp_expired =>
      'The code has expired. Please request a new one.';

  @override
  String get error_language_not_supported => 'This language is not supported.';

  @override
  String get error_store_not_available =>
      'This store is not accepting orders right now.';

  @override
  String get error_out_of_delivery_area =>
      'This location is outside our delivery area.';

  @override
  String get error_product_not_available =>
      'This item is not available right now.';

  @override
  String get error_invalid_status_transition =>
      'This can\'t be done at this stage of the order.';

  @override
  String get error_delivery_pin_invalid => 'The PIN is incorrect.';

  @override
  String get error_payment_failed => 'The payment could not be completed.';

  @override
  String get error_too_many_requests =>
      'Too many attempts. Please wait a moment.';

  @override
  String get error_server_error =>
      'Something went wrong on our side. Please try again.';

  @override
  String get error_network => 'No internet connection. Check your network.';

  @override
  String get error_timeout => 'The connection is slow. Please try again.';

  @override
  String get error_unknown => 'Something went wrong. Please try again.';

  @override
  String get order_status_new => 'Order received';

  @override
  String get order_status_confirmed => 'Confirmed';

  @override
  String get order_status_cooking => 'Preparing';

  @override
  String get order_status_ready_for_pickup => 'Ready for pickup';

  @override
  String get order_status_rider_assigned => 'Rider assigned';

  @override
  String get order_status_picked_up => 'Picked up';

  @override
  String get order_status_on_the_way => 'On the way';

  @override
  String get order_status_arrived => 'Rider has arrived';

  @override
  String get order_status_delivered => 'Delivered';

  @override
  String get order_status_cancelled => 'Cancelled';

  @override
  String get order_status_failed_delivery => 'Delivery failed';

  @override
  String get order_status_unknown => 'Unknown';

  @override
  String location_coordinates(String latitude, String longitude) {
    return '$latitude, $longitude';
  }

  @override
  String get nav_orders => 'Orders';

  @override
  String get order_complete_title => 'Thank you! Your order has been placed.';

  @override
  String get order_number_label => 'Order number';

  @override
  String get order_pin_label => 'Delivery PIN';

  @override
  String get order_pin_hint =>
      'Tell this PIN to the rider when your food arrives.';

  @override
  String get order_view => 'View order';

  @override
  String get order_history_title => 'Orders';

  @override
  String get order_history_empty => 'No orders yet';

  @override
  String get order_detail_title => 'Order details';

  @override
  String get order_cancel => 'Cancel order';

  @override
  String get order_cancel_confirm => 'Cancel this order?';

  @override
  String order_ordered_at(String time) {
    return 'Ordered $time';
  }

  @override
  String get order_status_title => 'Status';

  @override
  String get error_payment_required => 'This order has not been paid yet.';

  @override
  String get tracking_title => 'Live tracking';

  @override
  String get tracking_rider => 'Your rider';

  @override
  String tracking_distance(String distance) {
    return '$distance km away';
  }

  @override
  String tracking_updated(String time) {
    return 'Updated $time';
  }

  @override
  String get vehicle_motorbike => 'Motorbike';

  @override
  String get vehicle_bicycle => 'Bicycle';

  @override
  String get vehicle_car => 'Car';

  @override
  String get payment_title => 'Payment';

  @override
  String get payment_amount => 'Amount to pay';

  @override
  String get payment_phone_label => 'Mobile money number';

  @override
  String payment_pay_with(String method) {
    return 'Pay with $method';
  }

  @override
  String get payment_waiting =>
      'Check your phone and approve the payment with your PIN.';

  @override
  String get payment_retry => 'Try again';

  @override
  String get payment_received => 'Payment received';

  @override
  String get payment_pay_now => 'Pay now';

  @override
  String get payment_status_pending => 'Waiting for payment';

  @override
  String get payment_status_paid => 'Paid';

  @override
  String get payment_status_failed => 'Payment failed';

  @override
  String get payment_status_refunded => 'Refunded';

  @override
  String get error_payment_not_required =>
      'This order does not need an online payment.';
}
