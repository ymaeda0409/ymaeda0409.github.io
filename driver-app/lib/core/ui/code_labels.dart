import 'package:bento_core/bento_core.dart';

import '../../l10n/generated/app_localizations.dart';

/// The only place where API codes are mapped to translations. Unknown codes never crash.
String errorText(AppLocalizations l, Object? error) {
  final code = error is ApiException ? error.code : ApiException.unknown;
  return switch (code.toLowerCase()) {
    'bad_request' => l.error_bad_request,
    'unauthenticated' => l.error_unauthenticated,
    'forbidden' => l.error_forbidden,
    'account_disabled' => l.error_account_disabled,
    'resource_not_found' => l.error_resource_not_found,
    'validation_failed' => l.error_validation_failed,
    'otp_invalid' => l.error_otp_invalid,
    'otp_expired' => l.error_otp_expired,
    'invalid_status_transition' => l.error_invalid_status_transition,
    'offer_not_available' => l.error_offer_not_available,
    'delivery_pin_invalid' => l.error_delivery_pin_invalid,
    'delivery_pin_locked' => l.error_delivery_pin_locked,
    'too_many_requests' => l.error_too_many_requests,
    'server_error' => l.error_server_error,
    'network' => l.error_network,
    'timeout' => l.error_timeout,
    _ => l.error_unknown,
  };
}

String deliveryStatusText(AppLocalizations l, String status) {
  return switch (status.toLowerCase()) {
    'rider_assigned' => l.order_status_rider_assigned,
    'picked_up' => l.order_status_picked_up,
    'on_the_way' => l.order_status_on_the_way,
    'arrived' => l.order_status_arrived,
    'delivered' => l.order_status_delivered,
    'cancelled' => l.order_status_cancelled,
    'failed_delivery' => l.order_status_failed_delivery,
    _ => l.order_status_unknown,
  };
}

String vehicleText(AppLocalizations l, String type) {
  return switch (type) {
    'MOTORBIKE' => l.vehicle_motorbike,
    'BICYCLE' => l.vehicle_bicycle,
    'CAR' => l.vehicle_car,
    _ => type,
  };
}
