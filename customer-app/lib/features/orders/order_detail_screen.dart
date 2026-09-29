import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/format/money.dart';
import '../../core/ui/widgets.dart';
import '../checkout/order_repository.dart';
import 'order.dart';
import 'order_providers.dart';
import 'order_widgets.dart';

/// 15 Order Detail (status is refreshed every 20 s while the order is active;
/// the live map comes with tracking in PHASE 5).
class OrderDetailScreen extends ConsumerStatefulWidget {
  const OrderDetailScreen({super.key, required this.orderId});

  final int orderId;

  @override
  ConsumerState<OrderDetailScreen> createState() => _OrderDetailScreenState();
}

class _OrderDetailScreenState extends ConsumerState<OrderDetailScreen> {
  Timer? _poll;

  @override
  void initState() {
    super.initState();
    _poll = Timer.periodic(const Duration(seconds: 20), (_) {
      final order = ref.read(orderDetailProvider(widget.orderId)).value;
      if (order == null || order.isActive) ref.invalidate(orderDetailProvider(widget.orderId));
    });
  }

  @override
  void dispose() {
    _poll?.cancel();
    super.dispose();
  }

  Future<void> _cancel(Order order) async {
    final l = context.l10n;
    final ok = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        content: Text(l.order_cancel_confirm),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: Text(l.common_cancel)),
          TextButton(onPressed: () => Navigator.pop(context, true), child: Text(l.order_cancel)),
        ],
      ),
    );
    if (ok != true) return;
    try {
      await ref.read(orderRepositoryProvider).cancel(order.id);
      ref.invalidate(orderDetailProvider(order.id));
      ref.invalidate(ordersProvider);
    } catch (e) {
      if (mounted) showError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l.order_detail_title), leading: closeToHomeIfRoot(context)),
      body: ref.watch(orderDetailProvider(widget.orderId)).when(
            loading: () => const LoadingView(),
            error: (e, _) => ErrorView(error: e, onRetry: () => ref.invalidate(orderDetailProvider(widget.orderId))),
            data: (order) => RefreshIndicator(
              onRefresh: () => ref.refresh(orderDetailProvider(widget.orderId).future),
              child: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Wrap(spacing: 8, runSpacing: 4, crossAxisAlignment: WrapCrossAlignment.center, children: [
                    Text(order.orderNumber, style: theme.textTheme.titleMedium),
                    StatusChip(order.status),
                  ]),
                  Text(l.order_ordered_at(formatDateTime(order.orderedAt, context.locale))),
                  if (order.scheduledAt != null) Text(l.checkout_scheduled_for(formatDateTime(order.scheduledAt!, context.locale))),
                  const SizedBox(height: 12),
                  if (order.deliveryPin != null && order.isActive) DeliveryPinCard(pin: order.deliveryPin!),
                  const SizedBox(height: 12),
                  Text(l.order_status_title, style: theme.textTheme.titleMedium),
                  Card(child: StatusTimeline(entries: order.timeline)),
                  const SizedBox(height: 12),
                  Text(l.checkout_order_summary, style: theme.textTheme.titleMedium),
                  Card(
                    child: Padding(
                      padding: const EdgeInsets.all(12),
                      child: Column(children: [
                        for (final item in order.items)
                          ListTile(
                            contentPadding: EdgeInsets.zero,
                            leading: Text('${item.quantity}×', style: theme.textTheme.titleMedium),
                            title: Text(item.name),
                            subtitle: item.options.isEmpty ? null : Text(item.options.join(', ')),
                            trailing: Text(formatMoney(item.total, order.currency, context.locale)),
                          ),
                        const Divider(),
                        OrderAmounts(order: order),
                      ]),
                    ),
                  ),
                  if (order.canCancel) ...[
                    const SizedBox(height: 16),
                    OutlinedButton(onPressed: () => _cancel(order), child: Text(l.order_cancel)),
                  ],
                ],
              ),
            ),
          ),
    );
  }
}
