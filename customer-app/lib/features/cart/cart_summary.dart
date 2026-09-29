import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/format/money.dart';
import '../../core/ui/widgets.dart';
import '../catalog/catalog_providers.dart';
import 'cart.dart';

/// Estimated totals for the cart (delivery fee of the store serving the current location).
final cartTotalsProvider = Provider<CartTotals>((ref) {
  final cart = ref.watch(cartProvider);
  final store = ref.watch(currentStoreProvider).value;
  final fee = store != null && store.store.id == cart.storeId ? store.deliveryFee : 0;
  return CartTotals(subtotal: cart.subtotal, deliveryFee: cart.isEmpty ? 0 : fee);
});

class CartSummary extends ConsumerWidget {
  const CartSummary({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final totals = ref.watch(cartTotalsProvider);
    final currency = ref.watch(cartProvider).currency;
    String money(int v) => formatMoney(v, currency, context.locale);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _Row(l.cart_subtotal, money(totals.subtotal)),
            _Row(l.cart_delivery_fee, money(totals.deliveryFee)),
            _Row(l.cart_service_fee, money(totals.serviceFee)),
            if (totals.discount > 0) _Row(l.cart_discount, money(-totals.discount)),
            const Divider(height: 24),
            _Row(l.cart_total, money(totals.total), emphasize: true),
          ],
        ),
      ),
    );
  }
}

class _Row extends StatelessWidget {
  const _Row(this.label, this.value, {this.emphasize = false});

  final String label;
  final String value;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    final style = emphasize ? Theme.of(context).textTheme.titleLarge : Theme.of(context).textTheme.bodyLarge;
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(child: Text(label, style: style)),
          const SizedBox(width: 12),
          Text(value, style: style),
        ],
      ),
    );
  }
}
