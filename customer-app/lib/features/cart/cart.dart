import 'dart:convert';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../catalog/models.dart';

class CartLine {
  const CartLine({
    required this.productId,
    required this.name,
    required this.optionIds,
    required this.optionNames,
    required this.unitPrice,
    required this.optionsPrice,
    required this.quantity,
  });

  factory CartLine.fromJson(Map<String, dynamic> json) => CartLine(
        productId: json['product_id'] as int,
        name: json['name'] as String,
        optionIds: (json['option_ids'] as List).cast<int>(),
        optionNames: (json['option_names'] as List).cast<String>(),
        unitPrice: json['unit_price'] as int,
        optionsPrice: json['options_price'] as int,
        quantity: json['quantity'] as int,
      );

  final int productId;

  /// Name at the time it was added; the cart screen re-resolves it in the current locale.
  final String name;
  final List<int> optionIds;
  final List<String> optionNames;
  final int unitPrice;
  final int optionsPrice;
  final int quantity;

  /// Same product with the same options merges into one line.
  String get key => '$productId:${([...optionIds]..sort()).join(',')}';

  int get total => (unitPrice + optionsPrice) * quantity;

  CartLine copyWith({int? quantity}) => CartLine(
        productId: productId,
        name: name,
        optionIds: optionIds,
        optionNames: optionNames,
        unitPrice: unitPrice,
        optionsPrice: optionsPrice,
        quantity: quantity ?? this.quantity,
      );

  Map<String, dynamic> toJson() => {
        'product_id': productId,
        'name': name,
        'option_ids': optionIds,
        'option_names': optionNames,
        'unit_price': unitPrice,
        'options_price': optionsPrice,
        'quantity': quantity,
      };
}

class Cart {
  const Cart({this.storeId, this.storeName, this.currency = 'MWK', this.lines = const []});

  factory Cart.fromJson(Map<String, dynamic> json) => Cart(
        storeId: json['store_id'] as int?,
        storeName: json['store_name'] as String?,
        currency: json['currency'] as String,
        lines: (json['lines'] as List).map((l) => CartLine.fromJson(l as Map<String, dynamic>)).toList(),
      );

  /// A cart belongs to exactly one store (prices and availability are per store).
  final int? storeId;
  final String? storeName;
  final String currency;
  final List<CartLine> lines;

  bool get isEmpty => lines.isEmpty;
  int get itemCount => lines.fold(0, (sum, l) => sum + l.quantity);
  int get subtotal => lines.fold(0, (sum, l) => sum + l.total);

  Map<String, dynamic> toJson() => {
        'store_id': storeId,
        'store_name': storeName,
        'currency': currency,
        'lines': lines.map((l) => l.toJson()).toList(),
      };
}

/// Client-side estimate; the server recalculates every amount when the order is placed.
class CartTotals {
  const CartTotals({required this.subtotal, required this.deliveryFee, this.serviceFee = 0, this.discount = 0});

  final int subtotal;
  final int deliveryFee;
  final int serviceFee;
  final int discount;

  int get total => subtotal + deliveryFee + serviceFee - discount;
}

final cartProvider = NotifierProvider<CartController, Cart>(CartController.new);

class CartController extends Notifier<Cart> {
  static const _key = 'cart';

  @override
  Cart build() {
    final raw = ref.read(sharedPreferencesProvider).getString(_key);
    return raw == null ? const Cart() : Cart.fromJson(jsonDecode(raw) as Map<String, dynamic>);
  }

  /// True when adding from [storeId] would discard the current cart.
  bool conflictsWith(int storeId) => !state.isEmpty && state.storeId != storeId;

  Future<void> add({
    required StoreInfo store,
    required Product product,
    required List<ProductOption> options,
    required int quantity,
  }) async {
    final base = conflictsWith(store.id) ? const Cart() : state;
    final line = CartLine(
      productId: product.id,
      name: product.name,
      optionIds: options.map((o) => o.id).toList(),
      optionNames: options.map((o) => o.name).toList(),
      unitPrice: product.price,
      optionsPrice: options.fold(0, (sum, o) => sum + o.price),
      quantity: quantity,
    );

    final lines = [...base.lines];
    final index = lines.indexWhere((l) => l.key == line.key);
    if (index >= 0) {
      lines[index] = lines[index].copyWith(quantity: lines[index].quantity + quantity);
    } else {
      lines.add(line);
    }
    await _save(Cart(storeId: store.id, storeName: store.name, currency: store.currency, lines: lines));
  }

  Future<void> setQuantity(String key, int quantity) async {
    final lines = [
      for (final l in state.lines)
        if (l.key != key) l else if (quantity > 0) l.copyWith(quantity: quantity),
    ];
    await _save(lines.isEmpty
        ? const Cart()
        : Cart(storeId: state.storeId, storeName: state.storeName, currency: state.currency, lines: lines));
  }

  Future<void> clear() => _save(const Cart());

  Future<void> _save(Cart cart) async {
    state = cart;
    await ref.read(sharedPreferencesProvider).setString(_key, jsonEncode(cart.toJson()));
  }
}
