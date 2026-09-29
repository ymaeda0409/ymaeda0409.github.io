import 'package:flutter/widgets.dart';
import 'package:intl/intl.dart';

/// intl has no number/date data for some languages (e.g. Chichewa).
/// Generic rule: format with the UI locale when intl knows it, otherwise English.
class FormattingLocale {
  const FormattingLocale._();

  static String of(Locale locale) {
    final tag = locale.toLanguageTag();
    if (NumberFormat.localeExists(tag) && DateFormat.localeExists(tag)) return tag;
    if (NumberFormat.localeExists(locale.languageCode) &&
        DateFormat.localeExists(locale.languageCode)) {
      return locale.languageCode;
    }
    return 'en';
  }
}
