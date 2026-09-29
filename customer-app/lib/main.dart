import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app.dart';
import 'core/providers.dart';
import 'core/storage/token_store.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting();

  final prefs = await SharedPreferences.getInstance();
  final tokens = SecureTokenStore();
  String? token;
  try {
    token = await tokens.read();
  } catch (_) {
    token = null; // Corrupted keystore: continue as guest.
  }

  runApp(ProviderScope(
    overrides: [
      sharedPreferencesProvider.overrideWithValue(prefs),
      tokenStoreProvider.overrideWithValue(tokens),
      initialTokenProvider.overrideWithValue(token),
    ],
    child: const BentoApp(),
  ));
}
