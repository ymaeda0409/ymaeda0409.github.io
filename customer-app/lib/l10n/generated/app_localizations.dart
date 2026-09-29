import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:intl/intl.dart' as intl;

import 'app_localizations_en.dart';
import 'app_localizations_ja.dart';
import 'app_localizations_ny.dart';

// ignore_for_file: type=lint

/// Callers can lookup localized strings with an instance of AppLocalizations
/// returned by `AppLocalizations.of(context)`.
///
/// Applications need to include `AppLocalizations.delegate()` in their app's
/// `localizationDelegates` list, and the locales they support in the app's
/// `supportedLocales` list. For example:
///
/// ```dart
/// import 'generated/app_localizations.dart';
///
/// return MaterialApp(
///   localizationsDelegates: AppLocalizations.localizationsDelegates,
///   supportedLocales: AppLocalizations.supportedLocales,
///   home: MyApplicationHome(),
/// );
/// ```
///
/// ## Update pubspec.yaml
///
/// Please make sure to update your pubspec.yaml to include the following
/// packages:
///
/// ```yaml
/// dependencies:
///   # Internationalization support.
///   flutter_localizations:
///     sdk: flutter
///   intl: any # Use the pinned version from flutter_localizations
///
///   # Rest of dependencies
/// ```
///
/// ## iOS Applications
///
/// iOS applications define key application metadata, including supported
/// locales, in an Info.plist file that is built into the application bundle.
/// To configure the locales supported by your app, you’ll need to edit this
/// file.
///
/// First, open your project’s ios/Runner.xcworkspace Xcode workspace file.
/// Then, in the Project Navigator, open the Info.plist file under the Runner
/// project’s Runner folder.
///
/// Next, select the Information Property List item, select Add Item from the
/// Editor menu, then select Localizations from the pop-up menu.
///
/// Select and expand the newly-created Localizations item then, for each
/// locale your application supports, add a new item and select the locale
/// you wish to add from the pop-up menu in the Value field. This list should
/// be consistent with the languages listed in the AppLocalizations.supportedLocales
/// property.
abstract class AppLocalizations {
  AppLocalizations(String locale)
    : localeName = intl.Intl.canonicalizedLocale(locale.toString());

  final String localeName;

