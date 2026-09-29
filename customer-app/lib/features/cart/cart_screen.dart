import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/format/money.dart';
import '../../core/ui/widgets.dart';
import '../catalog/catalog_providers.dart';
import 'cart.dart';
import 'cart_summary.dart';

/// 09 Cart
class CartScreen extends ConsumerWidget {
  const CartScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final cart = ref.watch(cartProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l.cart_title)),
      body: cart.isEmpty
          ? MessageView(
              icon: Icons.shopping_bag_outlined,
              message: l.cart_empty,
              action: FilledButton(onPressed: () => context.go('/home'), child: Text(l.cart_browse)),
            )
          : ListView(
              padding: const EdgeInsets.all(16),
              children: [
                if (cart.storeName != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Text(cart.storeName!, style: Theme.of(context).textTheme.titleMedium),
                  ),
                Text(l.cart_item_count(cart.itemCount)),
                const SizedBox(height: 12),
                for (final line in cart.lines) ...[CartLineTile(line: line, storeId: cart.storeId!), const SizedBox(height: 10)],
                const SizedBox(height: 8),
                const CartSummary(),
              ],
            ),
      bottomNavigationBar: cart.isEmpty
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: FilledButton(onPressed: () => context.push('/checkout'), child: Text(l.cart_checkout)),
              ),
            ),
    );
  }
}

/// Names follow the current UI language: re-resolved from the (cached) product detail;
/// the name stored when the item was added is the offline fallback.
({String name, List<String> options}) localizedLine(WidgetRef ref, CartLine line, int storeId) {
  final product = ref.watch(productDetailProvider((storeId: storeId, productId: line.productId))).value;
  if (product == null) return (name: line.name, options: line.optionNames);
  return (
    name: product.name,
    options: [
      for (final g in product.optionGroups)
        for (final o in g.options)
          if (line.optionIds.contains(o.id)) o.name,
    ],
  );
}

class CartLineTile extends ConsumerWidget {
  const CartLineTile({super.key, required this.line, required this.storeId});

  final CartLine line;
  final int storeId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final (:name, :options) = localizedLine(ref, line, storeId);
    final cart = ref.read(cartProvider.notifier);
    final currency = ref.watch(cartProvider).currency;

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: Theme.of(context).textTheme.titleMedium),
                  for (final option in options) Text(option, style: Theme.of(context).textTheme.bodySmall),
                  const SizedBox(height: 6),
                  Text(formatMoney(line.total, currency, context.locale)),
                ],
              ),
            ),
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                IconButton(
                  tooltip: line.quantity == 1 ? context.l10n.cart_remove : null,
                  onPressed: () => cart.setQuantity(line.key, line.quantity - 1),
                  icon: Icon(line.quantity == 1 ? Icons.delete_outline : Icons.remove),
                ),
                Text('${line.quantity}', style: Theme.of(context).textTheme.titleMedium),
                IconButton(
                  onPressed: line.quantity < 20 ? () => cart.setQuantity(line.key, line.quantity + 1) : null,
                  icon: const Icon(Icons.add),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
