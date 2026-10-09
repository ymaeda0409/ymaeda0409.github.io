import 'dart:async';

import 'package:bento_core/bento_core.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'app_locales.dart';
import 'config.dart';

/// Overridden in main() (and in tests).
final sharedPreferencesProvider = Provider<SharedPreferences>(
  (ref) => throw UnimplementedError(),
);
final tokenStoreProvider = Provider<TokenStore>(
  (ref) => throw UnimplementedError(),
);
final initialTokenProvider = Provider<String?>((ref) => null);

// ---------------------------------------------------------------- Locale

/// UI locale chosen by the rider (`null` until the first choice).
final localeProvider = NotifierProvider<LocaleController, Locale?>(
  LocaleController.new,
);

class LocaleController extends Notifier<Locale?> {
  static const _key = 'locale';

  @override
  Locale? build() =>
      AppLocales.parse(ref.read(sharedPreferencesProvider).getString(_key));

  /// Applies immediately; synced to users.preferred_language (used for push/SMS) when signed in.
  Future<void> select(Locale locale) async {
    state = locale;
    await ref
        .read(sharedPreferencesProvider)
        .setString(_key, locale.languageCode);
    if (ref.read(sessionProvider) != null) {
      try {
        await ref
            .read(apiClientProvider)
            .put<Object?>(
              '/account/language',
              body: {'language': locale.languageCode},
            );
      } catch (_) {
        // Best effort: Accept-Language keeps the app consistent meanwhile.
      }
    }
  }
}

final effectiveLocaleProvider = Provider<Locale>(
  (ref) => ref.watch(localeProvider) ?? AppLocales.fallback,
);

// ---------------------------------------------------------------- Session (token)

final sessionProvider = NotifierProvider<SessionController, String?>(
  SessionController.new,
);

class SessionController extends Notifier<String?> {
  @override
  String? build() => ref.read(initialTokenProvider);

  Future<void> signIn(String token) async {
    await ref.read(tokenStoreProvider).write(token);
    state = token;
    unawaited(ref.read(deviceRegistrarProvider).register());
  }

  Future<void> signOut() async {
    await ref.read(tokenStoreProvider).clear();
    state = null;
  }
}

// ---------------------------------------------------------------- API

/// A path such as `/api` (web build served next to the API) resolves against the
/// page's own origin, so one web bundle works on any host.
final apiBaseUrlProvider = Provider<String>(
  (ref) => AppConfig.apiBaseUrl.startsWith('/')
      ? Uri.base.resolve(AppConfig.apiBaseUrl).toString()
      : AppConfig.apiBaseUrl,
);

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(
    baseUrl: ref.watch(apiBaseUrlProvider),
    localeTag: () => ref.read(effectiveLocaleProvider).toLanguageTag(),
    token: () => ref.read(sessionProvider),
    onUnauthorized: () => ref.read(sessionProvider.notifier).signOut(),
  );
});

// ---------------------------------------------------------------- Push

/// Override with a firebase_messaging-backed source once a Firebase project is configured.
final pushTokenSourceProvider = Provider<PushTokenSource>(
  (ref) => const NoPushTokenSource(),
);

final deviceRegistrarProvider = Provider<DeviceRegistrar>(
  (ref) => DeviceRegistrar(
    ref.watch(apiClientProvider),
    ref.watch(pushTokenSourceProvider),
    app: 'driver',
  ),
);
