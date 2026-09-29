import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/locale/app_locales.dart';
import '../../core/providers.dart';
import '../../core/ui/widgets.dart';
import '../auth/auth_repository.dart';

/// 16 Account
class AccountScreen extends ConsumerWidget {
  const AccountScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final session = ref.watch(sessionProvider);
    final user = session.user;
    final locale = ref.watch(effectiveLocaleProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l.account_title)),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          if (!session.isSignedIn)
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      l.account_guest,
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 12),
                    FilledButton(
                      onPressed: () => context.push('/login'),
                      child: Text(l.account_sign_in),
                    ),
                  ],
                ),
              ),
            )
          else
            Card(
              child: Column(
                children: [
                  ListTile(
                    leading: const Icon(Icons.person_outline),
                    title: Text(l.account_name),
                    subtitle: Text(user?.name ?? '—'),
                    trailing: const Icon(Icons.edit_outlined),
                    onTap: () => _editName(context, ref, user?.name),
                  ),
                  ListTile(
                    leading: const Icon(Icons.phone_outlined),
                    title: Text(l.account_phone),
                    subtitle: Text(user?.phone ?? ''),
                  ),
                ],
              ),
            ),
          const SizedBox(height: 12),
          Card(
            child: Column(
              children: [
                ListTile(
                  leading: const Icon(Icons.translate),
                  title: Text(l.account_language),
                  subtitle: Text(AppLocales.nativeName(locale)),
                  trailing: const Icon(Icons.chevron_right),
                  onTap: () => context.push('/account/language'),
                ),
                if (session.isSignedIn)
                  ListTile(
                    leading: const Icon(Icons.location_on_outlined),
                    title: Text(l.account_addresses),
                    trailing: const Icon(Icons.chevron_right),
                    onTap: () => context.push('/account/addresses'),
                  ),
              ],
            ),
          ),
          if (session.isSignedIn) ...[
            const SizedBox(height: 24),
            OutlinedButton.icon(
              icon: const Icon(Icons.logout),
              label: Text(l.auth_logout),
              onPressed: () async {
                try {
                  await ref.read(deviceRegistrarProvider).unregister();
                  await ref.read(authRepositoryProvider).logout();
                } catch (_) {
                  // Offline: the local session is still cleared.
                }
                await ref.read(sessionProvider.notifier).signOut();
              },
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _editName(
    BuildContext context,
    WidgetRef ref,
    String? current,
  ) async {
    final controller = TextEditingController(text: current);
    final l = context.l10n;
    final name = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text(l.account_name),
        content: TextField(controller: controller, autofocus: true),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text(l.common_cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, controller.text.trim()),
            child: Text(l.common_save),
          ),
        ],
      ),
    );
    controller.dispose();
    if (name == null || name.isEmpty) return;
    try {
      final user = await ref.read(authRepositoryProvider).updateName(name);
      await ref.read(sessionProvider.notifier).updateUser(user);
    } catch (e) {
      if (context.mounted) showError(context, e);
    }
  }
}
