import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/ui/widgets.dart';
import 'order_providers.dart';
import 'order_widgets.dart';

/// 12 Order Complete: order number + delivery PIN.
class OrderCompleteScreen extends ConsumerWidget {
  const OrderCompleteScreen({super.key, required this.orderId});

  final int orderId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    return Scaffold(
      appBar: AppBar(
        automaticallyImplyLeading: false,
        actions: [CloseButton(onPressed: () => context.go('/home'))],
      ),
      body: ref
          .watch(orderDetailProvider(orderId))
          .when(
            loading: () => const LoadingView(),
            error: (e, _) => ErrorView(
              error: e,
              onRetry: () => ref.invalidate(orderDetailProvider(orderId)),
            ),
            data: (order) => ListView(
              padding: const EdgeInsets.all(24),
              children: [
                Icon(
                  Icons.check_circle,
                  size: 72,
                  color: Theme.of(context).colorScheme.primary,
                ),
                const SizedBox(height: 16),
                Text(
                  l.order_complete_title,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.headlineSmall,
                ),
                const SizedBox(height: 16),
                Text(l.order_number_label, textAlign: TextAlign.center),
                SelectableText(
                  order.orderNumber,
                  textAlign: TextAlign.center,
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 16),
                if (order.deliveryPin != null)
                  DeliveryPinCard(pin: order.deliveryPin!),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: () => context.go('/orders/${order.id}'),
                  child: Text(l.order_view),
                ),
              ],
            ),
          ),
    );
  }
}
