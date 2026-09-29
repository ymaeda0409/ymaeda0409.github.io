import 'package:bento_core/bento_core.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/core/ui/code_labels.dart';
import 'package:malawi_bento_customer/l10n/generated/app_localizations.dart';

/// Codes the backend can return (App\Enums\ErrorCode + documented later-phase codes).
const serverErrorCodes = [
  'BAD_REQUEST',
  'UNAUTHENTICATED',
  'INVALID_CREDENTIALS',
  'FORBIDDEN',
  'ACCOUNT_DISABLED',
  'RESOURCE_NOT_FOUND',
  'ROUTE_NOT_FOUND',
  'METHOD_NOT_ALLOWED',
  'CONFLICT',
  'VALIDATION_FAILED',
  'OTP_INVALID',
  'OTP_EXPIRED',
  'LANGUAGE_NOT_SUPPORTED',
  'STORE_NOT_AVAILABLE',
  'TOO_MANY_REQUESTS',
  'SERVER_ERROR',
  'OUT_OF_DELIVERY_AREA',
  'PRODUCT_NOT_AVAILABLE',
  'INVALID_STATUS_TRANSITION',
  'DELIVERY_PIN_INVALID',
  'PAYMENT_FAILED',
  'PAYMENT_REQUIRED',
  'PAYMENT_NOT_REQUIRED',
];

const statuses = [
  'NEW',
  'CONFIRMED',
  'COOKING',
  'READY_FOR_PICKUP',
  'RIDER_ASSIGNED',
  'PICKED_UP',
  'ON_THE_WAY',
  'ARRIVED',
  'DELIVERED',
  'CANCELLED',
  'FAILED_DELIVERY',
];

void main() {
  for (final locale in AppLocalizations.supportedLocales) {
    final l = lookupAppLocalizations(locale);

    test(
      'every server error code has its own message (${locale.languageCode})',
      () {
        for (final code in serverErrorCodes) {
          expect(
            errorText(l, ApiException(code)),
            isNot(l.error_unknown),
            reason: code,
          );
        }
        expect(
          errorText(l, const ApiException('SOMETHING_NEW')),
          l.error_unknown,
        );
        expect(errorText(l, StateError('x')), l.error_unknown);
      },
    );

    test('every order status has a label (${locale.languageCode})', () {
      for (final status in statuses) {
        expect(
          orderStatusText(l, status),
          isNot(l.order_status_unknown),
          reason: status,
        );
      }
      expect(orderStatusText(l, 'FUTURE_STATUS'), l.order_status_unknown);
    });
  }

  test('status labels differ by language (no hard-coded English)', () {
    final en = lookupAppLocalizations(const Locale('en'));
    final ja = lookupAppLocalizations(const Locale('ja'));
    expect(orderStatusText(en, 'COOKING'), 'Preparing');
    expect(orderStatusText(ja, 'COOKING'), '調理中');
  });
}