  static AppLocalizations of(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations)!;
  }

  static const LocalizationsDelegate<AppLocalizations> delegate =
      _AppLocalizationsDelegate();

  /// A list of this localizations delegate along with the default localizations
  /// delegates.
  ///
  /// Returns a list of localizations delegates containing this delegate along with
  /// GlobalMaterialLocalizations.delegate, GlobalCupertinoLocalizations.delegate,
  /// and GlobalWidgetsLocalizations.delegate.
  ///
  /// Additional delegates can be added by appending to this list in
  /// MaterialApp. This list does not have to be used at all if a custom list
  /// of delegates is preferred or required.
  static const List<LocalizationsDelegate<dynamic>> localizationsDelegates =
      <LocalizationsDelegate<dynamic>>[
        delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
      ];

  /// A list of this localizations delegate's supported locales.
  static const List<Locale> supportedLocales = <Locale>[
    Locale('en'),
    Locale('ja'),
    Locale('ny'),
  ];

  /// Canonical key: app.title
  ///
  /// In en, this message translates to:
  /// **'Malawi Bento'**
  String get app_title;

  /// Canonical key: language.native_name — the language's own name (autonym), shown in the language picker. Never translate into another language.
  ///
  /// In en, this message translates to:
  /// **'English'**
  String get language_native_name;

  /// Canonical key: common.retry
  ///
  /// In en, this message translates to:
  /// **'Retry'**
  String get common_retry;

  /// Canonical key: common.cancel
  ///
  /// In en, this message translates to:
  /// **'Cancel'**
  String get common_cancel;

  /// Canonical key: common.save
  ///
  /// In en, this message translates to:
  /// **'Save'**
  String get common_save;

  /// Canonical key: common.continue
  ///
  /// In en, this message translates to:
  /// **'Continue'**
  String get common_continue;

  /// Canonical key: common.ok
  ///
  /// In en, this message translates to:
  /// **'OK'**
  String get common_ok;

  /// Canonical key: common.delete
  ///
  /// In en, this message translates to:
  /// **'Delete'**
  String get common_delete;

  /// Canonical key: common.edit
  ///
  /// In en, this message translates to:
  /// **'Edit'**
  String get common_edit;

  /// Canonical key: common.optional
  ///
  /// In en, this message translates to:
  /// **'Optional'**
  String get common_optional;

  /// Canonical key: common.required
  ///
  /// In en, this message translates to:
  /// **'Required'**
  String get common_required;

  /// Canonical key: language.title
  ///
  /// In en, this message translates to:
  /// **'Choose your language'**
  String get language_title;

  /// Canonical key: language.subtitle
  ///
  /// In en, this message translates to:
  /// **'You can change this anytime in Account.'**
  String get language_subtitle;

  /// Canonical key: language.settings_title
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get language_settings_title;

  /// Canonical key: language.changed
  ///
  /// In en, this message translates to:
  /// **'Language updated'**
  String get language_changed;

  /// Canonical key: auth.phone_title
  ///
  /// In en, this message translates to:
  /// **'Sign in with your phone'**
  String get auth_phone_title;

  /// Canonical key: auth.phone_label
  ///
  /// In en, this message translates to:
  /// **'Phone number'**
  String get auth_phone_label;

  /// Canonical key: auth.send_code
  ///
  /// In en, this message translates to:
  /// **'Send code'**
  String get auth_send_code;

  /// Canonical key: auth.phone_invalid
  ///
  /// In en, this message translates to:
  /// **'Enter a valid phone number'**
  String get auth_phone_invalid;

  /// Canonical key: auth.otp_title
  ///
  /// In en, this message translates to:
  /// **'Enter the verification code'**
  String get auth_otp_title;

  /// Canonical key: auth.otp_sent_to
  ///
  /// In en, this message translates to:
  /// **'We sent a 6-digit code to {phone}'**
  String auth_otp_sent_to(String phone);

  /// Canonical key: auth.verify
  ///
  /// In en, this message translates to:
  /// **'Verify'**
  String get auth_verify;

  /// Canonical key: auth.resend
  ///
  /// In en, this message translates to:
  /// **'Resend code'**
  String get auth_resend;

  /// Canonical key: auth.resend_in
  ///
  /// In en, this message translates to:
  /// **'Resend in {seconds}s'**
  String auth_resend_in(int seconds);

  /// Canonical key: auth.login_required
  ///
  /// In en, this message translates to:
  /// **'Please sign in to continue'**
  String get auth_login_required;

  /// Canonical key: auth.logout
  ///
  /// In en, this message translates to:
  /// **'Sign out'**
  String get auth_logout;

  /// Canonical key: nav.home
  ///
  /// In en, this message translates to:
  /// **'Home'**
  String get nav_home;

  /// Canonical key: nav.cart
  ///
  /// In en, this message translates to:
  /// **'Cart'**
  String get nav_cart;

  /// Canonical key: nav.account
  ///
  /// In en, this message translates to:
  /// **'Account'**
  String get nav_account;

  /// Canonical key: home.title
  ///
  /// In en, this message translates to:
  /// **'Home'**
  String get home_title;

  /// Canonical key: home.delivery_to
  ///
  /// In en, this message translates to:
  /// **'Deliver to'**
  String get home_delivery_to;

  /// Canonical key: home.choose_location
  ///
  /// In en, this message translates to:
  /// **'Choose delivery location'**
  String get home_choose_location;

  /// Canonical key: home.categories
  ///
  /// In en, this message translates to:
  /// **'Categories'**
  String get home_categories;

  /// Canonical key: home.featured
  ///
  /// In en, this message translates to:
  /// **'Recommended'**
  String get home_featured;

  /// Canonical key: home.all_products
  ///
  /// In en, this message translates to:
  /// **'All items'**
  String get home_all_products;

  /// Canonical key: home.no_store
  ///
  /// In en, this message translates to:
  /// **'Sorry, we don\'t deliver to this location yet.'**
  String get home_no_store;

  /// Canonical key: home.store_closed
  ///
  /// In en, this message translates to:
  /// **'{store} is closed right now'**
  String home_store_closed(String store);

  /// Canonical key: location.title
  ///
  /// In en, this message translates to:
  /// **'Delivery location'**
  String get location_title;

  /// Canonical key: location.use_current
  ///
  /// In en, this message translates to:
  /// **'Use my current location'**
  String get location_use_current;

  /// Canonical key: location.saved_addresses
  ///
  /// In en, this message translates to:
  /// **'Saved addresses'**
  String get location_saved_addresses;

  /// Canonical key: location.new_address
  ///
  /// In en, this message translates to:
  /// **'New address'**
  String get location_new_address;

  /// Canonical key: location.name_label
  ///
  /// In en, this message translates to:
  /// **'Label (e.g. Home, Office)'**
  String get location_name_label;

  /// Canonical key: location.area_label
  ///
  /// In en, this message translates to:
  /// **'Area'**
  String get location_area_label;

  /// Canonical key: location.street_label
  ///
  /// In en, this message translates to:
  /// **'Street'**
  String get location_street_label;

  /// Canonical key: location.building_label
  ///
  /// In en, this message translates to:
  /// **'Building'**
  String get location_building_label;

  /// Canonical key: location.landmark_label
  ///
  /// In en, this message translates to:
  /// **'Landmark'**
  String get location_landmark_label;

  /// Canonical key: location.landmark_hint
  ///
  /// In en, this message translates to:
  /// **'e.g. Near the blue gate'**
  String get location_landmark_hint;

  /// Canonical key: location.note_label
  ///
  /// In en, this message translates to:
  /// **'Note for the rider'**
  String get location_note_label;

  /// Canonical key: location.confirm
  ///
  /// In en, this message translates to:
  /// **'Deliver here'**
  String get location_confirm;

  /// Canonical key: location.permission_denied
  ///
  /// In en, this message translates to:
  /// **'Location permission is needed to find you'**
  String get location_permission_denied;

  /// Canonical key: location.move_map
  ///
  /// In en, this message translates to:
  /// **'Move the map to place the pin'**
  String get location_move_map;

  /// Canonical key: product.add_to_cart
  ///
  /// In en, this message translates to:
  /// **'Add to cart'**
  String get product_add_to_cart;

  /// Canonical key: product.sold_out
  ///
  /// In en, this message translates to:
  /// **'Sold out'**
  String get product_sold_out;

  /// Canonical key: product.choose_up_to
  ///
  /// In en, this message translates to:
  /// **'Choose up to {count}'**
  String product_choose_up_to(int count);

  /// Canonical key: product.choose_exactly
  ///
  /// In en, this message translates to:
  /// **'Choose {count}'**
  String product_choose_exactly(int count);

  /// Canonical key: product.quantity
  ///
  /// In en, this message translates to:
  /// **'Quantity'**
  String get product_quantity;

  /// Canonical key: product.preparation_time
  ///
  /// In en, this message translates to:
  /// **'About {minutes} min'**
  String product_preparation_time(int minutes);

  /// Canonical key: product.added
  ///
  /// In en, this message translates to:
  /// **'Added to cart'**
  String get product_added;

  /// Canonical key: product.list_empty
  ///
  /// In en, this message translates to:
  /// **'No items available'**
  String get product_list_empty;

  /// Canonical key: cart.title
  ///
  /// In en, this message translates to:
  /// **'Cart'**
  String get cart_title;

  /// Canonical key: cart.empty
  ///
  /// In en, this message translates to:
  /// **'Your cart is empty'**
  String get cart_empty;

  /// Canonical key: cart.browse
  ///
  /// In en, this message translates to:
  /// **'Browse the menu'**
  String get cart_browse;

  /// Canonical key: cart.subtotal
  ///
  /// In en, this message translates to:
  /// **'Subtotal'**
  String get cart_subtotal;

  /// Canonical key: cart.delivery_fee
  ///
  /// In en, this message translates to:
  /// **'Delivery fee'**
  String get cart_delivery_fee;

  /// Canonical key: cart.service_fee
  ///
  /// In en, this message translates to:
  /// **'Service fee'**
  String get cart_service_fee;

  /// Canonical key: cart.discount
  ///
  /// In en, this message translates to:
  /// **'Discount'**
  String get cart_discount;

  /// Canonical key: cart.total
  ///
  /// In en, this message translates to:
  /// **'Total'**
  String get cart_total;

  /// Canonical key: cart.checkout
  ///
  /// In en, this message translates to:
  /// **'Checkout'**
  String get cart_checkout;

  /// Canonical key: cart.item_count
  ///
  /// In en, this message translates to:
  /// **'{count, plural, =0{No items} =1{1 item} other{{count} items}}'**
  String cart_item_count(int count);

  /// Canonical key: cart.replace_title
  ///
  /// In en, this message translates to:
  /// **'Start a new cart?'**
  String get cart_replace_title;

  /// Canonical key: cart.replace_message
  ///
  /// In en, this message translates to:
  /// **'Your cart has items from {store}. Adding this item will clear it.'**
  String cart_replace_message(String store);

  /// Canonical key: cart.replace_confirm
  ///
  /// In en, this message translates to:
  /// **'Start new cart'**
  String get cart_replace_confirm;

  /// Canonical key: cart.remove
  ///
  /// In en, this message translates to:
  /// **'Remove'**
  String get cart_remove;

  /// Canonical key: checkout.title
  ///
  /// In en, this message translates to:
  /// **'Checkout'**
  String get checkout_title;

  /// Canonical key: checkout.delivery_address
  ///
  /// In en, this message translates to:
  /// **'Delivery address'**
  String get checkout_delivery_address;

  /// Canonical key: checkout.change
  ///
  /// In en, this message translates to:
  /// **'Change'**
  String get checkout_change;

  /// Canonical key: checkout.payment_method
  ///
  /// In en, this message translates to:
  /// **'Payment method'**
  String get checkout_payment_method;

  /// Canonical key: checkout.delivery_time
  ///
  /// In en, this message translates to:
  /// **'Delivery time'**
  String get checkout_delivery_time;

  /// Canonical key: checkout.asap
  ///
  /// In en, this message translates to:
  /// **'As soon as possible'**
  String get checkout_asap;

  /// Canonical key: checkout.schedule
  ///
  /// In en, this message translates to:
  /// **'Schedule for later'**
  String get checkout_schedule;

  /// Canonical key: checkout.scheduled_for
  ///
  /// In en, this message translates to:
  /// **'Scheduled: {time}'**
  String checkout_scheduled_for(String time);

  /// Canonical key: checkout.order_summary
  ///
  /// In en, this message translates to:
  /// **'Order summary'**
  String get checkout_order_summary;

  /// Canonical key: checkout.mobile_money_phone
  ///
  /// In en, this message translates to:
  /// **'Mobile Money number'**
  String get checkout_mobile_money_phone;

  /// Canonical key: checkout.estimate_note
  ///
  /// In en, this message translates to:
  /// **'The final amount is confirmed when you place the order.'**
  String get checkout_estimate_note;

  /// Canonical key: checkout.select_address
  ///
  /// In en, this message translates to:
  /// **'Please choose a saved delivery address'**
  String get checkout_select_address;

  /// Canonical key: order.place_order
  ///
  /// In en, this message translates to:
  /// **'Place Order'**
  String get order_place_order;

  /// Canonical key: payment.cash
  ///
  /// In en, this message translates to:
  /// **'Cash on delivery'**
  String get payment_cash;

  /// Canonical key: payment.airtel_money
  ///
  /// In en, this message translates to:
  /// **'Airtel Money'**
  String get payment_airtel_money;

  /// Canonical key: payment.tnm_mpamba
  ///
  /// In en, this message translates to:
  /// **'TNM Mpamba'**
  String get payment_tnm_mpamba;

  /// Canonical key: account.title
  ///
  /// In en, this message translates to:
  /// **'Account'**
  String get account_title;

  /// Canonical key: account.name
  ///
  /// In en, this message translates to:
  /// **'Name'**
  String get account_name;

  /// Canonical key: account.phone
  ///
  /// In en, this message translates to:
  /// **'Phone'**
  String get account_phone;

  /// Canonical key: account.language
  ///
  /// In en, this message translates to:
  /// **'Language'**
  String get account_language;

  /// Canonical key: account.addresses
  ///
  /// In en, this message translates to:
  /// **'Saved addresses'**
  String get account_addresses;

  /// Canonical key: account.sign_in
  ///
  /// In en, this message translates to:
  /// **'Sign in'**
  String get account_sign_in;

  /// Canonical key: account.guest
  ///
  /// In en, this message translates to:
  /// **'You are not signed in'**
  String get account_guest;

  /// Canonical key: address.title
  ///
  /// In en, this message translates to:
  /// **'Saved addresses'**
  String get address_title;

  /// Canonical key: address.empty
  ///
  /// In en, this message translates to:
  /// **'No saved addresses yet'**
  String get address_empty;

  /// Canonical key: address.default
  ///
  /// In en, this message translates to:
  /// **'Default'**
  String get address_default;

  /// Canonical key: address.set_default
  ///
  /// In en, this message translates to:
  /// **'Set as default'**
  String get address_set_default;

  /// Canonical key: address.delete_confirm
  ///
  /// In en, this message translates to:
  /// **'Delete this address?'**
  String get address_delete_confirm;

  /// Canonical key: error.bad_request
  ///
  /// In en, this message translates to:
  /// **'The request is invalid.'**
  String get error_bad_request;

  /// Canonical key: error.unauthenticated
  ///
  /// In en, this message translates to:
  /// **'Please sign in to continue.'**
  String get error_unauthenticated;

  /// Canonical key: error.invalid_credentials
  ///
  /// In en, this message translates to:
  /// **'The e-mail or password is incorrect.'**
  String get error_invalid_credentials;

  /// Canonical key: error.forbidden
  ///
  /// In en, this message translates to:
  /// **'You do not have permission to do this.'**
  String get error_forbidden;

  /// Canonical key: error.account_disabled
  ///
  /// In en, this message translates to:
  /// **'Your account has been disabled.'**
  String get error_account_disabled;

  /// Canonical key: error.resource_not_found
  ///
  /// In en, this message translates to:
  /// **'The requested item was not found.'**
  String get error_resource_not_found;

  /// Canonical key: error.route_not_found
  ///
  /// In en, this message translates to:
  /// **'This feature is not available yet.'**
  String get error_route_not_found;

  /// Canonical key: error.method_not_allowed
  ///
  /// In en, this message translates to:
  /// **'This action is not allowed.'**
  String get error_method_not_allowed;

  /// Canonical key: error.conflict
  ///
  /// In en, this message translates to:
  /// **'This can\'t be done right now.'**
  String get error_conflict;

  /// Canonical key: error.validation_failed
  ///
  /// In en, this message translates to:
  /// **'Please check the information you entered.'**
  String get error_validation_failed;

  /// Canonical key: error.otp_invalid
  ///
  /// In en, this message translates to:
  /// **'The verification code is incorrect.'**
  String get error_otp_invalid;

  /// Canonical key: error.otp_expired
  ///
  /// In en, this message translates to:
  /// **'The code has expired. Please request a new one.'**
  String get error_otp_expired;

  /// Canonical key: error.language_not_supported
  ///
  /// In en, this message translates to:
  /// **'This language is not supported.'**
  String get error_language_not_supported;

  /// Canonical key: error.store_not_available
  ///
  /// In en, this message translates to:
  /// **'This store is not accepting orders right now.'**
  String get error_store_not_available;

  /// Canonical key: error.out_of_delivery_area
  ///
  /// In en, this message translates to:
  /// **'This location is outside our delivery area.'**
  String get error_out_of_delivery_area;

  /// Canonical key: error.product_not_available
  ///
  /// In en, this message translates to:
  /// **'This item is not available right now.'**
  String get error_product_not_available;

  /// Canonical key: error.invalid_status_transition
  ///
  /// In en, this message translates to:
  /// **'This can\'t be done at this stage of the order.'**
  String get error_invalid_status_transition;

  /// Canonical key: error.delivery_pin_invalid
  ///
  /// In en, this message translates to:
  /// **'The PIN is incorrect.'**
  String get error_delivery_pin_invalid;

  /// Canonical key: error.payment_failed
  ///
  /// In en, this message translates to:
  /// **'The payment could not be completed.'**
  String get error_payment_failed;

  /// Canonical key: error.too_many_requests
  ///
  /// In en, this message translates to:
  /// **'Too many attempts. Please wait a moment.'**
  String get error_too_many_requests;

  /// Canonical key: error.server_error
  ///
  /// In en, this message translates to:
  /// **'Something went wrong on our side. Please try again.'**
  String get error_server_error;

  /// Canonical key: error.network
  ///
  /// In en, this message translates to:
  /// **'No internet connection. Check your network.'**
  String get error_network;

  /// Canonical key: error.timeout
  ///
  /// In en, this message translates to:
  /// **'The connection is slow. Please try again.'**
  String get error_timeout;

  /// Canonical key: error.unknown
  ///
  /// In en, this message translates to:
  /// **'Something went wrong. Please try again.'**
  String get error_unknown;

  /// Canonical key: order.status.new
  ///
  /// In en, this message translates to:
  /// **'Order received'**
  String get order_status_new;

  /// Canonical key: order.status.confirmed
  ///
  /// In en, this message translates to:
  /// **'Confirmed'**
  String get order_status_confirmed;

  /// Canonical key: order.status.cooking
  ///
  /// In en, this message translates to:
  /// **'Preparing'**
  String get order_status_cooking;

  /// Canonical key: order.status.ready_for_pickup
  ///
  /// In en, this message translates to:
  /// **'Ready for pickup'**
  String get order_status_ready_for_pickup;

  /// Canonical key: order.status.rider_assigned
  ///
  /// In en, this message translates to:
  /// **'Rider assigned'**
  String get order_status_rider_assigned;

  /// Canonical key: order.status.picked_up
  ///
  /// In en, this message translates to:
  /// **'Picked up'**
  String get order_status_picked_up;

  /// Canonical key: order.status.on_the_way
  ///
  /// In en, this message translates to:
  /// **'On the way'**
  String get order_status_on_the_way;

  /// Canonical key: order.status.arrived
  ///
  /// In en, this message translates to:
  /// **'Rider has arrived'**
  String get order_status_arrived;

  /// Canonical key: order.status.delivered
  ///
  /// In en, this message translates to:
  /// **'Delivered'**
  String get order_status_delivered;

  /// Canonical key: order.status.cancelled
  ///
  /// In en, this message translates to:
  /// **'Cancelled'**
  String get order_status_cancelled;

  /// Canonical key: order.status.failed_delivery
  ///
  /// In en, this message translates to:
  /// **'Delivery failed'**
  String get order_status_failed_delivery;

  /// Canonical key: order.status.unknown
  ///
  /// In en, this message translates to:
  /// **'Unknown'**
  String get order_status_unknown;

  /// Canonical key: location.coordinates — GPS point shown when no address label exists
  ///
  /// In en, this message translates to:
  /// **'{latitude}, {longitude}'**
  String location_coordinates(String latitude, String longitude);
}

class _AppLocalizationsDelegate
    extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  Future<AppLocalizations> load(Locale locale) {
    return SynchronousFuture<AppLocalizations>(lookupAppLocalizations(locale));
  }

  @override
  bool isSupported(Locale locale) =>
      <String>['en', 'ja', 'ny'].contains(locale.languageCode);

  @override
  bool shouldReload(_AppLocalizationsDelegate old) => false;
}

AppLocalizations lookupAppLocalizations(Locale locale) {
  // Lookup logic when only language code is specified.
  switch (locale.languageCode) {
    case 'en':
      return AppLocalizationsEn();
    case 'ja':
      return AppLocalizationsJa();
    case 'ny':
      return AppLocalizationsNy();
  }

  throw FlutterError(
    'AppLocalizations.delegate failed to load unsupported locale "$locale". This is likely '
    'an issue with the localizations generation tool. Please file an issue '
    'on GitHub with a reproducible sample app and the gen-l10n configuration '
    'that was used.',
  );
}
