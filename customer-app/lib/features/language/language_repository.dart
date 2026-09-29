import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/locale/app_locales.dart';
import '../../core/providers.dart';

/// Languages offered in the picker: server-active languages ∩ languages bundled
/// in this app build, in server order. Offline → bundled list.
final availableLocalesProvider = FutureProvider<List<Locale>>((ref) async {
  try {
    final data = await ref.read(apiClientProvider).get<List<dynamic>>('/languages');
    final locales = data
        .map((l) => AppLocales.parse((l as Map<String, dynamic>)['code'] as String))
        .whereType<Locale>()
        .toList();
    return locales.isEmpty ? AppLocales.supported : locales;
  } catch (_) {
    return AppLocales.supported;
  }
});
