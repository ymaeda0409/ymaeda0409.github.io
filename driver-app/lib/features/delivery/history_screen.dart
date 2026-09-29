import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../../core/ui/code_labels.dart';
import '../../core/ui/widgets.dart';
import 'driver_repository.dart';
import 'models.dart';

final historyProvider = FutureProvider.autoDispose<List<Delivery>>((ref) {
  ref.watch(effectiveLocaleProvider);
  return ref.watch(driverRepositoryProvider).history();
});

/// 12 Delivery History
class HistoryScreen extends ConsumerWidget {
  const HistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    return Scaffold(
      appBar: AppBar(title: Text(l.driver_history_title)),
      body: ref
          .watch(historyProvider)
          .when(
            loading: () => const LoadingView(),
            error: (e, _) => ErrorView(
              error: e,
              onRetry: () => ref.invalidate(historyProvider),
            ),
            data: (items) => items.isEmpty
                ? Center(child: Text(l.driver_history_empty))
                : ListView.separated(
                    padding: const EdgeInsets.all(16),
                    itemCount: items.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final d = items[i];
                      return Card(
                        child: ListTile(
                          title: Text(l.driver_order_number(d.orderNumber)),
                          subtitle: Text(
                            [
                              deliveryStatusText(l, d.status),
                              if (d.deliveredAt != null)
                                formatDateTime(d.deliveredAt!, context.locale),
                            ].join(' · '),
                          ),
                          trailing: Text(l.driver_items_count(d.itemCount)),
                        ),
                      );
                    },
                  ),
          ),
    );
  }
}
