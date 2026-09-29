/// Catalog models. All display strings arrive already resolved to the request locale.
class StoreInfo {
  const StoreInfo({
    required this.id,
    required this.name,
    required this.currency,
    required this.isOpen,
    this.description,
    this.announcement,
  });

  factory StoreInfo.fromJson(Map<String, dynamic> json) => StoreInfo(
        id: json['id'] as int,
        name: json['name'] as String,
        currency: json['currency'] as String,
        isOpen: json['is_open'] as bool,
        description: json['description'] as String?,
        announcement: json['announcement'] as String?,
      );

  final int id;
  final String name;
  final String currency;
  final bool isOpen;
  final String? description;
  final String? announcement;
}

class AvailableStore {
  const AvailableStore({
    required this.store,
    required this.kitchenId,
    required this.deliveryZoneId,
    required this.distanceKm,
    required this.deliveryFee,
    required this.currency,
    required this.isOpen,
  });

  factory AvailableStore.fromJson(Map<String, dynamic> json) => AvailableStore(
        store: StoreInfo.fromJson(json['store'] as Map<String, dynamic>),
        kitchenId: json['kitchen_id'] as int?,
        deliveryZoneId: json['delivery_zone_id'] as int,
        distanceKm: (json['distance_km'] as num).toDouble(),
        deliveryFee: json['delivery_fee'] as int,
        currency: json['currency'] as String,
        isOpen: json['is_open'] as bool,
      );

  final StoreInfo store;
  final int? kitchenId;
  final int deliveryZoneId;
  final double distanceKm;
  final int deliveryFee;
  final String currency;
  final bool isOpen;
}

class Category {
  const Category({required this.id, required this.code, required this.name, this.imageUrl});

  factory Category.fromJson(Map<String, dynamic> json) => Category(
        id: json['id'] as int,
        code: json['code'] as String,
        name: json['name'] as String? ?? '',
        imageUrl: json['image_url'] as String?,
      );

  final int id;
  final String code;
  final String name;
  final String? imageUrl;
}

class ProductOption {
  const ProductOption({required this.id, required this.name, required this.price});

  factory ProductOption.fromJson(Map<String, dynamic> json) => ProductOption(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        price: json['price'] as int,
      );

  final int id;
  final String name;
  final int price;
}

class OptionGroup {
  const OptionGroup({
    required this.id,
    required this.name,
    required this.minSelect,
    required this.maxSelect,
    required this.options,
  });

  factory OptionGroup.fromJson(Map<String, dynamic> json) => OptionGroup(
        id: json['id'] as int,
        name: json['name'] as String? ?? '',
        minSelect: json['min_select'] as int,
        maxSelect: json['max_select'] as int,
        options: (json['options'] as List).map((o) => ProductOption.fromJson(o as Map<String, dynamic>)).toList(),
      );

  final int id;
  final String name;
  final int minSelect;
  final int maxSelect;
  final List<ProductOption> options;

  bool get isRequired => minSelect > 0;
  bool get isSingleChoice => maxSelect == 1;
}

class Product {
  const Product({
    required this.id,
    required this.categoryId,
    required this.name,
    this.description,
    this.imageUrl,
    required this.price,
    required this.currency,
    required this.preparationMinutes,
    required this.isFeatured,
    required this.isSoldOut,
    this.optionGroups = const [],
  });

  factory Product.fromJson(Map<String, dynamic> json) => Product(
        id: json['id'] as int,
        categoryId: json['category_id'] as int,
        name: json['name'] as String? ?? '',
        description: json['description'] as String?,
        imageUrl: json['image_url'] as String?,
        price: json['price'] as int,
        currency: json['currency'] as String,
        preparationMinutes: json['preparation_minutes'] as int,
        isFeatured: json['is_featured'] as bool,
        isSoldOut: json['is_sold_out'] as bool,
        optionGroups: ((json['option_groups'] as List?) ?? const [])
            .map((g) => OptionGroup.fromJson(g as Map<String, dynamic>))
            .toList(),
      );

  final int id;
  final int categoryId;
  final String name;
  final String? description;
  final String? imageUrl;
  final int price;
  final String currency;
  final int preparationMinutes;
  final bool isFeatured;
  final bool isSoldOut;
  final List<OptionGroup> optionGroups;
}
