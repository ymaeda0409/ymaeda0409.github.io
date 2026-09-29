import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/format/money.dart';
import '../../core/providers.dart';
import '../../core/ui/widgets.dart';
import 'order_providers.dart';
import 'order_widgets.dart';

/// 14 Order History
class OrderHistoryScreen extends ConsumerWidget {
  const OrderHistoryScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final signedIn = ref.watch(sessionProvider).isSignedIn;

    return Scaffold(
      appBar: AppBar(title: Text(l.order_history_title)),
      body: !signedIn
          ? MessageView(
              icon: Icons.receipt_long_outlined,
              message: l.auth_login_required,
              action: FilledButton(onPressed: () => context.push('/login'), child: Text(l.account_sign_in)),
            )
          : ref.watch(ordersProvider).when(
                loading: () => const LoadingView(),
                error: (e, _) => ErrorView(error: e, onRetry: () => ref.invalidate(ordersProvider)),
                data: (orders) => orders.isEmpty
                    ? MessageView(icon: Icons.receipt_long_outlined, message: l.order_history_empty)
                    : RefreshIndicator(
                        onRefresh: () => ref.refresh(ordersProvider.future),
                        child: ListView.separated(
                          padding: const EdgeInsets.all(16),
                          itemCount: orders.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 10),
                          itemBuilder: (context, i) {
                            final o = orders[i];
                            return Card(
                              child: ListTile(
                                onTap: () => context.push('/orders/${o.id}'),
                                title: Text(o.storeName ?? o.orderNumber),
                                subtitle: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                                  Text(formatDateTime(o.orderedAt, context.locale)),
                                  Text(l.cart_item_count(o.itemCount)),
                                ]),
                                trailing: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    Text(formatMoney(o.total, o.currency, context.locale)),
                                  ],
                                ),
                                isThreeLine: true,
                                leading: StatusChip(o.status),
                              ),
                            );
                          },
                        ),
                      ),
              ),
    );
  }
}
