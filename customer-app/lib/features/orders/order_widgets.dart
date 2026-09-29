import 'package:flutter/material.dart';

import '../../core/format/money.dart';
import '../../core/ui/code_labels.dart';
import '../../core/ui/widgets.dart';
import 'order.dart';

class StatusChip extends StatelessWidget {
  const StatusChip(this.status, {super.key});

  final String status;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final color = switch (status) {
      'DELIVERED' => scheme.primaryContainer,
      'CANCELLED' || 'FAILED_DELIVERY' => scheme.errorContainer,
      _ => scheme.secondaryContainer,
    };
    return Chip(
      backgroundColor: color,
      visualDensity: VisualDensity.compact,
      label: Text(orderStatusText(context.l10n, status)),
    );
  }
}

/// Delivery PIN shown large so the customer can read it to the rider.
class DeliveryPinCard extends StatelessWidget {
  const DeliveryPinCard({super.key, required this.pin});

  final String pin;

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    return Card(
      color: Theme.of(context).colorScheme.primaryContainer,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(children: [
          Text(l.order_pin_label, style: Theme.of(context).textTheme.titleMedium),
          const SizedBox(height: 4),
          Text(pin, style: Theme.of(context).textTheme.displaySmall?.copyWith(letterSpacing: 12, fontWeight: FontWeight.bold)),
          const SizedBox(height: 4),
          Text(l.order_pin_hint, textAlign: TextAlign.center),
        ]),
      ),
    );
  }
}

/// Vertical timeline of status changes (labels translated from codes).
class StatusTimeline extends StatelessWidget {
  const StatusTimeline({super.key, required this.entries});

  final List<TimelineEntry> entries;

  @override
  Widget build(BuildContext context) {
    return Column(children: [
      for (final (i, e) in entries.indexed)
        ListTile(
          dense: true,
          leading: Icon(i == entries.length - 1 ? Icons.radio_button_checked : Icons.check_circle_outline,
              color: Theme.of(context).colorScheme.primary),
          title: Text(orderStatusText(context.l10n, e.status)),
          trailing: Text(formatTime(e.at, context.locale)),
        ),
    ]);
  }
}

class OrderAmounts extends StatelessWidget {
  const OrderAmounts({super.key, required this.order});

  final Order order;

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    String money(int v) => formatMoney(v, order.currency, context.locale);
    Widget row(String label, String value, {bool bold = false}) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 3),
          child: Row(children: [
            Expanded(child: Text(label, style: bold ? Theme.of(context).textTheme.titleMedium : null)),
            Text(value, style: bold ? Theme.of(context).textTheme.titleMedium : null),
          ]),
        );
    return Column(children: [
      row(l.cart_subtotal, money(order.subtotal)),
      row(l.cart_delivery_fee, money(order.deliveryFee)),
      row(l.cart_service_fee, money(order.serviceFee)),
      if (order.discount > 0) row(l.cart_discount, money(-order.discount)),
      const Divider(),
      row(l.cart_total, money(order.total), bold: true),
    ]);
  }
}
