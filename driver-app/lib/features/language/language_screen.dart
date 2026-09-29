import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/app_locales.dart';
import '../../core/providers.dart';
import '../../core/ui/widgets.dart';

/// 01 Language Selection (first launch) and 13 Language Settings.
/// Languages are listed by their own names; choosing one switches the UI instantly.
class LanguageScreen extends ConsumerStatefulWidget {
  const LanguageScreen({super.key, required this.onboarding});

  final bool onboarding;

  @override
  ConsumerState<LanguageScreen> createState() => _LanguageScreenState();
}

class _LanguageScreenState extends ConsumerState<LanguageScreen> {
  late Locale _selected;

  @override
  void initState() {
    super.initState();
    _selected =
        ref.read(localeProvider) ??
        AppLocales.resolve(WidgetsBinding.instance.platformDispatcher.locales);
  }

  Future<void> _select(Locale locale) async {
    setState(() => _selected = locale);
    if (!widget.onboarding) {
      await ref.read(localeProvider.notifier).select(locale);
      if (mounted) showMessage(context, context.l10n.language_changed);
    }
  }

  @override
  Widget build(BuildContext context) {
    // During onboarding the screen previews the highlighted language before it is saved.
    return Localizations.override(
      context: context,
      locale: _selected,
      child: Builder(
        builder: (context) {
          final l = context.l10n;
          return Scaffold(
            appBar: widget.onboarding
                ? null
                : AppBar(title: Text(l.language_settings_title)),
            body: SafeArea(
              child: ListView(
                padding: const EdgeInsets.all(24),
                children: [
                  if (widget.onboarding) ...[
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
                  ],
                  RadioGroup<String>(
                    groupValue: _selected.languageCode,
                    onChanged: (code) => _select(
                      AppLocales.supported.firstWhere(
                        (s) => s.languageCode == code,
                      ),
                    ),
                    child: Column(
                      children: [
                        for (final locale in AppLocales.supported)
                          Padding(
                            padding: const EdgeInsets.only(bottom: 10),
                            child: Card(
                              child: RadioListTile<String>(
                                value: locale.languageCode,
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                  vertical: 6,
                                ),
                                title: Text(
                                  AppLocales.nativeName(locale),
                                  style: Theme.of(context)
                                      .textTheme
                                      .titleMedium,
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                  if (widget.onboarding) ...[
                    const SizedBox(height: 16),
                    FilledButton(
                      onPressed: () =>
                          ref.read(localeProvider.notifier).select(_selected),
                      child: Text(l.common_continue),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}
