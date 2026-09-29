import 'package:bento_core/bento_core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import 'models.dart';

final catalogRepositoryProvider = Provider<CatalogRepository>(
  (ref) => ApiCatalogRepository(ref.watch(apiClientProvider)),
);

abstract class CatalogRepository {
  Future<List<AvailableStore>> availableStores(
    double latitude,
    double longitude,
  );
  Future<List<Category>> categories(int storeId);
  Future<List<Product>> products(
    int storeId, {
    int? categoryId,
    bool featured = false,
  });
  Future<Product> product(int storeId, int productId);
}

class ApiCatalogRepository implements CatalogRepository {
  ApiCatalogRepository(this._api);

  final ApiClient _api;

  @override
  Future<List<AvailableStore>> availableStores(
    double latitude,
    double longitude,
  ) async {
    final data = await _api.get<List<dynamic>>(
      '/stores/available',
      query: {'latitude': latitude, 'longitude': longitude},
    );
    return data
        .map((e) => AvailableStore.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  @override
  Future<List<Category>> categories(int storeId) async {
    final data = await _api.get<List<dynamic>>(
      '/categories',
      query: {'store_id': storeId},
    );
    return data
        .map((e) => Category.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  @override
  Future<List<Product>> products(
    int storeId, {
    int? categoryId,
    bool featured = false,
  }) async {
    final data = await _api.get<List<dynamic>>(
      '/products',
      query: {
        'store_id': storeId,
        'category_id': categoryId,
        if (featured) 'featured': 1,
      },
    );
    return data
        .map((e) => Product.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  @override
  Future<Product> product(int storeId, int productId) async => Product.fromJson(
    await _api.get<Map<String, dynamic>>(
      '/products/$productId',
      query: {'store_id': storeId},
    ),
  );
}
