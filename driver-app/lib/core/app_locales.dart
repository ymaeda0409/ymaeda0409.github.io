import 'package:flutter/widgets.dart';

import '../l10n/generated/app_localizations.dart';

/// Locale rules for the rider app. Nothing else compares language codes.
class AppLocales {
  const AppLocales._();

  static const fallback = Locale('en');

  static List<Locale> get supported => AppLocalizations.supportedLocales;

  static bool isSupported(Locale locale) =>
      supported.any((s) => s.languageCode == locale.languageCode);

  /// First launch: device language when bundled, otherwise English.
  static Locale resolve(Iterable<Locale> deviceLocales) {
    for (final device in deviceLocales) {
      for (final s in supported) {
        if (s.languageCode == device.languageCode) return s;
      }
    }
    return fallback;
  }

  static Locale? parse(String? code) {
    if (code == null || code.isEmpty) return null;
    final locale = Locale.fromSubtags(languageCode: code.split('-').first);
    return isSupported(locale) ? locale : null;
  }

  static String nativeName(Locale locale) =>
      lookupAppLocalizations(locale).language_native_name;
}
