import 'package:bento_core/bento_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../core/ui/widgets.dart';
import '../location/delivery_location_controller.dart';
import 'catalog_providers.dart';
import 'models.dart';
import 'widgets.dart';

/// 05 Home: "Deliver to" + location, categories, recommended, all items.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final location = ref.watch(deliveryLocationProvider);

    return Scaffold(
      appBar: AppBar(
        toolbarHeight: 72,
        titleSpacing: 16,
        title: const _DeliverToHeader(),
      ),
      body: location == null
          ? MessageView(
              icon: Icons.location_on_outlined,
              message: l.home_choose_location,
              action: FilledButton(
                onPressed: () => context.push('/location'),
                child: Text(l.home_choose_location),
              ),
            )
          : ref
                .watch(currentStoreProvider)
                .when(
                  loading: () => const LoadingView(),
                  error: (e, _) => ErrorView(
                    error: e,
                    onRetry: () => ref.invalidate(availableStoresProvider),
                  ),
                  data: (store) => store == null
                      ? MessageView(
                          icon: Icons.wrong_location_outlined,
                          message: l.home_no_store,
                          action: OutlinedButton(
                            onPressed: () => context.push('/location'),
                            child: Text(l.home_choose_location),
                          ),
                        )
                      : _StoreMenu(available: store),
                ),
    );
  }
}

class _DeliverToHeader extends ConsumerWidget {
  const _DeliverToHeader();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final location = ref.watch(deliveryLocationProvider);
    final label = location == null
        ? l.home_choose_location
        : location.label ??
              l.location_coordinates(
                formatDecimal(location.latitude, context.locale),
                formatDecimal(location.longitude, context.locale),
              );
    return InkWell(
      onTap: () => context.push('/location'),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            l.home_delivery_to,
            style: Theme.of(context).textTheme.labelMedium,
          ),
          Row(
            children: [
              Flexible(
                child: Text(
                  label,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
              const Icon(Icons.expand_more),
            ],
          ),
        ],
      ),
    );
  }
}

class _StoreMenu extends ConsumerWidget {
  const _StoreMenu({required this.available});

  final AvailableStore available;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l = context.l10n;
    final storeId = available.store.id;
    final categories = ref.watch(categoriesProvider(storeId));
    final featured = ref.watch(
      productsProvider(ProductQuery(storeId: storeId, featured: true)),
    );
    final all = ref.watch(productsProvider(ProductQuery(storeId: storeId)));
    final theme = Theme.of(context);

    return RefreshIndicator(
      onRefresh: () async {
        ref.invalidate(availableStoresProvider);
        ref.invalidate(productsProvider);
        ref.invalidate(categoriesProvider);
      },
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: _StoreBanner(available: available),
            ),
          ),
          _SectionTitle(l.home_categories),
          SliverToBoxAdapter(
            child: SizedBox(
              height: 48,
              child: categories.when(
                loading: () => const SizedBox.shrink(),
                error: (_, _) => const SizedBox.shrink(),
                data: (items) => ListView.separated(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: items.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 8),
                  itemBuilder: (_, i) => ActionChip(
                    label: Text(items[i].name),
                    onPressed: () => context.push('/category/${items[i].id}'),
                  ),
                ),
              ),
            ),
          ),
          if ((featured.value ?? const []).isNotEmpty) ...[
            _SectionTitle(l.home_featured),
            SliverToBoxAdapter(
              child: SizedBox(
                // Image + two title lines + price; text part grows with the user's font scale.
                height: 180 + MediaQuery.textScalerOf(context).scale(84),
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: featured.value!.length,
                  separatorBuilder: (_, _) => const SizedBox(width: 12),
                  itemBuilder: (_, i) => Align(
                    alignment: Alignment.topCenter,
                    child: FeaturedCard(product: featured.value![i]),
                  ),
                ),
              ),
            ),
          ],
          _SectionTitle(l.home_all_products),
          ...all.when(
            loading: () => [const SliverToBoxAdapter(child: LoadingView())],
            error: (e, _) => [
              SliverToBoxAdapter(
                child: ErrorView(
                  error: e,
                  onRetry: () => ref.invalidate(productsProvider),
                ),
              ),
            ],
            data: (items) => [
              if (items.isEmpty)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      l.product_list_empty,
                      style: theme.textTheme.bodyLarge,
                    ),
                  ),
                ),
              SliverPadding(
                padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                sliver: SliverList.separated(
                  itemCount: items.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 10),
                  itemBuilder: (_, i) => ProductTile(product: items[i]),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _StoreBanner extends StatelessWidget {
  const _StoreBanner({required this.available});

  final AvailableStore available;

  @override
  Widget build(BuildContext context) {
    final l = context.l10n;
    final store = available.store;
    final scheme = Theme.of(context).colorScheme;
    return Card(
      color: available.isOpen ? scheme.primaryContainer : scheme.errorContainer,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(store.name, style: Theme.of(context).textTheme.titleLarge),
            if (!available.isOpen) Text(l.home_store_closed(store.name)),
            if (store.announcement != null) ...[
              const SizedBox(height: 4),
              Text(store.announcement!),
            ],
            if (store.description != null) ...[
              const SizedBox(height: 4),
              Text(store.description!),
            ],
            const SizedBox(height: 8),
            Wrap(
              spacing: 6,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                const Icon(Icons.delivery_dining, size: 20),
                Text(l.cart_delivery_fee),
                Text(
                  formatMoney(
                    available.deliveryFee,
                    available.currency,
                    context.locale,
                  ),
                  style: const TextStyle(fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  const _SectionTitle(this.title);

  final String title;

  @override
  Widget build(BuildContext context) => SliverToBoxAdapter(
    child: Padding(
      padding: const EdgeInsets.fromLTRB(16, 20, 16, 10),
      child: Text(title, style: Theme.of(context).textTheme.titleLarge),
    ),
  );
}
