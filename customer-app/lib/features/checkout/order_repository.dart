import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/network/api_client.dart';
import '../../core/providers.dart';
import '../cart/cart.dart';

final orderRepositoryProvider = Provider<OrderRepository>((ref) => ApiOrderRepository(ref.watch(apiClientProvider)));

enum PaymentMethod {
  cash('CASH'),
  airtelMoney('AIRTEL_MONEY'),
  tnmMpamba('TNM_MPAMBA');

  const PaymentMethod(this.code);

  /// Code sent to / received from the API.
  final String code;

  bool get isMobileMoney => this != PaymentMethod.cash;
}

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
        if (scheduledAt != null) 'scheduled_at': scheduledAt!.toUtc().toIso8601String(),
        'items': [
          for (final line in cart.lines)
            {'product_id': line.productId, 'quantity': line.quantity, 'option_ids': line.optionIds},
        ],
      };
}

abstract class OrderRepository {
  /// Returns the created order id. Amounts are recalculated by the server.
  Future<int> place(PlaceOrderRequest request);
}

class ApiOrderRepository implements OrderRepository {
  ApiOrderRepository(this._api);

  final ApiClient _api;

  @override
  Future<int> place(PlaceOrderRequest request) async {
    final data = await _api.post<Map<String, dynamic>>('/orders', body: request.toJson());
    return data['id'] as int;
  }
}
