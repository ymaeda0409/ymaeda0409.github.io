import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:malawi_bento_customer/core/providers.dart';
import 'package:malawi_bento_customer/features/cart/cart.dart';
import 'package:malawi_bento_customer/features/catalog/models.dart';
import 'package:shared_preferences/shared_preferences.dart';

const store1 = StoreInfo(
  id: 1,
  name: 'Lilongwe Central Store',
  currency: 'MWK',
  isOpen: true,
);
const store2 = StoreInfo(
  id: 2,
  name: 'Limbe Store',
  currency: 'MWK',
  isOpen: true,
);
const large = ProductOption(id: 2, name: 'Large', price: 50000);

Product product(int id, int price) => Product(
  id: id,
  categoryId: 1,
  name: 'P$id',
  price: price,
  currency: 'MWK',
  preparationMinutes: 10,
  isFeatured: false,
  isSoldOut: false,
);

Future<ProviderContainer> container([
  Map<String, Object> prefs = const {},
]) async {
  SharedPreferences.setMockInitialValues(prefs);
  final p = await SharedPreferences.getInstance();
  final c = ProviderContainer(
    overrides: [sharedPreferencesProvider.overrideWithValue(p)],
  );
  addTearDown(c.dispose);
  return c;
}

void main() {
  test('lines merge by product and options; totals use minor units', () async {
    final c = await container();
    final cart = c.read(cartProvider.notifier);

    await cart.add(
      store: store1,
      product: product(1, 350000),
      options: const [],
      quantity: 1,
    );
    await cart.add(
      store: store1,
      product: product(1, 350000),
      options: const [],
      quantity: 2,
    );
    await cart.add(
      store: store1,
      product: product(1, 350000),
      options: const [large],
      quantity: 1,
    );
    await cart.add(
      store: store1,
      product: product(2, 80000),
      options: const [],
      quantity: 1,
    );

    final state = c.read(cartProvider);
    expect(state.lines, hasLength(3));
    expect(state.itemCount, 5);
    expect(state.subtotal, 350000 * 3 + 400000 + 80000);
  });

  test(
    'quantity zero removes the line and an empty cart forgets its store',
    () async {
      final c = await container();
      final cart = c.read(cartProvider.notifier);
      await cart.add(
        store: store1,
        product: product(1, 350000),
        options: const [],
        quantity: 1,
      );

      await cart.setQuantity(c.read(cartProvider).lines.single.key, 0);

      expect(c.read(cartProvider).isEmpty, isTrue);
      expect(c.read(cartProvider).storeId, isNull);
    },
  );

  test('adding from another store replaces the cart', () async {
    final c = await container();
    final cart = c.read(cartProvider.notifier);
    await cart.add(
      store: store1,
      product: product(1, 350000),
      options: const [],
      quantity: 1,
    );

    expect(cart.conflictsWith(2), isTrue);
    await cart.add(
      store: store2,
      product: product(9, 100000),
      options: const [],
      quantity: 1,
    );

    expect(c.read(cartProvider).storeId, 2);
    expect(c.read(cartProvider).lines.single.productId, 9);
  });

  test('cart survives app restarts (offline friendly)', () async {
    final c = await container();
    await c
        .read(cartProvider.notifier)
        .add(
          store: store1,
          product: product(1, 350000),
          options: const [large],
          quantity: 2,
        );
    final saved = (await SharedPreferences.getInstance()).getString('cart')!;

    final restarted = await container({'cart': saved});
    expect(restarted.read(cartProvider).subtotal, 800000);
    expect(restarted.read(cartProvider).lines.single.optionIds, [2]);
  });

  test('totals add the delivery fee', () {
    const totals = CartTotals(subtotal: 700000, deliveryFee: 150000);
    expect(totals.total, 850000);
  });
}
