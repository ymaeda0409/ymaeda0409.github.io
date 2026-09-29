import '../../l10n/generated/app_localizations.dart';
import '../network/api_exception.dart';

/// The only place where API codes are mapped to translation keys
/// (`error.<code>` / `order.status.<code>`). Unknown codes never crash the UI.
String errorText(AppLocalizations l, Object? error) {
  final code = error is ApiException ? error.code : ApiException.unknown;
  return switch (code.toLowerCase()) {
    'bad_request' => l.error_bad_request,
    'unauthenticated' => l.error_unauthenticated,
    'invalid_credentials' => l.error_invalid_credentials,
    'forbidden' => l.error_forbidden,
    'account_disabled' => l.error_account_disabled,
    'resource_not_found' => l.error_resource_not_found,
    'route_not_found' => l.error_route_not_found,
    'method_not_allowed' => l.error_method_not_allowed,
    'conflict' => l.error_conflict,
    'validation_failed' => l.error_validation_failed,
    'otp_invalid' => l.error_otp_invalid,
    'otp_expired' => l.error_otp_expired,
    'language_not_supported' => l.error_language_not_supported,
    'store_not_available' => l.error_store_not_available,
    'out_of_delivery_area' => l.error_out_of_delivery_area,
    'product_not_available' => l.error_product_not_available,
    'invalid_status_transition' => l.error_invalid_status_transition,
    'delivery_pin_invalid' => l.error_delivery_pin_invalid,
    'payment_failed' => l.error_payment_failed,
    'too_many_requests' => l.error_too_many_requests,
    'server_error' => l.error_server_error,
    'network' => l.error_network,
    'timeout' => l.error_timeout,
    _ => l.error_unknown,
  };
}

String orderStatusText(AppLocalizations l, String status) {
  return switch (status.toLowerCase()) {
    'new' => l.order_status_new,
    'confirmed' => l.order_status_confirmed,
    'cooking' => l.order_status_cooking,
    'ready_for_pickup' => l.order_status_ready_for_pickup,
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
