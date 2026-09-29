import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../cart/cart.dart';
import '../checkout/order_repository.dart';
import 'order.dart';

final ordersProvider = FutureProvider.autoDispose<List<Order>>((ref) async {
  if (!ref.watch(sessionProvider.select((s) => s.isSignedIn))) return const [];
  return ref.watch(orderRepositoryProvider).list();
});

final orderDetailProvider = FutureProvider.autoDispose.family<Order, int>(
  (ref, id) => ref.watch(orderRepositoryProvider).detail(id),
);

/// Server quote for the current cart to a saved address (null address → no quote).
final checkoutQuoteProvider = FutureProvider.autoDispose.family<Quote?, int?>((ref, addressId) async {
  final cart = ref.watch(cartProvider);
  ref.watch(effectiveLocaleProvider);
  if (addressId == null || cart.isEmpty) return null;
  return ref.watch(orderRepositoryProvider).quote(cart, addressId);
});
