import 'package:bento_core/bento_core.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/providers.dart';
import '../cart/cart.dart';
import '../orders/order.dart';
import '../orders/tracking.dart';

final orderRepositoryProvider = Provider<OrderRepository>(
  (ref) => ApiOrderRepository(ref.watch(apiClientProvider)),
);

enum PaymentMethod {
  cash('CASH'),
  airtelMoney('AIRTEL_MONEY'),
  tnmMpamba('TNM_MPAMBA');

  const PaymentMethod(this.code);

  /// Code sent to / received from the API.
  final String code;

  bool get isMobileMoney => this != PaymentMethod.cash;
}

List<Map<String, dynamic>> cartItemsPayload(Cart cart) => [
  for (final line in cart.lines)
    {
      'product_id': line.productId,
      'quantity': line.quantity,
      'option_ids': line.optionIds,
    },
];

class PlaceOrderRequest {
  const PlaceOrderRequest({
    required this.cart,
    required this.addressId,
    required this.paymentMethod,
    this.scheduledAt,
  });

  final Cart cart;
  final int addressId;
  final PaymentMethod paymentMethod;
  final DateTime? scheduledAt;

  Map<String, dynamic> toJson() => {
    'store_id': cart.storeId,
    'delivery_address_id': addressId,
    'payment_method': paymentMethod.code,
    if (scheduledAt != null)
      'scheduled_at': scheduledAt!.toUtc().toIso8601String(),
    'items': cartItemsPayload(cart),
  };
}

abstract class OrderRepository {
  /// Server totals for the cart delivered to a saved address.
  Future<Quote> quote(Cart cart, int addressId);

  /// Places the order; amounts are recalculated by the server.
  Future<Order> place(PlaceOrderRequest request);
  Future<List<Order>> list();
  Future<Order> detail(int id);
  Future<Order> cancel(int id);
  Future<Tracking> tracking(int id);
}

class ApiOrderRepository implements OrderRepository {
  ApiOrderRepository(this._api);

  final ApiClient _api;

  @override
  Future<Quote> quote(Cart cart, int addressId) async => Quote.fromJson(
    await _api.post<Map<String, dynamic>>(
      '/orders/quote',
      body: {
        'store_id': cart.storeId,
        'delivery_address_id': addressId,
        'items': cartItemsPayload(cart),
      },
    ),
  );

  @override
  Future<Order> place(PlaceOrderRequest request) async => Order.fromJson(
    await _api.post<Map<String, dynamic>>('/orders', body: request.toJson()),
  );

  @override
  Future<List<Order>> list() async =>
      (await _api.get<List<dynamic>>('/orders'))
          .map((o) => Order.fromJson(o as Map<String, dynamic>))
          .toList();

  @override
  Future<Order> detail(int id) async =>
      Order.fromJson(await _api.get<Map<String, dynamic>>('/orders/$id'));

  @override
  Future<Tracking> tracking(int id) async => Tracking.fromJson(
    await _api.get<Map<String, dynamic>>('/orders/$id/tracking'),
  );

  @override
  Future<Order> cancel(int id) async => Order.fromJson(
    await _api.post<Map<String, dynamic>>('/orders/$id/cancel'),
  );
}
