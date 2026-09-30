import 'package:bento_core/bento_core.dart';
import 'package:dio/dio.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app.dart';
import 'core/config/app_config.dart';
import 'demo/demo_backend.dart';
import 'features/location/geo_service.dart';
import 'core/providers.dart';

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

  runApp(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(prefs),
        tokenStoreProvider.overrideWithValue(tokens),
        initialTokenProvider.overrideWithValue(token),
        if (AppConfig.demoMode)
          dioProvider.overrideWithValue(
            Dio()..httpClientAdapter = DemoBackend(prefs),
          ),
        if (AppConfig.demoMode)
          geoServiceProvider.overrideWithValue(const DemoGeoService()),
      ],
      child: const BentoApp(),
    ),
  );
}
