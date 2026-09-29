import 'dart:convert';

import 'package:flutter/widgets.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../features/auth/user.dart';
import 'config/app_config.dart';
import 'locale/app_locales.dart';
import 'network/api_client.dart';
import 'storage/token_store.dart';

/// Overridden in main() (and in tests) with ready instances.
final sharedPreferencesProvider = Provider<SharedPreferences>((ref) => throw UnimplementedError());
final tokenStoreProvider = Provider<TokenStore>((ref) => throw UnimplementedError());

/// Initial auth token read before runApp (so the first requests are authenticated).
final initialTokenProvider = Provider<String?>((ref) => null);

// ---------------------------------------------------------------- Locale

/// The UI locale chosen by the user. `null` = not chosen yet (first launch).
final localeProvider = NotifierProvider<LocaleController, Locale?>(LocaleController.new);

class LocaleController extends Notifier<Locale?> {
  static const _key = 'locale';

  @override
  Locale? build() => AppLocales.parse(ref.read(sharedPreferencesProvider).getString(_key));

  /// Applies immediately (no restart) and syncs to the server when signed in.
  Future<void> select(Locale locale) async {
    state = locale;
    await ref.read(sharedPreferencesProvider).setString(_key, locale.languageCode);
    if (ref.read(sessionProvider).isSignedIn) {
      try {
        await ref.read(apiClientProvider).put<Object?>('/account/language', body: {'language': locale.languageCode});
      } catch (_) {
        // Best effort: the header-based locale keeps the app consistent meanwhile.
      }
    }
  }
}

/// Locale actually used for rendering and API calls.
final effectiveLocaleProvider = Provider<Locale>((ref) => ref.watch(localeProvider) ?? AppLocales.fallback);

// ---------------------------------------------------------------- Session

class Session {
  const Session({this.token, this.user});

  final String? token;
  final AppUser? user;

  bool get isSignedIn => token != null;
}

final sessionProvider = NotifierProvider<SessionController, Session>(SessionController.new);

class SessionController extends Notifier<Session> {
  static const _userKey = 'user';

  @override
  Session build() {
    final token = ref.read(initialTokenProvider);
    final cached = ref.read(sharedPreferencesProvider).getString(_userKey);
    final user = token != null && cached != null
        ? AppUser.fromJson(jsonDecode(cached) as Map<String, dynamic>)
        : null;
    return Session(token: token, user: user);
  }

  Future<void> signIn(String token, AppUser user) async {
    await ref.read(tokenStoreProvider).write(token);
    await ref.read(sharedPreferencesProvider).setString(_userKey, jsonEncode(user.toJson()));
    state = Session(token: token, user: user);
  }

  Future<void> updateUser(AppUser user) async {
    await ref.read(sharedPreferencesProvider).setString(_userKey, jsonEncode(user.toJson()));
    state = Session(token: state.token, user: user);
  }

  Future<void> signOut() async {
    await ref.read(tokenStoreProvider).clear();
    await ref.read(sharedPreferencesProvider).remove(_userKey);
    state = const Session();
  }
}

// ---------------------------------------------------------------- API

final apiBaseUrlProvider = Provider<String>((ref) => AppConfig.apiBaseUrl);

final apiClientProvider = Provider<ApiClient>((ref) {
  return ApiClient(
    baseUrl: ref.watch(apiBaseUrlProvider),
    localeTag: () => ref.read(effectiveLocaleProvider).toLanguageTag(),
    token: () => ref.read(sessionProvider).token,
    onUnauthorized: () => ref.read(sessionProvider.notifier).signOut(),
  );
});
