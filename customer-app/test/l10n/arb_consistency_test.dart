import 'dart:convert';
import 'dart:io';

import 'package:flutter_test/flutter_test.dart';

/// Every language file must define exactly the template's keys with the same
/// placeholders, so adding a language cannot silently miss strings.
void main() {
  final dir = Directory('lib/l10n');
  Map<String, dynamic> load(String name) =>
      jsonDecode(File('${dir.path}/$name').readAsStringSync()) as Map<String, dynamic>;

  final template = load('app_en.arb');
  final keys = template.keys.where((k) => !k.startsWith('@')).toSet();
  final files = dir.listSync().whereType<File>().map((f) => f.uri.pathSegments.last).where((n) => n.endsWith('.arb'));

  Set<String> placeholders(String text) =>
      RegExp(r'\{(\w+)[,}]').allMatches(text).map((m) => m.group(1)!).toSet();

  test('bundled languages', () {
    expect(files, containsAll(['app_en.arb', 'app_ny.arb', 'app_ja.arb']));
  });

  for (final file in files) {
    test('$file has the same keys and placeholders as app_en.arb', () {
      final arb = load(file);
      final arbKeys = arb.keys.where((k) => !k.startsWith('@')).toSet();
      expect(arbKeys.difference(keys), isEmpty, reason: 'extra keys');
      expect(keys.difference(arbKeys), isEmpty, reason: 'missing keys');
      for (final key in keys) {
        final value = arb[key] as String;
        expect(value.trim(), isNotEmpty, reason: '$file: $key is empty');
        expect(placeholders(value), placeholders(template[key] as String), reason: '$file: $key placeholders');
      }
    });
  }

  test('keys follow the canonical feature_sub naming', () {
    for (final key in keys) {
      expect(RegExp(r'^[a-z]+(_[a-z0-9]+)+$').hasMatch(key), isTrue, reason: key);
    }
  });
}
