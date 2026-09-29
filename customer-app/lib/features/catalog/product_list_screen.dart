import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/ui/widgets.dart';
import 'catalog_providers.dart';
import 'widgets.dart';

/// 07 Product List (by category)
class ProductListScreen extends ConsumerWidget {
  const ProductListScreen({super.key, required this.categoryId});

  final int categoryId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final store = ref.watch(currentStoreProvider).value;
    if (store == null) {
      return Scaffold(appBar: AppBar(), body: const LoadingView());
    }

    final storeId = store.store.id;
    final category = ref
        .watch(categoriesProvider(storeId))
        .value
        ?.where((c) => c.id == categoryId)
        .firstOrNull;
    final products = ref.watch(
      productsProvider(ProductQuery(storeId: storeId, categoryId: categoryId)),
    );

    return Scaffold(
      appBar: AppBar(title: Text(category?.name ?? '')),
      body: products.when(
        loading: () => const LoadingView(),
        error: (e, _) => ErrorView(
          error: e,
          onRetry: () => ref.invalidate(productsProvider),
        ),
        data: (items) => items.isEmpty
            ? MessageView(
                icon: Icons.no_food_outlined,
                message: context.l10n.product_list_empty,
              )
            : ListView.separated(
                padding: const EdgeInsets.all(16),
                itemCount: items.length,
                separatorBuilder: (_, _) => const SizedBox(height: 10),
                itemBuilder: (_, i) => ProductTile(product: items[i]),
              ),
      ),
    );
  }
}
