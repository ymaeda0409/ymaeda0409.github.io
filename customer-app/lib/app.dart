import 'package:bento_core/bento_core.dart';
import 'package:flutter/cupertino.dart' show CupertinoLocalizations;
import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'core/config/app_config.dart';
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
      // Public web demo: a visible label so nobody mistakes it for the live service.
      builder: AppConfig.demoMode
          ? (context, child) => _DemoLabel(child: child!)
          : null,
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

class _DemoLabel extends StatelessWidget {
  const _DemoLabel({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Stack(
      children: [
        child,
        PositionedDirectional(
          top: 0,
          end: 12,
          child: IgnorePointer(
            child: SafeArea(
              child: Material(
                color: scheme.tertiary,
                borderRadius: const BorderRadius.vertical(
                  bottom: Radius.circular(8),
                ),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 2,
                  ),
                  child: Text(
                    AppLocalizations.of(context).demo_banner,
                    style: TextStyle(
                      color: scheme.onTertiary,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }
}
