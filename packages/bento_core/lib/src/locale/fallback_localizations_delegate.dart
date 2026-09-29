import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';

/// Flutter's built-in Material/Cupertino/Widgets localizations do not cover
/// every language we support (e.g. Chichewa). This wrapper applies one generic
/// rule — "if the built-in delegate lacks the locale, use the fallback locale" —
/// instead of language-specific branches.
class FallbackLocalizationsDelegate<T> extends LocalizationsDelegate<T> {
  const FallbackLocalizationsDelegate(
    this.inner, {
    this.fallback = const Locale('en'),
  });

  final LocalizationsDelegate<T> inner;
  final Locale fallback;

  @override
  Type get type => T;

  @override
  bool isSupported(Locale locale) => true;

  @override
  Future<T> load(Locale locale) =>
      inner.load(inner.isSupported(locale) ? locale : fallback);

  @override
  bool shouldReload(covariant LocalizationsDelegate<T> old) => false;

  @override
  String toString() =>
      'FallbackLocalizationsDelegate<${objectRuntimeType(inner, 'Delegate')}>';
}
