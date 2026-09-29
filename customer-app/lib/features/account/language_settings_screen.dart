import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/ui/widgets.dart';
import '../language/language_list.dart';

/// 18 Language Settings: applied instantly, synced to the account when signed in.
class LanguageSettingsScreen extends ConsumerWidget {
  const LanguageSettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      appBar: AppBar(title: Text(context.l10n.language_settings_title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          LanguageList(
            selected: ref.watch(effectiveLocaleProvider),
            onSelected: (locale) async {
              await ref.read(localeProvider.notifier).select(locale);
              if (context.mounted) {
                showMessage(context, context.l10n.language_changed);
              }
            },
          ),
        ],
      ),
    );
  }
}
