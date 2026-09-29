import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/ui/widgets.dart';
import '../cart/cart.dart';
import 'catalog_providers.dart';
import 'models.dart';
import 'widgets.dart';

/// 08 Product Detail: options (required/optional, single/multi), quantity, add to cart.
class ProductDetailScreen extends ConsumerWidget {
  const ProductDetailScreen({super.key, required this.productId});

  final int productId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final store = ref.watch(currentStoreProvider).value;
    if (store == null) {
      return Scaffold(appBar: AppBar(), body: const LoadingView());
    }

    final key = (storeId: store.store.id, productId: productId);
    return ref
        .watch(productDetailProvider(key))
        .when(
          loading: () => Scaffold(appBar: AppBar(), body: const LoadingView()),
          error: (e, _) => Scaffold(
            appBar: AppBar(),
            body: ErrorView(
              error: e,
              onRetry: () => ref.invalidate(productDetailProvider(key)),
            ),
          ),
          data: (product) =>
              _ProductDetail(product: product, store: store.store),
        );
  }
}

class _ProductDetail extends ConsumerStatefulWidget {
  const _ProductDetail({required this.product, required this.store});

  final Product product;
  final StoreInfo store;

  @override
  ConsumerState<_ProductDetail> createState() => _ProductDetailState();
}

class _ProductDetailState extends ConsumerState<_ProductDetail> {
  /// group id → selected option ids
  final Map<int, Set<int>> _selected = {};
  int _quantity = 1;

  @override
  void initState() {
    super.initState();
    // Preselect the first option of required single-choice groups (e.g. "Regular" rice).
    for (final g in widget.product.optionGroups) {
      _selected[g.id] = {
        if (g.isRequired && g.isSingleChoice && g.options.isNotEmpty)
          g.options.first.id,
      };
    }
  }

  List<ProductOption> get _chosenOptions => [
    for (final g in widget.product.optionGroups)
      for (final o in g.options)
        if (_selected[g.id]!.contains(o.id)) o,
  ];

  bool get _valid => widget.product.optionGroups.every((g) {
    final n = _selected[g.id]!.length;
    return n >= g.minSelect && n <= g.maxSelect;
  });

  int get _total =>
      (widget.product.price +
          _chosenOptions.fold<int>(0, (s, o) => s + o.price)) *
      _quantity;

  void _toggle(OptionGroup group, ProductOption option) {
    setState(() {
      final set = _selected[group.id]!;
      if (group.isSingleChoice) {
        set
          ..clear()
          ..add(option.id);
      } else if (set.contains(option.id)) {
        set.remove(option.id);
      } else if (set.length < group.maxSelect) {
        set.add(option.id);
      }
    });
  }

  Future<void> _addToCart() async {
    final l = context.l10n;
    final cart = ref.read(cartProvider.notifier);
    if (cart.conflictsWith(widget.store.id)) {
      final replace = await showDialog<bool>(
        context: context,
        builder: (context) => AlertDialog(
          title: Text(l.cart_replace_title),
          content: Text(
            l.cart_replace_message(ref.read(cartProvider).storeName ?? ''),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: Text(l.common_cancel),
            ),
            TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: Text(l.cart_replace_confirm),
            ),
          ],
        ),
      );
      if (replace != true) return;
    }
    await cart.add(
      store: widget.store,
      product: widget.product,
      options: _chosenOptions,
      quantity: _quantity,
    );
    if (!mounted) return;
    showMessage(context, l.product_added);
    context.pop();
  }

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final theme = Theme.of(context);
    final product = widget.product;

    return Scaffold(
      appBar: AppBar(leading: closeToHomeIfRoot(context)),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
        children: [
          LayoutBuilder(
            builder: (_, c) => ProductImage(
              url: product.imageUrl,
              size: c.maxWidth.clamp(0, 480),
            ),
          ),
          const SizedBox(height: 16),
          Text(product.name, style: theme.textTheme.headlineSmall),
          const SizedBox(height: 4),
          Wrap(
            spacing: 12,
            crossAxisAlignment: WrapCrossAlignment.center,
            children: [
              PriceText(product.price, product.currency),
              if (product.preparationMinutes > 0)
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.schedule, size: 18),
                    const SizedBox(width: 4),
                    Text(
                      l.product_preparation_time(product.preparationMinutes),
                    ),
                  ],
                ),
              if (product.isSoldOut) const SoldOutBadge(),
            ],
          ),
          if (product.description != null) ...[
            const SizedBox(height: 12),
            Text(product.description!),
          ],
          for (final group in product.optionGroups) ...[
            const SizedBox(height: 20),
            Wrap(
              spacing: 8,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Text(group.name, style: theme.textTheme.titleMedium),
                Chip(
                  visualDensity: VisualDensity.compact,
                  label: Text(
                    group.isRequired ? l.common_required : l.common_optional,
                  ),
                ),
                Text(
                  group.minSelect == group.maxSelect
                      ? l.product_choose_exactly(group.maxSelect)
                      : l.product_choose_up_to(group.maxSelect),
                ),
              ],
            ),
            Card(
              child: Column(
                children: [
                  for (final option in group.options)
                    CheckboxListTile(
                      value: _selected[group.id]!.contains(option.id),
                      onChanged: (_) => _toggle(group, option),
                      title: Text(option.name),
                      secondary: option.price > 0
                          ? Text(
                              formatMoney(
                                option.price,
                                product.currency,
                                context.locale,
                              ),
                            )
                          : null,
                      controlAffinity: ListTileControlAffinity.leading,
                    ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: Text(
                  l.product_quantity,
                  style: theme.textTheme.titleMedium,
                ),
              ),
              IconButton.filledTonal(
                onPressed: _quantity > 1
                    ? () => setState(() => _quantity--)
                    : null,
                icon: const Icon(Icons.remove),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: Text('$_quantity', style: theme.textTheme.titleLarge),
              ),
              IconButton.filledTonal(
                onPressed: _quantity < 20
                    ? () => setState(() => _quantity++)
                    : null,
                icon: const Icon(Icons.add),
              ),
            ],
          ),
        ],
      ),
      bottomNavigationBar: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: FilledButton(
            onPressed: product.isSoldOut || !_valid ? null : _addToCart,
            child: Wrap(
              alignment: WrapAlignment.center,
              spacing: 12,
              children: [
                Text(l.product_add_to_cart),
                Text(formatMoney(_total, product.currency, context.locale)),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
