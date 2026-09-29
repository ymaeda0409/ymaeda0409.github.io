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

  /// The language's own name (autonym). Never translate into another language.
  ///
  /// In en, this message translates to:
  /// **'English'**
  String get language_native_name;

  /// Canonical key: app.title
  ///
  /// In en, this message translates to:
  /// **'Malawi Bento Rider'**
  String get app_title;

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

  /// Canonical key: language.title
  ///
  /// In en, this message translates to:
  /// **'Choose your language'**
  String get language_title;

  /// Canonical key: language.subtitle
  ///
  /// In en, this message translates to:
  /// **'You can change this anytime in Settings.'**
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
  /// **'Rider sign in'**
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

  /// Canonical key: auth.logout
  ///
  /// In en, this message translates to:
  /// **'Sign out'**
  String get auth_logout;

  /// Canonical key: auth.not_a_driver
  ///
  /// In en, this message translates to:
  /// **'This phone number is not registered as a rider. Please contact your store.'**
  String get auth_not_a_driver;

  /// Canonical key: driver.home_title
  ///
  /// In en, this message translates to:
  /// **'Deliveries'**
  String get driver_home_title;

  /// Canonical key: driver.status_online
  ///
  /// In en, this message translates to:
  /// **'You are online'**
  String get driver_status_online;

  /// Canonical key: driver.status_offline
  ///
  /// In en, this message translates to:
  /// **'You are offline'**
  String get driver_status_offline;

  /// Canonical key: driver.go_online
  ///
  /// In en, this message translates to:
  /// **'GO ONLINE'**
  String get driver_go_online;

  /// Canonical key: driver.go_offline
  ///
  /// In en, this message translates to:
  /// **'GO OFFLINE'**
  String get driver_go_offline;

  /// Canonical key: driver.waiting_requests
  ///
  /// In en, this message translates to:
  /// **'Waiting for delivery requests…'**
  String get driver_waiting_requests;

  /// Canonical key: driver.offline_hint
  ///
  /// In en, this message translates to:
  /// **'Go online to receive delivery requests.'**
  String get driver_offline_hint;

  /// Canonical key: driver.location_required
  ///
  /// In en, this message translates to:
  /// **'Location access is needed to go online.'**
  String get driver_location_required;

  /// Canonical key: driver.new_delivery
  ///
  /// In en, this message translates to:
  /// **'New delivery'**
  String get driver_new_delivery;

  /// Canonical key: driver.request_expires_in
  ///
  /// In en, this message translates to:
  /// **'{seconds}s to respond'**
  String driver_request_expires_in(int seconds);

  /// Canonical key: driver.distance_to_pickup
  ///
  /// In en, this message translates to:
  /// **'{distance} km to pickup'**
  String driver_distance_to_pickup(String distance);

  /// Canonical key: driver.delivery_distance
  ///
  /// In en, this message translates to:
  /// **'{distance} km delivery'**
  String driver_delivery_distance(String distance);

  /// Canonical key: driver.accept
  ///
  /// In en, this message translates to:
  /// **'ACCEPT'**
  String get driver_accept;

  /// Canonical key: driver.decline
  ///
  /// In en, this message translates to:
  /// **'DECLINE'**
  String get driver_decline;

  /// Canonical key: driver.collect_cash
  ///
  /// In en, this message translates to:
  /// **'Collect {amount} in cash'**
  String driver_collect_cash(String amount);

  /// Canonical key: driver.prepaid
  ///
  /// In en, this message translates to:
  /// **'Already paid'**
  String get driver_prepaid;

  /// Canonical key: driver.pickup_title
  ///
  /// In en, this message translates to:
  /// **'Pick up'**
  String get driver_pickup_title;

  /// Canonical key: driver.pickup_at
  ///
  /// In en, this message translates to:
  /// **'Pick up at'**
  String get driver_pickup_at;

  /// Canonical key: driver.navigate
  ///
  /// In en, this message translates to:
  /// **'Navigate'**
  String get driver_navigate;

  /// Canonical key: driver.call
  ///
  /// In en, this message translates to:
  /// **'Call'**
  String get driver_call;

  /// Canonical key: driver.order_number
  ///
  /// In en, this message translates to:
  /// **'Order {number}'**
  String driver_order_number(String number);

  /// Canonical key: driver.items_count
  ///
  /// In en, this message translates to:
  /// **'{count, plural, =1{1 item} other{{count} items}}'**
  String driver_items_count(int count);

  /// Canonical key: driver.confirm_pickup
  ///
  /// In en, this message translates to:
  /// **'I HAVE THE ORDER'**
  String get driver_confirm_pickup;

  /// Canonical key: driver.delivery_title
  ///
  /// In en, this message translates to:
  /// **'Deliver'**
  String get driver_delivery_title;

  /// Canonical key: driver.deliver_to
  ///
  /// In en, this message translates to:
  /// **'Deliver to'**
  String get driver_deliver_to;

  /// Canonical key: driver.landmark
  ///
  /// In en, this message translates to:
  /// **'Landmark'**
  String get driver_landmark;

  /// Canonical key: driver.note
  ///
  /// In en, this message translates to:
  /// **'Note'**
  String get driver_note;

  /// Canonical key: driver.arrived
  ///
  /// In en, this message translates to:
  /// **'I HAVE ARRIVED'**
  String get driver_arrived;

  /// Canonical key: driver.pin_title
  ///
  /// In en, this message translates to:
  /// **'Delivery PIN'**
  String get driver_pin_title;

  /// Canonical key: driver.pin_hint
  ///
  /// In en, this message translates to:
  /// **'Ask the customer for their 4-digit PIN.'**
  String get driver_pin_hint;

  /// Canonical key: driver.pin_attempts_left
  ///
  /// In en, this message translates to:
  /// **'{count} attempts left'**
  String driver_pin_attempts_left(int count);

  /// Canonical key: driver.confirm_delivery
  ///
  /// In en, this message translates to:
  /// **'CONFIRM DELIVERY'**
  String get driver_confirm_delivery;

  /// Canonical key: driver.delivery_failed
  ///
  /// In en, this message translates to:
  /// **'Customer not available'**
  String get driver_delivery_failed;

  /// Canonical key: driver.fail_confirm
  ///
  /// In en, this message translates to:
  /// **'Mark this delivery as failed?'**
  String get driver_fail_confirm;

  /// Canonical key: driver.complete_title
  ///
  /// In en, this message translates to:
  /// **'Delivered!'**
  String get driver_complete_title;

  /// Canonical key: driver.complete_message
  ///
  /// In en, this message translates to:
  /// **'Great job. You are ready for the next delivery.'**
  String get driver_complete_message;

  /// Canonical key: driver.back_home
  ///
  /// In en, this message translates to:
  /// **'Back to home'**
  String get driver_back_home;

  /// Canonical key: driver.history_title
  ///
  /// In en, this message translates to:
  /// **'Delivery history'**
  String get driver_history_title;

  /// Canonical key: driver.history_empty
  ///
  /// In en, this message translates to:
  /// **'No deliveries yet'**
  String get driver_history_empty;

  /// Canonical key: driver.pending_sync
  ///
  /// In en, this message translates to:
  /// **'Offline — your update will be sent automatically.'**
  String get driver_pending_sync;

  /// Canonical key: driver.settings
  ///
  /// In en, this message translates to:
  /// **'Settings'**
  String get driver_settings;

  /// Canonical key: driver.vehicle
  ///
  /// In en, this message translates to:
  /// **'Vehicle: {vehicle}'**
  String driver_vehicle(String vehicle);

  /// Canonical key: error.bad_request
  ///
  /// In en, this message translates to:
  /// **'The request is invalid.'**
  String get error_bad_request;

  /// Canonical key: error.unauthenticated
  ///
  /// In en, this message translates to:
  /// **'Please sign in again.'**
  String get error_unauthenticated;

  /// Canonical key: error.forbidden
  ///
  /// In en, this message translates to:
  /// **'You do not have permission to do this.'**
  String get error_forbidden;

  /// Canonical key: error.account_disabled
  ///
  /// In en, this message translates to:
  /// **'Your rider account is suspended.'**
  String get error_account_disabled;

  /// Canonical key: error.resource_not_found
  ///
  /// In en, this message translates to:
  /// **'This delivery was not found.'**
  String get error_resource_not_found;

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

  /// Canonical key: error.invalid_status_transition
  ///
  /// In en, this message translates to:
  /// **'This delivery has already moved on.'**
  String get error_invalid_status_transition;

  /// Canonical key: error.offer_not_available
  ///
  /// In en, this message translates to:
  /// **'This request is no longer available.'**
  String get error_offer_not_available;

  /// Canonical key: error.delivery_pin_invalid
  ///
  /// In en, this message translates to:
  /// **'The PIN is incorrect. Ask the customer again.'**
  String get error_delivery_pin_invalid;

  /// Canonical key: error.delivery_pin_locked
  ///
  /// In en, this message translates to:
  /// **'Too many wrong PINs. Please call the store.'**
  String get error_delivery_pin_locked;

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
  /// **'No internet connection.'**
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

  /// Canonical key: order.status_rider_assigned
  ///
  /// In en, this message translates to:
  /// **'Go to pickup'**
  String get order_status_rider_assigned;

  /// Canonical key: order.status_picked_up
  ///
  /// In en, this message translates to:
  /// **'Picked up'**
  String get order_status_picked_up;

  /// Canonical key: order.status_on_the_way
  ///
  /// In en, this message translates to:
  /// **'On the way'**
  String get order_status_on_the_way;

  /// Canonical key: order.status_arrived
  ///
  /// In en, this message translates to:
  /// **'Arrived'**
  String get order_status_arrived;

  /// Canonical key: order.status_delivered
  ///
  /// In en, this message translates to:
  /// **'Delivered'**
  String get order_status_delivered;

  /// Canonical key: order.status_cancelled
  ///
  /// In en, this message translates to:
  /// **'Cancelled'**
  String get order_status_cancelled;

  /// Canonical key: order.status_failed_delivery
  ///
  /// In en, this message translates to:
  /// **'Delivery failed'**
  String get order_status_failed_delivery;

  /// Canonical key: order.status_unknown
  ///
  /// In en, this message translates to:
  /// **'Unknown'**
  String get order_status_unknown;

  /// Canonical key: vehicle.motorbike
  ///
  /// In en, this message translates to:
  /// **'Motorbike'**
  String get vehicle_motorbike;

  /// Canonical key: vehicle.bicycle
  ///
  /// In en, this message translates to:
  /// **'Bicycle'**
  String get vehicle_bicycle;

  /// Canonical key: vehicle.car
  ///
  /// In en, this message translates to:
  /// **'Car'**
  String get vehicle_car;
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
