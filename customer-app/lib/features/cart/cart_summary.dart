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
  const CartSummary({super.key, this.totals, this.currency});

  /// Server quote when available; otherwise the local estimate is shown.
  final CartTotals? totals;
  final String? currency;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final CartTotals shown = totals ?? ref.watch<CartTotals>(cartTotalsProvider);
    final code = currency ?? ref.watch(cartProvider).currency;
    String money(int v) => formatMoney(v, code, context.locale);

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _Row(l.cart_subtotal, money(shown.subtotal)),
            _Row(l.cart_delivery_fee, money(shown.deliveryFee)),
            _Row(l.cart_service_fee, money(shown.serviceFee)),
            if (shown.discount > 0) _Row(l.cart_discount, money(-shown.discount)),
            const Divider(height: 24),
            _Row(l.cart_total, money(shown.total), emphasize: true),
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
