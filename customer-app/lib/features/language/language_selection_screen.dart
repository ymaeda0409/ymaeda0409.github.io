import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/locale/app_locales.dart';
import '../../core/providers.dart';
import '../../core/ui/widgets.dart';
import 'language_list.dart';

/// 02 Language Selection (first launch). The device language is preselected when
/// bundled; otherwise English. Tapping a language previews the UI in it immediately.
class LanguageSelectionScreen extends ConsumerStatefulWidget {
  const LanguageSelectionScreen({super.key});

  @override
  ConsumerState<LanguageSelectionScreen> createState() =>
      _LanguageSelectionScreenState();
}

class _LanguageSelectionScreenState
    extends ConsumerState<LanguageSelectionScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (ref.read(localeProvider) == null) {
        final device = WidgetsBinding.instance.platformDispatcher.locales;
        ref.read(localeProvider.notifier).select(AppLocales.resolve(device));
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    return Scaffold(
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(24),
          children: [
            const SizedBox(height: 24),
            Icon(
              Icons.translate,
              size: 48,
              color: Theme.of(context).colorScheme.primary,
            ),
            const SizedBox(height: 16),
            Text(
              l.language_title,
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 8),
            Text(l.language_subtitle),
            const SizedBox(height: 24),
            LanguageList(
              selected: ref.watch(effectiveLocaleProvider),
              onSelected: (locale) =>
                  ref.read(localeProvider.notifier).select(locale),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: () async {
                // Persist the (possibly auto-detected) choice before leaving.
                await ref
                    .read(localeProvider.notifier)
                    .select(ref.read(effectiveLocaleProvider));
                if (context.mounted) context.go('/home');
              },
              child: Text(l.common_continue),
            ),
          ],
        ),
      ),
    );
  }
}
