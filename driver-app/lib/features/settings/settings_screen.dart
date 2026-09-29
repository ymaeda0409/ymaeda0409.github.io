import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/app_locales.dart';
import '../../core/providers.dart';
import '../../core/ui/code_labels.dart';
import '../../core/ui/widgets.dart';
import '../auth/auth_repository.dart';
import '../delivery/driver_controller.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final profile = ref.watch(driverControllerProvider).profile;
    return Scaffold(
      appBar: AppBar(title: Text(l.driver_settings)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (profile != null)
            Card(
              child: ListTile(
                leading: const Icon(Icons.person_outline),
                title: Text(profile.name ?? ''),
                subtitle: Text(
                  l.driver_vehicle(vehicleText(l, profile.vehicleType)),
                ),
              ),
            ),
          const SizedBox(height: 12),
          Card(
            child: ListTile(
              leading: const Icon(Icons.translate),
              title: Text(l.language_settings_title),
              subtitle: Text(
                AppLocales.nativeName(ref.watch(effectiveLocaleProvider)),
              ),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => context.push('/settings/language'),
            ),
          ),
          const SizedBox(height: 24),
          OutlinedButton.icon(
            icon: const Icon(Icons.logout),
            label: Text(l.auth_logout),
            onPressed: () async {
              try {
                await ref.read(driverControllerProvider.notifier).goOffline();
                await ref.read(deviceRegistrarProvider).unregister();
                await ref.read(authRepositoryProvider).logout();
              } catch (_) {
                // Offline: the local session is cleared anyway.
              }
              await ref.read(sessionProvider.notifier).signOut();
            },
          ),
        ],
      ),
    );
  }
}
