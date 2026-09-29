import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/locale/app_locales.dart';
import 'language_repository.dart';

/// Language options shown by their own names (English / Chichewa / 日本語),
/// so users can find their language whatever the current UI language is.
class LanguageList extends ConsumerWidget {
  const LanguageList({super.key, required this.selected, required this.onSelected});

  final Locale selected;
  final ValueChanged<Locale> onSelected;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final locales = ref.watch(availableLocalesProvider).value ?? AppLocales.supported;
    return RadioGroup<String>(
      groupValue: selected.languageCode,
      onChanged: (code) => onSelected(locales.firstWhere((l) => l.languageCode == code)),
      child: Column(
        children: [
          for (final locale in locales)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Card(
                child: RadioListTile<String>(
                  value: locale.languageCode,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                  title: Text(AppLocales.nativeName(locale), style: Theme.of(context).textTheme.titleMedium),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

