import 'package:bento_core/bento_core.dart';
import 'package:flutter/cupertino.dart' show CupertinoLocalizations;
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/locale/app_locales.dart';
import 'core/providers.dart';
import 'core/ui/theme.dart';
import 'l10n/generated/app_localizations.dart';
import 'router.dart';

class BentoApp extends ConsumerWidget {
  const BentoApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return MaterialApp.router(
      onGenerateTitle: (context) => AppLocalizations.of(context).app_title,
      theme: AppTheme.light(),
      routerConfig: ref.watch(routerProvider),
      // Changing the locale rebuilds the whole tree: no restart needed.
      locale: ref.watch(effectiveLocaleProvider),
      supportedLocales: AppLocales.supported,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        FallbackLocalizationsDelegate<MaterialLocalizations>(
          GlobalMaterialLocalizations.delegate,
        ),
        FallbackLocalizationsDelegate<CupertinoLocalizations>(
          GlobalCupertinoLocalizations.delegate,
        ),
        FallbackLocalizationsDelegate<WidgetsLocalizations>(
          GlobalWidgetsLocalizations.delegate,
        ),
      ],
    );
  }
}
