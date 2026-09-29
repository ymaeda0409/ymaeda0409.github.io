import 'package:flutter/widgets.dart';
import 'package:intl/intl.dart';

import '../locale/formatting_locale.dart';

/// Currency metadata. Amounts from the API are integer minor units.
class CurrencySpec {
  const CurrencySpec({required this.exponent, required this.symbol, required this.displayDecimals});

  /// Minor-unit digits used by the API (MWK: 2 → tambala).
  final int exponent;
  final String symbol;

  /// Digits shown to users (tambala are not used in practice).
  final int displayDecimals;
}

const currencies = <String, CurrencySpec>{
  'MWK': CurrencySpec(exponent: 2, symbol: 'MK', displayDecimals: 0),
};

/// Formats `minorUnits` of `currency` for `locale` (never by string concatenation).
String formatMoney(int minorUnits, String currency, Locale locale) {
  final spec = currencies[currency] ?? const CurrencySpec(exponent: 2, symbol: '', displayDecimals: 2);
  final format = NumberFormat.currency(
    locale: FormattingLocale.of(locale),
    name: currency,
    symbol: spec.symbol.isEmpty ? currency : '${spec.symbol} ',
    decimalDigits: spec.displayDecimals,
  );
  return format.format(minorUnits / _pow10(spec.exponent));
}

String formatDateTime(DateTime value, Locale locale) {
  final tag = FormattingLocale.of(locale);
  return DateFormat.yMMMd(tag).add_Hm().format(value.toLocal());
}

String formatTime(DateTime value, Locale locale) =>
    DateFormat.Hm(FormattingLocale.of(locale)).format(value.toLocal());

String formatDecimal(num value, Locale locale, {int digits = 5}) =>
    NumberFormat.decimalPatternDigits(locale: FormattingLocale.of(locale), decimalDigits: digits)
        .format(value);

int _pow10(int e) {
  var r = 1;
  for (var i = 0; i < e; i++) {
    r *= 10;
  }
  return r;
}
