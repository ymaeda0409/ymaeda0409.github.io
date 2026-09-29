import 'package:flutter/widgets.dart';

import '../../l10n/generated/app_localizations.dart';

/// Locale rules shared by the whole app. Nothing else compares language codes.
class AppLocales {
  const AppLocales._();

  static const fallback = Locale('en');

  /// Locales bundled in the app (generated from lib/l10n/app_*.arb).
  static List<Locale> get supported => AppLocalizations.supportedLocales;

  static bool isSupported(Locale locale) =>
      supported.any((s) => s.languageCode == locale.languageCode);

  /// First launch: use the device language when bundled, otherwise English.
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

  /// The language's own name (autonym), read from its own ARB file.
  static String nativeName(Locale locale) =>
      lookupAppLocalizations(locale).language_native_name;
}
