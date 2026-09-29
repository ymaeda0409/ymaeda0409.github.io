import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'core/providers.dart';
import 'features/auth/otp_screen.dart';
import 'features/auth/phone_screen.dart';
import 'features/delivery/complete_screen.dart';
import 'features/delivery/history_screen.dart';
import 'features/delivery/home_screen.dart';
import 'features/language/language_screen.dart';
import 'features/settings/settings_screen.dart';

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(sessionProvider, (_, _) => refresh.value++);
  ref.listen(localeProvider, (_, _) => refresh.value++);
  ref.onDispose(refresh.dispose);

  return GoRouter(
    initialLocation: '/home',
    refreshListenable: refresh,
    redirect: (context, state) {
      final path = state.uri.path;
      // 01 language first, 02 sign-in next; everything else needs both.
      if (ref.read(localeProvider) == null) {
        return path == '/welcome' ? null : '/welcome';
      }
      final signedIn = ref.read(sessionProvider) != null;
      if (!signedIn) return path.startsWith('/login') ? null : '/login';
      if (path == '/welcome' || path.startsWith('/login')) return '/home';
      return null;
    },
    routes: [
      GoRoute(
        path: '/welcome',
        builder: (_, _) => const LanguageScreen(onboarding: true),
      ),
      GoRoute(path: '/login', builder: (_, _) => const PhoneScreen()),
      GoRoute(
        path: '/login/otp',
        builder: (_, state) =>
            OtpScreen(phone: state.uri.queryParameters['phone']!),
      ),
      GoRoute(path: '/home', builder: (_, _) => const HomeScreen()),
      GoRoute(path: '/complete', builder: (_, _) => const CompleteScreen()),
      GoRoute(path: '/history', builder: (_, _) => const HistoryScreen()),
      GoRoute(path: '/settings', builder: (_, _) => const SettingsScreen()),
      GoRoute(
        path: '/settings/language',
        builder: (_, _) => const LanguageScreen(onboarding: false),
      ),
    ],
  );
});
