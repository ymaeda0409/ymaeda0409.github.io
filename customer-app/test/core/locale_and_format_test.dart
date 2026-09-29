import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:malawi_bento_customer/core/format/money.dart';
import 'package:malawi_bento_customer/core/locale/app_locales.dart';
import 'package:malawi_bento_customer/core/locale/formatting_locale.dart';

void main() {
  setUpAll(() => initializeDateFormatting());

  group('AppLocales.resolve (first launch)', () {
    test('uses a supported device language', () {
      expect(AppLocales.resolve(const [Locale('ja', 'JP')]), const Locale('ja'));
      expect(AppLocales.resolve(const [Locale('ny', 'MW')]), const Locale('ny'));
    });

    test('takes the first supported entry of the device list', () {
      expect(AppLocales.resolve(const [Locale('fr'), Locale('ja')]), const Locale('ja'));
    });

    test('falls back to English for unsupported languages', () {
      expect(AppLocales.resolve(const [Locale('fr', 'FR'), Locale('de')]), const Locale('en'));
      expect(AppLocales.resolve(const []), const Locale('en'));
    });

    test('parse ignores unknown codes', () {
      expect(AppLocales.parse('ja'), const Locale('ja'));
      expect(AppLocales.parse('xx'), isNull);
      expect(AppLocales.parse(null), isNull);
    });

    test('native names come from each language file', () {
      expect(AppLocales.nativeName(const Locale('ja')), '日本語');
      expect(AppLocales.nativeName(const Locale('ny')), 'Chichewa');
      expect(AppLocales.nativeName(const Locale('en')), 'English');
    });
  });

  group('formatting', () {
    test('intl-unknown locales format with English rules', () {
      expect(FormattingLocale.of(const Locale('ny')), 'en');
      expect(FormattingLocale.of(const Locale('ja')), 'ja');
    });

    test('money is formatted from minor units with the currency symbol', () {
      expect(formatMoney(350000, 'MWK', const Locale('en')), 'MK 3,500');
      expect(formatMoney(350000, 'MWK', const Locale('ny')), 'MK 3,500');
      expect(formatMoney(12345000, 'MWK', const Locale('ja')), 'MK 123,450');
      expect(formatMoney(0, 'MWK', const Locale('en')), 'MK 0');
    });

    test('dates are localized', () {
      final date = DateTime(2026, 9, 29, 18, 30);
      expect(formatDateTime(date, const Locale('ja')), contains('2026'));
      expect(formatDateTime(date, const Locale('ny')), formatDateTime(date, const Locale('en')));
    });
  });
}
