import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/ui/widgets.dart';
import '../location/address_repository.dart';

/// 17 Saved Addresses
class AddressesScreen extends ConsumerWidget {
  const AddressesScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final addresses = ref.watch(addressesProvider);
    final repo = ref.read(addressRepositoryProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l.address_title)),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => context.push('/location/new'),
        icon: const Icon(Icons.add),
        label: Text(l.location_new_address),
      ),
      body: addresses.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(
          error: e,
          onRetry: () => ref.invalidate(addressesProvider),
        ),
        data: (items) => items.isEmpty
            ? MessageView(
                icon: Icons.location_off_outlined,
                message: l.address_empty,
              )
            : ListView.separated(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 96),
                itemCount: items.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (context, i) {
                  final a = items[i];
                  return Card(
                    child: ListTile(
                      title: Wrap(
                        spacing: 8,
                        crossAxisAlignment: WrapCrossAlignment.center,
                        children: [
                          Text(a.name),
                          if (a.isDefault)
                            Chip(
                              visualDensity: VisualDensity.compact,
                              label: Text(l.address_default),
                            ),
                        ],
                      ),
                      subtitle: a.summary.isEmpty ? null : Text(a.summary),
                      trailing: PopupMenuButton<String>(
                        onSelected: (action) async {
                          try {
                            if (action == 'default') {
                              await repo.update(a.id, {'is_default': true});
                            }
                            if (action == 'delete' && context.mounted) {
                              final ok = await showDialog<bool>(
                                context: context,
                                builder: (context) => AlertDialog(
                                  content: Text(l.address_delete_confirm),
                                  actions: [
                                    TextButton(
                                      onPressed: () =>
                                          Navigator.pop(context, false),
                                      child: Text(l.common_cancel),
                                    ),
                                    TextButton(
                                      onPressed: () =>
                                          Navigator.pop(context, true),
                                      child: Text(l.common_delete),
                                    ),
                                  ],
                                ),
                              );
                              if (ok == true) await repo.delete(a.id);
                            }
                            ref.invalidate(addressesProvider);
                          } catch (e) {
                            if (context.mounted) showError(context, e);
                          }
                        },
                        itemBuilder: (_) => [
                          if (!a.isDefault)
                            PopupMenuItem(
                              value: 'default',
                              child: Text(l.address_set_default),
                            ),
                          PopupMenuItem(
                            value: 'delete',
                            child: Text(l.common_delete),
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
      ),
    );
  }
}
