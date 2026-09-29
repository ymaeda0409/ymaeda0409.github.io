import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../location/delivery_location_controller.dart';
import 'catalog_repository.dart';
import 'models.dart';

// Every catalog provider watches the locale, so switching language refetches
// translated names immediately without restarting the app.

final availableStoresProvider = FutureProvider<List<AvailableStore>>((
  ref,
) async {
  ref.watch(effectiveLocaleProvider);
  final location = ref.watch(deliveryLocationProvider);
  if (location == null) return const [];
  return ref
      .watch(catalogRepositoryProvider)
      .availableStores(location.latitude, location.longitude);
});

/// The store serving the current location (nearest one; MVP has a single store per area).
final currentStoreProvider = Provider<AsyncValue<AvailableStore?>>((ref) {
  return ref
      .watch(availableStoresProvider)
      .whenData((stores) => stores.isEmpty ? null : stores.first);
});

final categoriesProvider = FutureProvider.family<List<Category>, int>((
  ref,
  storeId,
) {
  ref.watch(effectiveLocaleProvider);
  return ref.watch(catalogRepositoryProvider).categories(storeId);
});

class ProductQuery {
  const ProductQuery({
    required this.storeId,
    this.categoryId,
    this.featured = false,
  });

  final int storeId;
  final int? categoryId;
  final bool featured;

  @override
  bool operator ==(Object other) =>
      other is ProductQuery &&
      other.storeId == storeId &&
      other.categoryId == categoryId &&
      other.featured == featured;

  @override
  int get hashCode => Object.hash(storeId, categoryId, featured);
}

final productsProvider = FutureProvider.family<List<Product>, ProductQuery>((
  ref,
  query,
) {
  ref.watch(effectiveLocaleProvider);
  return ref
      .watch(catalogRepositoryProvider)
      .products(
        query.storeId,
        categoryId: query.categoryId,
        featured: query.featured,
      );
});

final productDetailProvider =
    FutureProvider.family<Product, ({int storeId, int productId})>((ref, key) {
      ref.watch(effectiveLocaleProvider);
      return ref
          .watch(catalogRepositoryProvider)
          .product(key.storeId, key.productId);
    });
